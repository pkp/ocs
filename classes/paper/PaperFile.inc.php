<?php

/**
 * @file PaperFile.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperFile
 * @ingroup paper
 * @see PaperFileDAO
 *
 * @brief Paper file class.
 */

//$Id$

class PaperFile extends DataObject {

	/**
	 * Constructor.
	 */
	function PaperFile() {
		parent::DataObject();
	}

	/**
	 * Return absolute path to the file on the host filesystem.
	 * @return string
	 */
	function getFilePath() {
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$paper =& $paperDao->getPaper($this->getPaperId());
		$paperId = $paper->getSchedConfId();
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($paperId);

		return Config::getVar('files', 'files_dir') . '/conferences/' . $schedConf->getConferenceId() . '/schedConfs/' . $paperId .
		'/papers/' . $this->getPaperId() . '/' . $this->getType() . '/' . $this->getFileName();
	}

	//
	// Get/set methods
	//
	/**
	 * Get ID of file.
	 * @return int
	 */
	function getFileId() {
		return $this->getData('fileId');
	}

	/**
	 * Set ID of file.
	 * @param $fileId int
	 */
	function setFileId($fileId) {
		return $this->setData('fileId', $fileId);
	}

	/**
	 * Get revision number.
	 * @return int
	 */
	function getRevision() {
		return $this->getData('revision');
	}

	/**
	 * Set revision number.
	 * @param $revision int
	 */
	function setRevision($revision) {
		return $this->setData('revision', $revision);
	}

	/**
	 * Get ID of paper.
	 * @return int
	 */
	function getPaperId() {
		return $this->getData('paperId');
	}

	/**
	 * Set ID of paper.
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		return $this->setData('paperId', $paperId);
	}

	/**
	 * Get file name of the file.
	 * @param return string
	 */
	function getFileName() {
		return $this->getData('fileName');
	}

	/**
	 * Set file name of the file.
	 * @param $fileName string
	 */
	function setFileName($fileName) {
		return $this->setData('fileName', $fileName);
	}

	/**
	 * Get file type of the file.
	 * @ return string
	 */
	function getFileType() {
		return $this->getData('fileType');
	}

	/**
	 * Set file type of the file.
	 * @param $fileType string
	 */
	function setFileType($fileType) {
		return $this->setData('fileType', $fileType);
	}

	/**
	 * Get original uploaded file name of the file.
	 * @param return string
	 */
	function getOriginalFileName() {
		return $this->getData('originalFileName');
	}

	/**
	 * Set original uploaded file name of the file.
	 * @param $originalFileName string
	 */
	function setOriginalFileName($originalFileName) {
		return $this->setData('originalFileName', $originalFileName);
	}

	/**
	 * Get type of the file.
	 * @ return string
	 */
	function getType() {
		return $this->getData('type');
	}

	/**
	 * Set type of the file.
	 * @param $type string
	 */
	function setType($type) {
		return $this->setData('type', $type);
	}

	/**
	 * Get review stage of the file.
	 * @ return int
	 */
	function getStage() {
		return $this->getData('stage');
	}

	/**
	 * Set stage of the file.
	 * @param $stage string
	 */
	function setStage($stage) {
		return $this->setData('stage', $stage);
	}

	/**
	 * Get uploaded date of file.
	 * @return date
	 */

	function getDateUploaded() {
		return $this->getData('dateUploaded');
	}


	/**
	 * Set uploaded date of file.
	 * @param $dateUploaded date
	 */

	function setDateUploaded($dateUploaded) {
		return $this->SetData('dateUploaded', $dateUploaded);
	}

	/**
	 * Get modified date of file.
	 * @return date
	 */

	function getDateModified() {
		return $this->getData('dateModified');
	}


	/**
	 * Set modified date of file.
	 * @param $dateModified date
	 */

	function setDateModified($dateModified) {
		return $this->SetData('dateModified', $dateModified);
	}

	/**
	 * Get file size of file.
	 * @return int
	 */

	function getFileSize() {
		return $this->getData('fileSize');
	}


	/**
	 * Set file size of file.
	 * @param $fileSize int
	 */

	function setFileSize($fileSize) {
		return $this->SetData('fileSize', $fileSize);
	}

	/**
	 * Get nice file size of file.
	 * @return string
	 */

	function getNiceFileSize() {
		return FileManager::getNiceFileSize($this->getData('fileSize'));
	}

	/**
	 * Get viewable.
	 * @return boolean
	 */

	function getViewable() {
		return $this->getData('viewable');
	}


	/**
	 * Set viewable.
	 * @param $viewable boolean
	 */

	function setViewable($viewable) {
		return $this->SetData('viewable', $viewable);
	}

	/**
	 * Check if the file may be displayed inline.
	 * @return boolean
	 */
	function isInlineable() {
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		return $paperFileDao->isInlineable($this);
	}
}

?>
