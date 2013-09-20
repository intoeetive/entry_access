<?php 

/*
=====================================================
 Entry access
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: tab.entry_access.php
-----------------------------------------------------
 Purpose: Restrict access to entry to specific members
=====================================================
*/

//error_reporting(0);

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

class Entry_access_tab {

	
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
        $this->EE->lang->loadfile('entry_access');  
        $this->limit = 100; //number of members to fetch initially
        $this->EE->db->select('settings');
        $this->EE->db->where('class', 'Entry_access_ext');
        $this->EE->db->limit(1);
        $query = $this->EE->db->get('extensions');
        $this->settings = unserialize($query->row('settings')); 
	}

	function publish_tabs($channel_id, $entry_id = '')
	{

		$settings = array();
        
        $group_access = array();
        $member_access = array();
        $category_access = array();
        
        $mc_check = $this->EE->db->select('settings')->from('modules')->where('module_name', 'Member_categories')->limit(1)->get(); 
        
        if ($entry_id!='')
        {
            $this->EE->db->select('entry_access');
            $this->EE->db->from('channel_titles');
            $this->EE->db->where('entry_id', $entry_id);
            $q = $this->EE->db->get();
            $entry_access = unserialize($q->row('entry_access'));
            if (!empty($entry_access))
            {
                $group_access = $entry_access['group_access'];
                $member_access = $entry_access['member_access'];
                $category_access = (isset($entry_access['category_access']))?$entry_access['category_access']:array();
            }
        
            $deny_cp_access = true;
            if ($this->EE->session->userdata['group_id']==1)
            {
                $deny_cp_access = false;     
            }
            
            if (empty($group_access) && empty($member_access))
            {
                $deny_cp_access = false;   
            }
            
            if ($deny_cp_access === true) 
            {
                $this->EE->db->select('author_id');
                $this->EE->db->from('channel_titles');
                $this->EE->db->where('entry_id', $entry_id);
                $aq = $this->EE->db->get();
                if ($aq->row('author_id') == $this->EE->session->userdata['member_id'])
                {
                    $deny_cp_access = false;     
                }
            }
            
            if ($deny_cp_access === true) 
            {
                if (!empty($group_access) && in_array($this->EE->session->userdata['group_id'], $group_access))
                {
                    $deny_cp_access = false;     
                }
            }
            
            if ($deny_cp_access === true) 
            {
                if (!empty($member_access) && in_array($this->EE->session->userdata['member_id'], $member_access))
                {
                    $deny_cp_access = false;     
                }
            }
            
        	if ($mc_check->num_rows()>0)
        	{
        		if (!empty($category_access))
                {
                    $q = $this->EE->db->select('cat_id')->from('exp_category_members')->where('member_id', $this->EE->session->userdata['member_id'])->get();
					if ($q->num_rows()>0)
					{
						foreach ($q->result_array() as $row)
						{
							if (in_array($row['cat_id'], $category_access))
							{
								$deny_cp_access = false;     
								break;
							}
						}
					}
					
                }
        	}
            
            if ($deny_cp_access === true) 
            {
                show_error($this->EE->lang->line('unauthorized_access'));
            }
        
        }
        else
        {
            if(isset($this->settings['assign_access_to_author_group']) && $this->settings['assign_access_to_author_group']==true)
            {
                if ($this->EE->session->userdata('group_id') != 1)
                {
                    $this->settings['group_access'][] = $this->EE->session->userdata('group_id');
                }
                $this->settings['group_access'] = array_unique($this->settings['group_access']);
            }
			$group_access = (isset($this->settings['group_access'])) ? $this->settings['group_access'] : array();
            $member_access = (isset($this->settings['member_access'])) ? $this->settings['member_access'] : array();
            $category_access = (isset($this->settings['category_access'])) ? $this->settings['category_access'] : array();
        }
        
        $group_field_list_items = array();
        $this->EE->db->select('group_id, group_title');
        $this->EE->db->from('member_groups');
        $this->EE->db->where('group_id != 1');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $group_field_list_items[$row['group_id']] = $row['group_title'];
        }
        
        $total_members = $this->EE->db->count_all('members');
        
        $member_field_list_items = array();
        $this->EE->db->select('member_id, screen_name');
        $this->EE->db->from('members');
        $this->EE->db->where('group_id > 4');
        if ($total_members > $this->limit)
        {
            $this->EE->db->limit($this->limit);
        }
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $member_field_list_items[$row['member_id']] = $row['screen_name'];
        }
        
        if ($total_members > $this->limit)
        {
            if (!empty($member_access))
            {
                $this->EE->db->select('member_id, screen_name');
                $this->EE->db->from('members');
                $this->EE->db->where_in('member_id', $member_access);
                $q = $this->EE->db->get();
                foreach ($q->result_array() as $row)
                {
                    if (!isset($member_field_list_items[$row['member_id']]))
                    {
                        $member_field_list_items[$row['member_id']] = $row['screen_name'];
                    }
                }
            }
        }
        
        
        $theme_folder_url = trim($this->EE->config->item('theme_folder_url'), '/').'/third_party/entry_access/';
        $this->EE->cp->add_to_foot('<link type="text/css" href="'.$theme_folder_url.'ui.multiselect.css" rel="stylesheet" />');
        //$this->EE->cp->add_js_script(array('plugin' => 'sortable'));
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'plugins/localisation/jquery.localisation-min.js"></script>');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'plugins/blockUI/jquery.blockUI.js"></script>');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'ui.multiselect.js"></script>');
   
        $field_name_prefix = '';
        $ee_version = '2.'.str_replace('.', '', substr(APP_VER, 2));
		if ($ee_version >= 2.40)
		{
			$field_name_prefix = 'field_id_';
		}
        
		$js = "
            $('#".$field_name_prefix."entry_access__group_access').multiselect({ droppable: 'none', sortable: 'none' });
        ";
        if ($total_members > $this->limit)
        {
            $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Entry_access' AND method='find_members'");
            $remoteUrl = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id');
            $js .= "
            $('#".$field_name_prefix."entry_access__member_access').multiselect({ droppable: 'none', sortable: 'none', remoteUrl: '$remoteUrl' });
            ";
        }
        else
        {
            $js .= "
            $('#".$field_name_prefix."entry_access__member_access').multiselect({ droppable: 'none', sortable: 'none' });
            ";
        }
        
        $settings[] = array(
           'field_id' => 'group_access',
           'field_label' => lang('group_access'),
           'field_required' => 'n',
           'field_data' => $group_access,
           'field_list_items' => $group_field_list_items,
           'field_fmt' => '',
           'field_instructions' => lang('group_access_instructions'),
           'field_show_fmt' => 'n',
           'field_fmt_options' => array(),
           'field_pre_populate' => 'n',
           'field_text_direction' => 'ltr',
           'field_type' => 'multi_select'
       );
	
		$settings[] = array(
           'field_id' => 'member_access',
           'field_label' => lang('member_access'),
           'field_required' => 'n',
           'field_data' => $member_access,
           'field_list_items' => $member_field_list_items,
           'field_fmt' => '',
           'field_instructions' => lang('member_access_instructions'),
           'field_show_fmt' => 'n',
           'field_fmt_options' => array(),
           'field_pre_populate' => 'n',
           'field_text_direction' => 'ltr',
           'field_type' => 'multi_select'
       );
       
       	if ($mc_check->num_rows()>0)
 		{
 			$category_field_list_items = array();
	        $mc_settings = unserialize($mc_check->row('settings'));   
			$this->EE->db->select('cat_id, cat_name');
	        $this->EE->db->from('categories');
	        $this->EE->db->where('site_id', $this->EE->config->item('site_id')); 
	        $this->EE->db->where_in('group_id', implode(',', $mc_settings[$this->EE->config->item('site_id')]['category_groups'])); 
	        $this->EE->db->order_by('cat_order', 'asc'); 
	        $query = $this->EE->db->get();
	        foreach ($query->result() as $obj)
	        {
	           $category_field_list_items[$obj->cat_id] = $obj->cat_name;
	        }
	        
	        $js .= "
            $('#".$field_name_prefix."entry_access__category_access').multiselect({ droppable: 'none', sortable: 'none' });
            ";
            
            $settings[] = array(
	           'field_id' => 'category_access',
	           'field_label' => lang('category_access'),
	           'field_required' => 'n',
	           'field_data' => $category_access,
	           'field_list_items' => $category_field_list_items,
	           'field_fmt' => '',
	           'field_instructions' => lang('category_access_instructions'),
	           'field_show_fmt' => 'n',
	           'field_fmt_options' => array(),
	           'field_pre_populate' => 'n',
	           'field_text_direction' => 'ltr',
	           'field_type' => 'multi_select'
	       );
		}
		
		
        $this->EE->javascript->output($js);
        $this->EE->javascript->compile();

		return $settings;
	}

	function validate_publish($params)
	{
		return FALSE;
	}
	
	function publish_data_db($params)
	{
		/*if ((!isset($params['mod_data']['group_access'])  OR $params['mod_data']['group_access'] == '') && (!isset($params['mod_data']['member_access'])  OR $params['mod_data']['member_access'] == ''))
    	{
    		return;
    	}*/

        $entry_access['group_access'] = array();
        $entry_access['member_access'] = array();
        if (isset($params['mod_data']['group_access']))
        {
            $entry_access['group_access'] = $params['mod_data']['group_access'];
        }
        if (isset($params['mod_data']['member_access']))
        {
            $entry_access['member_access'] = $params['mod_data']['member_access'];
        }
        if (isset($params['mod_data']['category_access']))
        {
            $entry_access['category_access'] = $params['mod_data']['category_access'];
        }
    	
    	$data = array(
    		'entry_access' => serialize($entry_access)
        );
        
        $this->EE->db->where('entry_id', $params['entry_id']); 
		$this->EE->db->update('channel_titles', $data); 
        
	}

	function publish_data_delete_db($params)
	{
		//do nothing :)
	}

}
/* END Class */

/* End of file tab.drafts.php */
/* Location: ./system/expressionengine/third_party/modules/download/tab.drafts.php */