<?php

/**
 * @file ConferenceFileManager.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceFileManager
 * @ingroup file
 *
 * @brief Class defining operations for private conference file management.
 */

//$Id$

import('file.FileManager');

class ConferenceFileManager extends FileManager {

	/** @var string the path to location of the files */
	var $filesDir;

	/** @var int the ID of the associated conference */
	var $conferenceId;

	/** @var Conference the associated conference */
	var $conference;

	/**
	 * Constructor.
	 * Create a manager for handling conference file uploads.
	 * @param $conferenceId int
	 */
	function ConferenceFileManager(&$conference) {
		$this->conferenceId = $conference->getId();
		$this->conference =& $conference;
		$this->filesDir = Config::getVar('files', 'files_dir') . '/conferences/' . $this->conferenceId . '/';
	}

	function uploadFile($fileName, $destFileName) {
		return parent::uploadFile($fileName, $this->filesDir . $destFileName);
	}

	function downloadFile($filePath, $fileType, $inline = false) {
		return parent::downloadFile($this->filesDir . $filePath, $fileType, $inline);
	}

	function deleteFile($fileName) {
		return parent::deleteFile($this->filesDir . $fileName);
	}
}

?>
