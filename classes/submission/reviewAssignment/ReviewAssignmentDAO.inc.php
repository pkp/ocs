<?php

/**
 * @file ReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignmentDAO
 * @ingroup submission
 * @see ReviewAssignment
 *
 * @brief Class for DAO relating reviewers to papers.
 */

//$Id$

import('submission.reviewAssignment.ReviewAssignment');

class ReviewAssignmentDAO extends DAO {
	var $userDao;
	var $paperFileDao;
	var $suppFileDao;
	var $paperCommentsDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::DAO();
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
	}

	/**
	 * Retrieve a review assignment by reviewer and paper.
	 * @param $paperId int
	 * @param $reviewerId int
	 * @param $stage int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignment($paperId, $reviewerId, $stage) {
		$result =& $this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage) LEFT JOIN papers a ON (r.paper_id = a.paper_id) WHERE r.paper_id = ? AND r.reviewer_id = ? AND r.cancelled <> 1 AND r.stage = ?',
			array((int) $paperId, (int) $reviewerId, (int) $stage)
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
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage) LEFT JOIN papers a ON (r.paper_id = a.paper_id) WHERE r.review_id = ?',
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
	 * Determine the order of active reviews for the given stage of the give paper
	 * @param $paperId int
	 * @param $stage int
	 * @return array associating review ID with number; ie if review ID 26 is first, returned['26']=0
	 */
	function &getReviewIndexesForStage($paperId, $stage) {
		$returner = array();
		$index = 0;
		$result =& $this->retrieve(
			'SELECT review_id FROM review_assignments WHERE paper_id = ? AND stage = ? AND (cancelled = 0 OR cancelled IS NULL) ORDER BY review_id',
			array((int) $paperId, (int) $stage)
			);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_id']] = $index++;
			$result->MoveNext();
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
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage) LEFT JOIN papers a ON (r.paper_id = a.paper_id) WHERE (r.cancelled IS NULL OR r.cancelled = 0) AND r.date_notified IS NOT NULL AND r.date_completed IS NULL ORDER BY r.paper_id'
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
	function &getReviewAssignmentsByPaperId($paperId, $stage = null) {
		$reviewAssignments = array();

		$args = array((int) $paperId);
		if ($stage) $args[] = (int) $stage;

		$result =& $this->retrieve('
			SELECT	r.*,
				r2.review_revision,
				a.review_file_id,
				u.first_name,
				u.last_name
			FROM review_assignments r
				LEFT JOIN users u ON (r.reviewer_id = u.user_id)
				LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage)
				LEFT JOIN papers a ON (r.paper_id = a.paper_id)
			WHERE r.paper_id = ? '
				. ($stage ? ' AND r.stage = ?':'')
			. ' ORDER BY ' . ($stage?'':' stage,') . 'review_id',
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
				LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage)
				LEFT JOIN papers a ON (r.paper_id = a.paper_id) WHERE r.reviewer_id = ?
			ORDER BY stage, review_id',
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
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage) LEFT JOIN papers a ON (r.paper_id = a.paper_id) WHERE r.review_form_id = ? ORDER BY stage, review_id',
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
	 * Get a review file for a paper for each stage.
	 * @param $paperId int
	 * @return array PaperFiles
	 */
	function &getReviewFilesByStage($paperId) {
		$returner = array();

		$result =& $this->retrieve('
			SELECT a.*,
				r.stage as stage
			FROM review_stages r,
				paper_files a,
				papers art
			WHERE art.paper_id=r.paper_id
				AND r.paper_id=?
				AND r.paper_id=a.paper_id
				AND a.file_id=art.review_file_id
				AND a.revision=r.review_revision
				AND a.paper_id=r.paper_id', 
			(int) $paperId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['stage']] =& $this->paperFileDao->_returnPaperFileFromRow($row);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get all author-viewable reviewer files for a paper for each stage.
	 * @param $paperId int
	 * @return array returned[stage][reviewer_index] = array of PaperFiles
	 */
	function &getAuthorViewableFilesByStage($paperId) {
		$files = array();

		$result =& $this->retrieve(
			'SELECT	f.*, a.reviewer_id AS reviewer_id
			FROM	review_assignments a,
				paper_files f
			WHERE	reviewer_file_id = file_id
				AND viewable = 1
				AND a.paper_id = ?
			ORDER BY a.stage, a.reviewer_id, a.review_id', 
			array((int) $paperId)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($files[$row['stage']]) || !is_array($files[$row['stage']])) {
				$files[$row['stage']] = array();
				$thisReviewerId = $row['reviewer_id'];
				$reviewerIndex = 0;
			}
			else if ($thisReviewerId != $row['reviewer_id']) {
				$thisReviewerId = $row['reviewer_id'];
				$reviewerIndex++;
			}

			$thisPaperFile =& $this->paperFileDao->_returnPaperFileFromRow($row);
			$files[$row['stage']][$reviewerIndex][] = $thisPaperFile;
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $files;
	}

	/**
	 * Get the most recent last modified date for all review assignments for each stage of a submission.
	 * @param $paperId int
	 * @param $stage int
	 * @return array associating stage with most recent last modified date
	 */
	function &getLastModifiedByStage($paperId) {
		$returner = array();

		$result =& $this->retrieve(
			'SELECT stage, MAX(last_modified) as last_modified FROM review_assignments WHERE paper_id=? GROUP BY stage', 
			array((int) $paperId)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['stage']] = $this->datetimeFromDB($row['last_modified']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the first notified date from all review assignments for a stage of a submission.
	 * @param $paperId int
	 * @param $stage int
	 * @return array Associative array of ($stage_num => $earliest_date_of_notification)*
	 */
	function &getEarliestNotificationByStage($paperId) {
		$returner = array();

		$result =& $this->retrieve(
			'SELECT stage, MIN(date_notified) as earliest_date FROM review_assignments WHERE paper_id=? GROUP BY stage', 
			array((int) $paperId)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['stage']] = $this->datetimeFromDB($row['earliest_date']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get all cancelled/declined review assignments for a paper.
	 * @param $paperId int
	 * @return array ReviewAssignments
	 */
	function &getCancelsAndRegrets($paperId) {
		$reviewAssignments = array();

		$result =& $this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_stages r2 ON (r.paper_id = r2.paper_id AND r.stage = r2.stage) LEFT JOIN papers a ON (r.paper_id = a.paper_id) WHERE r.paper_id = ? AND (r.cancelled = 1 OR r.declined = 1) ORDER BY stage, review_id',
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
		$reviewAssignment->setPaperId($row['paper_id']);
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
		$reviewAssignment->setStage($row['stage']);
		$reviewAssignment->setReviewFileId($row['review_file_id']);
		$reviewAssignment->setReviewRevision($row['review_revision']);
		$reviewAssignment->setReviewFormId($row['review_form_id']);

		// Files
		$reviewAssignment->setReviewFile($this->paperFileDao->getPaperFile($row['review_file_id'], $row['review_revision']));
		$reviewAssignment->setReviewerFile($this->paperFileDao->getPaperFile($row['reviewer_file_id']));
		$reviewAssignment->setReviewerFileRevisions($this->paperFileDao->getPaperFileRevisions($row['reviewer_file_id']));
		$reviewAssignment->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));


		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		HookRegistry::call('ReviewAssignmentDAO::_returnReviewAssignmentFromRow', array(&$reviewAssignment, &$row));

		return $reviewAssignment;
	}

	/**
	 * Insert a new Review Assignment.
	 * @param $reviewAssignment ReviewAssignment
	 */	
	function insertReviewAssignment(&$reviewAssignment) {
		$this->update(
			sprintf('INSERT INTO review_assignments
				(paper_id, reviewer_id, stage, recommendation, declined, replaced, cancelled, date_assigned, date_notified, date_confirmed, date_completed, date_acknowledged, date_due, reviewer_file_id, quality, date_rated, last_modified, date_reminded, reminder_was_automatic, review_form_id)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, %s, %s, %s, %s, %s, %s, ?, ?, %s, %s, %s, ?, ?)',
				$this->datetimeToDB($reviewAssignment->getDateAssigned()), $this->datetimeToDB($reviewAssignment->getDateNotified()), $this->datetimeToDB($reviewAssignment->getDateConfirmed()), $this->datetimeToDB($reviewAssignment->getDateCompleted()), $this->datetimeToDB($reviewAssignment->getDateAcknowledged()), $this->datetimeToDB($reviewAssignment->getDateDue()), $this->datetimeToDB($reviewAssignment->getDateRated()), $this->datetimeToDB($reviewAssignment->getLastModified()), $this->datetimeToDB($reviewAssignment->getDateReminded())),
			array(
				(int) $reviewAssignment->getPaperId(),
				(int) $reviewAssignment->getReviewerId(),
				max((int) $reviewAssignment->getStage(), 1),
				$reviewAssignment->getRecommendation(),
				(int) $reviewAssignment->getDeclined(),
				(int) $reviewAssignment->getReplaced(),
				(int) $reviewAssignment->getCancelled(),
				$reviewAssignment->getReviewerFileId(),
				$reviewAssignment->getQuality(),
				$reviewAssignment->getReminderWasAutomatic(),
				$reviewAssignment->getReviewFormId()
			)
		);

		$reviewAssignment->setId($this->getInsertReviewId());
		return $reviewAssignment->getId();
	}

	/**
	 * Update an existing review assignment.
	 * @param $reviewAssignment object
	 */
	function updateReviewAssignment(&$reviewAssignment) {
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
					quality = ?,
					date_rated = %s,
					last_modified = %s,
					date_reminded = %s,
					reminder_was_automatic = ?,
					review_form_id = ?
				WHERE review_id = ?',
				$this->datetimeToDB($reviewAssignment->getDateAssigned()), $this->datetimeToDB($reviewAssignment->getDateNotified()), $this->datetimeToDB($reviewAssignment->getDateConfirmed()), $this->datetimeToDB($reviewAssignment->getDateCompleted()), $this->datetimeToDB($reviewAssignment->getDateAcknowledged()), $this->datetimeToDB($reviewAssignment->getDateDue()), $this->datetimeToDB($reviewAssignment->getDateRated()), $this->datetimeToDB($reviewAssignment->getLastModified()), $this->datetimeToDB($reviewAssignment->getDateReminded())),
			array(
				(int) $reviewAssignment->getPaperId(),
				(int) $reviewAssignment->getReviewerId(),
				(int) $reviewAssignment->getStage(),
				$reviewAssignment->getRecommendation(),
				(int) $reviewAssignment->getDeclined(),
				(int) $reviewAssignment->getReplaced(),
				(int) $reviewAssignment->getCancelled(),
				$reviewAssignment->getReviewerFileId(),
				$reviewAssignment->getQuality(),
				$reviewAssignment->getReminderWasAutomatic(),
				$reviewAssignment->getReviewFormId(),
				(int) $reviewAssignment->getId()
			)
		);
	}

	/**
	 * Delete review assignment.
	 * @param $reviewId int
	 */
	function deleteReviewAssignmentById($reviewId) {
		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponseDao->deleteByReviewId($reviewId);

		return $this->update(
			'DELETE FROM review_assignments WHERE review_id = ?',
			(int) $reviewId
		);
	}

	/**
	 * Delete review assignments by paper.
	 * @param $paperId int
	 * @return boolean
	 */
	function deleteReviewAssignmentsByPaper($paperId) {
		$returner = false;
		$result =& $this->retrieve(
			'SELECT review_id FROM review_assignments WHERE paper_id = ?',
			(int) $paperId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$reviewId = $row['review_id'];

			$this->update('DELETE FROM review_form_responses WHERE review_id = ?', $reviewId);
			$this->update('DELETE FROM review_assignments WHERE review_id = ?', $reviewId);

			$result->MoveNext();
			$returner = true;
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get the ID of the last inserted review assignment.
	 * @return int
	 */
	function getInsertReviewId() {
		return $this->getInsertId('review_assignments', 'review_id');
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a scheduled conference.
	 * @return array
	 */
	function getAverageQualityRatings($schedConfId) {
		$averageQualityRatings = Array();
		$result =& $this->retrieve(
			'SELECT R.reviewer_id, AVG(R.quality) AS average, COUNT(R.quality) AS count FROM review_assignments R, papers A WHERE R.paper_id = A.paper_id AND A.sched_conf_id = ? GROUP BY R.reviewer_id',
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
			'SELECT r.reviewer_id, COUNT(r.review_id) AS count FROM review_assignments r, papers a WHERE r.paper_id = a.paper_id AND a.sched_conf_id = ? AND r.date_completed IS NOT NULL AND r.cancelled = 0 GROUP BY r.reviewer_id',
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
}

?>
