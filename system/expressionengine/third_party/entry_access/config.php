<?php

if ( ! defined('ENTRY_ACCESS_ADDON_NAME'))
{
	define('ENTRY_ACCESS_ADDON_NAME',         'Entry Access');
	define('ENTRY_ACCESS_ADDON_VERSION',      '1.6.0');
}

$config['name'] = ENTRY_ACCESS_ADDON_NAME;
$config['version']= ENTRY_ACCESS_ADDON_VERSION;
$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/31';