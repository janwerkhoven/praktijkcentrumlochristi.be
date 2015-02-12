<?php
/**
* @file
* @brief    sigplus Image Gallery Plus javascript engine service classes
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

/**
* Service class for JavaScript code management.
*/
class SIGPlusEngineServices {
	/** True if the engine uses the MooToos library. */
	private $mootools = false;
	/** True if the engine uses the jQuery library. */
	private $jquery = false;
	/** Custom tags added to page header. */
	private $customtags = array();
	/** List of registered lightbox engine instances. */
	private $lightboxengines = array();
	/** List of registered slider engine instances. */
	private $sliderengines = array();
	/** List of caption engine instances. */
	private $captionsengines = array();
	/** JavaScript snippets to run on HTML DOM ready event. */
	private $scripts = array();
	private $scriptblocks = array();
	/** URL of external content that replaces an element in the HTML DOM. */
	private $ajaxurl = false;
	/** Identifier of HTML DOM element that is replaced by external content. */
	private $ajaxid = false;

	/** Content delivery network to use on a site that is publicly available (i.e. not an intranet network), 'none' or 'local'. */
	public $ajaxapi = 'default';
	/** Whether to use uncompressed versions of scripts. */
	public $debug = false;
	/** Singleton instance. */
	private static $inst = false;

	public static function instance() {
		if (self::$inst === false) {
			self::$inst = new SIGPlusEngineServices();
		}
		return self::$inst;
	}

	/**
	* Adds MooTools support.
	*/
	public function addMooTools() {
		if ($this->mootools) {
			return;
		}
		switch ($this->ajaxapi) {
			case 'none':
				break;
			default:
				if ($this->debug) {
					JHTML::script('mootools-uncompressed.js', 'media/system/js/', false);
				} else {
					JHTML::_('behavior.mootools');
				}
		}
		$this->mootools = true;
	}

	/**
	* Adds jQuery support.
	*/
	public function addJQuery() {
		if ($this->jquery) {
			return;
		}
		$document =& JFactory::getDocument();

		switch ($this->ajaxapi) {
			case 'none':  // do not load jQuery, recommended when you have another extension (e.g. a system plug-in) that loads jQuery
				break;
			case 'local':  // use local copy of jQuery, recommended only for intranet sites
				$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/jquery.js');
				break;
			case 'cdn-google':  // use jQuery from Google AJAX library
				if ($this->debug) {
					$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.js');
				} else {
					$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js');
				}
				$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/jquery.noconflict.js');
				break;
			case 'cdn-microsoft':  // use jQuery from Microsoft Ajax Content Delivery Network
				if ($this->debug) {
					$document->addScript('http://ajax.microsoft.com/ajax/jQuery/jquery-1.4.2.js');
				} else {
					$document->addScript('http://ajax.microsoft.com/ajax/jQuery/jquery-1.4.2.min.js');
				}
				$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/jquery.noconflict.js');
				break;
			case 'cdn':
			case 'cdn-jquery':
				if ($this->debug) {
					$document->addScript('http://code.jquery.com/jquery-1.4.2.js');
				} else {
					$document->addScript('http://code.jquery.com/jquery-1.4.2.min.js');
				}
				$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/jquery.noconflict.js');
				break;
			default:  // use jQuery from Google AJAX library with on-demand inclusion
				$document->addScript('http://www.google.com/jsapi');
				if ($this->debug) {
					$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/safemode.debug.js');
					$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/safemode.finalize.js');
				} else {
					$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/safemode.initialize.min.js');
					$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/safemode.finalize.min.js');
				}
		}
		$document->addScriptDeclaration('if (typeof(__jQuery__) == "undefined") { var __jQuery__ = jQuery; }');
		$this->jquery = true;
	}

	/**
	* Fetch an engine from the engine registry, adding a new instance if necessary.
	* @param engines The associative array that maps engine names to instances.
	* @param engine A unique name used to instantiate the engine.
	*/
	private function getEngine($engines, $engine) {
		if (is_null($engine)) {  // use first registered engine, if any
			if (empty($engines)) {
				return false;
			} else {
				return reset($engines);  // returns first registered engine
			}
		} elseif ($engine === false) {
			return false;
		} else {
			if (!isset($engines[$engine])) {
				$engines[$engine] = SIGPlusEngine::create($engine);
			}
			return $engines[$engine];
		}
	}

	public function getLightboxEngine($lightboxengine) {
		return $this->getEngine($this->lightboxengines, $lightboxengine);
	}

	public function getSliderEngine($sliderengine) {
		return $this->getEngine($this->sliderengines, $sliderengine);
	}

	public function getCaptionsEngine($captionsengine) {
		return $this->getEngine($this->captionsengines, $captionsengine);
	}

	public function getMetadataEngine($lightboxengine) {
		$engine = $this->getLightboxEngine($lightboxengine);
		if ($engine !== false && $engine->isInlineContentSupported()) {
			return $engine;
		} else {
			return $this->getLightboxEngine('boxplus');
		}
	}

	public function addCustomTag($tag) {
		if (!in_array($tag, $this->customtags)) {
			$document =& JFactory::getDocument();
			$document->addCustomTag($tag);
			$this->customtags[] = $tag;
		}
	}

	public function addStyleDefaultLanguage() {
		$this->addCustomTag('<meta http-equiv="Content-Style-Type" content="text/css" />');
	}

	public function addStyles() {
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base(true).'/plugins/content/sigplus/css/sigplus.css');
		$this->addCustomTag('<!--[if lt IE 8]><link rel="stylesheet" href="'.JURI::base(true).'/plugins/content/sigplus/css/sigplus.ie7.css" type="text/css" /><![endif]-->');
	}

	/**
	* Appends a JavaScript snippet to the code to be run on the HTML DOM ready event.
	*/
	public function addOnReadyScript($script) {
		$this->scripts[] = $script;
	}

	/**
	* Causes onready event scripts to execute only when an AJAX request has successfully terminated.
	* @param url The URL to use for the HTTP GET request.
	* @param id The identifier of the HTML element that the fetched HTML content replaces.
	*/
	public function setAjaxOnReady($url, $id) {
		$this->ajaxurl = $url;
		$this->ajaxid = $id;
	}

	/**
	* Adds all HTML DOM ready event scripts to the page as a @c script declaration.
	*/
	public function addOnReadyScripts() {
		if (!empty($this->scripts)) {
			$script = implode("\n", $this->scripts);
			if ($this->ajaxurl !== false && $this->ajaxid !== false) {
				$this->addJQuery();
				// register client-side script to replace placeholder with external content
				$script =
					'__jQuery__.get("'.$this->ajaxurl.'", function(ajaxdata) {'."\n".
					'__jQuery__("#'.$this->ajaxid.'").replaceWith(ajaxdata);'."\n".
					$script."\n".
					'});';
				$this->ajaxurl = false;
				$this->ajaxid = false;
			}
			$this->scriptblocks[] = $script;
		}
		$this->scripts = array();  // clear scripts added to document
	}

	public function addOnReadyEvent() {
		if (!empty($this->scripts)) {
			$this->addOnReadyScripts();
		}
		if (!empty($this->scriptblocks)) {
			if ($this->jquery) {
				$onready = '__jQuery__(document).ready(';
			} else {
				$onready = 'window.addEvent("domready",';
			}
			$onready .= 'function() {'."\n".implode("\n", $this->scriptblocks)."\n".'});';
			$document =& JFactory::getDocument();
			$document->addScriptDeclaration($onready);
			$this->scriptblocks = array();
		}
	}
}

/**
* Base class for engines based on a javascript framework.
*/
class SIGPlusEngine {
	public function getIdentifier() {
		return 'default';
	}

	public function getCustomParameters($params) {
		if ($params !== false && !empty($params)) {
			return json_encode($params);
		} else {
			return '{}';
		}
	}

	/**
	* Filename for javascript code to load.
	*/
	protected function getScriptFilename($identifier = false) {
		if (!$identifier) {
			$identifier = $this->getIdentifier();
		}
		$instance =& SIGPlusEngineServices::instance();
		if ($instance->debug) {
			return $identifier.'.js';
		} else {
			return $identifier.'.min.js';
		}
	}

	/**
	* Adds MooTools support.
	*/
	protected function addMooTools() {
		$instance =& SIGPlusEngineServices::instance();
		$instance->addMooTools();
	}

	/**
	* Adds jQuery support.
	*/
	protected function addJQuery() {
		$instance =& SIGPlusEngineServices::instance();
		$instance->addJQuery();
	}

	public function addCustomTag($tag) {
		$instance =& SIGPlusEngineServices::instance();
		$instance->addCustomTag($tag);
	}

	/**
	* Adds style sheet references to the HTML @c head element.
	*/
	public function addStyles() {
		$document =& JFactory::getDocument();
		$document->addStyleSheet(JURI::base(true).'/plugins/content/sigplus/engines/'.$this->getIdentifier().'/css/'.$this->getIdentifier().'.css');
	}

	/**
	* Appends a JavaScript snippet to the code to be run on the HTML DOM ready event.
	*/
	protected function addOnReadyScript($script) {
		$instance =& SIGPlusEngineServices::instance();
		$instance->addOnReadyScript($script);
	}

	/**
	* Factory method for engine instantiation.
	*/
	public static function create($engine) {
		// check for parameters passed to engine
		$pos = strpos($engine, '/');
		if ($pos !== false) {
			$params = array('theme'=>substr($engine, $pos+1));
			$engine = substr($engine, 0, $pos);
		} else {
			$params = array();
		}

		$engineclassname = str_replace('.', '', $engine);
		if (!ctype_alnum($engineclassname)) {  // simple name required
			return false;
		}

		$engineclass = 'SIGPlus'.str_replace('.', '', $engineclassname).'Engine';
		$enginefile = dirname(__FILE__).DS.'engines'.DS.$engine.'.php';
		if (is_file($enginefile)) {
			require_once $enginefile;
		}
		if (class_exists($engineclass)) {
			return new $engineclass($params);
		} else {
			return false;  // inclusion failure
		}
	}
}

/**
* Base class for lightbox-clone support.
*/
class SIGPlusLightboxEngine extends SIGPlusEngine {
	/**
	* A default constructor that ignores all optional arguments.
	*/
	public function __construct($params = false) { }

	public function getCustomParameters($params) {
		return parent::getCustomParameters($params->lightbox_params);
	}

	public function isInlineContentSupported() {
		return false;
	}

	/**
	* Adds script references that are common to normal and fully customized gallery initialization.
	* @remark When overriding this method, the base method should normally be called.
	*/
	protected function addCommonScripts() {
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/'.$this->getIdentifier().'/js/'.$this->getScriptFilename());
	}

	/**
	* Adds script references to the HTML @c head element to bind the click event to lightbox pop-up activation.
	*/
	protected function addInitializationScripts() {
		$this->addCommonScripts();
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/'.$this->getIdentifier().'/js/initialization.js');
	}

	/**
	* Adds script references to the HTML @c head element to support fully customized gallery initialization.
	* @remark When overriding this method, the base method should normally NOT be called.
	*/
	public function addActivationScripts() {
		$this->addCommonScripts();
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/'.$this->getIdentifier().'/js/activation.js');
	}

	/**
	* The value to use in the @c rel attribute of anchor elements to bind the lightbox-clone.
	* @param gallery The unique identifier for the image gallery. Images in the same gallery are grouped together.
	* @return A valid value for the @c rel attribute of an @c a element.
	*/
	public function getLinkAttribute($gallery = false) {
		if ($gallery !== false) {
			return $this->getIdentifier().'-'.$gallery;
		} else {
			return $this->getIdentifier();
		}
	}
}

/**
* Base class for image slider support.
*/
class SIGPlusSliderEngine extends SIGPlusEngine {
	public function getCustomParameters($params) {
		return parent::getCustomParameters($params->slider_params);
	}

	/**
	* Adds script references to the HTML @c head element to support image gallery generation with lightbox popup.
	*/
	public function addScripts() {
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/'.$this->getIdentifier().'/js/'.$this->getScriptFilename());
	}
}

/**
* Base class for image captions support.
*/
class SIGPlusCaptionsEngine extends SIGPlusEngine {
	protected $download = false;
	protected $metadata = false;

	public function getCustomParameters($params) {
		return parent::getCustomParameters($params->captions_params);
	}

	public function showDownload($state = true) {
		$this->download = $state;
	}

	public function showMetadata($state = true) {
		$this->metadata = $state;
	}

	public function addScripts() {
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/engines/'.$this->getIdentifier().'/js/'.$this->getScriptFilename().'.js');
	}
}
