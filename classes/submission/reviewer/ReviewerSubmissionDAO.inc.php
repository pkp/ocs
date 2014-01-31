<?php

/**
 * @file ReviewerSubmissionDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSubmissionDAO
 * @ingroup submission
 * @see ReviewerSubmission
 *
 * @brief Operations for retrieving and modifying ReviewerSubmission objects.
 */

//$Id$

import('submission.reviewer.ReviewerSubmission');

class ReviewerSubmissionDAO extends DAO {
	var $paperDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $editAssignmentDao;
	var $paperFileDao;
	var $suppFileDao;
	var $paperCommentDao;

	/**
	 * Constructor.
	 */
	function ReviewerSubmissionDAO() {
		parent::DAO();
		$this->paperDao =& DAORegistry::getDAO('PaperDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$this->paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
	}

	/**
	 * Retrieve a reviewer submission by paper ID.
	 * @param $paperId int
	 * @param $reviewerId int
	 * @return ReviewerSubmission
	 */
	function &getReviewerSubmission($reviewId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result =& $this->retrieve(
			'SELECT	p.*, r.*,
				r2.review_revision,
				u.first_name,
				u.last_name,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN review_assignments r ON (p.paper_id = r.paper_id)
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_stages r2 ON (p.paper_id = r2.paper_id AND r.stage = r2.stage)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	r.review_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$reviewId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnReviewerSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a ReviewerSubmission object from a row.
	 * @param $row array
	 * @return ReviewerSubmission
	 */
	function &_returnReviewerSubmissionFromRow(&$row) {
		$reviewerSubmission = new ReviewerSubmission();

		// Director Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$reviewerSubmission->setEditAssignments($editAssignments->toArray());

		// Files
		$reviewerSubmission->setSubmissionFile($this->paperFileDao->getPaperFile($row['submission_file_id']));
		$reviewerSubmission->setRevisedFile($this->paperFileDao->getPaperFile($row['revised_file_id']));
		$reviewerSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));
		$reviewerSubmission->setReviewFile($this->paperFileDao->getPaperFile($row['review_file_id']));
		$reviewerSubmission->setReviewerFile($this->paperFileDao->getPaperFile($row['reviewer_file_id']));
		$reviewerSubmission->setReviewerFileRevisions($this->paperFileDao->getPaperFileRevisions($row['reviewer_file_id']));

		// Comments
		$reviewerSubmission->setMostRecentPeerReviewComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		// Director Decisions
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$reviewerSubmission->setDecisions($this->getDirectorDecisions($row['paper_id'], $i), $i);
		}

		// Review Assignment 
		$reviewerSubmission->setReviewId($row['review_id']);
		$reviewerSubmission->setReviewerId($row['reviewer_id']);
		$reviewerSubmission->setReviewerFullName($row['first_name'].' '.$row['last_name']);
		$reviewerSubmission->setRecommendation($row['recommendation']);
		$reviewerSubmission->setDateAssigned($this->datetimeFromDB($row['date_assigned']));
		$reviewerSubmission->setDateNotified($this->datetimeFromDB($row['date_notified']));
		$reviewerSubmission->setDateConfirmed($this->datetimeFromDB($row['date_confirmed']));
		$reviewerSubmission->setDateCompleted($this->datetimeFromDB($row['date_completed']));
		$reviewerSubmission->setDateAcknowledged($this->datetimeFromDB($row['date_acknowledged']));
		$reviewerSubmission->setDateDue($this->datetimeFromDB($row['date_due']));
		$reviewerSubmission->setDeclined($row['declined']);
		$reviewerSubmission->setReplaced($row['replaced']);
		$reviewerSubmission->setCancelled($row['cancelled']==1?1:0);
		$reviewerSubmission->setReviewerFileId($row['reviewer_file_id']);
		$reviewerSubmission->setQuality($row['quality']);
		$reviewerSubmission->setStage($row['stage']);
		$reviewerSubmission->setReviewFileId($row['review_file_id']);
		$reviewerSubmission->setReviewRevision($row['review_revision']);

		// Paper attributes
		$this->paperDao->_paperFromRow($reviewerSubmission, $row);

		HookRegistry::call('ReviewerSubmissionDAO::_returnReviewerSubmissionFromRow', array(&$reviewerSubmission, &$row));

		return $reviewerSubmission;
	}

	/**
	 * Update an existing review submission.
	 * @param $reviewSubmission ReviewSubmission
	 */
	function updateReviewerSubmission(&$reviewerSubmission) {
		return $this->update(
			sprintf('UPDATE review_assignments
				SET	paper_id = ?,
					reviewer_id = ?,
					stage = ?,
					recommendation = ?,
					declined = ?,
					replaced = ?,
					cancelled = ?,
					date_assigned = %s,
					date_notified = %s,
					date_confirmed = %s,
					date_completed = %s,
					date_acknowledged = %s,
					date_due = %s,
					reviewer_file_id = ?,
					quality = ?
				WHERE review_id = ?',
				$this->datetimeToDB($reviewerSubmission->getDateAssigned()), $this->datetimeToDB($reviewerSubmission->getDateNotified()), $this->datetimeToDB($reviewerSubmission->getDateConfirmed()), $this->datetimeToDB($reviewerSubmission->getDateCompleted()), $this->datetimeToDB($reviewerSubmission->getDateAcknowledged()), $this->datetimeToDB($reviewerSubmission->getDateDue())),
			array(
				$reviewerSubmission->getPaperId(),
				$reviewerSubmission->getReviewerId(),
				$reviewerSubmission->getStage(),
				$reviewerSubmission->getRecommendation(),
				$reviewerSubmission->getDeclined(),
				$reviewerSubmission->getReplaced(),
				$reviewerSubmission->getCancelled(),
				$reviewerSubmission->getReviewerFileId(),
				$reviewerSubmission->getQuality(),
				$reviewerSubmission->getReviewId()
			)
		);
	}

	/**
	 * Get all submissions for a reviewer of a conference.
	 * @param $reviewerId int
	 * @param $schedConfId int
	 * @param $rangeInfo object
	 * @return array ReviewerSubmissions
	 */
	function &getReviewerSubmissionsByReviewerId($reviewerId, $schedConfId, $active = true, $rangeInfo = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result =& $this->retrieveRange(
			'SELECT	p.*,
				r.*,
				r2.review_revision,
				u.first_name,
				u.last_name,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM papers p
				LEFT JOIN review_assignments r ON (p.paper_id = r.paper_id)
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	p.sched_conf_id = ? AND
				r.reviewer_id = ? AND
				r.date_notified IS NOT NULL' .
				($active?
					' AND r.date_completed IS NULL AND r.declined <> 1 AND (r.cancelled = 0 OR r.cancelled IS NULL) AND p.status = ' . STATUS_QUEUED:
					' AND (r.date_completed IS NOT NULL OR r.cancelled = 1 OR r.declined = 1 OR p.status <> ' . STATUS_QUEUED . ')'
				),
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$schedConfId,
				$reviewerId
			),
			$rangeInfo
		);


		$returner = new DAOResultFactory($result, $this, '_returnReviewerSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get count of active and complete assignments
	 * @param reviewerId int
	 * @param schedConfId int
	 */
	function getSubmissionsCount($reviewerId, $schedConfId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = '
			SELECT r.date_completed, r.declined, r.cancelled, a.status
			FROM papers a
				LEFT JOIN review_assignments r ON (a.paper_id = r.paper_id)
				LEFT JOIN tracks t ON (t.track_id = a.track_id)
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage)
			WHERE a.sched_conf_id = ? AND r.reviewer_id = ? AND r.date_notified IS NOT NULL';

		$result =& $this->retrieve($sql, array($schedConfId, $reviewerId));

		while (!$result->EOF) {
			if ($result->fields['date_completed'] == null && $result->fields['declined'] != 1 && $result->fields['cancelled'] != 1 && $result->fields['status'] == STATUS_QUEUED) {
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

		$result =& $this->retrieve('
			SELECT edit_decision_id, director_id, decision, date_decided
			FROM edit_decisions
			WHERE paper_id = ?'
				. ($stage?' AND stage = ?':'')
			. ' ORDER BY edit_decision_id ASC',
			count($args)==1?shift($args):$args);

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
}

?>
