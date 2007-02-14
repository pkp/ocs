<?php

/**
 * LayoutEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutEditor
 *
 * Class for LayoutEditorSubmission DAO.
 * Operations for retrieving and modifying LayoutEditorSubmission objects.
 *
 * $Id$
 */

import('submission.layoutEditor.LayoutEditorSubmission');

class LayoutEditorSubmissionDAO extends DAO {

	/** Helper DAOs */
	var $paperDao;
	var $layoutDao;
	var $galleyDao;
	var $editAssignmentDao;
	var $suppFileDao;
	var $paperCommentDao;

	/**
	 * Constructor.
	 */
	function LayoutEditorSubmissionDAO() {
		parent::DAO();
		
		$this->paperDao = &DAORegistry::getDAO('PaperDAO');
		$this->layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$this->galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
	}
	
	/**
	 * Retrieve a layout editor submission by paper ID.
	 * @param $paperId int
	 * @return LayoutEditorSubmission
	 */
	function &getSubmission($paperId, $schedConfId =  null) {
		if (isset($schedConfId)) {
			$result = &$this->retrieve(
				'SELECT a.*, s.title AS track_title, s.title_alt1 AS track_title_alt1, s.title_alt2 AS track_title_alt2, s.abbrev AS track_abbrev, s.abbrev_alt1 AS track_abbrev_alt1, s.abbrev_alt2 AS track_abbrev_alt2
				FROM papers a
				LEFT JOIN tracks s ON s.track_id = a.track_id
				WHERE paper_id = ? AND a.sched_conf_id = ?',
				array($paperId, $schedConfId)
			);
			
		} else {
			$result = &$this->retrieve(
				'SELECT a.*, s.title AS track_title, s.title_alt1 AS track_title_alt1, s.title_alt2 AS track_title_alt2, s.abbrev AS track_abbrev, s.abbrev_alt1 AS track_abbrev_alt1, s.abbrev_alt2 AS track_abbrev_alt2
				FROM papers a
				LEFT JOIN tracks s ON s.track_id = a.track_id
				WHERE paper_id = ?',
				$paperId
			);
		}

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Internal function to return a LayoutEditorSubmission object from a row.
	 * @param $row array
	 * @return LayoutEditorSubmission
	 */
	function &_returnSubmissionFromRow(&$row) {
		$submission = &new LayoutEditorSubmission();
		$this->paperDao->_paperFromRow($submission, $row);
		$submission->setLayoutAssignment($this->layoutDao->getLayoutAssignmentByPaperId($row['paper_id']));
		
		// Comments
		$submission->setMostRecentLayoutComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_LAYOUT, $row['paper_id']));

		$submission->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));

		$submission->setGalleys($this->galleyDao->getGalleysByPaper($row['paper_id']));
		
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$submission->setEditAssignments($editAssignments->toArray());

		HookRegistry::call('LayoutEditorSubmissionDAO::_returnLayoutEditorSubmissionFromRow', array(&$submission, &$row));

		return $submission;
	}
	
	/**
	 * Update an existing layout editor sbusmission.
	 * @param $submission LayoutEditorSubmission
	 */
	function updateSubmission(&$submission) {
		// Only update layout-specific data
		$layoutAssignment =& $submission->getLayoutAssignment();
		$this->layoutDao->updateLayoutAssignment($layoutAssignment);
	}
	
	/**
	 * Get set of layout editing assignments assigned to the specified layout editor.
	 * @param $editorId int
	 * @param $schedConfId int
	 * @param $searchField int SUBMISSION_FIELD_... constant
	 * @param $searchMatch String 'is' or 'contains'
	 * @param $search String Search string
	 * @param $dateField int SUBMISSION_FIELD_DATE_... constant
	 * @param $dateFrom int Search from timestamp
	 * @param $dateTo int Search to timestamp
	 * @param $active boolean true to select active assignments, false to select completed assignments
	 * @return array LayoutEditorSubmission
	 */
	function &getSubmissions($editorId, $schedConfId = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $active = true, $rangeInfo = null) {
		if (isset($schedConfId)) $params = array($editorId, $schedConfId);
		else $params = array($editorId);

		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND (LOWER(a.title) = LOWER(?) OR LOWER(a.title_alt1) = LOWER(?) OR LOWER(a.title_alt2) = LOWER(?))';
				} else {
					$searchSql = ' AND (LOWER(a.title) LIKE LOWER(?) OR LOWER(a.title_alt1) LIKE LOWER(?) OR LOWER(a.title_alt2) LIKE LOWER(?))';
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $search;
				break;
			case SUBMISSION_FIELD_PRESENTER:
				$first_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.last_name');
				$first_middle_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.middle_name', '\' \'', 'aa.last_name');
				$last_comma_first = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name', '\' \'', 'aa.middle_name');

				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(aa.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} else {
					$searchSql = " AND (LOWER(aa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
			case SUBMISSION_FIELD_EDITOR:
				$first_last = $this->_dataSource->Concat('ed.first_name', '\' \'', 'ed.last_name');
				$first_middle_last = $this->_dataSource->Concat('ed.first_name', '\' \'', 'ed.middle_name', '\' \'', 'ed.last_name');
				$last_comma_first = $this->_dataSource->Concat('ed.last_name', '\', \'', 'ed.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('ed.last_name', '\', \'', 'ed.first_name', '\' \'', 'ed.middle_name');
				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(ed.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} else {
					$searchSql = " AND (LOWER(ed.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
		}

		if (!empty($dateFrom) || !empty($dateTo)) switch($dateField) {
			case SUBMISSION_FIELD_DATE_SUBMITTED:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND a.date_submitted >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND a.date_submitted <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND l.date_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND l.date_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		$sql = 'SELECT DISTINCT
				a.*,
				l.*,
				s.title AS track_title,
				s.title_alt1 AS track_title_alt1,
				s.title_alt2 AS track_title_alt2,
				s.abbrev AS track_abbrev,
				s.abbrev_alt1 AS track_abbrev_alt1,
				s.abbrev_alt2 AS track_abbrev_alt2
			FROM
				papers a
			INNER JOIN paper_presenters aa ON (aa.paper_id = a.paper_id)
			INNER JOIN layouted_assignments l ON (l.paper_id = a.paper_id)
			LEFT JOIN tracks s ON s.track_id = a.track_id
			LEFT JOIN edit_assignments e ON (e.paper_id = a.paper_id)
			LEFT JOIN users ed ON (e.editor_id = ed.user_id)
			WHERE
				l.editor_id = ? AND
				' . (isset($schedConfId)?'a.sched_conf_id = ? AND':'') . '
				l.date_notified IS NOT NULL';
		
		if ($active) {
			$sql .= ' AND (l.date_completed IS NULL)'; 
		} else {
			$sql .= ' AND (l.date_completed IS NOT NULL)';
		}

		$result = &$this->retrieveRange(
			$sql . ' ' . $searchSql . ' ORDER BY a.paper_id ASC',
			count($params)==1?array_shift($params):$params,
			$rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get count of active and complete assignments
	 * @param editorId int
	 * @param schedConfId int
	 */
	function getSubmissionsCount($editorId, $schedConfId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = 'SELECT l.date_completed FROM papers a NATURAL JOIN layouted_assignments l LEFT JOIN tracks s ON s.track_id = a.track_id WHERE l.editor_id = ? AND a.sched_conf_id = ? AND l.date_notified IS NOT NULL';

		$result = &$this->retrieve($sql, array($editorId, $schedConfId));
		while (!$result->EOF) {
			if ($result->fields['date_completed'] == null) {
				$submissionsCount[0] += 1;
			} else {
				$submissionsCount[1] += 1;
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
	}

}

?>
