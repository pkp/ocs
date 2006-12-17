<?php

/**
 * PublicFileManager.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * PublicFileManager class.
 * Wrapper class for uploading files to a site/conference's public directory.
 *
 * $Id$
 */

import('file.FileManager');

class PublicFileManager extends FileManager {

	/**
	 * Get the path to the site public files directory.
	 * @return string
	 */
	function getSiteFilesPath() {
		return Config::getVar('files', 'public_files_dir') . '/site';
	}

	/**
	 * Upload a file to the site's public directory.
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function uploadSiteFile($fileName, $destFileName) {
 		return $this->uploadFile($fileName, $this->getSiteFilesPath() . '/' . $destFileName);
 	}
 	
 	/**
	 * Delete a file from the site's public directory.
 	 * @param $fileName string the target file name
	 * @return boolean
 	 */
 	function removeSiteFile($fileName) {
 		return $this->deleteFile($this->getSiteFilesPath() . '/' . $fileName);
 	}
 	
	/**
	 * Get the path to a conference's public files directory.
	 * @param $conferenceId int
	 * @return string
	 */
	function getConferenceFilesPath($conferenceId) {
		return Config::getVar('files', 'public_files_dir') . '/conferences/' . $conferenceId;
	}
	
	/**
	 * Get the path to an event's public files directory.
	 * @param $eventId int
	 * @return string
	 */
	function getEventFilesPath($eventId) {
		$eventDao =& DAORegistry::getDAO('EventDAO');
		$event =& $eventDao->getEvent($eventId);
		return Config::getVar('files', 'public_files_dir') . '/conferences/' . $event->getConferenceId() . '/events/' . $eventId;
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
	 * Upload a file to a events's public directory.
	 * @param $eventId int
	 * @param $fileName string the name of the file in the upload form
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function uploadEventFile($eventId, $fileName, $destFileName) {
 		return $this->uploadFile($fileName, $this->getEventFilesPath($eventId) . '/' . $destFileName);
 	}
 	
	/**
	 * Write a file to a events's public directory.
	 * @param $eventId int
	 * @param $destFileName string the destination file name
	 * @param $contents string the contents to write to the file
	 * @return boolean
	 */
 	function writeEventFile($eventId, $destFileName, &$contents) {
 		return $this->writeFile($this->getEventFilesPath($eventId) . '/' . $destFileName, $contents);
 	}
 	
	/**
	 * Copy a file to a events's public directory.
	 * @param $eventId int
	 * @param $sourceFile string the source of the file to copy
	 * @param $destFileName string the destination file name
	 * @return boolean
	 */
 	function copyEventFile($eventId, $sourceFile, $destFileName) {
 		return $this->copyFile($sourceFile, $this->getEventFilesPath($eventId) . '/' . $destFileName);
 	}

 	/**
	 * Delete a file from a event's public directory.
 	 * @param $eventId int
 	 * @param $fileName string the target file name
	 * @return boolean
 	 */
 	function removeEventFile($eventId, $fileName) {
 		return $this->deleteFile($this->getEventFilesPath($eventId) . '/' . $fileName);
 	}
 	
}

?>
