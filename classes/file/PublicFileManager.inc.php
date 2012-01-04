<?php

/**
 * @file PublicFileManager.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicFileManager
 * @ingroup file
 *
 * @brief Wrapper class for uploading files to a site/conference's public directory.
 */

//$Id$

import('file.PKPPublicFileManager');

class PublicFileManager extends PKPPublicFileManager {
	/**
	 * Get the path to a conference's public files directory.
	 * @param $conferenceId int
	 * @return string
	 */
	function getConferenceFilesPath($conferenceId) {
		return Config::getVar('files', 'public_files_dir') . '/conferences/' . $conferenceId;
	}

	/**
	 * Get the path to a scheduled conference's public files directory.
	 * @param $schedConfId int
	 * @return string
	 */
	function getSchedConfFilesPath($schedConfId) {
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($schedConfId);
		return Config::getVar('files', 'public_files_dir') . '/conferences/' . $schedConf->getConferenceId() . '/schedConfs/' . $schedConfId;
	}

	/**
	 * Upload a file to a conferences's public directory.
	 * @param $conferenceId int
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function uploadConferenceFile($conferenceId, $fileName, $destFileName) {
 		return $this->uploadFile($fileName, $this->getConferenceFilesPath($conferenceId) . '/' . $destFileName);
 	}

	/**
	 * Write a file to a conferences's public directory.
	 * @param $conferenceId int
	 * @param $destFileName string the destination file name
	 * @param $contents string the contents to write to the file
	 * @return boolean
	 */
 	function writeConferenceFile($conferenceId, $destFileName, &$contents) {
 		return $this->writeFile($this->getConferenceFilesPath($conferenceId) . '/' . $destFileName, $contents);
 	}

	/**
	 * Copy a file to a conferences's public directory.
	 * @param $conferenceId int
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function copyConferenceFile($conferenceId, $sourceFile, $destFileName) {
 		return $this->copyFile($sourceFile, $this->getConferenceFilesPath($conferenceId) . '/' . $destFileName);
 	}

 	/**
	 * Delete a file from a conference's public directory.
 	 * @param $conferenceId int
 	 * @param $fileName string the target file name
	 * @return boolean
 	 */
 	function removeConferenceFile($conferenceId, $fileName) {
 		return $this->deleteFile($this->getConferenceFilesPath($conferenceId) . '/' . $fileName);
 	}

	/**
	 * Upload a file to a scheduled conference's public directory.
	 * @param $schedConfId int
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function uploadSchedConfFile($schedConfId, $fileName, $destFileName) {
 		return $this->uploadFile($fileName, $this->getSchedConfFilesPath($schedConfId) . '/' . $destFileName);
 	}

	/**
	 * Write a file to a scheduled conference's public directory.
	 * @param $schedConfId int
	 * @param $destFileName string the destination file name
	 * @param $contents string the contents to write to the file
	 * @return boolean
	 */
 	function writeSchedConfFile($schedConfId, $destFileName, &$contents) {
 		return $this->writeFile($this->getSchedConfFilesPath($schedConfId) . '/' . $destFileName, $contents);
 	}

	/**
	 * Copy a file to a scheduled conference's public directory.
	 * @param $schedConfId int
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function copySchedConfFile($schedConfId, $sourceFile, $destFileName) {
 		return $this->copyFile($sourceFile, $this->getSchedConfFilesPath($schedConfId) . '/' . $destFileName);
 	}

 	/**
	 * Delete a file from a scheduled conference's public directory.
 	 * @param $schedConfId int
 	 * @param $fileName string the target file name
	 * @return boolean
 	 */
 	function removeSchedConfFile($schedConfId, $fileName) {
 		return $this->deleteFile($this->getSchedConfFilesPath($schedConfId) . '/' . $fileName);
 	}
}

?>
