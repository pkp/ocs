<?php

/**
 * @file PaperNote.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperNote
 * @ingroup paper
 * @see PaperNoteDAO
 *
 * @brief Class for PaperNote.
 */

//$Id$

import('paper.PaperFile');

class PaperNote extends PaperFile {

	/**
	 * Constructor.
	 */
	function PaperNote() {
		parent::DataObject();
	}

	/**
	 * get paper note id
	 * @return int
	 */
	function getNoteId() {
		return $this->getData('noteId');
	}

	/**
	 * set paper note id
	 * @param $noteId int
	 */
	function setNoteId($noteId) {
		return $this->setData('noteId',$noteId);
	}

	/**
	 * get paper id
	 * @return int
	 */
	function getPaperId() {
		return $this->getData('paperId');
	}

	/**
	 * set paper id
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		return $this->setData('paperId',$paperId);
	}

	/**
	 * get user id
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * set user id
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId',$userId);
	}

 	/**
	 * get date created
	 * @return date
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * set date created
	 * @param $dateCreated date
	 */
	function setDateCreated($dateCreated) {
		return $this->setData('dateCreated',$dateCreated);
	}

 	/**
	 * get date modified
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * set date modified
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		return $this->setData('dateModified',$dateModified);
	}

	/**
	 * get title
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}

	/**
	 * set title
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title',$title);
	}

	/**
	 * get note
	 * @return string
	 */
	function getNote() {
		return $this->getData('note');
	}

	/**
	 * set note
	 * @param $note string
	 */
	function setNote($note) {
		return $this->setData('note',$note);
	}

	/**
	 * get file id
	 * @return int
	 */
	function getFileId() {
		return $this->getData('fileId');
	}

	/**
	 * set file id
	 * @param $fileId int
	 */
	function setFileId($fileId) {
		return $this->setData('fileId',$fileId);
	}

 }

?>
