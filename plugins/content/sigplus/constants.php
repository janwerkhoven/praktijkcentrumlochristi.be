<?php
/**
* @file
* @brief    sigplus Image Gallery Plus constants
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// sort criterion for file system functions
define('SIGPLUS_FILENAME', 0);  // sort based on file name
define('SIGPLUS_MTIME', 1);     // sort based on last modified time

// sort order for file system functions
define('SIGPLUS_ASCENDING', 0);
define('SIGPLUS_DESCENDING', 1);
