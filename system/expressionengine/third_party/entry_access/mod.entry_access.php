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
 File: mod.entry_access.php
-----------------------------------------------------
 Purpose: Restrict access to entry to specific members
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}


class Entry_access {

    var $return_data	= ''; 	
    
    var $settings = array();

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
    	$this->EE =& get_instance(); 
    }
    /* END */
    
    function find_members()
    { 
        $str = urldecode($this->EE->input->get_post('q'));
        if (strlen($str)<3)
        {
            exit();
        }
        $this->EE->db->select('member_id, screen_name');
        $this->EE->db->from('members');
        $this->EE->db->where('screen_name LIKE "%'.$str.'%"');
        $q = $this->EE->db->get();
        $out = '';
        foreach ($q->result_array() as $row)
        {
            $out .= $row['member_id']."=".$row['screen_name']."\n";
        }
        echo trim($out);
        exit();
    }
    

	//display all mebers who can access the entry
	function members()
	{
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		if ($entry_id=='')
		{
			return $this->EE->TMPL->no_results();
		}
		
		$this->EE->db->select('author_id, entry_access');
        $this->EE->db->from('channel_titles');
        $this->EE->db->where('entry_id', $entry_id);
        $ea_q = $this->EE->db->get();
        if ($ea_q->num_rows()==0)
        {
        	return $this->EE->TMPL->no_results();
        }
        $ids = array();
        $entry_access = unserialize($ea_q->row('entry_access'));
		
		$join = array();
		$sql_what = "exp_members.*";
		if ($this->EE->TMPL->fetch_param('custom_fields')=="yes")
		{
			$q = $this->EE->db->select('m_field_id, m_field_name, m_field_fmt')
					->from('exp_member_fields')
					->get();
			$field_formatting = array();
			foreach ($q->result_array() as $row)
			{
				$sql_what .= ", m_field_id_".$row['m_field_id']." AS `".$row['m_field_name']."`";
				$field_formatting[$row['field_name']] = array(
					'text_format'	=> $row['m_field_fmt'],
					'html_format'	=> 'safe',
					'allow_img_url'	=> 'n',
					'auto_links'	=> 'y'
				);
			}
			$join[] = array('exp_member_data', 'exp_member_data.member_id=exp_members.member_id', 'left');
			
		}
		
		$this->EE->db->select($sql_what);
		$this->EE->db->from('exp_members');
		foreach ($join as $a)
		{
			$this->EE->db->join($a[0], $a[1], $a[2]);
		}
		if (!empty($entry_access))
        {
            $this->EE->db->where('exp_members.member_id', $ea_q->row('author_id'));
            $this->EE->db->or_where_in('exp_members.group_id', 1);
			$member_access = $entry_access['member_access'];
            if (!empty($member_access))
            {
            	$this->EE->db->or_where_in('exp_members.member_id', $member_access);
            }
			$group_access = $entry_access['group_access'];
			if (!empty($group_access))
            {
            	$this->EE->db->or_where_in('exp_members.group_id', $group_access);
            }
            
            $category_access = (isset($entry_access['categories_access']))?$entry_access['categories_access']:array();
			if (!empty($category_access))
            {
            	$this->EE->db->join('exp_category_members', 'exp_members.member_id=exp_category_members.member_id', 'left');
				$this->EE->db->or_where_in('exp_category_members.cat_id', $category_access);
            }
            
        }
		
		$query = $this->EE->db->get();
		
		if ($query->num_rows()==0)
		{
			return $this->EE->TMPL->no_results();
		}

		$variables = array();

		foreach ($query->result_array() as $row)
		{
			unset($row["password"]);
			unset($row["unique_id"]);
			unset($row["crypt_key"]);
			unset($row["authcode"]);
	        $variable_row = $row;

          	if ($this->EE->TMPL->fetch_param('custom_fields')=="yes")
	        {
				foreach ($field_formatting as $field=>$format)
				{
        			$variable_row[$field] = array($row[$field], $format);
				}
        	}

	        $variables[] = $variable_row;
		}
		
		$output = $this->EE->TMPL->parse_variables(trim($this->EE->TMPL->tagdata), $variables);
		
		return $output;
		
	}

}
/* END */
?>