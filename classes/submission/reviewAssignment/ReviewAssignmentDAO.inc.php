<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignmentDAO
 * @ingroup submission
 * @see ReviewAssignment
 *
 * @brief Class for DAO relating reviewers to papers.
 */

// $Id$


import('classes.submission.reviewAssignment.ReviewAssignment');
import('lib.pkp.classes.submission.reviewAssignment.PKPReviewAssignmentDAO');

class ReviewAssignmentDAO extends PKPReviewAssignmentDAO {
	var $paperFileDao;
	var $suppFileDao;
	var $paperCommentsDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::PKPReviewAssignmentDAO();
		$this->paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
	}

	/**
	 * Retrieve a review assignment by reviewer and paper.
	 * @param $paperId int
	 * @param $reviewerId int
	 * @param $round int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignment($paperId, $reviewerId, $round) {
		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN papers a ON (r.submission_id = a.paper_id)
			WHERE	a.paper_id = ? AND
				r.reviewer_id = ? AND
				r.cancelled <> 1 AND
				r.round = ?',
			array((int) $paperId, (int) $reviewerId, (int) $round)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a review assignment by review assignment id.
	 * @param $reviewId int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignmentById($reviewId) {
		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN papers a ON (r.submission_id = a.paper_id)
			WHERE	r.review_id = ?',
			(int) $reviewId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get all incomplete review assignments for all conferences
	 * @param $paperId int
	 * @return array ReviewAssignments
	 */
	function &getIncompleteReviewAssignments() {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN papers a ON (r.submission_id = a.paper_id)
			WHERE	(r.cancelled IS NULL OR r.cancelled = 0) AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NULL AND
				r.declined <> 1
			ORDER BY r.submission_id'
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Get all review assignments for a paper.
	 * @param $paperId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByPaperId($paperId, $round = null) {
		$reviewAssignments = array();

		$args = array((int) $paperId);
		if ($round) $args[] = (int) $round;

		$result =& $this->retrieve(
			'SELECT	r.*,
				r2.review_revision,
				a.review_file_id,
				u.first_name,
				u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN papers a ON (r.submission_id = a.paper_id)
			WHERE	r.submission_id = ?
			' . ($round ? ' AND r.round = ?':'') . '
			ORDER BY ' . ($round?'':' round,') . 'review_id',
			$args
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Get all review assignments for a reviewer.
	 * @param $userId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByUserId($userId) {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT r.*,
				r2.review_revision,
				a.review_file_id,
				u.first_name,
				u.last_name
			FROM review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN papers a ON (r.submission_id = a.paper_id)
			WHERE	r.reviewer_id = ?
			ORDER BY round, review_id',
			(int) $userId
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Get all review assignments for a review form.
	 * @param $reviewFormId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByReviewFormId($reviewFormId) {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN papers a ON (r.submission_id = a.paper_id)
			WHERE	r.review_form_id = ?
			ORDER BY round, review_id',
			(int) $reviewFormId
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Get a review file for a paper for each round.
	 * @param $paperId int
	 * @return array PaperFiles
	 */
	function &getReviewFilesByRound($paperId) {
		$returner = array();

		$result =& $this->retrieve(
			'SELECT a.*, r.round as round
			FROM	review_rounds r,
				paper_files a,
				papers art
			WHERE	art.paper_id = r.submission_id AND
				r.submission_id = ? AND
				r.submission_id = a.paper_id AND
				a.file_id = art.review_file_id AND
				a.revision = r.review_revision',
			(int) $paperId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['round']] =& $this->paperFileDao->_returnPaperFileFromRow($row);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get all author-viewable reviewer files for a paper for each round.
	 * @param $paperId int
	 * @return array returned[round][reviewer_index] = array of PaperFiles
	 */
	function &getAuthorViewableFilesByRound($paperId) {
		$files = array();

		$result =& $this->retrieve(
			'SELECT	f.*, r.reviewer_id
			FROM	review_assignments r,
				paper_files f
			WHERE	reviewer_file_id = file_id
				AND viewable = 1
				AND r.submission_id = ?
			ORDER BY r.round, r.reviewer_id, r.review_id',
			array((int) $paperId)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($files[$row['round']]) || !is_array($files[$row['round']])) {
				$files[$row['round']] = array();
				$thisReviewerId = $row['reviewer_id'];
				$reviewerIndex = 0;
			}
			else if ($thisReviewerId != $row['reviewer_id']) {
				$thisReviewerId = $row['reviewer_id'];
				$reviewerIndex++;
			}

			$thisPaperFile =& $this->paperFileDao->_returnPaperFileFromRow($row);
			$files[$row['round']][$reviewerIndex][] = $thisPaperFile;
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $files;
	}

	/**
	 * Get all cancelled/declined review assignments for a paper.
	 * @param $paperId int
	 * @return array ReviewAssignments
	 */
	function &getCancelsAndRegrets($paperId) {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT	r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name
			FROM	review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_rounds r2 ON (r.submission_id = r2.submission_id AND r.round = r2.round)
				LEFT JOIN papers a ON (r.submission_id = a.paper_id)
			WHERE	r.submission_id = ? AND
				(r.cancelled = 1 OR r.declined = 1)
			ORDER BY round, review_id',
			(int) $paperId
		);

		while (!$result->EOF) {
			$reviewAssignments[] =& $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $reviewAssignments;
	}

	/**
	 * Internal function to return a review assignment object from a row.
	 * @param $row array
	 * @return ReviewAssignment
	 */
	function &_returnReviewAssignmentFromRow(&$row) {
		$reviewAssignment = new ReviewAssignment();
		$reviewAssignment->setId($row['review_id']);
		$reviewAssignment->setSubmissionId($row['submission_id']);
		$reviewAssignment->setReviewerId($row['reviewer_id']);
		$reviewAssignment->setReviewerFullName($row['first_name'].' '.$row['last_name']);
		$reviewAssignment->setRecommendation($row['recommendation']);
		$reviewAssignment->setDateAssigned($this->datetimeFromDB($row['date_assigned']));
		$reviewAssignment->setDateNotified($this->datetimeFromDB($row['date_notified']));
		$reviewAssignment->setDateConfirmed($this->datetimeFromDB($row['date_confirmed']));
		$reviewAssignment->setDateCompleted($this->datetimeFromDB($row['date_completed']));
		$reviewAssignment->setDateAcknowledged($this->datetimeFromDB($row['date_acknowledged']));
		$reviewAssignment->setDateDue($this->datetimeFromDB($row['date_due']));
		$reviewAssignment->setLastModified($this->datetimeFromDB($row['last_modified']));
		$reviewAssignment->setDeclined($row['declined']);
		$reviewAssignment->setReplaced($row['replaced']);
		$reviewAssignment->setCancelled($row['cancelled']);
		$reviewAssignment->setReviewerFileId($row['reviewer_file_id']);
		$reviewAssignment->setQuality($row['quality']);
		$reviewAssignment->setDateRated($this->datetimeFromDB($row['date_rated']));
		$reviewAssignment->setDateReminded($this->datetimeFromDB($row['date_reminded']));
		$reviewAssignment->setReminderWasAutomatic($row['reminder_was_automatic']);
		$reviewAssignment->setRound($row['round']);
		$reviewAssignment->setReviewFileId($row['review_file_id']);
		$reviewAssignment->setReviewRevision($row['review_revision']);
		$reviewAssignment->setReviewFormId($row['review_form_id']);

		// Files
		$reviewAssignment->setReviewFile($this->paperFileDao->getPaperFile($row['review_file_id'], $row['review_revision']));
		$reviewAssignment->setReviewerFile($this->paperFileDao->getPaperFile($row['reviewer_file_id']));
		$reviewAssignment->setReviewerFileRevisions($this->paperFileDao->getPaperFileRevisions($row['reviewer_file_id']));
		$reviewAssignment->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['submission_id']));


		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->paperCommentDao->getMostRecentPaperComment($row['submission_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		HookRegistry::call('ReviewAssignmentDAO::_returnReviewAssignmentFromRow', array(&$reviewAssignment, &$row));

		return $reviewAssignment;
	}

	/**
	 * Delete review assignments by paper.
	 * @param $paperId int
	 * @return boolean
	 */
	function deleteReviewAssignmentsByPaper($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->deleteBySubmissionId($paperId);
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a scheduled conference.
	 * @return array
	 */
	function getAverageQualityRatings($schedConfId) {
		$averageQualityRatings = Array();
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, AVG(r.quality) AS average, COUNT(r.quality) AS count
			FROM	review_assignments r,
				papers a
			WHERE	r.submission_id = a.paper_id AND
				a.sched_conf_id = ?
			GROUP BY r.reviewer_id',
			(int) $schedConfId
			);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$averageQualityRatings[$row['reviewer_id']] = array('average' => $row['average'], 'count' => $row['count']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $averageQualityRatings;
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a scheduled conference.
	 * @return array
	 */
	function getCompletedReviewCounts($schedConfId) {
		$returner = Array();
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, COUNT(r.review_id) AS count
			FROM	review_assignments r,
				papers a
			WHERE	r.submission_id = a.paper_id AND
				a.sched_conf_id = ? AND
				r.date_completed IS NOT NULL AND
				r.cancelled = 0
			GROUP BY r.reviewer_id',
			(int) $schedConfId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['reviewer_id']] = $row['count'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the number of completed reviews for all published review forms of a conference.
	 * @return array
	 */
	function getCompletedReviewCountsForReviewForms($conferenceId) {
		$returner = array();
		$result =& $this->retrieve(
			'SELECT	r.review_form_id, COUNT(r.review_id) AS count
			FROM	review_assignments r,
				papers a,
				review_forms rf
			WHERE	r.submission_id = a.paper_id AND
				a.conference_id = ? AND
				r.review_form_id = rf.review_form_id AND
				rf.published = 1 AND
				r.date_completed IS NOT NULL
			GROUP BY r.review_form_id',
			(int) $conferenceId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_form_id']] = $row['count'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the number of active reviews for all published review forms of a conference.
	 * @return array
	 */
	function getActiveReviewCountsForReviewForms($conferenceId) {
		$returner = array();
		$result =& $this->retrieve(
			'SELECT	r.review_form_id, COUNT(r.review_id) AS count
			FROM	review_assignments r,
				papers a,
				review_forms rf
			WHERE	r.submission_id = a.paper_id AND
				a.conference_id = ? AND
				r.review_form_id = rf.review_form_id AND
				rf.published = 1 AND
				r.date_confirmed IS NOT NULL AND
				r.date_completed IS NULL
			GROUP BY r.review_form_id',
			$conferenceId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_form_id']] = $row['count'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	function &getReviewIndexesForStage($paperId, $round) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getReviewIndexesForRound($paperId, $round);
		return $returner;
	}

	function &getReviewFilesByStage($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& getReviewFilesByRound($paperId);
		return $returner;
	}

	function &getLastModifiedByStage($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getLastModifiedByRound($paperId);
		return $returner;
	}

	function &getEarliestNotificationByStage($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getEarliestNotificationByRound($paperId);
		return $returner;
	}
}

?>
