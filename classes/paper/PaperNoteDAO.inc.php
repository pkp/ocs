<?php

/**
 * @file classes/paper/PaperNoteDAO.inc.php
 *
 * Copyright (c) 2005-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperNoteDAO
 * @ingroup paper
 * @see PaperNote
 *
 * @brief Operations for retrieving and modifying PaperNote objects.
 */

// $Id$


import('classes.paper.PaperNote');
import('classes.note.NoteDAO');

class PaperNoteDAO extends NoteDAO {
	function PaperNoteDAO() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated class PaperNoteDAO; use NoteDAO instead.');
		parent::NoteDAO();
	}

	/**
	 * Retrieve Paper Notes by paper id.
	 * @param $paperId int
	 * @return DAOResultFactory containing PaperNotes
	 */
	function &getPaperNotes($paperId, $rangeInfo = NULL) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		$returner =& $this->getByAssoc(ASSOC_TYPE_PAPER, $paperId);
		return $returner;
	}

	/**
	 * Retrieve Paper Notes by user id.
	 * @param $userId int
	 * @return DAOResultFactory containing PaperNotes
	 */
	function &getPaperNotesByUserId($userId, $rangeInfo = NULL) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		$returner =& $this->getByUserId($userId, $rangeInfo);
		return $returner;
	}

	/**
	 * Retrieve Paper Note by note id
	 * @param $noteId int
	 * @return PaperNote object
	 */
	function getPaperNoteById($noteId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		$returner =& $this->getById($noteId);
		return $returner;
	}

	/**
	 * inserts a new paper note into notes table
	 * @param PaperNote object
	 * @return Paper Note Id int
	 */
	function insertPaperNote(&$paperNote) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		$paperNote->setAssocType(ASSOC_TYPE_PAPER);
		$journal =& Request::getJournal();
		$paperNote->setContextId($journal->getId());
		return $this->insertObject($paperNote);
	}

	/**
	 * Get the ID of the last inserted paper note.
	 * @return int
	 */
	function getInsertPaperNoteId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->getInsertNoteId();
	}

	/**
	 * removes an paper note by id
	 * @param noteId int
	 */
	function deletePaperNoteById($noteId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->deleteById($noteId);
	}

	/**
	 * updates an paper note
	 * @param PaperNote object
	 */
	function updatePaperNote($paperNote) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->updateObject($paperNote);
	}

	/**
	 * get all paper note file ids
	 * @param fileIds array
	 */
	function getAllPaperNoteFileIds($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->getAllFileIds(ASSOC_TYPE_PAPER, $paperId);
	}

	/**
	 * clear all paper notes
	 * @param fileIds array
	 */
	function clearAllPaperNotes($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function');
		return $this->deleteByAssoc(ASSOC_TYPE_PAPER, $paperId);
	}
}

?>
