<?php

/**
 * @file classes/note/Note.inc.php
 *
 * Copyright (c) 2005-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Note
 * @ingroup note
 * @see NoteDAO
 *
 * @brief Class for OCS Note.
 */



import('classes.paper.PaperFile');
import('lib.pkp.classes.note.PKPNote');

class Note extends PKPNote {
	/**
	 * Constructor.
	 */
	function Note() {
		parent::PKPNote();
	}

	/**
	 * get paper note id
	 * @return int
	 */
	function getNoteId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * set paper note id
	 * @param $noteId int
	 */
	function setNoteId($noteId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($noteId);
	}

	/**
	 * get paper id
	 * @return int
	 */
	function getPaperId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getAssocId();
	}

	/**
	 * set paper id
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setAssocId($paperId);
	}

	/**
	 * get note
	 * @return string
	 */
	function getNote() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getContents();
	}

	/**
	 * set note
	 * @param $note string
	 */
	function setNote($note) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setContents($note);
	}

	/**
	 * get file
	 * @return string
	 */
	function getFile() {
		return $this->getData('file');
	}

	/**
	 * set note
	 * @param $note string
	 */
	function setFile($file) {
		return $this->setData('file', $file);
	}

	function getOriginalFileName() {
		$file = $this->getFile();
		return $file->getOriginalFileName();
	}
}

?>
