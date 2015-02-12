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


class cdwebgalleryWebGalleryIntegration {
	
	/**
	 * Integration
	 * 
	 * @return array	Image set parameters.
	 */
	function integration($match = '') {
		
		// make image array - $image_array
		preg_match_all('(<img.*?>)', $match, $image_array);
		$image_array = (isset($image_array[0]) ? $image_array[0] : array());
		
		// if no images in array, return
		if (!$image_array) return false;
		
		$abs_path = plgContentCdWebGallery::absPath();

		// prevent non-exists images
		$image_array_tmp = array();
		foreach ($image_array as $img) {
			$img_path = JPath::clean($abs_path . DS . plgContentCdWebGallery::getAttr($img, 'src'));
			if (JFile::exists($img_path)) {
				$image_array_tmp [] = $img;
			}
		}
		$image_array = $image_array_tmp;
		
		$i = 0;
		$image_set = array();
		foreach ($image_array as $image)
		{
			$image_set[$i]->img_tag = $image;
			$image_set[$i]->img_src = plgContentCdWebGallery::getAttr($image, 'src');
			$image_set[$i]->img_src_path = JPath::clean($abs_path . DS . $image_set[$i]->img_src);
			$image_set[$i]->img_alt = plgContentCdWebGallery::getAttr($image, 'alt');
			$image_set[$i]->img_title = plgContentCdWebGallery::getAttr($image, 'title');

			$i++;
		}
				
		// >>> define thumbnail variables
		// absolute path to the thumbnail cache foler
		$thumb_cache_dir = plgContentCdWebGallery::cacheDir();
		
		
		// create a thumbnail cache
		$thumbnail_names = plgContentCdWebGallery::createThumbnails($image_set);
		
		$i = 0;
		foreach ($thumbnail_names as $thumb)
		{
			$image_set[$i]->thumb_name = $thumb;
			$image_set[$i]->thumb_path = $thumb_cache_dir . DS . $image_set[$i]->thumb_name;
			$image_set[$i]->thumb_src = plgContentCdWebGallery::cacheDir(true) . '/' . $image_set[$i]->thumb_name;

			$thumb_size = getimagesize($image_set[$i]->thumb_path);
			$image_set[$i]->thumb_width = (isset($thumb_size[0]) ? $thumb_size[0] : '');
			$image_set[$i]->thumb_height = (isset($thumb_size[1]) ? $thumb_size[1] : '');

			$i++;
		}
		
		return $image_set;
	}

}

?>