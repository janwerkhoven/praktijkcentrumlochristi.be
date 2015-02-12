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


class cdwebgalleryWebGalleryExpression {

	/**
	 * Regular expression
	 *
	 * @return string	Regular expression.
	 */
	function regex() {
		$regex = "#{webgallery(?:\s?(.*?)?)?}(.*?){/webgallery}#is";
		return $regex;
	}
	
	/**
	 * Callback function to process the regular expression
	 * 
	 * @param $match
	 * @return string
	 */
	function callback(&$match) {
		// $match[0] - whole regular result
		// $match[1] - settings
		// $match[2] - HTML
		
		// settings
		$settings_match = ($match[1] ? $match[1] : '');
		
		// get settings
		$params = plgContentCdWebGallery::overrides($settings_match);
		
		// images
		$image_match = (isset($match[2]) ? trim(strip_tags($match[2], '<img>')) : '');
		
		// run integration
		$image_set = plgContentCdWebGallery::integration($image_match);
		if (!$image_set) return false;
		
		// engine
		$classname = plgContentCdWebGallery::engine($settings_match);
		if (!$classname) return false;
		
		// call ENGINE::tag() function - the calling is  necessary
		$tag = call_user_func(array($classname, 'tag'), plgContentCdWebGallery::tag($image_set));
		
		// add theme to A and IMG tags
		$html_tag = plgContentCdWebGallery::loadTheme($tag);
		
		return $html_tag;
	}
}

?>