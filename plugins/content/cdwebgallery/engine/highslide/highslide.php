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


class highslideWebGalleryEngine {
	
	var $params = null;
	
	
	/**
	 * Get Instance
	 * 
	 * @return instance
	 */
	function &getInstance() {
		static $instance;
		if ($instance == null) $instance = new highslideWebGalleryEngine();
		return $instance;
	}
	
	/**
	 * Check the pre-requirements
	 * 
	 * @return string	Error message.
	 */
	function preRequirements() {
		$message = plgContentCdWebGallery::checkScriptegrator('1.4.0', 'highslide', 'site');
		return $message;
	}
	
	/**
	 * Add script declaration to header
	 * 
	 * @param $params
	 * @return string
	 */
	function addScriptDeclaration() {
		
		$random_id = $this->params->get('id', 0);;
		
		$enginefolder = plgContentCdWebGallery::engineFolder();
		
		// engine params
		$show_controls = ($this->params->get('highslide_show_controls', 1) ? 'true' : 'false');
		
		$controls_position = $this->params->get('highslide_controls_position', 'top center');
		$controls_opacity = $this->params->get('highslide_controls_opacity', 0.75);
		$hide_controls_onmouseout = ($this->params->get('highslide_hide_controls_onmouseout', 1) ? 'true' : 'false');
		$interval = $this->params->get('highslide_interval', 5000);
		$fixedControls = (string)$this->params->get('highslide_fixedControls');
		
		switch ($fixedControls) {
			case 'fit':
			default:
				$fixedControls = '\'fit\'';
				break;
			case '':
				$fixedControls = 'false';
				break;
			case '1':
				$fixedControls = 'true';
				break;
		}
		
		$script = "
		<!--
		// Add the controlbar
		hs.addSlideshow({
			slideshowGroup: '$random_id',
			interval: $interval,
			repeat: false,
			useControls: $show_controls,
			fixedControls: $fixedControls,
			overlayOptions: {
				opacity: $controls_opacity,
				position: '$controls_position',
				hideOnMouseOut: $hide_controls_onmouseout
			}
		});
		//-->";
		
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
		
		$slideshowGroup = 'slideshowGroup: \'' . $this->params->get('id', 0) . '\', ';
		
		// engine params
		$params = plgContentCdWebGallery::parseIni(dirname(__FILE__));
		
		switch ($this->params->get('highslide_theme', 'white')) {
			case 'white':
			default:
				$wrapperClassName = 'outlineType: \'rounded-white\', ';
				break;
			case 'dark':
				$wrapperClassName = 'wrapperClassName: \'dark\', outlineType: \'glossy-dark\', ';
				break;
		}
		
		$caption = 'captionEval: \'this.thumb.' . $this->params->get('highslide_caption', 'title') . '\'';
		
		foreach($image_set as $image) {
			$image->a = str_replace('></a>', ' class="highslide" onclick="return hs.expand(this, { ' . $slideshowGroup . $wrapperClassName . $caption . ' } )"></a>', $image->a);
		}
		
		return $image_set;
	}
	
}

?>
