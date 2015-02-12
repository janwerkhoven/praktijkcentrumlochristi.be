<?php
/**
* @file
* @brief    sigplus Image Gallery Plus plug-in for Joomla
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

if (version_compare(PHP_VERSION, '5.1.0') >= 0) {
	require_once JPATH_PLUGINS.DS.'content'.DS.'sigplus'.DS.'sigplus.php';
} else {
	die('sigplus requires PHP version 5.1 or later.');
}