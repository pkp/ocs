<?php

/**
 * LayoutAssignmentDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutAssignment
 *
 * DAO class for layout editing assignments.
 *
 * $Id$
 */

import('submission.layoutAssignment.LayoutAssignment');

class LayoutAssignmentDAO extends DAO {

	var $paperFileDao;

	/**
	 * Constructor.
	 */
	function LayoutAssignmentDAO() {
		parent::DAO();
		$this->paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
	}
	
	/**
	 * Retrieve a layout assignment by assignment ID.
	 * @param $layoutId int
	 * @return LayoutAssignment
	 */
	function &getLayoutAssignmentById($layoutId) {
		$result = &$this->retrieve(
			'SELECT l.*, u.first_name, u.last_name, u.email
				FROM layouted_assignments l
				LEFT JOIN users u ON (l.editor_id = u.user_id)
				WHERE layouted_id = ?',
			$layoutId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnLayoutAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve the ID of the layout editor for an paper.
	 * @param $paperId int
	 * @return int
	 */
	function getLayoutEditorIdByPaperId($paperId) {
		$result =& $this->retrieve(
			'SELECT editor_id FROM layouted_assignments WHERE paper_id = ?',
			$paperId
		);
		$returner = null;
		if ($result->RecordCount() != 0) {
			$row = $result->GetRowAssoc(false);
			$returner = $row['editor_id'];
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve the layout editing assignment for an paper.
	 * @param $paperId int
	 * @return LayoutAssignment
	 */
	function &getLayoutAssignmentByPaperId($paperId) {
		$result = &$this->retrieve(
			'SELECT l.*, u.first_name, u.last_name, u.email
				FROM layouted_assignments l
				LEFT JOIN users u ON (l.editor_id = u.user_id)
				WHERE paper_id = ?',
			$paperId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnLayoutAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a layout assignment object from a row.
	 * @param $row array
	 * @return LayoutAssignment
	 */
	function &_returnLayoutAssignmentFromRow(&$row) {
		$layoutAssignment = &new LayoutAssignment();
		$layoutAssignment->setLayoutId($row['layouted_id']);
		$layoutAssignment->setPaperId($row['paper_id']);
		$layoutAssignment->setEditorId($row['editor_id']);
		$layoutAssignment->setEditorFullName($row['first_name'].' '.$row['last_name']);
		$layoutAssignment->setEditorEmail($row['email']);
		$layoutAssignment->setDateNotified($this->datetimeFromDB($row['date_notified']));
		$layoutAssignment->setDateUnderway($this->datetimeFromDB($row['date_underway']));
		$layoutAssignment->setDateCompleted($this->datetimeFromDB($row['date_completed']));
		$layoutAssignment->setDateAcknowledged($this->datetimeFromDB($row['date_acknowledged']));
		$layoutAssignment->setLayoutFileId($row['layout_file_id']);
		
		if ($row['layout_file_id'] && $row['layout_file_id']) {
			$layoutAssignment->setLayoutFile($this->paperFileDao->getPaperFile($row['layout_file_id']));
		}
			
		HookRegistry::call('LayoutAssignmentDAO::_returnLayoutAssignmentFromRow', array(&$layoutAssignment, &$row));

		return $layoutAssignment;
	}
	
	/**
	 * Insert a new layout assignment.
	 * @param $layoutAssignment LayoutAssignment
	 */	
	function insertLayoutAssignment(&$layoutAssignment) {
		$this->update(
			sprintf('INSERT INTO layouted_assignments
				(paper_id, editor_id, date_notified, date_underway, date_completed, date_acknowledged, layout_file_id)
				VALUES
				(?, ?, %s, %s, %s, %s, ?)',
				$this->datetimeToDB($layoutAssignment->getDateNotified()), $this->datetimeToDB($layoutAssignment->getDateUnderway()), $this->datetimeToDB($layoutAssignment->getDateCompleted()), $this->datetimeToDB($layoutAssignment->getDateAcknowledged())),
			array(
				$layoutAssignment->getPaperId(),
				$layoutAssignment->getEditorId(),
				$layoutAssignment->getLayoutFileId()
			)
		);
		
		$layoutAssignment->setLayoutId($this->getInsertLayoutId());
		return $layoutAssignment->getLayoutId();
	}
	
	/**
	 * Update an layout assignment.
	 * @param $layoutAssignment LayoutAssignment
	 */
	function updateLayoutAssignment(&$layoutAssignment) {
		return $this->update(
			sprintf('UPDATE layouted_assignments
				SET	paper_id = ?,
					editor_id = ?,
					date_notified = %s,
					date_underway = %s,
					date_completed = %s,
					date_acknowledged = %s,
					layout_file_id = ?
				WHERE layouted_id = ?',
				$this->datetimeToDB($layoutAssignment->getDateNotified()), $this->datetimeToDB($layoutAssignment->getDateUnderway()), $this->datetimeToDB($layoutAssignment->getDateCompleted()), $this->datetimeToDB($layoutAssignment->getDateAcknowledged())),
			array(
				$layoutAssignment->getPaperId(),
				$layoutAssignment->getEditorId(),
				$layoutAssignment->getLayoutFileId(),
				$layoutAssignment->getLayoutId()
			)
		);
	}
	
	/**
	 * Delete layout assignment.
	 * @param $layoutId int
	 */
	function deleteLayoutAssignmentById($layoutId) {
		return $this->update(
			'DELETE FROM layouted_assignments WHERE layouted_id = ?',
			$layoutId
		);
	}
	
	/**
	 * Delete layout assignments by paper.
	 * @param $paperId int
	 */
	function deleteLayoutAssignmentsByPaper($paperId) {
		return $this->update(
			'DELETE FROM layouted_assignments WHERE paper_id = ?',
			$paperId
		);
	}

	/**
	 * Get the ID of the last inserted layout assignment.
	 * @return int
	 */
	function getInsertLayoutId() {
		return $this->getInsertId('layouted_assignments', 'layouted_id');
	}
}

?>
