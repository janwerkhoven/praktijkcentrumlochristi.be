<?php
/**
* @file
* @brief    sigplus Image Gallery Plus module for Joomla
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

/*
* sigplus Image Gallery Plus module for Joomla
* Copyright 2009-2010 Levente Hunyadi
*
* sigplus is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* sigplus is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

if (version_compare(PHP_VERSION, '5.1.0') < 0) {
	jexit('sigplus requires PHP version 5.1 or later.');
}

if (!defined('SIGPLUS_VERSION_MODULE')) {
	define('SIGPLUS_VERSION_MODULE', '1.3.1');
}

if (!defined('SIGPLUS_DEBUG')) {
	// Triggers debug mode. Debug uses uncompressed version of scripts rather than the bandwidth-saving minified versions.
	define('SIGPLUS_DEBUG', false);
}
if (!defined('SIGPLUS_LOGGING')) {
	// Triggers logging mode. Verbose status messages are printed to the output.
	define('SIGPLUS_LOGGING', false);
}

// include the helper file
$import = JPATH_PLUGINS.DS.'content'.DS.'sigplus'.DS.'core.php';
if (!is_file($import)) {
	$galleryhtml = '<p><strong>[sigplus] Critical error:</strong> <kbd>mod_sigplus</kbd> (sigplus module) requires <kbd>plg_sigplus</kbd> (sigplus plug-in) to be installed. The latest version of <kbd>plg_sigplus</kbd> is available from <a href="http://joomlacode.org/gf/project/sigplus/frs/">JoomlaCode</a>.</p>';
	require JModuleHelper::getLayoutPath("mod_sigplus");
	return;
}
require_once $import;

if (!defined('SIGPLUS_VERSION') || !defined('SIGPLUS_VERSION_MODULE') || SIGPLUS_VERSION !== SIGPLUS_VERSION_MODULE) {
	$galleryhtml = '<p><strong>[sigplus] Critical error:</strong> <kbd>mod_sigplus</kbd> (sigplus module) requires a matching version of <kbd>plg_sigplus</kbd> (sigplus plug-in) to be installed. Currently you have <kbd>mod_sigplus</kbd> version '.SIGPLUS_VERSION_MODULE.' but your version of <kbd>plg_sigplus</kbd> is '.SIGPLUS_VERSION.'. The latest version of <kbd>plg_sigplus</kbd> and <kbd>mod_sigplus</kbd> is available from <a href="http://joomlacode.org/gf/project/sigplus/frs/">JoomlaCode</a>.</p>';
	require JModuleHelper::getLayoutPath("mod_sigplus");
	return;
}

// get parameters from the module's configuration
$configuration = new SIGPlusConfiguration();
$configuration->setParameters($params);

// set image base folder to Joomla root
$imagefolder = $configuration->services->imagesfolder;
$configuration->services->imagesfolder = '';

// get the items to display from the helper
try {
	$core = new SIGPlusCore($configuration);
	$galleryhtml = $core->getGalleryHtml($imagefolder);
	$core->addGalleryEngines();
} catch (Exception $e) {
	$galleryhtml = $e->getMessage();
}

// include the template for display
require JModuleHelper::getLayoutPath("mod_sigplus");