<?php

/**
 * PaperHTMLGalley.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package paper
 *
 * PaperHTMLGalley class.
 * An HTML galley may include an optional stylesheet and set of images.
 *
 * $Id$
 */

import('paper.PaperGalley');

class PaperHTMLGalley extends PaperGalley {

	/**
	 * Constructor.
	 */
	function PaperHTMLGalley() {
		parent::PaperGalley();
	}
	
	/**
	 * Check if galley is an HTML galley.
	 * @return boolean
	 */
	function isHTMLGalley() {
		return true;
	}
	
	/**
	 * Return string containing the contents of the HTML file.
	 * This function performs any necessary filtering, like image URL replacement.
	 * @param $baseImageUrl string base URL for image references
	 * @return string
	 */
	function getHTMLContents() {
		import('file.PaperFileManager');
		$fileManager = &new PaperFileManager($this->getPaperId());
		$contents = $fileManager->readFile($this->getFileId());
		
		// Replace image references
		$images = &$this->getImageFiles();

		foreach ($images as $image) {
			$imageUrl = Request::url(null, 'paper', 'viewFile', array($this->getPaperId(), $this->getGalleyId(), $image->getFileId()));
			$contents = preg_replace(
				'/[Ss][Rr][Cc]\s*=\s*"([^"]*' . preg_quote($image->getOriginalFileName()) .    ')"/', 
				'src="' . $imageUrl . '"',
				$contents,
				1
			);
		}

		// Perform replacement for ocs://... URLs
		$contents = String::regexp_replace_callback(
			'/(<[^<>]*")[Oo][Jj][Ss]:\/\/([^"]+)("[^<>]*>)/',
			array(&$this, '_handleOcsUrl'),
			$contents
		);

		return $contents;
	}

	function _handleOcsUrl($matchArray) {
		$url = $matchArray[2];
		$urlParts = explode('/', $url);
		if (isset($urlParts[0])) switch(String::strtolower($urlParts[0])) {
			case 'conference':
				$url = Request::url(
					isset($urlParts[1]) ?
						$urlParts[1] :
						Request::getRequestedConferencePath()
				);
				break;
			case 'paper':
				if (isset($urlParts[1])) {
					$url = Request::url(null, 'paper', 'view', $urlParts[1]);
				}
				break;
			case 'schedConf':
				if (isset($urlParts[1])) {
					$url = Request::url(null, null, 'view', $urlParts[1]);
				} else {
					$url = Request::url(null, null, 'current');
				}
				break;
			case 'suppfile':
				if (isset($urlParts[1]) && isset($urlParts[2])) {
					$url = Request::url(null, 'paper', 'downloadSuppFile', array($urlParts[1], $urlParts[2]));
				}
				break;
			case 'sitepublic':
					array_shift($urlParts);
					import ('file.PublicFileManager');
					$publicFileManager = &new PublicFileManager();
					$url = Request::getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . implode('/', $urlParts);
				break;
			case 'public':
					array_shift($urlParts);
					$schedConf = &Request::getSchedConf();
					import ('file.PublicFileManager');
					$publicFileManager = &new PublicFileManager();
					$url = Request::getBaseUrl() . '/' . $publicFileManager->getSchedConfFilesPath($schedConf->getSchedConfId()) . '/' . implode('/', $urlParts);
				break;
		}
		return $matchArray[1] . $url . $matchArray[3];
	}

	/**
	 * Check if the specified file is a dependent file.
	 * @param $fileId int
	 * @return boolean
	 */
	function isDependentFile($fileId) {
		if ($this->getStyleFileId() == $fileId) return true;
		foreach ($this->getImageFiles() as $image) {
			if ($image->getFileId() == $fileId) return true;
		}
		return false;
	}

	//
	// Get/set methods
	//
	
	/**
	 * Get ID of associated stylesheet file, if applicable.
	 * @return int
	 */
	function getStyleFileId() {
		return $this->getData('styleFileId');
	}
	
	/**
	 * Set ID of associated stylesheet file.
	 * @param $styleFileId int
	 */
	function setStyleFileId($styleFileId) {
		return $this->setData('styleFileId', $styleFileId);
	}
	
	/**
	 * Return the stylesheet file associated with this HTML galley, if applicable.
	 * @return PaperFile
	 */
	function &getStyleFile() {
		$styleFile = &$this->getData('styleFile');
		return $styleFile;
	}
		
	/**
	 * Set the stylesheet file for this HTML galley.
	 * @param PaperFile $styleFile
	 */
	function setStyleFile(&$styleFile) {
		$this->setData('styleFile', $styleFile);
	}
		
	/**
	 * Return array of image files for this HTML galley.
	 * @return array
	 */
	function &getImageFiles() {
		$images = &$this->getData('images');
		return $images;
	}
		
	/**
	 * Set array of image files for this HTML galley.
	 * @param $images array
	 * @return array
	 */
	function setImageFiles(&$images) {
		return $this->setData('images', $images);
	}
	
}

?>
