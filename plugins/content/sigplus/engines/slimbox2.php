<?php
/**
* @file
* @brief    sigplus Image Gallery Plus Slimbox2 lightbox engine
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
* Support class for Slimbox2 (jQuery-based).
* @see http://www.digitalia.be/software/slimbox2
*/
class SIGPlusSlimbox2Engine extends SIGPlusLightboxEngine {
	public function getIdentifier() {
		return 'slimbox2';
	}

	protected function addCommonScripts() {
		$this->addJQuery();
		parent::addCommonScripts();
	}

	public function addScripts($galleryid, $params) {
		$this->addInitializationScripts();
		$script = '__jQuery__("#'.$galleryid.'").bindSlimbox('.$this->getCustomParameters($params).');';
		$this->addOnReadyScript($script);
	}
}