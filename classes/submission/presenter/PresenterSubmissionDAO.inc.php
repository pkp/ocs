<?php

/**
 * PresenterSubmissionDAO.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for PresenterSubmission DAO.
 * Operations for retrieving and modifying PresenterSubmission objects.
 *
 * $Id$
 */

import('submission.presenter.PresenterSubmission');

class PresenterSubmissionDAO extends DAO {

	var $paperDao;
	var $presenterDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $paperFileDao;
	var $suppFileDao;
	var $paperCommentDao;
	var $galleyDao;

	/**
	 * Constructor.
	 */
	function PresenterSubmissionDAO() {
		parent::DAO();
		$this->paperDao = &DAORegistry::getDAO('PaperDAO');
		$this->presenterDao = &DAORegistry::getDAO('PresenterDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$this->galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
	}
	
	/**
	 * Retrieve a presenter submission by paper ID.
	 * @param $paperId int
	 * @return PresenterSubmission
	 */
	function &getPresenterSubmission($paperId) {
		$result = &$this->retrieve(
			'SELECT
				p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				WHERE p.paper_id = ?', $paperId);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnPresenterSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Internal function to return a PresenterSubmission object from a row.
	 * @param $row array
	 * @return PresenterSubmission
	 */
	function &_returnPresenterSubmissionFromRow(&$row) {
		$presenterSubmission = &new PresenterSubmission();

		// Paper attributes
		$this->paperDao->_paperFromRow($presenterSubmission, $row);
		
		// Director Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$presenterSubmission->setEditAssignments($editAssignments->toArray());
		
		// Director Decisions
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$presenterSubmission->setDecisions($this->getDirectorDecisions($row['paper_id'], $i), $i);
		}
				
		// Review Assignments
		for ($i = 1; $i <= $row['current_stage']; $i++)
			$presenterSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByPaperId($row['paper_id'], $i), $i);

		// Comments
		$presenterSubmission->setMostRecentDirectorDecisionComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_DIRECTOR_DECISION, $row['paper_id']));
		
		// Files
		$presenterSubmission->setSubmissionFile($this->paperFileDao->getPaperFile($row['submission_file_id']));
		$presenterSubmission->setRevisedFile($this->paperFileDao->getPaperFile($row['revised_file_id']));
		$presenterSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$presenterSubmission->setPresenterFileRevisions($this->paperFileDao->getPaperFileRevisions($row['revised_file_id'], $i), $i);
		}
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$presenterSubmission->setDirectorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['director_file_id'], $i), $i);
		}
		$presenterSubmission->setLayoutFile($this->paperFileDao->getPaperFile($row['layout_file_id']));
		$presenterSubmission->setGalleys($this->galleyDao->getGalleysByPaper($row['paper_id']));

		HookRegistry::call('PresenterSubmissionDAO::_returnPresenterSubmissionFromRow', array(&$presenterSubmission, &$row));

		return $presenterSubmission;
	}
	
	/**
	 * Update an existing presenter submission.
	 * @param $presenterSubmission PresenterSubmission
	 */
	function updatePresenterSubmission(&$presenterSubmission) {
		// Update paper
		if ($presenterSubmission->getPaperId()) {
			$paper = &$this->paperDao->getPaper($presenterSubmission->getPaperId());
			
			// Only update fields that an presenter can actually edit.
			$paper->setRevisedFileId($presenterSubmission->getRevisedFileId());
			$paper->setDateStatusModified($presenterSubmission->getDateStatusModified());
			$paper->setLastModified($presenterSubmission->getLastModified());
			// FIXME: These two are necessary for designating the
			// original as the review version, but they are probably
			// best not exposed like this.
			$paper->setReviewFileId($presenterSubmission->getReviewFileId());
			$paper->setDirectorFileId($presenterSubmission->getDirectorFileId());
			
			$this->paperDao->updatePaper($paper);
		}
	}
	
	/**
	 * Get all incomplete submissions.
	 * @return DAOResultFactory containing PresenterSubmissions
	 */
	function &getIncompleteSubmissions() {
		$incompleteSubmissions = array();
		$sql = 'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				WHERE p.submission_progress != 0 AND p.status = ' . (int)SUBMISSION_STATUS_QUEUED;

		$result = &$this->retrieveRange($sql);
		while(!$result->EOF) {
			$incompleteSubmissions[] = &$this->_returnPresenterSubmissionFromRow($result->getRowAssoc(false));
			$result->moveNext();
		}
		return $incompleteSubmissions;
	}
	
	/**
	 * Get all presenter submissions for an presenter.
	 * @param $presenterId int
	 * @return DAOResultFactory containing PresenterSubmissions
	 */
	function &getPresenterSubmissions($presenterId, $schedConfId, $active = true, $rangeInfo = null) {
		$sql = 'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				WHERE p.sched_conf_id = ? AND p.user_id = ?';

		if ($active) {
			$sql .= ' AND p.status = 1';
		} else {
			$sql .= ' AND ((p.status <> ' . (int) SUBMISSION_STATUS_QUEUED . ' AND p.submission_progress = 0) OR
				(p.status = ' . (int) SUBMISSION_STATUS_ARCHIVED . '))'; 
		}

		$result = &$this->retrieveRange($sql, array($schedConfId, $presenterId), $rangeInfo);
		
		$returner = &new DAOResultFactory($result, $this, '_returnPresenterSubmissionFromRow');
		return $returner;
	}
	
	//
	// Miscellaneous
	//
	
	/**
	 * Get the director decisions for a review stage of a paper.
	 * @param $paperId int
	 * @param $stage int
	 */
	function getDirectorDecisions($paperId, $stage = null) {
		$decisions = array();
		$args = array($paperId);
		if($stage) {
			$args[] = $stage;
		}
	
		$result = &$this->retrieve(
			'SELECT edit_decision_id, director_id, decision, date_decided
			FROM edit_decisions
			WHERE paper_id = ? ' .
			($stage?' AND stage = ?':'') .
			' ORDER BY date_decided ASC',
			(count($args)==1?shift($args):$args)
		);
		
		while (!$result->EOF) {
			$decisions[] = array(
				'editDecisionId' => $result->fields['edit_decision_id'],
				'directorId' => $result->fields['director_id'],
				'decision' => $result->fields['decision'],
				'dateDecided' => $this->datetimeFromDB($result->fields['date_decided'])
			);
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $decisions;
	}

	/**
	 * Get count of active and complete assignments
	 * @param presenterId int
	 * @param schedConfId int
	 */
	function getSubmissionsCount($presenterId, $schedConfId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = '
			SELECT
				count(*), status
			FROM papers p 
			LEFT JOIN tracks s ON (s.track_id = p.track_id)
			WHERE p.sched_conf_id = ? AND p.user_id = ?
			GROUP BY p.status';

		$result = &$this->retrieve($sql, array($schedConfId, $presenterId));

		while (!$result->EOF) {
			if ($result->fields['status'] != 1) {
				$submissionsCount[1] += $result->fields[0];
			} else {
				$submissionsCount[0] += $result->fields[0];
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
	}
}

?>
