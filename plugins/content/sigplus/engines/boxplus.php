<?php
/**
* @file
* @brief    sigplus Image Gallery Plus boxplus lightweight pop-up window engine
* @author   Levente Hunyadi
* @version  1.3.1
* @remarks  Copyright (C) 2009-2010 Levente Hunyadi
* @remarks  Licensed under GNU/GPLv3, see http://www.gnu.org/licenses/gpl-3.0.html
* @see      http://hunyadi.info.hu/projects/sigplus
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
* Support class for jQuery-based boxplus lightweight pop-up window engine.
* @see http://hunyadi.info.hu/projects/boxplus/
*/
class SIGPlusBoxPlusEngine extends SIGPlusLightboxEngine {
	private $theme = 'lightsquare';

	public function getIdentifier() {
		return 'boxplus';
	}

	public function __construct($params = false) {
		parent::__construct($params);
		if (isset($params['theme'])) {
			$this->theme = $params['theme'];
		}
	}

	public function isInlineContentSupported() {
		return true;
	}

	public function addMetadataScripts() {
		$this->addCommonScripts();
	}
	
	public function getMetadataFunction() {
		return 'function (icon, image) { icon.click(function () { __jQuery__("#" + image.attr("id") + "_iptc").boxplusDialog() }); }';
	}
	
	public function addStyles() {
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base(true).'/plugins/content/sigplus/engines/boxplus/popup/css/boxplus.css');
		$this->addCustomTag('<!--[if IE]><link rel="stylesheet" href="'.JURI::base(true).'/plugins/content/sigplus/engines/boxplus/popup/css/boxplus.ie.css" type="text/css" /><![endif]-->');
		$this->addCustomTag('<!--[if lt IE 8]><link rel="stylesheet" href="'.JURI::base(true).'/plugins/content/sigplus/engines/boxplus/popup/css/boxplus.ie7.css" type="text/css" /><![endif]-->');
		$document->addStyleSheet(JURI::base(true).'/plugins/content/sigplus/engines/boxplus/popup/css/boxplus.'.$this->theme.'.css', 'text/css', null, array('title'=>'boxplus-'.$this->theme));
		$this->addCustomTag('<!--[if IE]><link rel="stylesheet" href="'.JURI::base(true).'/plugins/content/sigplus/engines/boxplus/popup/css/boxplus.'.$this->theme.'.ie.css" type="text/css" title="boxplus-'.$this->theme.'" /><![endif]-->');
	}

	protected function addCommonScripts() {
		$this->addJQuery();
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/boxplus/popup/js/'.$this->getScriptFilename());
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/boxplus/lang/'.$this->getScriptFilename('boxplus.lang'));
	}

	public function addActivationScripts() {
		$this->addCommonScripts();
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/boxplus/popup/js/activation.js');
	}

	public function addScripts($galleryid, $params) {
		$this->addCommonScripts();
		$language =& JFactory::getLanguage();
		list($lang, $country) = explode('-', $language->getTag());
		$script =
			'__jQuery__("#'.$galleryid.'").boxplusGallery(__jQuery__.extend('.$this->getCustomParameters($params).', { '.
				'theme: "'.$this->theme.'", '.
				'description: function (anchor) { var s = __jQuery__("#" + __jQuery__("img", anchor).attr("id") + "_summary"); return s.size() ? s.html() : anchor.attr("title"); }, '.
				'download: function (anchor) { var d = __jQuery__("#" + __jQuery__("img", anchor).attr("id") + "_metadata a[rel=download]"); return d.size() ? d.attr("href") : ""; }, '.
				'metadata: function (anchor) { var m = __jQuery__("#" + __jQuery__("img", anchor).attr("id") + "_iptc"); return m.size() ? m : ""; } '.
			' })); '.
			'__jQuery__.boxplusLanguage("'.$lang.'", "'.$country.'");';
		$this->addOnReadyScript($script);
	}
}
