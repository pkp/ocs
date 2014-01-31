<?php

/**
 * @file EditAssignmentDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditAssignmentDAO
 * @ingroup submission
 * @see EditAssignment
 *
 * @brief Class for DAO relating directors to papers.
 */

//$Id$

import('submission.editAssignment.EditAssignment');

class EditAssignmentDAO extends DAO {
	/**
	 * Retrieve an edit assignment by id.
	 * @param $editId int
	 * @return EditAssignment
	 */
	function &getEditAssignment($editId) {
		$result =& $this->retrieve(
			'SELECT e.*,
				u.first_name,
				u.last_name,
				u.email,
				u.initials,
				r.role_id AS director_role_id
			FROM papers p
				LEFT JOIN edit_assignments e ON (e.paper_id = p.paper_id)
				LEFT JOIN users u ON (e.director_id = u.user_id)
				LEFT JOIN roles r ON (r.user_id = e.director_id AND r.role_id = ' . ROLE_ID_DIRECTOR . ' AND r.sched_conf_id = p.sched_conf_id)
			WHERE e.edit_id = ? AND p.paper_id = e.paper_id',
			$editId
			);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnEditAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve edit assignments by paper id.
	 * @param $paperId int
	 * @return EditAssignment
	 */
	function &getEditAssignmentsByPaperId($paperId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS director_role_id
			FROM papers p
				LEFT JOIN edit_assignments e ON (p.paper_id = e.paper_id)
				LEFT JOIN users u ON (e.director_id = u.user_id)
				LEFT JOIN roles r ON (r.user_id = e.director_id AND r.role_id = ' . ROLE_ID_DIRECTOR . ' AND r.sched_conf_id = p.sched_conf_id)
			WHERE e.paper_id = ? AND p.paper_id = e.paper_id ORDER BY e.date_notified ASC',
			$paperId
			);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Retrieve those edit assignments that relate to full directors.
	 * @param $paperId int
	 * @return EditAssignment
	 */
	function &getDirectorAssignmentsByPaperId($paperId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS director_role_id FROM papers a, edit_assignments e, users u, roles r WHERE r.user_id = e.director_id AND r.role_id = ' . ROLE_ID_DIRECTOR . ' AND e.paper_id = ? AND r.sched_conf_id = a.sched_conf_id AND a.paper_id = e.paper_id AND e.director_id = u.user_id ORDER BY e.date_notified ASC',
			$paperId
			);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Retrieve those edit assignments that relate to track directors with
	 * review access.
	 * @param $paperId int
	 * @return EditAssignment
	 */
	function &getTrackDirectorAssignmentsByPaperId($paperId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS director_role_id
			FROM papers p
				LEFT JOIN edit_assignments e ON (p.paper_id = e.paper_id)
				LEFT JOIN users u ON (e.director_id = u.user_id)
				LEFT JOIN roles r ON (r.user_id = e.director_id AND r.role_id = ' . ROLE_ID_DIRECTOR . ' AND r.sched_conf_id = p.sched_conf_id)
			WHERE e.paper_id = ? AND p.paper_id = e.paper_id AND r.role_id IS NULL ORDER BY e.date_notified ASC',
			$paperId
			);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Retrieve edit assignments by user id.
	 * @param $paperId int
	 * @return EditAssignment
	 */
	function &getEditAssignmentsByUserId($userId) {
		$result =& $this->retrieve(
			'SELECT e.*, u.first_name, u.last_name, u.email, u.initials, r.role_id AS director_role_id
			FROM papers p
				LEFT JOIN edit_assignments e ON (p.paper_id = e.paper_id)
				LEFT JOIN users u ON (e.director_id = u.user_id)
				LEFT JOIN roles r ON (r.user_id = e.director_id AND r.role_id = ' . ROLE_ID_DIRECTOR . ' AND r.sched_conf_id = p.sched_conf_id)
				WHERE e.director_id = ? AND p.paper_id = e.paper_id ORDER BY e.date_notified ASC',
			$userId
			);

		$returner = new DAOResultFactory($result, $this, '_returnEditAssignmentFromRow');
		return $returner;
	}

	/**
	 * Internal function to return an edit assignment object from a row.
	 * @param $row array
	 * @return EditAssignment
	 */
	function &_returnEditAssignmentFromRow(&$row) {
		$editAssignment = new EditAssignment();
		$editAssignment->setEditId($row['edit_id']);
		$editAssignment->setPaperId($row['paper_id']);
		$editAssignment->setDirectorId($row['director_id']);
		$editAssignment->setDirectorFullName($row['first_name'].' '.$row['last_name']);
		$editAssignment->setDirectorFirstName($row['first_name']);
		$editAssignment->setDirectorLastName($row['last_name']);
		$editAssignment->setDirectorInitials($row['initials']);
		$editAssignment->setDirectorEmail($row['email']);
		$editAssignment->setIsDirector($row['director_role_id']==ROLE_ID_DIRECTOR?1:0);
		$editAssignment->setDateUnderway($this->datetimeFromDB($row['date_underway']));
		$editAssignment->setDateNotified($this->datetimeFromDB($row['date_notified']));

		HookRegistry::call('EditAssignmentDAO::_returnEditAssignmentFromRow', array(&$editAssignment, &$row));

		return $editAssignment;
	}

	/**
	 * Insert a new EditAssignment.
	 * @param $editAssignment EditAssignment
	 */	
	function insertEditAssignment(&$editAssignment) {
		$this->update(
			sprintf('INSERT INTO edit_assignments
				(paper_id, director_id, date_notified, date_underway)
				VALUES
				(?, ?, %s, %s)',
				$this->datetimeToDB($editAssignment->getDateNotified()),
				$this->datetimeToDB($editAssignment->getDateUnderway())),
			array(
				$editAssignment->getPaperId(),
				$editAssignment->getDirectorId()
			)
		);

		$editAssignment->setEditId($this->getInsertEditId());
		return $editAssignment->getEditId();
	}

	/**
	 * Update an existing edit assignment.
	 * @param $editAssignment EditAssignment
	 */
	function updateEditAssignment(&$editAssignment) {
		return $this->update(
			sprintf('UPDATE edit_assignments
				SET	paper_id = ?,
					director_id = ?,
					date_notified = %s,
					date_underway = %s
				WHERE edit_id = ?',
				$this->datetimeToDB($editAssignment->getDateNotified()),
				$this->datetimeToDB($editAssignment->getDateUnderway())),
			array(
				$editAssignment->getPaperId(),
				$editAssignment->getDirectorId(),
				$editAssignment->getEditId()
			)
		);
	}

	/**
	 * Delete edit assignment.
	 * @param $reviewId int
	 */
	function deleteEditAssignmentById($editId) {
		return $this->update(
			'DELETE FROM edit_assignments WHERE edit_id = ?',
			$editId
		);
	}

	/**
	 * Delete edit assignments by paper.
	 * @param $paperId int
	 */
	function deleteEditAssignmentsByPaper($paperId) {
		return $this->update(
			'DELETE FROM edit_assignments WHERE paper_id = ?',
			$paperId
		);
	}

	/**
	 * Get the ID of the last inserted edit assignment.
	 * @return int
	 */
	function getInsertEditId() {
		return $this->getInsertId('edit_assignments', 'edit_id');
	}

	/**
	 * Get the assignment counts and last assigned date for all directors in the given conference.
	 * @return array
	 */
	function getDirectorStatistics($schedConfId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result =& $this->retrieve(
			'SELECT	ea.director_id,
				COUNT(ea.paper_id) AS complete
			FROM	edit_assignments ea,
				papers p
			WHERE	ea.paper_id=p.paper_id AND
				p.sched_conf_id = ? AND (
					p.status = ' . STATUS_ARCHIVED . ' OR
					p.status = ' . STATUS_PUBLISHED . ' OR
					p.status = ' . STATUS_DECLINED . '
				)
			GROUP BY ea.director_id',
			$schedConfId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['director_id']])) $statistics[$row['director_id']] = array();
			$statistics[$row['director_id']]['complete'] = $row['complete'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Get counts of incomplete submissions
		$result =& $this->retrieve(
			'SELECT	ea.director_id,
				COUNT(ea.paper_id) AS incomplete
			FROM	edit_assignments ea,
				papers p
			WHERE	ea.paper_id = p.paper_id AND
				p.sched_conf_id = ? AND
				p.status = ' . STATUS_QUEUED . '
			GROUP BY ea.director_id',
			$schedConfId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['director_id']])) $statistics[$row['director_id']] = array();
			$statistics[$row['director_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return $statistics;
	}
}

?>
