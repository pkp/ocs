<?php

/**
 * @file PaperHTMLGalley.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperHTMLGalley
 * @ingroup paper
 *
 * @brief An HTML galley may include an optional stylesheet and set of images.
 */

//$Id$

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
		$fileManager = new PaperFileManager($this->getPaperId());
		$contents = $fileManager->readFile($this->getFileId());

		// Replace image references
		$images =& $this->getImageFiles();

		foreach ($images as $image) {
			$imageUrl = Request::url(null, 'paper', 'viewFile', array($this->getPaperId(), $this->getGalleyId(), $image->getFileId()));
			$pattern = preg_quote(rawurlencode($image->getOriginalFileName()));
			$contents = preg_replace(
				'/[Ss][Rr][Cc]\s*=\s*"([^"]*' . $pattern . ')"/', 
				'src="' . $imageUrl . '"',
				$contents
			);
			$contents = preg_replace(
				'/[Hh][Rr][Ee][Ff]\s*=\s*"([^"]*' . $pattern . ')"/', 
				'href="' . $imageUrl . '"',
				$contents
			);
		}

		// Perform replacement for ocs://... URLs
		$contents = preg_replace_callback(
			'/(<[^<>]*")[Oo][Jj][Ss]:\/\/([^"]+)("[^<>]*>)/',
			array(&$this, '_handleOcsUrl'),
			$contents
		);

		// Perform variable replacement for site info etc.
		$schedConf =& Request::getSchedConf();
		$site =& Request::getSite();

		$paramArray = array(
			'confTitle' => $schedConf->getSchedConfTitle(),
			'siteTitle' => $site->getLocalizedTitle(),
			'currentUrl' => Request::getRequestUrl()
		);

		foreach ($paramArray as $key => $value) {
			$contents = str_replace('{$' . $key . '}', $value, $contents);
		}
		return $contents;
	}

	function _handleOcsUrl($matchArray) {
		$url = $matchArray[2];
		$anchor = null;
		if (($i = strpos($url, '#')) !== false) {
			$anchor = substr($url, $i+1);
			$url = substr($url, 0, $i);
		}
		$urlParts = explode('/', $url);
		if (isset($urlParts[0])) switch(String::strtolower($urlParts[0])) {
			case 'conference':
				$url = Request::url(
					isset($urlParts[1]) ?
						$urlParts[1] :
						Request::getRequestedConferencePath(),
					null,
					null,
					null,
					null,
					null,
					$anchor
				);
				break;
			case 'paper':
				if (isset($urlParts[1])) {
					$url = Request::url(
						null,
						null,
						'paper',
						'view',
						$urlParts[1],
						null,
						$anchor
					);
				}
				break;
			case 'schedConf':
				if (isset($urlParts[1])) {
					$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
					$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
					$thisSchedConf =& $schedConfDao->getSchedConfByPath($urlParts[1]);
					if (!$thisSchedConf) break;
					$thisConference =& $conferenceDao->getConference($thisSchedConf->getConferenceId());
					$url = Request::url(
						$thisConference->getPath(),
						$thisSchedConf->getPath(),
						null,
						null,
						null,
						null,
						$anchor
					);
				} else {
					$url = Request::url(
						null,
						null,
						'schedConfs',
						'current',
						null,
						null,
						$anchor
					);
				}
				break;
			case 'suppfile':
				if (isset($urlParts[1]) && isset($urlParts[2])) {
					$url = Request::url(
						null,
						null,
						'paper',
						'downloadSuppFile',
						array($urlParts[1], $urlParts[2]),
						null,
						$anchor
					);
				}
				break;
			case 'sitepublic':
					array_shift($urlParts);
					import ('file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$url = Request::getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
				break;
			case 'public':
					array_shift($urlParts);
					$schedConf =& Request::getSchedConf();
					import ('file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$url = Request::getBaseUrl() . '/' . $publicFileManager->getSchedConfFilesPath($schedConf->getId()) . '/' . implode('/', $urlParts) . ($anchor?'#' . $anchor:'');
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
		$styleFile =& $this->getData('styleFile');
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
		$images =& $this->getData('images');
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
