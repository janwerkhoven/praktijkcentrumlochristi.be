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


class prettyPhotoWebGalleryEngine {
	
	var $params = null;
	
	
	/**
	 * Get Instance
	 * 
	 * @return instance
	 */
	function &getInstance() {
		static $instance;
		if ($instance == null) $instance = new prettyPhotoWebGalleryEngine();
		return $instance;
	}
	
	/**
	 * Check the pre-requirements
	 * 
	 * @return string	Error message.
	 */
	function preRequirements() {
		$message = plgContentCdWebGallery::checkScriptegrator('1.4.0', 'jquery', 'site');
		return $message;
	}
	
	/**
	 * Add script declaration to header
	 * 
	 * @param $params
	 * @return string
	 */
	function addScriptDeclaration() {
		
		// theme
		$opacity = $this->params->get('prettyPhoto_opacity', '0.35');
		$theme = $this->params->get('prettyPhoto_theme', 'light_rounded');
		$modal = ((int)$this->params->get('prettyPhoto_modal', 0) ? 'true' : 'false');
		$hideflash = ((int)$this->params->get('prettyPhoto_hideflash', 0) ? 'true' : 'false');
		$overlay_gallery = ((int)$this->params->get('prettyPhoto_overlay_gallery', 0) === 0 ? 'false' : 'true');
		
		$random_id = $this->params->get('id', 0);
		
		$rel = 'webgallery_prettyPhoto_' . $random_id;
		
		$script = '
		<!--
		jQuery(document).ready(function($){
			$("a[rel*=\''.$rel.'\']").prettyPhoto( {
				animationSpeed: \'normal\',
				opacity: ' . $opacity . ',
				showTitle: false,
				allowresize: true,
				theme: \'' . $theme . '\',
				hideflash: ' . $hideflash . ',
				modal: ' . $modal . ',
				overlay_gallery : ' . $overlay_gallery . '
			});
		});
		//-->';
		
		return $script;
	}

	/**
	 * Return tag array
	 * 
	 * @param $image_set
	 * 
	 * @return array
	 */
	function tag($image_set) {
		
		$random_id = $this->params->get('id', 0);
		
		$rel_tag = 'webgallery_prettyPhoto_'.$random_id.'[' . $random_id . ']';
		
		foreach($image_set as $image) {
			$image->a = str_replace('></a>', ' rel="' . $rel_tag . '"></a>', $image->a);
		}
		
		return $image_set;
	}
	
}

?>