<?php
/**
 * Core Design Web Gallery plugin for Joomla! 1.5
 * @author		Daniel Rataj, <info@greatjoomla.com>
 * @package		Joomla
 * @subpackage	Content
 * @category   	Plugin
 * @version		1.1.0
 * @copyright	Copyright (C) 2007 - 2010 Great Joomla!, http://www.greatjoomla.com
 * @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL 3
 * 
 * This file is part of Great Joomla! extension.   
 * This extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');


class greyboxWebGalleryEngine {
	
	var $params = null;
	
	
	/**
	 * Get Instance
	 * 
	 * @return instance
	 */
	function &getInstance() {
		static $instance;
		if ($instance == null) $instance = new greyboxWebGalleryEngine();
		return $instance;
	}
	
	function importFiles() {
		$files['css'] =
			array(
				'app/gb_styles.css'
			);
		
		$path = JURI::root(true) . '/plugins/content/cdwebgallery/engine/greybox/app/';
		
		$files['js'] =
			array(
				'app/application.js.php?path=' . $path,
				'app/AJS.js',
				'app/AJS_fx.js',
				'app/gb_scripts.js'
			);
		
		return $files;
	}
	
	/**
	 * Return tag array
	 * 
	 * @param $image_set
	 * @return array
	 */
	function tag($image_set) {
		
		$rel_tag = 'gb_image[]';
		if (count($image_set) > 1) $rel_tag = 'gb_imageset[\'' . $this->params->get('id', 0) . '\']';
		
		foreach($image_set as $image) {
			$image->a = str_replace('></a>', ' rel="' . $rel_tag . '"></a>', $image->a);
		}
		
		return $image_set;
	}
	
}

?>
