<?php
/**
* @file
* @brief    sigplus Image Gallery Plus general image services
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

require_once dirname(__FILE__).DS.'dependencies.php';
require_once dirname(__FILE__).DS.'exception.php';
require_once dirname(__FILE__).DS.'filesystem.php';

/**
* A short caption and a more verbose description attached to an image.
* Objects of this class are instantiated based on a "labels.txt" file.
*/
class SIGPlusImageLabel {
	/** Image file name (without path) this label entry corresponds to. */
	public $imagefile;
	/** The short caption attached to the image. */
	private $caption;
	/** The longer description attached to the image if any. */
	private $description;

	function __construct($imagefile, $caption, $description = false) {
		$this->imagefile = $imagefile;
		$this->caption = $caption;
		$this->description = $description;
	}

	public function getCaptionHtml() {
		return $this->caption;
	}

	/**
	* Image description with special HTML characters escaped.
	*/
	public function getDescriptionHtml() {
		if ($this->description) {
			$description = $this->description;
		} else {
			$description = $this->caption;  // copy caption to description if omitted
		}
		return $description;
	}

	/**
	* Image description without HTML tags.
	*/
	public function getDescriptionText() {
		return strip_tags($this->description);
	}
}

/**
* System-wide image gallery generation configuration parameters.
*/
class SIGPlusImageServicesConfiguration {
	/** Whether to support multilingual labeling. */
	public $multilingual = false;
	/** Base directory for images. */
	public $imagesfolder = 'images/stories';
	/** Subdirectory for watermarked images. */
	public $watermarkfolder = 'watermark';
	/** Subdirectory for thumbnail images. */
	public $thumbsfolder = 'thumbs';
	/** Subdirectory for preview images. */
	public $previewfolder = 'preview';
	/** Subdirectory for full-size images. */
	public $fullsizefolder = false;
	/** Whether to use Joomla cache folder. */
	public $thumbscache = false;
	/** Image processing library to use. */
	public $library = 'default';

	public function validate() {
		$this->multilingual = (bool) $this->multilingual;
		$this->thumbscache = (bool) $this->thumbscache;
		switch ($this->library) {
			case 'gd':
				if (!is_gd_supported()) {
					$this->library = 'default';
				}
				break;
			case 'imagick':
				if (!is_imagick_supported()) {
					$this->library = 'default';
				}
				break;
			default:
				$this->library = 'default';
		}
	}

	public function checkFolders() {
		// image base folder
		if ($this->imagesfolder !== '') {
			$imagesfolder = make_relative_path($this->imagesfolder);
			if ($imagesfolder === false) {
				throw new SIGPlusBaseFolderException($this->imagesfolder);
			}
			$imagespath = JPATH_ROOT.DS.str_replace('/', DS, $imagesfolder);
			if (!is_dir($imagespath)) {
				throw new SIGPlusBaseFolderPermissionException($imagespath);
			}
			$this->imagesfolder = $imagesfolder;
		}

		// thumbnail folder (either inside image folder or cache folder)
		$thumbsfolder = make_relative_path($this->thumbsfolder);
		if ($thumbsfolder === false) {
			throw new SIGPlusThumbFolderException($this->thumbsfolder);
		}

		// preview image folder (either inside image folder or cache folder)
		$previewfolder = make_relative_path($this->previewfolder);
		if ($previewfolder === false) {
			throw new SIGPlusPreviewFolderException($this->previewfolder);
		}

		// check that thumbnail folder and preview folder and not identical
		if (!$this->thumbscache && $thumbsfolder == $previewfolder) {
			throw new SIGPlusFolderConflictException($this->previewfolder);
		}

		// set folders
		$this->previewfolder = $previewfolder;
		$this->thumbsfolder = $thumbsfolder;

		// full size image folder
		if ($this->fullsizefolder) {
			$fullsizefolder = make_relative_path($this->fullsizefolder);
			if ($fullsizefolder === false) {
				throw new SIGPlusFullsizeFolderException($this->fullsizefolder);
			}
			$this->fullsizefolder = $fullsizefolder;
		} else {  // no folder available for high-resolution images
			$this->fullsizefolder = false;
		}
	}

	public function setParameters(JRegistry $params) {
		$this->multilingual = $params->get('labels_multilingual', $this->multilingual);  // get whether to use multilingual labeling
		$this->imagesfolder = $params->get('base_folder', $params->get('images_folder', $this->imagesfolder));
		$this->thumbsfolder = $params->get('thumb_folder', $this->thumbsfolder);
		$this->previewfolder = $params->get('preview_folder', $this->previewfolder);
		$this->fullsizefolder = $params->get('fullsize_folder', $this->fullsizefolder);
		$this->thumbscache = $params->get('thumb_cache', $this->thumbscache);
		$this->library = $params->get('library', $this->library);
		$this->validate();
	}
}

/**
* Image and thumbnail file and folder services.
*/
class SIGPlusImageServices {
	/** System-wide configuration parameters. */
	private $config;

	public function __construct(SIGPlusImageServicesConfiguration $config) {
		$this->config = $config;
		$this->config->checkFolders();
	}

	public function hash() {
		return md5(serialize($this->config));
	}

	public function getLibrary() {
		return $this->config->library;
	}

	/**
	* Creates a directory if it does not already exist.
	* @param directory The full path to the directory.
	*/
	private function createDirectoryOnDemand($directory) {
		if (!is_dir($directory)) {  // directory does not exist
			@mkdir($directory, 0755, true);  // try to create it
			if (!is_dir($directory)) {
				throw new SIGPlusFolderPermissionException($directory);
			}
			// create an index.html to prevent getting a web directory listing
			@file_put_contents($directory.DS.'index.html', '<html><body bgcolor="#FFFFFF"></body></html>');
		}
	}

	/**
	* Maps an image folder to a directory relative to Joomla root.
	*/
	public function getImageDirectory($imagefolder) {
		$folder = make_relative_path($imagefolder);
		if ($folder === false) {
			throw new SIGPlusImageFolderException($imagefolder);
		}
		return ($this->config->imagesfolder ? $this->config->imagesfolder.'/' : '').$folder;
	}

	/**
	* Maps an image folder to a full file system path.
	*/
	public function getImagePath($imagefolder) {
		return JPATH_ROOT.DS.str_replace('/', DS, $this->getImageDirectory($imagefolder));  // replace '/' with platform-specific directory separator
	}

	/** The full file system path to the high-resolution image version. */
	private function getFullsizeImagePath($imagefolder, $imagefile) {
		$imagebase = $this->getImagePath($imagefolder);
		if (!$this->config->fullsizefolder) {
			return $imagebase.DS.$imagefile;
		}
		$imagepath = $imagebase.DS.str_replace('/', DS, $this->config->fullsizefolder).DS.$imagefile;
		if (!is_file($imagepath)) {
			return $imagebase.DS.$imagefile;
		}
		return $imagepath;
	}

	/**
	* The base URL for images.
	* Defaults to "images/stories" w.r.t. Joomla root.
	* @return A URL for the image base directory.
	*/
	public function getImageBaseUrl() {
		return JURI::base(true).($this->config->imagesfolder ? '/'.$this->config->imagesfolder : '');
	}

	/**
	* Image URL without Joomla root URL prefix.
	*/
	private function getImageShortUrl($imagefolder, $imagefile) {
		return ($this->config->imagesfolder ? $this->config->imagesfolder.'/' : '').$imagefolder.'/'.rawurlencode($imagefile);
	}

	/**
	* Full-size image URL without Joomla root URL prefix.
	*/
	private function getFullsizeImageShortUrl($imagefolder, $imagefile) {
		if ($this->config->fullsizefolder && is_file($this->getImagePath($imagefolder).DS.str_replace('/', DS, $this->config->fullsizefolder).DS.$imagefile)) {
			return ($this->config->imagesfolder ? $this->config->imagesfolder.'/' : '').$imagefolder.'/'.$this->config->fullsizefolder.'/'.rawurlencode($imagefile);
		} else {
			return $this->getImageShortUrl($imagefolder, $imagefile);
		}
	}

	/**
	* Generate one-time hash to prevent client-side URL tampering.
	* The hash encrypts user data, full image path in file system and image size.
	*/
	private function getImageDownloadHash($imagepath, $userdata = '[anonymous]') {
		$imagesize = @getimagesize($imagepath);
		return md5($userdata.$imagepath.'_'.$imagesize[0].'x'.$imagesize[1]);
	}

	/**
	* Image download URL.
	* @param imageurl The short URL to transform.
	* @param authentication If true, the hash to prevent URL tampering will include user login information.
	*/
	private function getImageDownloadUrl($imagepath, $imageurl, $authentication = false) {
		if ($authentication) {
			$user =& JFactory::getUser();
			if (!$user->id) {  // forbidden to access image if user is not logged in
				return JURI::base(true).'/plugins/content/sigplus/css/404.png';
			}
			$hash = $this->getImageDownloadHash($imagepath, $user->lastvisitDate);
		} else {
			$hash = $this->getImageDownloadHash($imagepath);  // no user data required
		}
		return JURI::base(true).'/plugins/content/sigplus/download.php/'.$imageurl.'?h='.$hash.( $authentication ? '&a=1' : '' );
	}

	/**
	* Temporary or permanent link to image resource.
	* @param authentication If true, URL is to be a temporary link to image that is available to the currently logged-in user; if false, URL is to be a permanent link.
	*/
	private function getAuthenticatedUrl($imagepath, $imageurl, $authentication = false) {
		if ($authentication) {
			return $this->getImageDownloadUrl($imagepath, $imageurl, $authentication);
		} else {
			return JURI::base(true).'/'.$imageurl;
		}
	}

	/**
	* The full URL to an image.
	*/
	public function getImageUrl($imagefolder, $imagefile, $authentication = false) {
		$imagepath = $this->getImagePath($imagefolder).DS.$imagefile;
		$imageurl = $this->getImageShortUrl($imagefolder, $imagefile);
		return $this->getAuthenticatedUrl($imagepath, $imageurl, $authentication);
	}

	/**
	* The full URL to the high-resolution version of an image.
	*/
	public function getFullsizeImageUrl($imagefolder, $imagefile, $authentication = false) {
		$imagepath = $this->getFullsizeImagePath($imagefolder, $imagefile);
		$imageurl = $this->getFullsizeImageShortUrl($imagefolder, $imagefile);
		return $this->getAuthenticatedUrl($imagepath, $imageurl, $authentication);
	}

	/**
	* The full URL for downloading the high-resolution version of an image.
	*/
	public function getFullsizeImageDownloadUrl($imagefolder, $imagefile, $authentication = false) {
		$imagepath = $this->getFullsizeImagePath($imagefolder, $imagefile);
		$imageurl = $this->getFullsizeImageShortUrl($imagefolder, $imagefile);
		return $this->getImageDownloadUrl($imagepath, $imageurl, $authentication);
	}

	/**
	* The path subject to obfuscation for collision avoidance.
	* @param imagedirectory Path to image file relative to Joomla root.
	* @param imagefile Image file name and extension without path.
	*/
	public function getImageHashBase($imagedirectory, $imagefile, SIGPlusPreviewParameters $params) {
		$extension = pathinfo($imagefile, PATHINFO_EXTENSION);
		if ($extension) {
			$extension = '.'.$extension;
		}
		if ($params->crop) {
			$fitcode = 'x';  // center and crop
		} else {
			$fitcode = 'f';  // fit to dimensions
		}
		switch ($extension) {
			case '.jpg': case '.jpeg': case '.JPG': case '.JPEG':
				$quality = $params->quality; break;
			default:
				$quality = '';
		}
		return $imagedirectory.'/'.$params->height.'x'.$params->width.$fitcode.$quality.'_'.$imagefile;
	}

	/**
	* Returns the obfuscated name for an image file to avoid name conflicts.
	*/
	private function getImageHash($imagedirectory, $imagefile, SIGPlusPreviewParameters $params) {
		$extension = pathinfo($imagefile, PATHINFO_EXTENSION);
		if ($extension) {
			$extension = '.'.$extension;
		}
		return md5($this->getImageHashBase($imagedirectory, $imagefile, $params)).$extension;
	}

	/**
	* The full path to a preview or thumbnail image based on configuration settings.
	* @param generatedfolder The subfolder where the generated images are to be stored.
	*/
	private function getGeneratedImagePath($generatedfolder, $imagedirectory, $imagefile, SIGPlusPreviewParameters $params) {
		if ($this->config->thumbscache) {  // use cache folder
			$directory = JPATH_CACHE.DS.str_replace('/', DS, $generatedfolder);
			$this->createDirectoryOnDemand($directory);
			return $directory.DS.$this->getImageHash($imagedirectory, $imagefile, $params);  // hash original image file paths to avoid name conflicts
		} else {  // a relative path
			$directory = JPATH_ROOT.DS.str_replace('/', DS, $imagedirectory.'/'.$generatedfolder);
			$this->createDirectoryOnDemand($directory);
			return $directory.DS.$imagefile;
		}
	}

	/**
	* The full path to a watermarked image based on configuration settings.
	* The directory should be writable but the file need not exist.
	* @param imagedirectory The file system path to the directory where the image resides.
	* @return The full path to an watermarked image, or false on error.
	*/
	public function getWatermarkedPath($imagedirectory, $imagefile) {
		$params = new SIGPlusPreviewParameters();
		$params->width = 0;  // special values for watermarked image
		$params->height = 0;
		$params->crop = false;
		$params->quality = 0;
		return $this->getGeneratedImagePath($this->config->watermarkfolder, $imagedirectory, $imagefile, $params);
	}

	/**
	* The full path to a preview image based on configuration settings.
	* The directory should be writable but the file need not exist.
	* @param imagedirectory The file system path to the directory where the image resides.
	* @return The full path to an image thumbnail, or false on error.
	*/
	public function getPreviewPath($imagedirectory, $imagefile, SIGPlusPreviewParameters $params) {
		return $this->getGeneratedImagePath($this->config->previewfolder, $imagedirectory, $imagefile, $params);
	}

	/**
	* The full path to an image thumbnail based on configuration settings.
	* The directory should be writable but the file need not exist.
	* @param imagedirectory The file system path to the directory where the image resides.
	* @return The full path to an image thumbnail, or false on error.
	*/
	public function getThumbnailPath($imagedirectory, $imagefile, SIGPlusPreviewParameters $params) {
		if ($params->isThumbnailRequired()) {
			return $this->getGeneratedImagePath($this->config->thumbsfolder, $imagedirectory, $imagefile, $params->getThumbnailParameters());
		} else {
			return $this->getPreviewPath($imagedirectory, $imagefile, $params);
		}
	}

	private function getGeneratedImageUrl($generatedfolder, $imagefolder, $imagefile, SIGPlusPreviewParameters $params) {
		if ($this->config->thumbscache) {  // use cache folder
			$imagedirectory = $this->getImageDirectory($imagefolder);
			return JURI::base(true).'/cache/'.$generatedfolder.'/'.$this->getImageHash($imagedirectory, $imagefile, $params);
		} else {  // a relative path
			return $this->getImageBaseUrl().'/'.$imagefolder.'/'.$generatedfolder.'/'.rawurlencode($imagefile);
		}
	}

	/**
	* The URL to a watermarked image based on configuration settings.
	* @return The URL to the watermarked image.
	*/
	public function getWatermarkedUrl($imagefolder, $imagefile) {
		$params = new SIGPlusPreviewParameters();
		$params->width = 0;
		$params->height = 0;
		$params->crop = false;
		$params->quality = 0;
		return $this->getGeneratedImageUrl($this->config->watermarkfolder, $imagefolder, $imagefile, $params);
	}

	/**
	* The URL to a preview image based on configuration settings.
	* A preview image typically has a higher resolution than a thumbnail image. It is not verified whether the URL points to a valid location.
	* @return The URL to the image thumbnail.
	*/
	public function getPreviewUrl($imagefolder, $imagefile, SIGPlusPreviewParameters $params) {
		return $this->getGeneratedImageUrl($this->config->previewfolder, $imagefolder, $imagefile, $params);
	}

	/**
	* The URL to an image thumbnail based on configuration settings.
	* It is not verified whether the URL points to a valid location.
	* @return The URL to the image thumbnail.
	*/
	public function getThumbnailUrl($imagefolder, $imagefile, SIGPlusPreviewParameters $params) {
		if ($params->isThumbnailRequired()) {
			return $this->getGeneratedImageUrl($this->config->thumbsfolder, $imagefolder, $imagefile, $params->getThumbnailParameters());
		} else {
			return $this->getPreviewUrl($imagefolder, $imagefile, $params);
		}
	}

	/**
	* Returns the language-specific labels filename.
	* @return File system path to the language file to use, or false if no labels file exists.
	*/
	private function getLabelsFilename($imagedirectory, $labelsfilename) {
		if ($this->config->multilingual) {  // check for language-specific labels file
			$lang =& JFactory::getLanguage();
			$labelsfile = JPATH_ROOT.DS.str_replace('/', DS, $imagedirectory).DS.$labelsfilename.'.'.$lang->getTag().'.txt';
			if (is_file($labelsfile)) {
				return $labelsfile;
			}
		}
		// default to language-neutral labels file
		$labelsfile = JPATH_ROOT.DS.str_replace('/', DS, $imagedirectory).DS.$labelsfilename.'.txt';  // filesystem path to labels file
		if (is_file($labelsfile)) {
			return $labelsfile;
		}
		return false;
	}

	public function getLabelsFromFilenames($imagedirectory) {
		$files = @scandir(JPATH_ROOT.DS.str_replace('/', DS, $imagedirectory));
		if ($files === false) {
			return array();
		}
		$files = array_filter($files, 'is_regular_file');  // list files inside the specified path but omit hidden files
		$labels = array();
		foreach ($files as $file) {
			$extension = pathinfo($file, PATHINFO_EXTENSION);
			switch ($extension) {
				case 'jpg': case 'jpeg': case 'JPG': case 'JPEG':
				case 'gif': case 'GIF':
				case 'png': case 'PNG':
					$labels[] = new SIGPlusImageLabel($file, pathinfo_filename($file));
			}
		}
		return $labels;
	}

	/**
	* Short captions and descriptions attached to images with a "labels.txt" file.
	* @return An array of SIGPlusImageLabel instances, or an empty array of no "labels.txt" file is found.
	*/
	public function getLabels($imagedirectory, $labelsfilename, &$defaultcaption, &$defaultdescription) {
		$labelsfile = $this->getLabelsFilename($imagedirectory, $labelsfilename);
		if (!is_file($labelsfile)) {
			return array();
		}
		$labels = array();
		// $lines = file($labelsfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$contents = file_get_contents($labelsfile);
		if (!strcmp("\xEF\xBB\xBF", substr($contents,0,3))) {  // file starts with UTF-8 BOM
			$contents = substr($contents, 3);  // remove UTF-8 BOM
		}
		$contents = str_replace("\r", "\n", $contents);  // normalize line endings
		$matches = array();
		preg_match_all('/^([^|\r\n]+)(?:[|]([^|\r\n]*)(?:[|]([^\r\n]*))?)?$/mu', $contents, $matches, PREG_SET_ORDER);
		if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
			switch (preg_last_error()) {
				case PREG_BAD_UTF8_ERROR:
					throw new SIGPlusEncodingException($labelsfile);
			}
		}
		// foreach ($lines as $line) {
		foreach ($matches as $match) {
			// list($imagefile, $caption, $description) = explode('|', $line, 3);
			$imagefile = $match[1];
			$caption = count($match) > 2 ? html_entity_decode($match[2], ENT_QUOTES, 'UTF-8') : false;
			$description = count($match) > 3 ? html_entity_decode($match[3], ENT_QUOTES, 'UTF-8') : false;

			if ($imagefile == '*') {  // set default label
				$defaultcaption = $caption;
				$defaultdescription = $description;
			} else {
				$imagefile = file_exists_mcext($imagedirectory.DS.$imagefile);
				if ($imagefile === false) {  // check that image file truly exists
					continue;
				}
				$labels[] = new SIGPlusImageLabel($imagefile, $caption, $description);
			}
		}
		return $labels;
	}

	/**
	* Returns a cache key that uniquely identifies a gallery setup.
	*/
	private function getCacheKey($imagedirectory, $params) {
		$imagepath = JPATH_ROOT.DS.str_replace('/', DS, $imagedirectory);
		$files = @scandir($imagepath);
		$filedata = array();
		if ($files !== false) {
			foreach ($files as $file) {
				$stat = stat($imagepath.DS.$file);
				$filedata[$file] = array($stat['size'], $stat['mtime']);
			}
		}
		return md5(
			$this->hash().
			$params->hash().
			md5(serialize($filedata)).
			$imagedirectory
		);
	}

	/**
	* Returns the path to cached content for an image gallery.
	*/
	public function getCachedContentPath($imagedirectory, $params) {
		if ($this->config->thumbscache) {  // use cache folder
			$cachedirectory = JPATH_CACHE.DS.str_replace('/', DS, $this->config->thumbsfolder);
			$cachekey = $this->getCacheKey($imagedirectory, $params);
			return $cachedirectory.DS.$cachekey.'.js';
		}
		return false;
	}

	/**
	* Returns the URL to cached content for an image gallery.
	*/
	public function getCachedContentUrl($cachekey) {
		if ($this->config->thumbscache) {  // use cache folder
			return JURI::base(true).'/cache/'.$this->config->thumbsfolder.'/'.$cachekey.'.js';
		}
		return false;  // not supported
	}

	/**
	* Fetches cached content for the specified directory and parameters.
	* @param imagedirectory The file system path to the directory where the images reside.
	* @param params Parameters that affect how the gallery is to be displayed.
	*/
	public function getCachedContent($imagedirectory, $params) {
		if ($this->config->thumbscache) {  // use cache folder
			$cachedirectory = JPATH_CACHE.DS.str_replace('/', DS, $this->config->thumbsfolder);
			$cachekey = $this->getCacheKey($imagedirectory, $params);
			$cachefilename = $cachedirectory.DS.$cachekey.'.js';
			if (is_file($cachefilename)) {
				return $cachekey;
			}
		}
		return false;
	}

	/**
	* Persists content for the specified directory and parameters.
	*/
	public function saveCachedContent($imagedirectory, $params, $js) {
		if ($this->config->thumbscache) {
			$cachedirectory = JPATH_CACHE.DS.str_replace('/', DS, $this->config->thumbsfolder);
			$this->createDirectoryOnDemand($cachedirectory);
			$cachekey = $this->getCacheKey($imagedirectory, $params);
			$cachefilename = $cachedirectory.DS.$cachekey.'.js';
			if (file_put_contents($cachefilename, $js) !== false) {
				return $cachekey;
			}
		}
		return false;
	}
}
