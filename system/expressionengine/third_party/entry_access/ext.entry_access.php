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
 File: ext.entry_access.php
-----------------------------------------------------
 Purpose: Restrict access to entry to specific members 
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'entry_access/config.php';

class Entry_access_ext {

	var $name	     	= ENTRY_ACCESS_ADDON_NAME;
	var $version 		= ENTRY_ACCESS_ADDON_VERSION;
	var $description	= 'Restrict access to entry to specific members';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://www.intoeetive.com/docs/entry_access.html';
    var $site_id        = 1;
    
    var $settings 		= array();
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
        $this->settings = $settings;
        $this->limit = 100; //number of members to fetch initially
        $this->site_id = $this->EE->config->item('site_id');
	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		array(
    			'hook'		=> 'channel_entries_tagdata',
    			'method'	=> 'modify_tagdata',
    			'priority'	=> 110
    		),
            /*array(
                'hook'		=> 'channel_entries_query_result',
    			'method'	=> 'modify_query_result',
    			'priority'	=> 10
            ),
            array(
                'hook'		=> 'channel_module_create_pagination',
    			'method'	=> 'modify_pagination',
    			'priority'	=> 10
            ),*/
            array(
			    'method'    => "zenbu_entry_access_array",
			    'hook'      => "zenbu_add_column",
			    'priority'    => 110
			),
		  	array(
			    'method'    => "zenbu_entry_access_cell_content",
			    'hook'      => "zenbu_entry_cell_data",
			    'priority'    => 110
			),
		  	array(
		        'method'    => "zenbu_entry_access_custom_order_sort",
		        'hook'      => "zenbu_custom_order_sort",
		        'priority'    => 110
		    )
            
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	
        
        $this->EE->load->dbforge(); 
		if ($this->EE->db->field_exists('entry_access', 'channel_titles') == FALSE)
		{
			$this->EE->dbforge->add_column('channel_titles', array('entry_access' => array('type' => 'TEXT') ) );
		}
    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	if ($current < '1.5')
    	{
    		//new hooks
			$hooks = array(
	            array(
				    'method'    => "zenbu_entry_access_array",
				    'hook'      => "zenbu_add_column",
				    'priority'    => 110
				),
			  	array(
				    'method'    => "zenbu_entry_access_cell_content",
				    'hook'      => "zenbu_entry_cell_data",
				    'priority'    => 110
				),
			  	array(
			        'method'    => "zenbu_entry_access_custom_order_sort",
			        'hook'      => "zenbu_custom_order_sort",
			        'priority'    => 110
			    )
	            
	    	);
	    	
	        foreach ($hooks AS $hook)
	    	{
	    		$data = array(
	        		'class'		=> __CLASS__,
	        		'method'	=> $hook['method'],
	        		'hook'		=> $hook['hook'],
	        		'settings'	=> '',
	        		'priority'	=> $hook['priority'],
	        		'version'	=> $this->version,
	        		'enabled'	=> 'y'
	        	);
	            $this->EE->db->insert('extensions', $data);
	    	}	
    	}
    	
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
    }
    
    
    
    function settings_form($current)
    {
    	$this->EE->load->helper('form');
        
        //$group_access = (isset($current[$this->site_id]['group_access']))?$current[$this->site_id]['group_access']:array();
        //$member_access = (isset($current[$this->site_id]['member_access']))?$current[$this->site_id]['member_access']:array();
        $group_access = (isset($current['group_access']))?$current['group_access']:array();
        $member_access = (isset($current['member_access']))?$current['member_access']:array();

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
        $this->EE->db->where('group_id != 1');
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
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'plugins/localisation/jquery.localisation-min.js"></script>');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'plugins/blockUI/jquery.blockUI.js"></script>');
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_folder_url.'ui.multiselect.js"></script>');
   
        $js = "
            $('#entry_access__group_access').multiselect({ droppable: 'none', sortable: 'none' });
        ";
        if ($total_members > $this->limit)
        {
            $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Entry_access' AND method='find_members'");
            $remoteUrl = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id');
            $js .= "
            $('#entry_access__member_access').multiselect({ droppable: 'none', sortable: 'none', remoteUrl: '$remoteUrl' });
            ";
        }
        else
        {
            $js .= "
            $('#entry_access__member_access').multiselect({ droppable: 'none', sortable: 'none' });
            ";
        }
        
        $mode_a = array(
            'deny_all'  => lang('deny_all'),
            'allow_all'  => lang('allow_all')
        );
        
        //$vars['settings']['mode'] = form_dropdown('mode', $mode_a, 'deny_all'); //looks like it wouldn't be useful...
        $vars['settings']['assign_access_to_author_group'] = form_checkbox('assign_access_to_author_group', 'y', ((isset($current['assign_access_to_author_group']) && $current['assign_access_to_author_group']==true)?true:false));
        
        $out = '';

        $vars['name'] = lang('group_access');
        $vars['instructions'] = lang('group_access_settings_instructions');
        $vars['input'] = form_multiselect('group_access[]', $group_field_list_items, $group_access, 'id="entry_access__group_access"');
        $out .= $this->EE->load->view('settings', $vars, TRUE);	
        
        $vars['name'] = lang('member_access');
        $vars['instructions'] = lang('member_access_settings_instructions');
        $vars['input'] = form_multiselect('member_access[]', $member_field_list_items, $member_access, 'id="entry_access__member_access"');
        $out .= $this->EE->load->view('settings', $vars, TRUE);	
        
        //member categories installed?
        $mc_check = $this->EE->db->select('settings')->from('modules')->where('module_name', 'Member_categories')->limit(1)->get(); 
        if ($mc_check->num_rows()>0)
        {
        	$category_access = (isset($current[$this->site_id]['category_access']))?$current[$this->site_id]['category_access']:array();

	        $category_field_list_items = array();
	        $mc_settings = unserialize($mc_check->row('settings'));   
			$this->EE->db->select('cat_id, cat_name');
	        $this->EE->db->from('categories');
	        $this->EE->db->where('site_id', $this->site_id); 
	        $this->EE->db->where_in('group_id', implode(',', $mc_settings[$this->site_id]['category_groups'])); 
	        $this->EE->db->order_by('cat_order', 'asc'); 
	        $query = $this->EE->db->get();
	        foreach ($query->result() as $obj)
	        {
	           $category_field_list_items[$obj->cat_id] = $obj->cat_name;
	        }
	        
	        $vars['name'] = lang('category_access');
	        $vars['instructions'] = lang('category_access_settings_instructions');
	        $vars['input'] = form_multiselect('category_access[]', $category_field_list_items, $category_access, 'id="entry_access__category_access"');
	        $out .= $this->EE->load->view('settings', $vars, TRUE);	
	        
	        $js .= "
            $('#entry_access__category_access').multiselect({ droppable: 'none', sortable: 'none' });
            ";
        }
        
        $this->EE->javascript->output($js);
        $this->EE->javascript->compile();

    	return $this->EE->load->view('index', array('out'=>$out), TRUE);		
    }
    
    
    
    
    function save_settings()
    {
    	if (empty($_POST))
    	{
    		show_error($this->EE->lang->line('unauthorized_access'));
    	}
    	
    	unset($_POST['submit']);
    	if(isset($_POST['assign_access_to_author_group']))
    	{
    		$_POST['assign_access_to_author_group'] = true;
    	}
    	else
    	{
    		$_POST['assign_access_to_author_group'] = false;
    	}

    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update('extensions', array('settings' => serialize($_POST)));
    	
    	$this->EE->session->set_flashdata(
    		'message_success',
    	 	$this->EE->lang->line('preferences_updated')
    	);
    }
    
    
    
    function modify_pagination($obj, $count)
    {
    	//echo "modify_pagination";
		//var_dump($obj);
    }
    
    function channel_module_fetch_pagination_data($obj)
    {
    	//echo "channel_module_fetch_pagination_data";
    	//var_dump($obj);
    }
    
    
    function modify_query_result($obj, $query_result)
    {
        //echo "modify_query_result111";
        //var_dump($obj->pagination);
        //$this->EE->load->library('pagination');
        //$obj->pagination->template_data = '';
        //$obj->pagination->total_pages = 3;
        //$obj->pagination->render($obj->pagination->template_data);
        //$this->EE->TMPL->tagdata = 'dummy text';
        //return $query_result;
		if ($this->EE->session->userdata['group_id']==1)
        {
            return $query_result;
        }
        $result = array();
        //$mode = (isset($this->settings[$this->site_id]['mode']))?$this->settings[$this->site_id]['mode']:'deny_all';
        $mode = 'deny_all';
        foreach ($query_result as $row)
        {
            $access = $this->_check_access($row);
            if (($access==true && $mode=='deny_all') || ($access==false && $mode=='allow_all') || ($this->EE->session->userdata['member_id']==$row['author_id']))
            {
                $result[] = $row;
            }
        }
        return $result;
    }

    
    function modify_tagdata($tagdata, $row, $obj) 
    {
        //echo "modify_tagdata";
		//var_dump($tagdata);
		if ($this->EE->extensions->last_call!==FALSE)
        {
            $tagdata = $this->EE->extensions->last_call;
        }
        
        //$mode = (isset($this->settings[$this->site_id]['mode']))?$this->settings[$this->site_id]['mode']:'deny_all';
        $mode = 'deny_all';
        
        $access = $this->_check_access($row);
        
        if ($mode=='allow_all') 
        {
            if ($this->EE->session->userdata['group_id']==1)
            {
                $access = TRUE;
            }
            else
            if ($this->EE->session->userdata['member_id']==$row['author_id'])
            {
                $access = TRUE;
            }
            else
            {
                $access = !$access;
            }
        }
        
        //{grant_access}
        if ($access == TRUE)
        {
            if ( preg_match_all("/".LD."grant_access".RD."(.*?)".LD."\/grant_access".RD."/s", $tagdata, $tmp)!=0)
            {
                $tagdata = str_replace($tmp[0][0], $tmp[1][0], $tagdata);
            }

            if ( preg_match_all("/".LD."deny_access".RD."(.*?)".LD."\/deny_access".RD."/s", $tagdata, $tmp)!=0)
            {
                $tagdata = str_replace($tmp[0][0], '', $tagdata);
            }
        }
        else
        {
            if ( preg_match_all("/".LD."deny_access".RD."(.*?)".LD."\/deny_access".RD."/s", $tagdata, $tmp)!=0)
            {
                $tagdata = str_replace($tmp[0][0], $tmp[1][0], $tagdata);
            }

            if ( preg_match_all("/".LD."grant_access".RD."(.*?)".LD."\/grant_access".RD."/s", $tagdata, $tmp)!=0)
            {
                $tagdata = str_replace($tmp[0][0], '', $tagdata);
            }
        }
    
        return $tagdata;	
    }
    
    function _check_access($row)
    {
        $access = FALSE;
        if ($this->EE->session->userdata['group_id']==1)
        {
            $access = TRUE;
        }
        else
        if ($this->EE->session->userdata['member_id']==$row['author_id'])
        {
            $access = TRUE;
        }
        else
        {

            $this->EE->db->select('entry_access');
            $this->EE->db->from('channel_titles');
            $this->EE->db->where('entry_id', $row['entry_id']);
            $q = $this->EE->db->get();
            $entry_access = unserialize($q->row('entry_access'));
            if (empty($entry_access))
            {
                $access = TRUE;
            }
            else
            {
                $group_access = $entry_access['group_access'];
                $member_access = $entry_access['member_access'];
                $category_access = (isset($entry_access['category_access']))?$entry_access['category_access']:array();
                if (empty($group_access) && empty($member_access) && empty($category_access))
                {
                    $access = TRUE;
                }
                else
                {
                    if (!empty($group_access) && in_array($this->EE->session->userdata['group_id'], $group_access))
                    {
                        $access = TRUE;
                    }
                    else
                    if (!empty($member_access) && in_array($this->EE->session->userdata['member_id'], $member_access))
                    {
                        $access = TRUE;
                    }
                    else
                    if (!empty($category_access))
	                {
	                    $q = $this->EE->db->select('cat_id')->from('exp_category_members')->where('member_id', $this->EE->session->userdata['member_id'])->get();
						if ($q->num_rows()>0)
						{
							foreach ($q->result_array() as $row)
							{
								if (in_array($row['cat_id'], $category_access))
								{
									$access = TRUE;
									break;
								}
							}
						}
						
	                }
                }
                
            }
        }
        return $access;
    }
    
    
    //zenbu stuff
    /**
	 * ===============================
	 * function zenbu_entry_access_array
	 * ===============================
	 * Adds a row in Zenbu's Display settings section
	 * @return array 	$output 	An array of data used by Zenbu
	 * The $output array must have the following keys:
	 * column: Computer-readable used as identifier for settings. Keep it unique!
	 * label: Human-readable label used in the Display settings row.
	 */
	function zenbu_entry_access_array()
	{
		// Get whatever was passed through this hook from previous add-ons
		$field = $this->EE->extensions->last_call;

		// Add to this array with this add-on's data
		$field[] = array(
			'column'	=> 'show_entry_access',						// Computer/Cylon-readable
			'label'		=> $this->EE->lang->line('show_entry_access'),	// Human-readable
			);
		return $field;
	}
	
   	/**
	 * ======================================
	 * function zenbu_entry_access_cell_content
	 * ======================================
	 * Adds data to a Zenbu entry row
	 * @param int 	$entry_id 		The current Entry ID
	 * @param array $entry_array 	An array of all entries found by Zenbu
	 * @param int 	$channel_id 	The current channel ID for the entry
	 * 
	 * @return array 	$output 	An array of data used by Zenbu. 
	 * The key must match the computer-readable identifier, minus the 'show_' part.
	 */
	function zenbu_entry_access_cell_content($entry_id, $entry_array, $channel_id)
	{
		// Build filter for entry title link
		$filter_array						= array();
		$filter_array["return_to_zenbu"]	= "y";
		$filter_array						= base64_encode(serialize($filter_array));

		// Get whatever was passed through this hook from previous add-ons
		$output = $this->EE->extensions->last_call;
		
		$msg = '';
		$this->EE->db->select('author_id, entry_access');
        $this->EE->db->from('channel_titles');
        $this->EE->db->where('entry_id', $entry_id);
        $ea_q = $this->EE->db->get();
        if ($ea_q->row('entry_access')!='')
        {
        	$entry_access = unserialize($ea_q->row('entry_access'));
        	$member_access = $entry_access['member_access'];
        	$group_access = $entry_access['group_access'];
        	if (!empty($member_access) || !empty($group_access))
        	{
	        	$msg .= lang('members');
	        	$this->EE->db->select('screen_name');
				$this->EE->db->from('exp_members');
				$this->EE->db->where('exp_members.member_id', $ea_q->row('author_id'));
	            if (!empty($member_access))
	            {
					$this->EE->db->or_where_in('exp_members.member_id', $member_access);
	            }
	            $q = $this->EE->db->get();
	            foreach ($q->result_array() as $row)
	            {
	            	$msg .= $row['screen_name'].", ";
	            }
	            
				$msg .= BR.lang('groups');
				$this->EE->db->select('group_title');
				$this->EE->db->from('member_groups');
				$this->EE->db->where('group_id', 1);
				if (!empty($group_access))
	            {
					$this->EE->db->or_where_in('group_id', $group_access);
	            }
	            $q = $this->EE->db->get();
	            foreach ($q->result_array() as $row)
	            {
	            	$msg .= $row['group_title'].", ";
	            }
     		}
     		$msg = rtrim($msg, ", ");
        }
        if ($msg == '')
        {
        	$msg = lang('everyone');
        }

		// Add to this array with this add-on's data
		$output['entry_access'] = $msg;	

		return $output;
	}

	/**
	 * ===========================================
	 * function zenbu_entry_access_custom_order_sort
	 * ===========================================
	 * Adds custom entry ordering/sorting
	 * Build on top of main Active Record to retrieve Zenbu results
	 * @param string 	$sort 		The sort order (asc/desc)
	 * 
	 * @return void 
	 */
	function zenbu_entry_access_custom_order_sort($sort)
	{
		// Cells are pretty much the same, so nothing important to sort.
	}
    

}
// END CLASS
