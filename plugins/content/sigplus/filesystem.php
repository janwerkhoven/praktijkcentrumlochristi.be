<?php
/**
* @file
* @brief    sigplus Image Gallery Plus file system functions
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

/*
* sigplus Image Gallery Plus plug-in for Joomla
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
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once dirname(__FILE__).DS.'constants.php';

/**
* pathinfo with component selector argument PATHINFO_FILENAME implementation for PHP < 5.2.0.
*/
function pathinfo_filename($path) {
	$basename = pathinfo($path, PATHINFO_BASENAME);
	$p = strrpos($basename, '.');
	return substr($basename, 0, $p);  // drop extension from filename
}

/**
* Ensure that a string is a relative path, removing leading and trailing space and slashes from a path string.
*/
function make_relative_path($folder) {
	$folder = str_replace('\\', '/', trim($folder, "\t\n\r /"));  // remove leading and trailing spaces and slashes
	if (preg_match('#^[A-Za-z0-9._-]+(/[A-Za-z0-9._-]+)*$#', $folder)) {
		return $folder;
	} else {
		return false;  // cannot be made a valid relative path
	}
}

/**
* Check if a path is an absolute file system path.
*/
function is_absolute_path($path) {
	return (bool) preg_match('#^([A-Za-z0-9]+:)?[/\\\\]#', $path);
}

/**
* Filters regular files, skipping those that are hidden.
* The filename of a hidden file starts with a dot.
*/
function is_regular_file($filename) {
	return $filename[0] != '.';
}

/**
* List files and directories inside the specified path with modification time.
* @return An associative array with filenames as keys and timestamps as values.
*/
function scandirmtime($dir) {
	$dh = @opendir($dir);
	if ($dh === false) {  // cannot open directory
		return false;
	}	
	$files = array();
	while (false !== ($filename = readdir($dh))) {
		if (!is_regular_file($filename)) {
			continue;
		}
		$files[$filename] = filemtime($dir.DS.$filename);
	}
	closedir($dh);
	return $files;
}

/**
* List files and directories inside the specified path with custom sorting option.
* @param folder The directory whose files and subdirectories to list.
* @param criterion The sort criterion, e.g. filename or last modification time.
* @param order The sort order, ascending or descending.
*/
function scandirsorted($folder, $criterion = SIGPLUS_FILENAME, $order = SIGPLUS_ASCENDING) {
	switch ($criterion) {
		case SIGPLUS_FILENAME:
			$files = @scandir($folder, $order);
			if ($files === false) {
				return false;
			}
			return array_filter($files, 'is_regular_file');  // list files and directories inside the specified path but omit hidden files
		case SIGPLUS_MTIME:
			$files = scandirmtime($folder);
			if ($files === false) {
				return false;
			}
			switch ($order) {
				case SIGPLUS_ASCENDING:
					asort($files); break;
				case SIGPLUS_DESCENDING:
					arsort($files); break;
			}
			return array_keys($files);
		default:
			return false;
	}
}

/**
* Checks whether a file or directory exists accepting both lowercase and uppercase extension.
* @return The file name with extension as found in the file system.
*/
function file_exists_mcext($path) {
	$realpath = realpath($path);
	if ($realpath !== false) {
		return pathinfo($realpath, PATHINFO_BASENAME);  // file name possibly with extension
	}
	$filename = pathinfo($path, PATHINFO_BASENAME);  // file name possibly with extension
	if (file_exists($path)) {  // file exists as-is, no inspection of extension is necessary
		return $filename;
	}
	$extension = pathinfo($path, PATHINFO_EXTENSION);  // file extension if present
	if ($extension) {  // if file has extension
		$p = strrpos($path, '.');              // starting position of extension (incl. dot)
		$base = substr($path, 0, $p);          // everything up to extension
		$extension = substr($path, $p);        // extension (incl. dot)
		$p = strrpos($filename, '.');
		$filename = substr($filename, 0, $p);  // drop extension from filename
		$extension = strtolower($extension);
		if (file_exists($base.$extension)) {   // file with lowercase extension
			return $filename.$extension;
		}
		$extension = strtoupper($extension);
		if (file_exists($base.$extension)) {   // file with uppercase extension
			return $filename.$extension;
		}
	}
	return false;  // file not found
}