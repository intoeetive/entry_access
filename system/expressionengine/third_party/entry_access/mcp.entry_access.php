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
 File: mcp.entry_access.php
-----------------------------------------------------
 Purpose: Restrict access to entry to specific members
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'entry_access/config.php';

class Entry_access_mcp {

    var $version = ENTRY_ACCESS_ADDON_VERSION;
    
    var $settings = array();
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 

    } 
    
    
    function index()
    {
        $this->EE->functions->redirect(BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=entry_access');	
    }    
  
  

}
/* END */
?>