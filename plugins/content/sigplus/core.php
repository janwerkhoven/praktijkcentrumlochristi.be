<?php
/**
* @file
* @brief    sigplus Image Gallery Plus plug-in for Joomla
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

if (!defined('SIGPLUS_VERSION')) {
	define('SIGPLUS_VERSION', '1.3.1');
}

if (!defined('SIGPLUS_DEBUG')) {
	define('SIGPLUS_DEBUG', false);
}
if (!defined('SIGPLUS_LOGGING')) {
	define('SIGPLUS_LOGGING', false);
}

require_once dirname(__FILE__).DS.'exception.php';
require_once dirname(__FILE__).DS.'params.php';
require_once dirname(__FILE__).DS.'services.php';
require_once dirname(__FILE__).DS.'thumbs.php';
require_once dirname(__FILE__).DS.'engines.php';

function array_value($array, $key) {
	return isset($array[$key]) ? $array[$key] : false;
}

/**
* Builds HTML from tag name, attribute array and element content.
*/
function make_html($element, $attrs = false, $content = false) {
	$html = '<'.$element;
	if ($attrs !== false) {
		foreach ($attrs as $key => $value) {
			if ($value !== false) {
				$html .= ' '.$key.'="'.htmlspecialchars($value).'"';
			}
		}
	}
	if ($content !== false) {
		$html .= '>'.$content.'</'.$element.'>';
	} else {
		$html .= '/>';
	}
	return $html;
}

/**
* Returns the href attribute value of an anchor element.
*/
function get_anchor_attrs($html) {
	$matches = array();
	if (!preg_match('#<a\s([^<>]*)>#u', $html, $matches)) {
		return false;
	}
	$attrs = string_to_array($matches[1]);
	return $attrs;
}

/**
* Logging services.
*/
class SIGPlusLogging {
	/** Error log. */
	private $log = array();
	/** Singleton instance. */
	private static $inst = false;

	public static function instance() {
		if (self::$inst === false) {
			self::$inst = new SIGPlusLogging();
		}
		return self::$inst;
	}

	public function append($message) {
		$this->log[] = $message;
	}

	public function fetch() {
		ob_start();
			print '<ul>';
			foreach ($this->log as $logentry) {
				print '<li>'.$logentry.'</li>';
			}
			print '</ul>';
			$this->log = array();
		return ob_get_clean();
	}
}

class SIGPlusCoreConfiguration {
	/** Whether to utilize a content delivery network to load javascript frameworks. */
	public $ajaxapi = true;
	/** Whether to enter debug mode. */
	public $debug = false;

	public function validate() {
		switch ($this->ajaxapi) {
			case 'none': case 'local': case 'cdn-google': case 'cdn-microsoft': case 'cdn-jquery':
				break;
			default:
				$this->ajaxapi = (bool) $this->ajaxapi ? 'default' : 'local';
		}
		$this->debug = (bool) $this->debug;
	}

	/**
	* Set parameter object from a Joomla JParameters object.
	*/
	public function setParameters(JRegistry $params) {
		$this->ajaxapi = $params->get('ajaxapi', true);
		$this->debug = $params->get('debug', false);
		$this->validate();
	}
}

/**
* System-wide global configuration settings.
*/
class SIGPlusConfiguration {
	public $core;
	public $services;
	public $galleries;

	public function setConfiguration(SIGPlusCoreConfiguration $core, SIGPlusImageServicesConfiguration $services, SIGPlusGalleryParameters $galleries) {
		$this->core = $core;
		$this->services = $services;
		$this->galleries = $galleries;
	}

	public function setParameters(JRegistry $params) {
		$this->core = new SIGPlusCoreConfiguration();  // global settings
		$this->core->setParameters($params);
		$this->services = new SIGPlusImageServicesConfiguration();  // image service settings
		$this->services->setParameters($params);
		$this->galleries = new SIGPlusGalleryParameters();  // administration back-end parameters
		$this->galleries->setParameters($params);
	}
}

/**
* sigplus Image Gallery Plus service class.
*/
class SIGPlusCore {
	/** General parameters. */
	private $imageservices;
	/** Associative array of default gallery-specific parameters. */
	private $defparams;
	/** Associative array of current gallery-specific parameters. */
	private $curparams;
	/** A list of identifiers issued. The list ensures uniqueness: duplicate identifiers are decorated to make them unique. */
	private static $galleryids = array();

    public function __construct(SIGPlusConfiguration $configuration) {
		// set general parameters
		$engineservices =& SIGPlusEngineServices::instance();
		$engineservices->ajaxapi = $configuration->core->ajaxapi;
		if (SIGPLUS_DEBUG) {  // force debug mode
			$engineservices->debug = true;
		} else {
			$engineservices->debug = (bool) $configuration->core->debug;
		}

		// set default global parameters for image galleries
		$this->defparams = $configuration->galleries;
		if (SIGPLUS_LOGGING) {
			$logging =& SIGPlusLogging::instance();
			$logging->append('Global parameters are:<pre>'.print_r($this->defparams, true).'</pre>');
		}

		// create image services object
		try {
			$this->imageservices = new SIGPlusImageServices($configuration->services);
		} catch (Exception $e) {
			$this->imageservices = null;  // image services not available
			throw $e;                     // re-throw exception
		}
	}

	/**
	* Creates a thumbnail image, a preview image, and a watermarked image for an original.
	* Images are generated only if they do not already exist.
	* A separate thumbnail image is generated if the preview is too large to act as a thumbnail.
	*/
	private function createPreviewImage($imagedirectory, $imagefile) {
		$imagepath = JPATH_ROOT.DS.str_replace('/', DS, $imagedirectory.'/'.$imagefile);
		$params = new SIGPlusPreviewParameters($this->curparams);
		$imagelibrary =& SIGPlusImageLibrary::instantiate($this->imageservices->getLibrary());

		// create watermarked image
		if ($this->curparams->watermark) {
			$watermarkedpath = $this->imageservices->getWatermarkedPath($imagedirectory, $imagefile, $params);
			if ($watermarkedpath !== false && !is_file($watermarkedpath)) {
				if (is_file($imagedirectory.DS.'watermark.png')) {
					$watermarkpath = $imagedirectory.DS.'watermark.png';
				} else {
					$watermarkpath = $this->imageservices->getImagePath('watermark.png');  // look inside base path (e.g. "images/stories")
				}
				$watermarkparams = $this->curparams->watermark_params;
				$watermarkparams['quality'] = $params->quality;  // GD cannot extract quality parameter from stored image, use quality set by user
				$result = $imagelibrary->createWatermarked($imagepath, $watermarkpath, $watermarkedpath, $watermarkparams);
				if (SIGPLUS_LOGGING) {
					$logging =& SIGPlusLogging::instance();
					if ($result) {
						$logging->append('Saved watermarked image to <kbd>'.$watermarkedpath.'</kbd>');
					} else {
						$logging->append('Failed to save watermarked image to <kbd>'.$watermarkedpath.'</kbd>');
					}
				}
			}
		}

		// create preview image
		$previewpath = $this->imageservices->getPreviewPath($imagedirectory, $imagefile, $params);
		if ($previewpath !== false && !is_file($previewpath)) {  // create image on-the-fly if not exists
			$result = $imagelibrary->createThumbnail($imagepath, $previewpath, $params->width, $params->height, $params->crop, $params->quality);
			if (SIGPLUS_LOGGING) {
				$logging =& SIGPlusLogging::instance();
				if ($result) {
					$logging->append('Saved preview image to <kbd>'.$previewpath.'</kbd>');
				} else {
					$logging->append('Failed to save preview image to <kbd>'.$previewpath.'</kbd>');
				}
			}
		}

		// create thumbnail image
		$thumbpath = $this->imageservices->getThumbnailPath($imagedirectory, $imagefile, $params);
		if ($thumbpath !== false && $thumbpath != $previewpath && !is_file($thumbpath)) {  // separate thumbnail image is required
			$thumbparams = $params->getThumbnailParameters();
			$result = $imagelibrary->createThumbnail($previewpath, $thumbpath, $thumbparams->width, $thumbparams->height, $thumbparams->crop, $thumbparams->quality);  // use preview image as source
			if (SIGPLUS_LOGGING) {
				$logging =& SIGPlusLogging::instance();
				if ($result) {
					$logging->append('Saved thumbnail to <kbd>'.$thumbpath.'</kbd>');
				} else {
					$logging->append('Failed to save thumbnail to <kbd>'.$thumbpath.'</kbd>');
				}
			}
		}
	}

	/**
	* Retrieves all data associated with an image.
	* @return An associative array of image metadata.
	*/
	private function getImageData($imagefolder, $imagefile, $label = false) {
		// get image thumbnail URL and parameters
		$params = new SIGPlusPreviewParameters($this->curparams);
		$thumburl = $this->imageservices->getThumbnailUrl($imagefolder, $imagefile, $params);
		$previewurl = $this->imageservices->getPreviewUrl($imagefolder, $imagefile, $params);

		// get image metadata
		if ($this->curparams->metadata) {
			require_once dirname(__FILE__).DS.'metadata.php';
			$imagepath = $this->imageservices->getImagePath($imagefolder);
			$metadata = SIGPlusIPTCServices::getImageMetadata($imagepath.DS.$imagefile);
		} else {
			$metadata = false;
		}

		// use caption and summary from labels file if available
		if ($label !== false) {
			$caption = $label->getCaptionHtml();
			$summary = $label->getDescriptionHtml();
		} else {
			$caption = false;
			$summary = false;
		}

		// use caption and summary from metadata if available
		if ($metadata !== false) {
			if (!$caption && isset($metadata['Headline'])) {
				if (is_array($metadata['Headline'])) {
					$caption = implode(';', $metadata['Headline']);
				} else {
					$caption = $metadata['Headline'];
				}
			}
			if (!$summary && isset($metadata['Caption-Abstract'])) {
				if (is_array($metadata['Caption-Abstract'])) {
					$summary = implode(';', $metadata['Caption-Abstract']);
				} else {
					$summary = $metadata['Caption-Abstract'];
				}
			}
		}

		// get lightbox and slider
		$engineservices =& SIGPlusEngineServices::instance();
		$lightbox = $engineservices->getLightboxEngine($this->curparams->lightbox);  // get selected lightbox engine if any or use default
		$slider = $engineservices->getSliderEngine($this->curparams->slider);        // get selected slider engine if any, or use default

		// get target URL for preview image
		$url = false;
		if ($lightbox) {  // display lightbox pop-up window when thumbnail is clicked
			if ($this->curparams->watermark) {
				$url = $this->imageservices->getWatermarkedUrl($imagefolder, $imagefile);
			} else {
				$url = $this->imageservices->getImageUrl($imagefolder, $imagefile, $this->curparams->authentication);
			}
			$anchor_attrs = array('href' => $url);
		} elseif ($summary && ($anchor = get_anchor_attrs($summary)) !== false) {  // check if there is a hyperlink in the description and use it as target link
			$anchor_attrs = $anchor;
		}

		// get preview image parameters
		$img_attrs = array('preview' => $previewurl);
		if ($slider && $this->curparams->progressive && $thumburl != $previewurl) {
			$img_attrs['thumb'] = $thumburl;
		}
		if ($this->curparams->crop) {
			$img_attrs['width'] = $this->curparams->width;
			$img_attrs['height'] = $this->curparams->height;
		} else {
			$imagedirectory = $this->imageservices->getImageDirectory($imagefolder);
			$imagedims = getimagesize($this->imageservices->getPreviewPath($imagedirectory, $imagefile, $params));
			if ($imagedims !== false) {
				$img_attrs['width'] = $imagedims[0];
				$img_attrs['height'] = $imagedims[1];
			} else {
				$img_attrs['width'] = $this->curparams->width;
				$img_attrs['height'] = $this->curparams->height;
			}
		}

		// set image caption and summary
		if ($caption) {
			$img_attrs['caption'] = $caption;
		}
		if ($summary) {
			$img_attrs['summary'] = $summary;
		}

		// get download URL
		if ($this->curparams->download) {
			$img_attrs['fullsize'] = $this->imageservices->getFullsizeImageDownloadUrl($imagefolder, $imagefile, $this->curparams->authentication);
		}

		if (SIGPLUS_LOGGING) {
			$imagedirectory = $this->imageservices->getImageDirectory($imagefolder);
			$logging =& SIGPlusLogging::instance();
			$logging->append('Thumbnail image hash base is <kbd>'.$this->imageservices->getImageHashBase($imagedirectory, $imagefile, $params).'</kbd>');
			$logging->append('Thumbnail image URL is <kbd>'.$thumburl.'</kbd>');
			if ($metadata !== false) {
				$logging->append('Image metadata is available.');
			}
		}

		$imagedata = array(
			'image' => $img_attrs);
		if (isset($anchor_attrs)) {
			$imagedata['anchor'] = $anchor_attrs;
		}
		if ($metadata) {
			$imagedata['metadata'] = $metadata;
		}
		return $imagedata;
	}

	/**
	* Returns JavaScript code for a preview image in a gallery list.
	*/
	private function getPreviewScript($galleryid, $imagefolder, $imagefile, $label = false) {
		$imagedata = $this->getImageData($imagefolder, $imagefile, $label);
		$anchor_attrs = isset($imagedata['anchor']) ? $imagedata['anchor'] : false;
		$img_attrs = $imagedata['image'];
		return '['.implode(',',
			array(
				($anchor_attrs ? '"'.addslashes($anchor_attrs['href']).'"' : 'null'),
				'"'.addslashes($img_attrs['preview']).'"',
				$img_attrs['width'],
				$img_attrs['height'],
				'"'.addslashes(isset($img_attrs['thumb']) ? $img_attrs['thumb'] : '').'"',
				'"'.addslashes(isset($img_attrs['caption']) ? $img_attrs['caption'] : '').'"',
				'"'.addslashes(isset($img_attrs['summary']) ? $img_attrs['summary'] : '').'"',
				'"'.addslashes(isset($img_attrs['fullsize']) ? $img_attrs['fullsize'] : '').'"',
				isset($imagedata['metadata']) ? json_encode($imagedata['metadata']) : 'null'
			)
		).']';
	}

	/**
	* Returns HTML code for a preview image in a gallery list.
	*/
	private function printPreviewHtml($galleryid, $index, $imagefolder, $imagefile, $label = false) {
		$imagedata = $this->getImageData($imagefolder, $imagefile, $label);
		if (isset($imagedata['anchor'])) {
			$anchor_attrs = $imagedata['anchor'];
		}
		$img_params = $imagedata['image'];

		$engineservices =& SIGPlusEngineServices::instance();
		$lightbox = $engineservices->getLightboxEngine($this->curparams->lightbox);  // get selected lightbox engine if any or use default

		// add rel attribute to hook lightbox
		if ($lightbox && isset($anchor_attrs)) {
			$anchor_attrs['rel'] = $lightbox->getLinkAttribute($galleryid);
		}

		// compose preview image (HTML img element)
		$imageid = $galleryid.'_img'.sprintf('%04d', $index);
		$img_attrs = array(
			'id' => $imageid);
		if (isset($img_params['thumb'])) {
			$img_attrs['src'] = $img_params['thumb'];
			$img_attrs['longdesc'] = $img_params['preview'];
		} else {
			$img_attrs['src'] = $img_params['preview'];
		}
		$img_attrs['width'] = $img_params['width'];
		$img_attrs['height'] = $img_params['height'];
		$img_attrs['alt'] = strip_tags(isset($img_params['caption']) ? $img_params['caption'] : $this->curparams->deftitle);
		if (!isset($img_params['summary']) && $this->curparams->defdescription !== false) {  // set default description if no description is supplied
			$img_params['summary'] = $this->curparams->defdescription;
		}
		if (isset($img_params['summary'])) {
			$img_attrs['title'] = strip_tags($img_params['summary']);
		}

		// compose metadata field (invisible HTML div element)
		ob_start();
			if (isset($img_params['summary'])) {
				// summary text to display below image
				print '<div id="'.$imageid.'_summary">'.$img_params['summary'].'</div>';
			}

			// image icons
			if (isset($img_params['fullsize'])) {
				print '<a rel="download" href="'.$img_params['fullsize'].'"></a>';
			}

			// image metadata
			if (isset($imagedata['metadata'])) {  // display IPTC image metadata in pop-up window if set
				print '<div id="'.$imageid.'_iptc">';
				print '<table>';
				foreach ($imagedata['metadata'] as $key => $value) {
					print '<tr><th>'.htmlspecialchars($key).'</th>';
					if (is_array($value)) {
						$stringvalue = implode(', ', $value);
					} else {
						$stringvalue = $value;
					}
					print '<td>'.nl2br(htmlspecialchars($stringvalue)).'</td></tr>';
				}
				print '</table>';
				print '</div>';
			}
		$meta = ob_get_clean();

		if ($this->curparams->maxcount > 0 && $index >= $this->curparams->maxcount) {  // images in excess of maximum thumbnail count
			print '<li style="display:none !important;">';  // images are shown in pop-up window but not on page
		} else {
			print '<li>';
		}
		$imagehtml = make_html('img', $img_attrs);
		if (isset($anchor_attrs)) {
			print make_html('a', $anchor_attrs, $imagehtml);
		} else {
			print $imagehtml;
		}
		if ($meta) {
			print '<div id="'.$imageid.'_metadata" style="display:none !important;">'.$meta.'</div>';
		}
		print '</li>';
	}

	/**
	* Adds style and script declarations for an image gallery.
	*/
	private function addStylesAndScripts($galleryid) {
		// add styles and scripts for image gallery
		$engineservices =& SIGPlusEngineServices::instance();
		$engineservices->addStyleDefaultLanguage();
		$engineservices->addStyles();
		$lightbox = $engineservices->getLightboxEngine($this->curparams->lightbox);  // get selected lightbox engine if any, or use default
		if ($lightbox) {
			$lightbox->addStyles();
			$lightbox->addScripts($galleryid, $this->curparams);
		}
		if ($this->curparams->metadata) {
			$metadatabox = $engineservices->getMetadataEngine($this->curparams->lightbox);
			if ($metadatabox) {
				$metadatabox->addStyles();
				$metadatabox->addMetadataScripts();
			}
		}
		$slider = $engineservices->getSliderEngine($this->curparams->slider);  // get selected slider engine if any, or use default
		if ($slider) {  // use image thumbnail navigation controls
			$slider->addStyles();
			$slider->addScripts($galleryid, $this->curparams);
		}
		$captions = $engineservices->getCaptionsEngine($this->curparams->captions);  // get selected captions engine if any, or use default
		if ($captions) {
			if ($this->curparams->metadata) {
				$captions->showMetadata(true);
			}
			if ($this->curparams->download) {
				$captions->showDownload(true);
			}
			$captions->addStyles();
			$captions->addScripts($galleryid, $this->curparams);
		}

		// add custom style declaration based on back-end and inline settings
		$cssrules = array();
		if ($this->curparams->margin !== false) {
			$cssrules[] = 'margin:'.$this->curparams->margin.'px !important;';
		}
		if ($this->curparams->borderwidth !== false && $this->curparams->borderstyle !== false && $this->curparams->bordercolor !== false) {
			$cssrules[] = 'border:'.$this->curparams->borderwidth.'px '.$this->curparams->borderstyle.' #'.$this->curparams->bordercolor.' !important;';
		} else {
			if ($this->curparams->borderwidth !== false) {
				$cssrules[] = 'border-width:'.$this->curparams->borderwidth.'px !important;';
			}
			if ($this->curparams->borderstyle !== false) {
				$cssrules[] = 'border-style:'.$this->curparams->borderstyle.' !important;';
			}
			if ($this->curparams->bordercolor !== false) {
				$cssrules[] = 'border-color:#'.$this->curparams->bordercolor.' !important;';
			}
		}
		if ($this->curparams->padding !== false) {
			$cssrules[] = 'padding:'.$this->curparams->padding.'px !important;';
		}
		if (!empty($cssrules)) {
			$document =& JFactory::getDocument();
			$document->addStyleDeclaration('#'.$galleryid.' ul > li img { '.implode("\n", $cssrules).' }');
		}
		//$document->addStyleDeclaration('#'.$galleryid.' ul > li { width: '.$this->curparams->width.'px; height: '.$this->curparams->height.'px; }');

		$engineservices->addOnReadyScripts();
	}

	/**
	* Generates an image gallery entirely defined with a labels file.
	*/
	private function getUserDefinedImageGalleryHtml($imagedirectory, $imagefolder, $labels, $galleryid, &$count) {
		$count = 0;
		foreach ($labels as $label) {
			$this->createPreviewImage($imagedirectory, $label->imagefile);
			$count++;
		}
		switch ($this->curparams->linkage) {
			case 'inline':
				$index = 0;
				ob_start();
				foreach ($labels as $label) {
					print $this->printPreviewHtml($galleryid, $index++, $imagefolder, $label->imagefile, $label);
				}
				return ob_get_clean();
			default:
				$items = array();
				foreach ($labels as $index => &$label) {
					$items[] = $this->getPreviewScript($galleryid, $imagefolder, $label->imagefile, $label);
				}
				return $items;
		}
	}

	/**
	* Generates an image gallery where some files have labels.
	*/
	private function getLabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, $labels, $galleryid, &$count) {
		if (empty($files)) {
			return false;
		}
		$labelmap = array();
		foreach ($labels as $label) {  // enumerate images listed in labels.txt
			$labelmap[$label->imagefile] = $label;
		}
		$files = array_filter($files, 'is_imagefile');
		$count = 0;
		foreach ($files as $file) {
			$this->createPreviewImage($imagedirectory, $file);
			$count++;
		}
		switch ($this->curparams->linkage) {
			case 'inline':
				$index = 0;
				ob_start();
				foreach ($files as $file) {
					$this->printPreviewHtml($galleryid, $index++, $imagefolder, $file, array_value($labelmap, $file));
				}
				return ob_get_clean();
			default:
				$items = array();
				foreach ($files as $index => $file) {
					$items[] = $this->getPreviewScript($galleryid, $imagefolder, $file, array_value($labelmap, $file));
				}
				return $items;
		}
	}

	/**
	* Generates an images gallery where files have no labels.
	*/
	private function getUnlabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, $galleryid, &$count) {
		return $this->getLabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, array(), $galleryid, $count);
	}

	/**
	* Ensures that a gallery identifier is unique across the page.
	* A gallery identifier is specified by the user or generated from the relative image path. Other extensions,
	* however, may duplicate article content on the page (e.g. show a short article extract in a module position),
	* making an identifier no longer unique. This function adds an ordinal to prevent conflicts when the same gallery
	* would occur multiple times on the page, causing scripts not to function properly.
	*/
	private function getUniqueGalleryId($galleryid) {
		if (in_array($galleryid, self::$galleryids)) {  // look for identifier in script-lifetime container
			$counter = 1000;
			do {
				$counter++;
				$gid = $galleryid.'_'.$counter;
			} while (in_array($gid, self::$galleryids));
			$galleryid = $gid;
		}
		self::$galleryids[] = $galleryid;
		return $galleryid;
	}

	/**
	* Generates image thumbnails with alternate text, title and lightbox pop-up activation on mouse click.
	* @param imagefolder The image folder to display interpreted w.r.t. the image base folder.
	* @param paramstring A whitespace-separated list of name="value" parameter values.
	*/
	private function getImageGalleryHtml($imagefolder, $paramstring = '') {
		// set gallery parameters
		$this->curparams = clone $this->defparams;  // parameters set in back-end
		$paramstring = htmlspecialchars_decode($paramstring);
		$this->curparams->setString($paramstring);  // parameters set inline

		// set gallery folders
		$imagefolder = trim($imagefolder, '/');  // remove leading and trailing backslash
		$imagedirectory = $this->imageservices->getImageDirectory($imagefolder);

		// set gallery identifier
		$galleryname = preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace('/', '_', $imagefolder));  // clear non-conformant special characters from name
		if ($this->curparams->id) {  // use user-supplied identifier
			$galleryid = $this->curparams->id;
		} else {  // automatically generate identifier for thumbnail gallery
			$galleryid = 'sigplus_'.$galleryname;
		}
		$galleryid = $this->getUniqueGalleryId($galleryid);

		// initialize logging
		$imagepath = JPATH_ROOT.DS.str_replace('/', DS, $imagedirectory);
		if (SIGPLUS_LOGGING) {
			$logging =& SIGPlusLogging::instance();
			$logging->append('Generating gallery "'.$galleryid.'" from directory: <kbd>'.$imagepath.'</kbd>');
			$logging->append('Local parameters for "'.$galleryid.'" are:<pre>'.print_r($this->curparams, true).'</pre>');
		}

		// verify if content is available in cache
		if ($this->curparams->linkage == 'external' && ($cachekey = $this->imageservices->getCachedContent($imagedirectory, $this->curparams)) !== false) {
			if (SIGPLUS_LOGGING) {
				$logging->append('Retrieving content with key '.$cachekey.' from cache.');
			}
		} else {
			$cachekey = false;
		}

		// set image gallery alignment (left, center or right)
		$gallerystyle = 'sigplus-gallery';
		switch ($this->curparams->alignment) {
			case 'left': case 'left-clear': case 'left-float': $gallerystyle .= ' sigplus-left'; break;
			case 'center': $gallerystyle .= ' sigplus-center'; break;
			case 'right': case 'right-clear': case 'right-float': $gallerystyle .= ' sigplus-right'; break;
		}
		switch ($this->curparams->alignment) {
			case 'left': case 'left-float': case 'right': case 'right-float': $gallerystyle .= ' sigplus-float'; break;
			case 'left-clear': case 'right-clear': $gallerystyle .= ' sigplus-clear'; break;
		}

		$engineservices =& SIGPlusEngineServices::instance();
		$slider = $engineservices->getSliderEngine($this->curparams->slider);  // get selected slider engine if any, or use default

		// generate gallery HTML code or setup script
		if ($cachekey === false) {
			// fetch image labels
			switch ($this->curparams->labels) {
				case 'filename':
					$labels = $this->imageservices->getLabelsFromFilenames($imagedirectory); break;
				default:
					$labels = $this->imageservices->getLabels($imagedirectory, $this->curparams->labels, $this->curparams->deftitle, $this->curparams->defdescription);
			}
			$count = 0;
			switch ($this->curparams->sortcriterion) {
				case SIGPLUS_SORT_LABELS_OR_FILENAME:
					if (empty($labels)) {  // there is no labels file to use
						$files = scandirsorted($imagepath, SIGPLUS_FILENAME, $this->curparams->sortorder);
						$htmlorscript = $this->getUnlabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, $galleryid, $count);
					} else {
						$htmlorscript = $this->getUserDefinedImageGalleryHtml($imagedirectory, $imagefolder, $labels, $galleryid, $count);
					}
					break;
				case SIGPLUS_SORT_LABELS_OR_MTIME:
					if (empty($labels)) {
						$files = scandirsorted($imagepath, SIGPLUS_MTIME, $this->curparams->sortorder);
						$htmlorscript = $this->getUnlabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, $galleryid, $count);
					} else {
						$htmlorscript = $this->getUserDefinedImageGalleryHtml($imagedirectory, $imagefolder, $labels, $galleryid, $count);
					}
					break;
				case SIGPLUS_SORT_MTIME:
					$files = scandirsorted($imagepath, SIGPLUS_MTIME, $this->curparams->sortorder);
					$htmlorscript = $this->getLabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, $labels, $galleryid, $count);
					break;
				case SIGPLUS_SORT_RANDOM:
					$files = @scandir($imagepath);
					if (!empty($files)) {
						shuffle($files);
					}
					$htmlorscript = $this->getLabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, $labels, $galleryid, $count);
					break;
				case SIGPLUS_SORT_RANDOMLABELS:
					if (empty($labels)) {  // there is no labels file to use
						$files = @scandir($imagepath);
						if (!empty($files)) {
							shuffle($files);
						}
						$htmlorscript = $this->getUnlabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, $galleryid, $count);
					} else {
						shuffle($labels);
						$htmlorscript = $this->getUserDefinedImageGalleryHtml($imagedirectory, $imagefolder, $labels, $galleryid, $count);
					}
					break;
				default:  // case SIGPLUS_SORT_FILENAME:
					$files = scandirsorted($imagepath, SIGPLUS_FILENAME, $this->curparams->sortorder);
					$htmlorscript = $this->getLabeledImageGalleryHtml($imagedirectory, $imagefolder, $files, $labels, $galleryid, $count);
					break;
			}
		}

		if ($cachekey === false && empty($htmlorscript)) {
			$html = JText::_('SIGPLUS_EMPTY');
		} else {  // cached content also needs placeholder
			ob_start();
			$editor =& JFactory::getEditor();
			//print $editor->_tagForSEF['start'];  // exclude image gallery from being processed by Joomla SEF
			print '<div id="'.$galleryid.'" class="'.$gallerystyle.'">';
			print ($slider !== false ? '<ul style="visibility:hidden;">' : '<ul>');
			if ($this->curparams->linkage == 'inline') {  // content produced as HTML only in inline linkage mode
				print $htmlorscript;
			}
			print '</ul>';
			print '</div>';
			//print $editor->_tagForSEF['end'];  // exclude image gallery from being processed by Joomla SEF
			$html = ob_get_clean();
		}

		if ($cachekey !== false) {  // retrieve content from cache
			$this->addGalleryScript();
			$document =& JFactory::getDocument();
			$document->addScript($this->imageservices->getCachedContentUrl($cachekey));
		} else {
			if (!empty($htmlorscript) && is_array($htmlorscript)) {
				$galleryscript = $this->getGalleryScript($galleryid, $htmlorscript);
				switch ($this->curparams->linkage) {
					case 'external':  // save generated content for future re-use in a temporary file in the cache folder
						$this->addGalleryScript();
						$externalscript = '__jQuery__(function () { '.$galleryscript.' });';
						if ($cachekey = $this->imageservices->saveCachedContent($imagedirectory, $this->curparams, $externalscript)) {  // content successfully cached
							// include reference to generated script
							$document =& JFactory::getDocument();
							$document->addScript($this->imageservices->getCachedContentUrl($cachekey));
						} else {
							// put generated script in HTML head as a fall-back
							$engineservices->addOnReadyScript($galleryscript);
						}
						break;
					case 'head':  // put generated content in HTML head (does not allow HTML body with bloating size, which would cause preg_replace in System - SEF to fail)
						$this->addGalleryScript();
						$engineservices->addOnReadyScript($galleryscript);
						break;
				}
			}
		}

		// add style and script declarations
		$this->addStylesAndScripts($galleryid);

		$this->curparams = false;
		return $html;
	}

	/**
	* Adds JavaScript code that dynamically creating an image gallery from a data array.
	*/
	private function addGalleryScript() {
		$engineservices =& SIGPlusEngineServices::instance();
		$engineservices->addJQuery();
		$document =& JFactory::getDocument();
		$document->addScript(JURI::base(true).'/plugins/content/sigplus/js/linkage.js');
	}

	private function getGalleryScript($galleryid, $script) {
		$engineservices =& SIGPlusEngineServices::instance();
		$lightbox = $engineservices->getLightboxEngine($this->curparams->lightbox);  // get selected lightbox engine if any or use default
		return '__jQuery__("#'.$galleryid.'").sigplusLinkage('.
			'['.implode(',', $script).'],'.
			'"'.($lightbox ? $lightbox->getLinkAttribute($galleryid) : '').'",'.  // rel attribute to hook lightbox
			'"'.addslashes($this->curparams->deftitle).'",'.
			'"'.addslashes($this->curparams->defdescription).'");';
	}

	/**
	* Generates image thumbnails with alternate text, title and lightbox pop-up activation on mouse click.
	* @param imagefolder The image folder to display interpreted w.r.t. the image base folder.
	* @param paramstring A whitespace-separated list of name="value" parameter values.
	*/
	public function getGalleryHtml($imagefolder, $paramstring = '') {
		if (!isset($this->imageservices)) {  // global error, image services are not available
			throw new SIGPlusInitializationException();
		}
		$oblevel = ob_get_level();
		try {
			return $this->getImageGalleryHtml($imagefolder, $paramstring);
		} catch (Exception $e) {  // local error
			for ($k = ob_get_level(); $k > $oblevel; $k--) {  // release output buffers
				ob_end_clean();
			}
			throw $e;  // re-throw exception
		}
	}

	/**
	* Generates image thumbnails with alternate text, title and lightbox pop-up activation on mouse click.
	* This method is to be called as a regular expression replace callback.
	* Any error messages are printed to screen.
	* @param match A regular expression match.
	*/
	public function getGalleryRegexReplacement($match) {
		return $this->getGalleryHtml($match[2], $match[1]);
	}

	/**
	* Adds activation code to a (fully customized) gallery.
	*/
	public function addGalleryEngines($customized = false) {
		if (!isset($this->imageservices)) {  // global error, image services not available
			throw new SIGPlusInitializationException();
		}
		$engineservices =& SIGPlusEngineServices::instance();
		if ($customized) {
			// hook anchors with image extensions to lightbox engine if any
			$engineservices->addStyles();
			$lightbox = $engineservices->getLightboxEngine($this->defparams->lightbox);  // get selected lightbox engine if any, or use default
			if ($lightbox) {
				$lightbox->addStyles();
				$lightbox->addActivationScripts();
			}
		}
		$engineservices->addOnReadyEvent();
	}
}