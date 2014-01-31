<?php

/**
 * @file PaperNoteDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperNoteDAO
 * @ingroup paper
 * @see PaperNote
 *
 * @brief Operations for retrieving and modifying PaperNote objects.
 */

//$Id$

import('paper.PaperNote');

class PaperNoteDAO extends DAO {
	/**
	 * Retrieve Paper Notes by paper id.
	 * @param $paperId int
	 * @return DAOResultFactory containing PaperNotes
	 */
	function &getPaperNotes($paperId, $rangeInfo = NULL) {
		$sql = 'SELECT n.*, a.file_name, a.original_file_name FROM paper_notes n LEFT JOIN paper_files a ON (n.file_id = a.file_id) WHERE a.paper_id = ? OR (n.file_id = 0 AND n.paper_id = ?) ORDER BY n.date_created DESC';

		$result =& $this->retrieveRange($sql, array($paperId, $paperId), $rangeInfo);

		$returner = new DAOResultFactory($result, $this, '_returnPaperNoteFromRow');
		return $returner;
	}

	/**
	 * Retrieve Paper Notes by user id.
	 * @param $userId int
	 * @return DAOResultFactory containing PaperNotes
	 */
	function &getPaperNotesByUserId($userId, $rangeInfo = NULL) {
		$sql = 'SELECT n.*, a.file_name, a.original_file_name FROM paper_notes n LEFT JOIN paper_files a ON (n.file_id = a.file_id) WHERE n.user_id = ? ORDER BY n.date_created DESC';

		$result =& $this->retrieveRange($sql, $userId, $rangeInfo);

		$returner = new DAOResultFactory($result, $this, '_returnPaperNoteFromRow');
		return $returner;
	}

	/**
	 * Retrieve Paper Note by note id
	 * @param $noteId int
	 * @return PaperNote object
	 */
	function getPaperNoteById($noteId) {
		$result =& $this->retrieve(
			'SELECT n.*, a.file_name, a.original_file_name FROM paper_notes n LEFT JOIN paper_files a ON (n.file_id = a.file_id) WHERE n.note_id = ?', $noteId
		);
		$paperNote =& $this->_returnPaperNoteFromRow($result->GetRowAssoc(false));

		$result->Close();
		unset($result);

		return $paperNote;
	}	

	/**
	 * creates and returns a paper note object from a row
	 * @param $row array
	 * @return PaperNote object
	 */
	function &_returnPaperNoteFromRow($row) {
		$paperNote = new PaperNote();
		$paperNote->setNoteId($row['note_id']);
		$paperNote->setPaperId($row['paper_id']);
		$paperNote->setUserId($row['user_id']);
		$paperNote->setDateCreated($this->datetimeFromDB($row['date_created']));
		$paperNote->setDateModified($this->datetimeFromDB($row['date_modified']));
		$paperNote->setTitle($row['title']);
		$paperNote->setNote($row['note']);
		$paperNote->setFileId($row['file_id']);

		$paperNote->setFileName($row['file_name']);
		$paperNote->setOriginalFileName($row['original_file_name']);

		HookRegistry::call('PaperNoteDAO::_returnPaperNoteFromRow', array(&$paperNote, &$row));

		return $paperNote;
	}

	/**
	 * inserts a new paper note into paper_notes table
	 * @param PaperNote object
	 * @return Paper Note Id int
	 */
	function insertPaperNote(&$paperNote) {
		$this->update(
			sprintf('INSERT INTO paper_notes
				(paper_id, user_id, date_created, date_modified, title, note, file_id)
				VALUES
				(?, ?, %s, %s, ?, ?, ?)',
				$this->datetimeToDB($paperNote->getDateCreated()), $this->datetimeToDB($paperNote->getDateModified())),
			array(
				$paperNote->getPaperId(),
				$paperNote->getUserId(),
				$paperNote->getTitle(),
				$paperNote->getNote(),
				$paperNote->getFileId()
			)
		);

		$paperNote->setNoteId($this->getInsertPaperNoteId());
		return $paperNote->getNoteId();
	}

	/**
	 * Get the ID of the last inserted paper note.
	 * @return int
	 */
	function getInsertPaperNoteId() {
		return $this->getInsertId('paper_notes', 'note_id');
	}	

	/**
	 * removes a paper note by id
	 * @param noteId int
	 */
	function deletePaperNoteById($noteId) {
		$this->update(
			'DELETE FROM paper_notes WHERE note_id = ?', $noteId
		);
	}

	/**
	 * updates a paper note
	 * @param PaperNote object
	 */
	function updatePaperNote($paperNote) {
		$this->update(
			sprintf('UPDATE paper_notes
				SET
					user_id = ?,
					date_modified = %s,
					title = ?,
					note = ?,
					file_id = ?
				WHERE note_id = ?',
				$this->datetimeToDB($paperNote->getDateModified())),
			array(
				$paperNote->getUserId(),
				$paperNote->getTitle(),
				$paperNote->getNote(),
				$paperNote->getFileId(),
				$paperNote->getNoteId()
			)
		);
	}

	/**
	 * get all paper note file ids
	 * @param fileIds array
	 */
	function getAllPaperNoteFileIds($paperId) {
		$fileIds = array();

		$result =& $this->retrieve(
			'SELECT a.file_id FROM paper_notes a WHERE paper_id = ? AND file_id > ?', array($paperId, 0)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$fileIds[] = $row['file_id'];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $fileIds;
	}	

	/**
	 * clear all paper notes
	 * @param fileIds array
	 */
	function clearAllPaperNotes($paperId) {
		$result =& $this->retrieve(
			'DELETE FROM paper_notes WHERE paper_id = ?', $paperId
		);

		$result->Close();
	}
}

?>
