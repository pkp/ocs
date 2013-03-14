<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignmentDAO.inc.php
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
		$this->paperFileDao = DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao = DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao = DAORegistry::getDAO('PaperCommentDAO');
	}

	/**
	 * Return the review file ID for a submission, given its submission ID.
	 * @param $submissionId int
	 * @return int
	 */
	function _getSubmissionReviewFileId($submissionId) {
		$result =& $this->retrieve(
			'SELECT review_file_id FROM papers WHERE paper_id = ?',
			(int) $submissionId
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : null;
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
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getById($reviewId);
		return $returner;
	}

	/**
	 * Get all review assignments for a paper.
	 * @param $paperId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByPaperId($paperId, $round = null) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getBySubmissionId($paperId, $round);
		return $returner;
	}

	/**
	 * Get all review assignments for a reviewer.
	 * @param $userId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByUserId($userId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getByUserId($userId);
		return $returner;
	}

	/**
	 * Get all review assignments for a review form.
	 * @param $reviewFormId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByReviewFormId($reviewFormId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		$returner =& $this->getByReviewFormId($reviewFormId);
		return $returner;
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

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ReviewAssignment
	 */
	function newDataObject() {
		return new ReviewAssignment();
	}

	/**
	 * Internal function to return a review assignment object from a row.
	 * @param $row array
	 * @return ReviewAssignment
	 */
	function &_fromRow(&$row) {
		$reviewAssignment =& parent::_fromRow($row);
		$reviewFileId = $this->_getSubmissionReviewFileId($reviewAssignment->getSubmissionId());
		$reviewAssignment->setReviewFileId($reviewFileId);

		// Files
		$reviewAssignment->setReviewFile($this->paperFileDao->getPaperFile($reviewFileId, $row['review_revision']));
		$reviewAssignment->setReviewerFile($this->paperFileDao->getPaperFile($row['reviewer_file_id']));
		$reviewAssignment->setReviewerFileRevisions($this->paperFileDao->getPaperFileRevisions($row['reviewer_file_id']));
		$reviewAssignment->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['submission_id']));

		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->paperCommentDao->getMostRecentPaperComment($row['submission_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		HookRegistry::call('ReviewAssignmentDAO::_fromRow', array(&$reviewAssignment, &$row));
		return $reviewAssignment;
	}

	/**
	* @see PKPReviewAssignmentDAO::getReviewRoundJoin()
	*/
	function getReviewRoundJoin() {
		return 'r.submission_id = r2.submission_id AND r.round = r2.round';
	}
}

?>
