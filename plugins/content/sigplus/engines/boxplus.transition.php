<?php
/**
* @file
* @brief    sigplus Image Gallery Plus boxplus image transition engine
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once JPATH_PLUGINS.DS.'content'.DS.'sigplus'.DS.'params.php';

/**
* Support class for jQuery-based boxplus transition engine.
* @see http://hunyadi.info.hu/projects/boxplus/
*/
class SIGPlusBoxPlusTransitionEngine extends SIGPlusSliderEngine {
	public function getIdentifier() {
		return 'boxplus.transition';
	}

	public function addStyles() {
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base(true).'/plugins/content/sigplus/engines/boxplus/slider/css/boxplus.transition.css');
	}

	public function addScripts($id, $params) {
		$this->addJQuery();
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/boxplus/slider/js/'.$this->getScriptFilename());
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/boxplus/lang/'.$this->getScriptFilename('boxplus.lang'));

		$language =& JFactory::getLanguage();
		list($lang, $country) = explode('-', $language->getTag());
		$script =
			'__jQuery__("#'.$id.' ul:first").boxplusTransition(__jQuery__.extend('.$this->getCustomParameters($params).', { '.
			'navigation:'.($params->overlay ? '"'.$params->orientation.'"' : 'false').', '.
			'duration:'.$params->duration.', '.
			'delay:'.$params->animation.' })); '.
			'__jQuery__.boxplusLanguage("'.$lang.'", "'.$country.'");';
		$this->addOnReadyScript($script);
	}
}