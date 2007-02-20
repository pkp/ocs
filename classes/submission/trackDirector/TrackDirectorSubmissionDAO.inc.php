<?php

/**
 * TrackDirectorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for TrackDirectorSubmission DAO.
 * Operations for retrieving and modifying TrackDirectorSubmission objects.
 *
 * $Id$
 */

import('submission.trackDirector.TrackDirectorSubmission');
import('submission.presenter.PresenterSubmission'); // Bring in director decision constants
import('submission.reviewer.ReviewerSubmission'); // Bring in director decision constants

class TrackDirectorSubmissionDAO extends DAO {

	var $paperDao;
	var $presenterDao;
	var $userDao;
	var $editAssignmentDao;
	var $reviewAssignmentDao;
	var $paperFileDao;
	var $suppFileDao;
	var $galleyDao;
	var $paperEmailLogDao;
	var $paperCommentDao;

	/**
	 * Constructor.
	 */
	function TrackDirectorSubmissionDAO() {
		parent::DAO();
		$this->paperDao = &DAORegistry::getDAO('PaperDAO');
		$this->presenterDao = &DAORegistry::getDAO('PresenterDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$this->paperEmailLogDao = &DAORegistry::getDAO('PaperEmailLogDAO');
		$this->paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
	}

	/**
	 * Retrieve a track director submission by paper ID.
	 * @param $paperId int
	 * @return DirectorSubmission
	 */
	function &getTrackDirectorSubmission($paperId) {
		$result = &$this->retrieve(
			'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2,
				r2.review_revision
				FROM papers p
					LEFT JOIN tracks t ON (t.track_id = p.track_id)
					LEFT JOIN review_rounds r2 ON (p.paper_id = r2.paper_id AND p.current_round = r2.round AND p.review_progress = r2.type)
				WHERE p.paper_id = ?', $paperId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnTrackDirectorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a TrackDirectorSubmission object from a row.
	 * @param $row array
	 * @return TrackDirectorSubmission
	 */
	function &_returnTrackDirectorSubmissionFromRow(&$row) {
		$trackDirectorSubmission = &new TrackDirectorSubmission();

		// Paper attributes
		$this->paperDao->_paperFromRow($trackDirectorSubmission, $row);

		// Director Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$trackDirectorSubmission->setEditAssignments($editAssignments->toArray());

		// Director Decisions
		for ($i = 1; $i <= $row['review_progress']; $i++) {
			for ($j = 1; $j <= $row['current_round']; $j++) {
				$trackDirectorSubmission->setDecisions($this->getDirectorDecisions($row['paper_id'], $i, $j), $i, $j);
			}
		}

		// Comments
		$trackDirectorSubmission->setMostRecentDirectorDecisionComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_DIRECTOR_DECISION, $row['paper_id']));

		// Files
		$trackDirectorSubmission->setSubmissionFile($this->paperFileDao->getPaperFile($row['submission_file_id']));
		$trackDirectorSubmission->setRevisedFile($this->paperFileDao->getPaperFile($row['revised_file_id']));
		$trackDirectorSubmission->setReviewFile($this->paperFileDao->getPaperFile($row['review_file_id']));
		$trackDirectorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));
		$trackDirectorSubmission->setDirectorFile($this->paperFileDao->getPaperFile($row['director_file_id']));
		$trackDirectorSubmission->setLayoutFile($this->paperFileDao->getPaperFile($row['layout_file_id']));

		for ($i = 1; $i <= $row['current_round']; $i++) {
			$trackDirectorSubmission->setDirectorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['director_file_id'], $i), $i);
			$trackDirectorSubmission->setPresenterFileRevisions($this->paperFileDao->getPaperFileRevisions($row['revised_file_id'], $i), $i);
		}

		// Review Rounds
		$trackDirectorSubmission->setReviewRevision($row['review_revision']);

		// Review Assignments
		for ($i = 1; $i <= $row['review_progress']; $i++)
			for ($j = 1; $j <= $row['current_round']; $j++)
				$trackDirectorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByPaperId($row['paper_id'], $i, $j), $i, $j);

		$trackDirectorSubmission->setGalleys($this->galleyDao->getGalleysByPaper($row['paper_id']));

		HookRegistry::call('TrackDirectorSubmissionDAO::_returnTrackDirectorSubmissionFromRow', array(&$trackDirectorSubmission, &$row));

		return $trackDirectorSubmission;
	}

	/**
	 * Update an existing track director submission.
	 * @param $trackDirectorSubmission TrackDirectorSubmission
	 */
	function updateTrackDirectorSubmission(&$trackDirectorSubmission) {
		// update edit assignment
		$editAssignments =& $trackDirectorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment($editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment($editAssignment);
			}
		}

		// Update director decisions; hacked necessarily to iterate by reference.
		for ($i = 1; $i <= $trackDirectorSubmission->getReviewProgress(); $i++) {
			for ($j = 1; $j <= $trackDirectorSubmission->getCurrentRound(); $j++) {
				$directorDecisions = $trackDirectorSubmission->getDecisions($i, $j);
				$insertedDecision = false;
				if (is_array($directorDecisions)) {
					for ($k = 0; $k < count($directorDecisions); $k++) {
						$directorDecision =& $directorDecisions[$k];
						if ($directorDecision['editDecisionId'] == null) {
							$this->update(
								sprintf('INSERT INTO edit_decisions
									(paper_id, type, round, director_id, decision, date_decided)
									VALUES (?, ?, ?, ?, ?, %s)',
									$this->datetimeToDB($directorDecision['dateDecided'])),
								array($trackDirectorSubmission->getPaperId(),
									$i,
									$j,
									$directorDecision['directorId'], $directorDecision['decision'])
							);
							$insertId = $this->getInsertId('edit_decisions', 'edit_decision_id');
							$directorDecision['editDecisionId'] = $insertId;
							$insertedDecision = true;
						}
						unset($directorDecision);
					}
				}
				if ($insertedDecision) {
					$trackDirectorSubmission->setDecisions($directorDecisions, $i, $j);
				}
			}
		}
		if ($this->reviewRoundExists($trackDirectorSubmission->getPaperId(), $trackDirectorSubmission->getReviewProgress(), $trackDirectorSubmission->getCurrentRound())) {
			$this->update(
				'UPDATE review_rounds
					SET
						review_revision = ?
					WHERE paper_id = ? AND round = ?',
				array(
					$trackDirectorSubmission->getReviewRevision(),
					$trackDirectorSubmission->getPaperId(),
					$trackDirectorSubmission->getCurrentRound()
				)
			);
		} elseif ($trackDirectorSubmission->getReviewRevision()!=null) {
			$this->createReviewRound(
				$trackDirectorSubmission->getPaperId(),
				$trackDirectorSubmission->getReviewProgress() === null ? 1 : $trackDirectorSubmission->getReviewProgress(),
				$trackDirectorSubmission->getCurrentRound() === null ? 1 : $trackDirectorSubmission->getCurrentRound(),
				$trackDirectorSubmission->getReviewRevision()
			);
		}

		// update review assignments
		foreach ($trackDirectorSubmission->getReviewAssignments(null, null) as $typeReviewAssignments) {
			foreach ($typeReviewAssignments as $roundReviewAssignments) {
				foreach ($roundReviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getReviewId() > 0) {
						$this->reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
					} else {
						$this->reviewAssignmentDao->insertReviewAssignment($reviewAssignment);
					}
				}
			}
		}

		// Remove deleted review assignments
		$removedReviewAssignments = $trackDirectorSubmission->getRemovedReviewAssignments();
		for ($i=0, $count=count($removedReviewAssignments); $i < $count; $i++) {
			$this->reviewAssignmentDao->deleteReviewAssignmentById($removedReviewAssignments[$i]->getReviewId());
		}

		// Update paper
		if ($trackDirectorSubmission->getPaperId()) {

			$paper = &$this->paperDao->getPaper($trackDirectorSubmission->getPaperId());

			// Only update fields that can actually be edited.
			$paper->setTrackId($trackDirectorSubmission->getTrackId());
			$paper->setReviewProgress($trackDirectorSubmission->getReviewProgress());
			$paper->setCurrentRound($trackDirectorSubmission->getCurrentRound());
			$paper->setReviewFileId($trackDirectorSubmission->getReviewFileId());
			$paper->setLayoutFileId($trackDirectorSubmission->getLayoutFileId());
			$paper->setDirectorFileId($trackDirectorSubmission->getDirectorFileId());
			$paper->setStatus($trackDirectorSubmission->getStatus());
			$paper->setDateStatusModified($trackDirectorSubmission->getDateStatusModified());
			$paper->setLastModified($trackDirectorSubmission->getLastModified());

			$this->paperDao->updatePaper($paper);
		}

	}

	function createReviewRound($paperId, $type, $round, $reviewRevision) {
		$this->update(
			'INSERT INTO review_rounds
				(paper_id, type, round, review_revision)
				VALUES
				(?, ?, ?, ?)',
			array($paperId, $type, $round, $reviewRevision)
		);
	}

	/**
	 * Get all track director submissions for a track director.
	 * @param $trackDirectorId int
	 * @param $schedConfId int
	 * @param $status boolean true if active, false if completed.
	 * @return array TrackDirectorSubmission
	 */
	function &getTrackDirectorSubmissions($trackDirectorId, $schedConfId, $status = true) {
		$trackDirectorSubmissions = array();

		$result = &$this->retrieve(
			'SELECT p.*,
			t.title AS track_title,
			t.title_alt1 AS track_title_alt1,
			t.title_alt2 AS track_title_alt2,
			t.abbrev AS track_abbrev,
			t.abbrev_alt1 AS track_abbrev_alt1,
			t.abbrev_alt2 AS track_abbrev_alt2,
			r2.review_revision
			FROM papers p LEFT JOIN edit_assignments e ON (e.paper_id = p.paper_id)
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN review_rounds r2 ON (p.paper_id = r2.paper_id AND p.review_progress = r2.type AND p.current_round = r2.round)
			WHERE p.sched_conf_id = ? AND e.director_id = ? AND p.status = ?',
			array($schedConfId, $trackDirectorId, $status)
		);

		while (!$result->EOF) {
			$trackDirectorSubmissions[] = &$this->_returnTrackDirectorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $trackDirectorSubmissions;
	}

	/**
	 * Retrieve unfiltered track director submissions
	 */
	function &getUnfilteredTrackDirectorSubmissions($trackDirectorId, $schedConfId, $trackId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $status = true, $rangeInfo = null) {
		$params = array($schedConfId, $trackDirectorId);

		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND (LOWER(p.title) = LOWER(?) OR LOWER(p.title_alt1) = LOWER(?) OR LOWER(p.title_alt2) = LOWER(?))';
				} else {
					$searchSql = ' AND (LOWER(p.title) LIKE LOWER(?) OR LOWER(p.title_alt1) LIKE LOWER(?) OR LOWER(p.title_alt2) LIKE LOWER(?))';
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $search;
				break;
			case SUBMISSION_FIELD_PRESENTER:
				$first_last = $this->_dataSource->Concat('pa.first_name', '\' \'', 'pa.last_name');
				$first_middle_last = $this->_dataSource->Concat('pa.first_name', '\' \'', 'pa.middle_name', '\' \'', 'pa.last_name');
				$last_comma_first = $this->_dataSource->Concat('pa.last_name', '\', \'', 'pa.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('pa.last_name', '\', \'', 'pa.first_name', '\' \'', 'pa.middle_name');

				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(aa.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} else {
					$searchSql = " AND (LOWER(aa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
			case SUBMISSION_FIELD_DIRECTOR:
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
					$searchSql .= ' AND p.date_submitted >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND p.date_submitted <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		$sql = 'SELECT DISTINCT
				p.*,
				e.can_review AS can_review,
				e.can_edit AS can_edit,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2,
				r2.review_revision
			FROM
				papers p
			INNER JOIN paper_presenters pa ON (pa.paper_id = p.paper_id)
			LEFT JOIN edit_assignments e ON (e.paper_id = p.paper_id)
			LEFT JOIN users ed ON (e.director_id = ed.user_id)
			LEFT JOIN tracks t ON (t.track_id = p.track_id)
			LEFT JOIN review_rounds r2 ON (p.paper_id = r2.paper_id and p.current_round = r2.round AND p.review_progress = r2.type)
			WHERE
				p.sched_conf_id = ? AND e.director_id = ?';

		if ($status === true) $sql .= ' AND (p.status = ' . SUBMISSION_STATUS_QUEUED . ')';
		else $sql .= ' AND (p.status <> ' . SUBMISSION_STATUS_QUEUED . ')';

		if ($trackId) {
			$params[] = $trackId;
			$searchSql .= ' AND p.track_id = ?';
		}

		$result = &$this->retrieveRange($sql . ' ' . $searchSql . ' ORDER BY paper_id ASC',
			$params,
			$rangeInfo
		);

		return $result;
	}

	/**
	 * Get all submissions in review for a conference.
	 * @param $trackDirectorId int
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getTrackDirectorSubmissionsInReview($trackDirectorId, $schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredTrackDirectorSubmissions($trackDirectorId, $schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submission = &$this->_returnTrackDirectorSubmissionFromRow($row);
			$paperId = $submission->getPaperId();

			// check if submission is still in review
			$inReview = true;
			$decisions = $submission->getDecisions();
			$type = array_pop($decisions);
			$decision = array_pop($type);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
					$inReview = false;
				}
			}

			if ($inReview && !$submission->getSubmissionProgress()) {
				$submissions[] =& $submission;
			}
			unset($submission);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($submissions);
		}
		return $returner;

	}

	/**
	 * Get all submissions in editing for a conference.
	 * @param $trackDirectorId int
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getTrackDirectorSubmissionsInEditing($trackDirectorId, $schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredTrackDirectorSubmissions($trackDirectorId, $schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submission = &$this->_returnTrackDirectorSubmissionFromRow($row);

			// check if submission is still in review
			$inReview = true;
			$decisions = $submission->getDecisions();
			$types = array_pop($decisions);
			$decision = array_pop($types);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
					$inReview = false;
				}
			}

			if (!$inReview && !$submission->getSubmissionProgress()) {
				$submissions[] =& $submission;
			}
			unset($submission);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($submissions);
		}
		return $returner;
	}

	/**
	 * Get all submissions in archives for a conference.
	 * @param $trackDirectorId int
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getTrackDirectorSubmissionsArchives($trackDirectorId, $schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredTrackDirectorSubmissions($trackDirectorId, $schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, false);

		while (!$result->EOF) {
			$submission = &$this->_returnTrackDirectorSubmissionFromRow($result->GetRowAssoc(false));

			if ($submission->getStatus() == SUBMISSION_STATUS_ARCHIVED) {
				$submissions[] =& $submission;
			}
			unset($submission);
			$result->MoveNext();
		}

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new VirtualArrayIterator($submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($submissions);
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get all submissions accepted to a conference.
	 * @param $trackDirectorId int
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getTrackDirectorSubmissionsAccepted($trackDirectorId, $schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredTrackDirectorSubmissions($trackDirectorId, $schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, false);

		while (!$result->EOF) {
			$submission = &$this->_returnTrackDirectorSubmissionFromRow($result->GetRowAssoc(false));

			if ($submission->getStatus() == SUBMISSION_STATUS_PUBLISHED) {
				$submissions[] =& $submission;
			}
			unset($submission);
			$result->MoveNext();
		}

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new VirtualArrayIterator($submissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($submissions);
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getTrackDirectorSubmissionsCount($trackDirectorId, $schedConfId) {

		$submissionsCount = array();
		for($i = 0; $i < 4; $i++) {
			$submissionsCount[$i] = 0;
		}

		$result = $this->getUnfilteredTrackDirectorSubmissions($trackDirectorId, $schedConfId);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$trackDirectorSubmission = &$this->_returnTrackDirectorSubmissionFromRow($row);

			// check if submission is still in review
			$inReview = true;
			$notDeclined = true;
			$decisions = $trackDirectorSubmission->getDecisions();
			$type = array_pop($decisions);
			$decision = array_pop($type);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_ACCEPT) {
					$inReview = false;
				} elseif ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
					$notDeclined = false;
				}
			}

			if (!$trackDirectorSubmission->getSubmissionProgress()) {
				if ($inReview) {
					if ($notDeclined && $row['can_review']) {
						// in review submissions
						$submissionsCount[0] += 1;
					}
				} else {
					// in editing submissions
					if ($row['can_edit']) {
						$submissionsCount[1] += 1;
					}
				}
			}
			unset($trackDirectorDecision);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
	}

	//
	// Miscellaneous
	//

	/**
	 * Delete editorial decisions by paper.
	 * @param $paperId int
	 */
	function deleteDecisionsByPaper($paperId) {
		return $this->update(
			'DELETE FROM edit_decisions WHERE paper_id = ?',
			$paperId
		);
	}

	/**
	 * Delete review rounds paper.
	 * @param $paperId int
	 */
	function deleteReviewRoundsByPaper($paperId) {
		return $this->update(
			'DELETE FROM review_rounds WHERE paper_id = ?',
			$paperId
		);
	}

	/**
	 * Get the director decisions for a review round of an paper.
	 * @param $paperId int
	 * @param $round int
	 * @param $type int
	 */
	function getDirectorDecisions($paperId, $type, $round) {
		$decisions = array();
		
		$params = array($paperId);
		if($type != null) $params[] = $type;
		if($round != null) $params[] = $round;
		
		$result = &$this->retrieve('
			SELECT edit_decision_id, director_id, decision, date_decided
				FROM edit_decisions
				WHERE paper_id = ?'
					. ($type == NULL ? '' : ' AND type = ?')
					. ($round == NULL ? '' : ' AND round = ?')
				. ' ORDER BY edit_decision_id ASC',
				count($params) == 1 ? shift($params) : $params);

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
	 * Get the highest review round.
	 * @param $paperId int
	 * @return int
	 */
	function getMaxReviewRound($paperId, $reviewType) {
		$result = &$this->retrieve(
			'SELECT MAX(round) FROM review_rounds WHERE paper_id = ? AND type = ?', array($paperId, $reviewType)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a review round exists for a specified paper.
	 * @param $paperId int
	 * @param $round int
	 * @return boolean
	 */
	function reviewRoundExists($paperId, $type, $round) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM review_rounds WHERE paper_id = ? AND type = ? and round = ?', array($paperId, $type, $round)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a reviewer is assigned to a specified paper.
	 * @param $paperId int
	 * @param $reviewerId int
	 * @return boolean
	 */
	function reviewerExists($paperId, $reviewerId, $type, $round) {
		$result = &$this->retrieve('
			SELECT COUNT(*)
			FROM review_assignments
			WHERE paper_id = ?
				AND reviewer_id = ?
				AND type = ?
				AND round = ?
				AND cancelled = 0',
			array($paperId, $reviewerId, $type, $round)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a list of all reviewers along with information about their current status with respect to an paper's current round.
	 * @param $schedConfId int
	 * @param $paperId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getReviewersForPaper($schedConfId, $paperId, $type, $round, $searchType = null, $search = null, $searchMatch = null, $rangeInfo = null) {
		$paramArray = array($paperId, $type, $round, $schedConfId, RoleDAO::getRoleIdFromPath('reviewer'));
		$searchSql = '';

		if (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND LOWER(username) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND LOWER(email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND LOWER(interests) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(last_name) LIKE LOWER(?) OR LOWER(username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}

		$result = &$this->retrieveRange('
			SELECT DISTINCT u.*, a.review_id as review_id
			FROM users u
			NATURAL JOIN roles r
			LEFT JOIN review_assignments a ON
				(a.reviewer_id = u.user_id
				 AND a.cancelled = 0
				 AND a.paper_id = ?
				 AND a.type = ?
				 AND a.round = ?)
			WHERE u.user_id = r.user_id
				AND r.sched_conf_id = ?
				AND r.role_id = ? ' . $searchSql . '
			ORDER BY last_name, first_name',
			$paramArray, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;
	}

	function &_returnReviewerUserFromRow(&$row) { // FIXME
		$user = &$this->userDao->_returnUserFromRow($row);
		$user->review_id = $row['review_id'];

		HookRegistry::call('TrackDirectorSubmissionDAO::_returnReviewerUserFromRow', array(&$user, &$row));

		return $user;
	}

	/**
	 * Retrieve a list of all reviewers not assigned to the specified paper.
	 * @param $schedConfId int
	 * @param $paperId int
	 * @return array matching Users
	 */
	function &getReviewersNotAssignedToPaper($schedConfId, $paperId) {
		$users = array();

		$result = &$this->retrieve(
			'SELECT u.* FROM users u NATURAL JOIN roles r LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.paper_id = ?) WHERE r.sched_conf_id = ? AND r.role_id = ? AND a.paper_id IS NULL ORDER BY last_name, first_name',
			array($paperId, $schedConfId, RoleDAO::getRoleIdFromPath('reviewer'))
		);

		while (!$result->EOF) {
			$users[] = &$this->userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $users;
	}

	/**
	 * Get the last assigned and last completed dates for all reviewers of the given conference.
	 * @return array
	 */
	function getReviewerStatistics($schedConfId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('select ra.reviewer_id as reviewer_id, max(ra.date_notified) as last_notified from review_assignments ra, papers p where ra.paper_id=p.paper_id and p.sched_conf_id=? group by ra.reviewer_id', $schedConfId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['last_notified'] = $this->datetimeFromDB($row['last_notified']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get completion status
		$result = &$this->retrieve('select r.reviewer_id as reviewer_id, COUNT(*) AS incomplete from review_assignments r, papers p where r.paper_id=p.paper_id and r.date_notified is not null and r.date_completed is null and r.cancelled = 0 and p.sched_conf_id = ? group by r.reviewer_id', $schedConfId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Calculate time taken for completed reviews
		$result = &$this->retrieve('select r.reviewer_id as reviewer_id, r.date_notified as date_notified, r.date_completed as date_completed from review_assignments r, papers p where r.paper_id=p.paper_id and r.date_notified is not null and r.date_completed is not null and p.sched_conf_id = ?', $schedConfId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();

			$completed = strtotime($this->datetimeFromDB($row['date_completed']));
			$notified = strtotime($this->datetimeFromDB($row['date_notified']));
			if (isset($statistics[$row['reviewer_id']]['total_span'])) {
				$statistics[$row['reviewer_id']]['total_span'] += $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] += 1;
			} else {
				$statistics[$row['reviewer_id']]['total_span'] = $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] = 1;
			}

			// Calculate the average length of review in weeks.
			$statistics[$row['reviewer_id']]['average_span'] = (($statistics[$row['reviewer_id']]['total_span'] / $statistics[$row['reviewer_id']]['completed_review_count']) / 60 / 60 / 24 / 7);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $statistics;
	}

}

?>
