<?php

/**
 * AuthorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for AuthorSubmission DAO.
 * Operations for retrieving and modifying AuthorSubmission objects.
 *
 * $Id$
 */

import('submission.author.AuthorSubmission');

class AuthorSubmissionDAO extends DAO {

	var $paperDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $paperFileDao;
	var $suppFileDao;
	var $copyeditorSubmissionDao;
	var $paperCommentDao;
	var $layoutAssignmentDao;
	var $proofAssignmentDao;
	var $galleyDao;

	/**
	 * Constructor.
	 */
	function AuthorSubmissionDAO() {
		parent::DAO();
		$this->paperDao = &DAORegistry::getDAO('PaperDAO');
		$this->authorDao = &DAORegistry::getDAO('AuthorDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$this->galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
	}
	
	/**
	 * Retrieve a author submission by paper ID.
	 * @param $paperId int
	 * @return AuthorSubmission
	 */
	function &getAuthorSubmission($paperId) {
		$result = &$this->retrieve(
			'SELECT
				p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2,
				t2.title AS secondary_track_title,
				t2.title_alt1 AS secondary_track_title_alt1,
				t2.title_alt2 AS secondary_track_title_alt2,
				t2.abbrev AS secondary_track_abbrev,
				t2.abbrev_alt1 AS secondary_track_abbrev_alt1,
				t2.abbrev_alt2 AS secondary_track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN tracks t2 ON (t2.track_id = p.secondary_track_id)
				WHERE p.paper_id = ?', $paperId);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnAuthorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Internal function to return a AuthorSubmission object from a row.
	 * @param $row array
	 * @return AuthorSubmission
	 */
	function &_returnAuthorSubmissionFromRow(&$row) {
		$authorSubmission = &new AuthorSubmission();

		// Paper attributes
		$this->paperDao->_paperFromRow($authorSubmission, $row);
		
		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$authorSubmission->setEditAssignments($editAssignments->toArray());
		
		// Editor Decisions
		for ($i = 1; $i <= $row['review_progress']; $i++) {
			for ($j = 1; $j <= $row['current_round']; $j++) {
				$authorSubmission->setDecisions($this->getEditorDecisions($row['paper_id'], $i, $j), $i, $j);
			}
		}
				
		// Review Assignments
		for ($i = 1; $i <= $row['review_progress']; $i++)
			for ($j = 1; $j <= $row['current_round']; $j++)
				$authorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByPaperId($row['paper_id'], $i, $j), $i, $j);

		// Comments
		$authorSubmission->setMostRecentEditorDecisionComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_EDITOR_DECISION, $row['paper_id']));
		
		// Files
		$authorSubmission->setSubmissionFile($this->paperFileDao->getPaperFile($row['submission_file_id']));
		$authorSubmission->setRevisedFile($this->paperFileDao->getPaperFile($row['revised_file_id']));
		$authorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setAuthorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['revised_file_id'], $i), $i);
		}
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setEditorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['editor_file_id'], $i), $i);
		}
		$authorSubmission->setGalleys($this->galleyDao->getGalleysByPaper($row['paper_id']));

		HookRegistry::call('AuthorSubmissionDAO::_returnAuthorSubmissionFromRow', array(&$authorSubmission, &$row));

		return $authorSubmission;
	}
	
	/**
	 * Update an existing author submission.
	 * @param $authorSubmission AuthorSubmission
	 */
	function updateAuthorSubmission(&$authorSubmission) {
		// Update paper
		if ($authorSubmission->getPaperId()) {
			$paper = &$this->paperDao->getPaper($authorSubmission->getPaperId());
			
			// Only update fields that an author can actually edit.
			$paper->setRevisedFileId($authorSubmission->getRevisedFileId());
			$paper->setDateStatusModified($authorSubmission->getDateStatusModified());
			$paper->setLastModified($authorSubmission->getLastModified());
			// FIXME: These two are necessary for designating the
			// original as the review version, but they are probably
			// best not exposed like this.
			$paper->setReviewFileId($authorSubmission->getReviewFileId());
			$paper->setEditorFileId($authorSubmission->getEditorFileId());
			
			$this->paperDao->updatePaper($paper);
		}
	}
	
	/**
	 * Get all incomplete submissions.
	 * @return DAOResultFactory containing AuthorSubmissions
	 */
	function &getIncompleteSubmissions() {
		$incompleteSubmissions = array();
		$sql = 'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2,
				t2.title AS secondary_track_title,
				t2.title_alt1 AS secondary_track_title_alt1,
				t2.title_alt2 AS secondary_track_title_alt2,
				t2.abbrev AS secondary_track_abbrev,
				t2.abbrev_alt1 AS secondary_track_abbrev_alt1,
				t2.abbrev_alt2 AS secondary_track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN tracks t2 ON (t2.track_id = p.secondary_track_id)
				WHERE p.submission_progress != 0 AND p.status = ' . (int)SUBMISSION_STATUS_QUEUED;

		$result = &$this->retrieveRange($sql);
		while(!$result->EOF) {
			$incompleteSubmissions[] = &$this->_returnAuthorSubmissionFromRow($result->getRowAssoc(false));
			$result->moveNext();
		}
		return $incompleteSubmissions;
	}
	
	/**
	 * Get all author submissions for an author.
	 * @param $authorId int
	 * @return DAOResultFactory containing AuthorSubmissions
	 */
	function &getAuthorSubmissions($authorId, $eventId, $active = true, $rangeInfo = null) {
		$sql = 'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2,
				t2.title AS secondary_track_title,
				t2.title_alt1 AS secondary_track_title_alt1,
				t2.title_alt2 AS secondary_track_title_alt2,
				t2.abbrev AS secondary_track_abbrev,
				t2.abbrev_alt1 AS secondary_track_abbrev_alt1,
				t2.abbrev_alt2 AS secondary_track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN tracks t2 ON (t2.track_id = p.secondary_track_id)
				WHERE p.event_id = ? AND p.user_id = ?';

		if ($active) {
			$sql .= ' AND p.status = 1';
		} else {
			$sql .= ' AND ((p.status <> ' . (int) SUBMISSION_STATUS_QUEUED . ' AND p.submission_progress = 0) OR
				(p.status = ' . (int) SUBMISSION_STATUS_EXPIRED . '))'; 
		}

		$result = &$this->retrieveRange($sql, array($eventId, $authorId), $rangeInfo);
		
		$returner = &new DAOResultFactory($result, $this, '_returnAuthorSubmissionFromRow');
		return $returner;
	}
	
	//
	// Miscellaneous
	//
	
	/**
	 * Get the editor decisions for a review round of an paper.
	 * @param $paperId int
	 * @param $round int
	 */
	function getEditorDecisions($paperId, $type = null, $round = null) {
		$decisions = array();
		$args = array($paperId);
		if($type) {
			$args[] = $type;
		}
		if($round) {
			$args[] = $round;
		}
	
		$result = &$this->retrieve(
			'SELECT edit_decision_id, editor_id, decision, date_decided
			FROM edit_decisions
			WHERE paper_id = ? ' .
			($type?' AND type = ?' :'') .
			($round?' AND round = ?':'') .
			' ORDER BY date_decided ASC',
			(count($args)==1?shift($args):$args)
		);
		
		while (!$result->EOF) {
			$decisions[] = array(
				'editDecisionId' => $result->fields['edit_decision_id'],
				'editorId' => $result->fields['editor_id'],
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
	 * @param authorId int
	 * @param eventId int
	 */
	function getSubmissionsCount($authorId, $eventId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = '
			SELECT
				count(*), status
			FROM papers p 
			LEFT JOIN tracks s ON (s.track_id = p.track_id)
			WHERE p.event_id = ? AND p.user_id = ?
			GROUP BY p.status';

		$result = &$this->retrieve($sql, array($eventId, $authorId));

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
