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

import('lib.pkp.classes.file.PKPPublicFileManager');

class PublicFileManager extends PKPPublicFileManager {
	/**
	 * Constructor
	 */
	function PublicFileManager() {
		parent::PKPPublicFileManager();
	}

	/**
	 * Get the files path associated with the specified context information.
	 * @param $assocType int Context assoc type
	 * @param $contextId int Context ID
	 */
	function getContextFilesPath($assocType, $contextId) {
		switch ($assocType) {
			case ASSOC_TYPE_CONFERENCE:
				return Config::getVar('files', 'public_files_dir') . '/conferences/' . $conferenceId;
			case ASSOC_TYPE_SCHED_CONF:
				$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
				$schedConf = $schedConfDao->getById($contextId);
				return Config::getVar('files', 'public_files_dir') . '/conferences/' . $schedConf->getConferenceId() . '/schedConfs/' . $schedConfId;
			default:
				assert(false);
				break;
		}
	}

	/**
	 * Get the path to a conference's public files directory.
	 * @param $conferenceId int
	 * @return string
	 */
	function getConferenceFilesPath($conferenceId) {
		return $this->getContextFilesPath(ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Get the path to a scheduled conference's public files directory.
	 * @param $schedConfId int
	 * @return string
	 */
	function getSchedConfFilesPath($schedConfId) {
		return $this->getContextFilesPath(ASSOC_TYPE_SCHED_CONF, $schedConfId);
	}

	/**
	 * Upload a file to a conferences's public directory.
	 * @param $conferenceId int
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function uploadConferenceFile($conferenceId, $fileName, $destFileName) {
 		return $this->uploadContextFile(ASSOC_TYPE_CONFERENCE, $fileName, $this->getConferenceFilesPath($conferenceId) . '/' . $destFileName);
 	}

	/**
	 * Write a file to a conferences's public directory.
	 * @param $conferenceId int
	 * @param $destFileName string the destination file name
	 * @param $contents string the contents to write to the file
	 * @return boolean
	 */
 	function writeConferenceFile($conferenceId, $destFileName, $contents) {
 		return $this->writeContextFile(ASSOC_TYPE_CONFERENCE, $conferenceId, $destFileName, $contents);
 	}

	/**
	 * Copy a file to a conferences's public directory.
	 * @param $conferenceId int
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function copyConferenceFile($conferenceId, $sourceFile, $destFileName) {
 		return $this->copyContextFile(ASSOC_TYPE_CONFERENCE, $conferenceId, $sourceFile, $destFileName);
 	}

 	/**
	 * Delete a file from a conference's public directory.
 	 * @param $conferenceId int
 	 * @param $fileName string the target file name
	 * @return boolean
 	 */
 	function removeConferenceFile($conferenceId, $fileName) {
 		return $this->removeContextFile(ASSOC_TYPE_CONFERENCE, $conferenceId, $fileName);
 	}

	/**
	 * Upload a file to a scheduled conference's public directory.
	 * @param $schedConfId int
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function uploadSchedConfFile($schedConfId, $fileName, $destFileName) {
 		return $this->uploadContextFile(ASSOC_TYPE_SCHED_CONF, $schedConfId, $fileName, $destFileName);
 	}

	/**
	 * Write a file to a scheduled conference's public directory.
	 * @param $schedConfId int
	 * @param $destFileName string the destination file name
	 * @param $contents string the contents to write to the file
	 * @return boolean
	 */
 	function writeSchedConfFile($schedConfId, $destFileName, $contents) {
 		return $this->writeContextFile(ASSOC_TYPE_SCHED_CONF, $schedConfId, $destFileName, $contents);
 	}

	/**
	 * Copy a file to a scheduled conference's public directory.
	 * @param $schedConfId int
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function copySchedConfFile($schedConfId, $sourceFile, $destFileName) {
 		return $this->copyContextFile(ASSOC_TYPE_SCHED_CONF, $schedConfId, $sourceFile, $destFileName);
 	}

 	/**
	 * Delete a file from a scheduled conference's public directory.
 	 * @param $schedConfId int
 	 * @param $fileName string the target file name
	 * @return boolean
 	 */
 	function removeSchedConfFile($schedConfId, $fileName) {
 		return $this->deleteContextFile(ASSOC_TYPE_SCHED_CONF, $schedConfId, $fileName);
 	}
}

?>
