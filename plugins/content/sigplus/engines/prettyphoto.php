<?php
/**
* @file
* @brief    sigplus Image Gallery Plus adapter for prettyPhoto lightbox engine substitute
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once dirname(__FILE__).DS.'boxplus.php';

/**
* Substitute class for prettyPhoto (jQuery-based).
* @see http://www.no-margin-for-errors.com/projects/prettyphoto-jquery-lightbox-clone/
*/
class SIGPlusPrettyPhotoEngine extends SIGPlusLightboxEngine {
	private $adaptee;

	public function getIdentifier() {
		return 'prettyphoto';
	}

	public function __construct($params = false) {
		if (isset($params['theme'])) {
			$theme = $params['theme'];
		} else {
			$theme = 'light_rounded';
		}
		switch ($theme) {
			case 'light_rounded': $theme = 'lightrounded'; break;
			case 'dark_rounded':  $theme = 'darkrounded'; break;
			case 'light_square':  $theme = 'lightsquare';  break;
			case 'dark_square':   $theme = 'darksquare';   break;
			case 'facebook':      $theme = 'prettyphoto';  break;
		}
		$params['theme'] = $theme;
		$this->adaptee = new SIGPlusBoxPlusEngine($params);
	}

	public function addStyles() {
		$this->adaptee->addStyles();
	}

	public function addActivationScripts() {
		$this->adaptee->addActivationScripts();
	}

	public function addScripts($galleryid, $params) {
		$this->adaptee->addScripts($galleryid, $params);
	}
}
