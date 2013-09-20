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
 File: upd.entry_access.php
-----------------------------------------------------
 Purpose: Restrict access to entry to specific members
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'entry_access/config.php';

class Entry_access_upd {

    var $version = ENTRY_ACCESS_ADDON_VERSION;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
    } 
    
    function install() { 
  
        $this->EE->load->dbforge(); 
        
        //----------------------------------------
		// EXP_MODULES
		// The settings column, Ellislab should have put this one in long ago.
		// No need for a seperate preferences table for each module.
		//----------------------------------------
		if ($this->EE->db->field_exists('settings', 'modules') == FALSE)
		{
			$this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
		}
        
        $settings = array();

        $data = array( 'module_name' => 'Entry_access' , 'module_version' => $this->version, 'has_cp_backend' => 'y', 'has_publish_fields' => 'y', 'settings'=> serialize($settings) ); 
        $this->EE->db->insert('modules', $data); 
                    
        $data = array( 'class' => 'Entry_access' , 'method' => 'find_members' ); 
        $this->EE->db->insert('actions', $data); 
        
        $this->EE->load->library('layout');
        $tabs['entry_access'] = array( 
                            'group_access' => array( 
                                'visible' => true, 
                                'collapse' => false, 
                                'htmlbuttons' => false, 
                                'width' => '100%'
                            ), 
                            'member_access' => array( 
                                'visible' => true, 
                                'collapse' => false, 
                                'htmlbuttons' => false, 
                                'width' => '100%' 
                            ),
                            'category_access' => array( 
			                    'visible' => true, 
			                    'collapse' => false, 
			                    'htmlbuttons' => false, 
			                    'width' => '100%' 
							)
                        ); 
        $this->EE->layout->add_layout_tabs($tabs, 'entry_access');

        return TRUE; 
        
    } 
    
    function uninstall() { 

        $this->EE->db->select('module_id'); 
        $query = $this->EE->db->get_where('modules', array('module_name' => 'Entry_access')); 
        
        $this->EE->db->where('module_id', $query->row('module_id')); 
        $this->EE->db->delete('module_member_groups'); 
        
        $this->EE->db->where('module_name', 'Entry_access'); 
        $this->EE->db->delete('modules'); 
        
        $this->EE->db->where('class', 'Entry_access'); 
        $this->EE->db->delete('actions'); 
        
        $this->EE->load->library('layout');
        $tabs['entry_access'] = array( 
                            'group_access' => array(), 
                            'member_access' => array(),
                            'category_access' => array()
                        );   
        $this->EE->layout->delete_layout_tabs($tabs);
        
        return TRUE; 
    } 
    
    function update($current='') 
    { 
        if ($current < 1.1) 
        { 
            
            $data = array( 'class' => 'Entry_access' , 'method' => 'find_members' ); 
            $this->EE->db->insert('actions', $data); 
            
        } 
        if ($current < 1.3) 
        { 
            
            $data = array( 'has_cp_backend' => 'y' ); 
            $this->EE->db->where('module_name', 'Entry_access');
            $this->EE->db->update('modules', $data); 
        } 
        
        if ($current < 1.5) 
        { 
            $sites_q = $this->EE->db->select('site_id')->from('sites')->get();
            $q = $this->EE->db->select('settings')->from('extensions')->where('class', 'Entry_access')->limit(1)->get();
            $old_settings = unserialize($q->row('settings'));
            $new_settings = array();
            foreach ($sites_q->result_array() as $row)
            {
                $new_settings[$row['site_id']] = $old_settings;
            }
            $data = array(
                'settings' => serialize($new_settings)
            );
            $this->EE->db->where('class', 'Entry_access');
            $this->EE->db->update('extensions', $data); 
            
            $this->EE->load->library('layout');
	        $tabs['entry_access'] = array( 
	                            'group_access' => array( 
	                                'visible' => true, 
	                                'collapse' => false, 
	                                'htmlbuttons' => false, 
	                                'width' => '100%'
	                            ), 
	                            'member_access' => array( 
	                                'visible' => true, 
	                                'collapse' => false, 
	                                'htmlbuttons' => false, 
	                                'width' => '100%' 
	                            ),
	                            'category_access' => array( 
				                    'visible' => true, 
				                    'collapse' => false, 
				                    'htmlbuttons' => false, 
				                    'width' => '100%' 
								)
   							);
        	$this->EE->layout->add_layout_tabs($tabs, 'entry_access');
            
        } 
        return TRUE; 
    } 
	

}
/* END */
?>