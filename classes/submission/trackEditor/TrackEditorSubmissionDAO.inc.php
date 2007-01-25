<?php

/**
 * TrackEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for TrackEditorSubmission DAO.
 * Operations for retrieving and modifying TrackEditorSubmission objects.
 *
 * $Id$
 */

import('submission.trackEditor.TrackEditorSubmission');
import('submission.author.AuthorSubmission'); // Bring in editor decision constants
import('submission.reviewer.ReviewerSubmission'); // Bring in editor decision constants

class TrackEditorSubmissionDAO extends DAO {

	var $paperDao;
	var $authorDao;
	var $userDao;
	var $editAssignmentDao;
	var $reviewAssignmentDao;
	var $layoutAssignmentDao;
	var $paperFileDao;
	var $suppFileDao;
	var $galleyDao;
	var $paperEmailLogDao;
	var $paperCommentDao;

	/**
	 * Constructor.
	 */
	function TrackEditorSubmissionDAO() {
		parent::DAO();
		$this->paperDao = &DAORegistry::getDAO('PaperDAO');
		$this->authorDao = &DAORegistry::getDAO('AuthorDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$this->paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$this->paperEmailLogDao = &DAORegistry::getDAO('PaperEmailLogDAO');
		$this->paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
	}

	/**
	 * Retrieve a track editor submission by paper ID.
	 * @param $paperId int
	 * @return EditorSubmission
	 */
	function &getTrackEditorSubmission($paperId) {
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
			$returner = &$this->_returnTrackEditorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a TrackEditorSubmission object from a row.
	 * @param $row array
	 * @return TrackEditorSubmission
	 */
	function &_returnTrackEditorSubmissionFromRow(&$row) {
		$trackEditorSubmission = &new TrackEditorSubmission();

		// Paper attributes
		$this->paperDao->_paperFromRow($trackEditorSubmission, $row);

		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$trackEditorSubmission->setEditAssignments($editAssignments->toArray());

		// Editor Decisions
		for ($i = 1; $i <= $row['review_progress']; $i++) {
			for ($j = 1; $j <= $row['current_round']; $j++) {
				$trackEditorSubmission->setDecisions($this->getEditorDecisions($row['paper_id'], $i, $j), $i, $j);
			}
		}

		// Comments
		$trackEditorSubmission->setMostRecentEditorDecisionComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_EDITOR_DECISION, $row['paper_id']));
		$trackEditorSubmission->setMostRecentLayoutComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_LAYOUT, $row['paper_id']));

		// Files
		$trackEditorSubmission->setSubmissionFile($this->paperFileDao->getPaperFile($row['submission_file_id']));
		$trackEditorSubmission->setRevisedFile($this->paperFileDao->getPaperFile($row['revised_file_id']));
		$trackEditorSubmission->setReviewFile($this->paperFileDao->getPaperFile($row['review_file_id']));
		$trackEditorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));
		$trackEditorSubmission->setEditorFile($this->paperFileDao->getPaperFile($row['editor_file_id']));

		for ($i = 1; $i <= $row['current_round']; $i++) {
			$trackEditorSubmission->setEditorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['editor_file_id'], $i), $i);
			$trackEditorSubmission->setAuthorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['revised_file_id'], $i), $i);
		}

		// Review Rounds
		$trackEditorSubmission->setReviewRevision($row['review_revision']);

		// Review Assignments
		for ($i = 1; $i <= $row['review_progress']; $i++)
			for ($j = 1; $j <= $row['current_round']; $j++)
				$trackEditorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByPaperId($row['paper_id'], $i, $j), $i, $j);

		// Layout Editing
		$trackEditorSubmission->setLayoutAssignment($this->layoutAssignmentDao->getLayoutAssignmentByPaperId($row['paper_id']));

		$trackEditorSubmission->setGalleys($this->galleyDao->getGalleysByPaper($row['paper_id']));

		HookRegistry::call('TrackEditorSubmissionDAO::_returnTrackEditorSubmissionFromRow', array(&$trackEditorSubmission, &$row));

		return $trackEditorSubmission;
	}

	/**
	 * Update an existing track editor submission.
	 * @param $trackEditorSubmission TrackEditorSubmission
	 */
	function updateTrackEditorSubmission(&$trackEditorSubmission) {
		// update edit assignment
		$editAssignments =& $trackEditorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment($editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment($editAssignment);
			}
		}

		// Update editor decisions; hacked necessarily to iterate by reference.
		for ($i = 1; $i <= $trackEditorSubmission->getReviewProgress(); $i++) {
			for ($j = 1; $j <= $trackEditorSubmission->getCurrentRound(); $j++) {
				$editorDecisions = $trackEditorSubmission->getDecisions($i, $j);
				$insertedDecision = false;
				if (is_array($editorDecisions)) {
					for ($k = 0; $k < count($editorDecisions); $k++) {
						$editorDecision =& $editorDecisions[$k];
						if ($editorDecision['editDecisionId'] == null) {
							$this->update(
								sprintf('INSERT INTO edit_decisions
									(paper_id, type, round, editor_id, decision, date_decided)
									VALUES (?, ?, ?, ?, ?, %s)',
									$this->datetimeToDB($editorDecision['dateDecided'])),
								array($trackEditorSubmission->getPaperId(),
									$i,
									$j,
									$editorDecision['editorId'], $editorDecision['decision'])
							);
							$insertId = $this->getInsertId('edit_decisions', 'edit_decision_id');
							$editorDecision['editDecisionId'] = $insertId;
							$insertedDecision = true;
						}
						unset($editorDecision);
					}
				}
				if ($insertedDecision) {
					$trackEditorSubmission->setDecisions($editorDecisions, $i, $j);
				}
			}
		}
		if ($this->reviewRoundExists($trackEditorSubmission->getPaperId(), $trackEditorSubmission->getReviewProgress(), $trackEditorSubmission->getCurrentRound())) {
			$this->update(
				'UPDATE review_rounds
					SET
						review_revision = ?
					WHERE paper_id = ? AND round = ?',
				array(
					$trackEditorSubmission->getReviewRevision(),
					$trackEditorSubmission->getPaperId(),
					$trackEditorSubmission->getCurrentRound()
				)
			);
		} elseif ($trackEditorSubmission->getReviewRevision()!=null) {
			$this->createReviewRound(
				$trackEditorSubmission->getPaperId(),
				$trackEditorSubmission->getReviewProgress() === null ? 1 : $trackEditorSubmission->getReviewProgress(),
				$trackEditorSubmission->getCurrentRound() === null ? 1 : $trackEditorSubmission->getCurrentRound(),
				$trackEditorSubmission->getReviewRevision()
			);
		}

		// update review assignments
		foreach ($trackEditorSubmission->getReviewAssignments(null, null) as $typeReviewAssignments) {
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
		$removedReviewAssignments = $trackEditorSubmission->getRemovedReviewAssignments();
		for ($i=0, $count=count($removedReviewAssignments); $i < $count; $i++) {
			$this->reviewAssignmentDao->deleteReviewAssignmentById($removedReviewAssignments[$i]->getReviewId());
		}

		// Update layout editing assignment
		$layoutAssignment =& $trackEditorSubmission->getLayoutAssignment();
		if (isset($layoutAssignment)) {
			if ($layoutAssignment->getLayoutId() > 0) {
				$this->layoutAssignmentDao->updateLayoutAssignment($layoutAssignment);
			} else {
				$this->layoutAssignmentDao->insertLayoutAssignment($layoutAssignment);
			}
		}
		
		// Update paper
		if ($trackEditorSubmission->getPaperId()) {

			$paper = &$this->paperDao->getPaper($trackEditorSubmission->getPaperId());

			// Only update fields that can actually be edited.
			$paper->setTrackId($trackEditorSubmission->getTrackId());
			$paper->setReviewProgress($trackEditorSubmission->getReviewProgress());
			$paper->setCurrentRound($trackEditorSubmission->getCurrentRound());
			$paper->setReviewFileId($trackEditorSubmission->getReviewFileId());
			$paper->setEditorFileId($trackEditorSubmission->getEditorFileId());
			$paper->setStatus($trackEditorSubmission->getStatus());
			$paper->setDateStatusModified($trackEditorSubmission->getDateStatusModified());
			$paper->setLastModified($trackEditorSubmission->getLastModified());

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
	 * Get all track editor submissions for a track editor.
	 * @param $trackEditorId int
	 * @param $eventId int
	 * @param $status boolean true if active, false if completed.
	 * @return array TrackEditorSubmission
	 */
	function &getTrackEditorSubmissions($trackEditorId, $eventId, $status = true) {
		$trackEditorSubmissions = array();

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
			WHERE p.event_id = ? AND e.editor_id = ? AND p.status = ?',
			array($eventId, $trackEditorId, $status)
		);

		while (!$result->EOF) {
			$trackEditorSubmissions[] = &$this->_returnTrackEditorSubmissionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $trackEditorSubmissions;
	}

	/**
	 * Retrieve unfiltered track editor submissions
	 */
	function &getUnfilteredTrackEditorSubmissions($trackEditorId, $eventId, $trackId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $status = true, $rangeInfo = null) {
		$params = array($eventId, $trackEditorId);

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
			case SUBMISSION_FIELD_AUTHOR:
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
					$searchSql .= ' AND p.date_submitted >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND p.date_submitted <= ' . $this->datetimeToDB($dateTo);
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
			INNER JOIN paper_authors pa ON (pa.paper_id = p.paper_id)
			LEFT JOIN edit_assignments e ON (e.paper_id = p.paper_id)
			LEFT JOIN users ed ON (e.editor_id = ed.user_id)
			LEFT JOIN tracks t ON (t.track_id = p.track_id)
			LEFT JOIN review_rounds r2 ON (p.paper_id = r2.paper_id and p.current_round = r2.round AND p.review_progress = r2.type)
			LEFT JOIN layouted_assignments l ON (l.paper_id = p.paper_id) LEFT JOIN users le ON (le.user_id = l.editor_id)
			WHERE
				p.event_id = ? AND e.editor_id = ?';

		// "Active" submissions have a status of SUBMISSION_STATUS_QUEUED and
		// the layout editor has not yet been acknowledged.
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
	 * @param $trackEditorId int
	 * @param $eventId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getTrackEditorSubmissionsInReview($trackEditorId, $eventId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredTrackEditorSubmissions($trackEditorId, $eventId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submission = &$this->_returnTrackEditorSubmissionFromRow($row);
			$paperId = $submission->getPaperId();

			// check if submission is still in review
			$inReview = true;
			$decisions = $submission->getDecisions();
			$type = array_pop($decisions);
			$decision = array_pop($type);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
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
	 * @param $trackEditorId int
	 * @param $eventId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getTrackEditorSubmissionsInEditing($trackEditorId, $eventId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredTrackEditorSubmissions($trackEditorId, $eventId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submission = &$this->_returnTrackEditorSubmissionFromRow($row);

			// check if submission is still in review
			$inReview = true;
			$decisions = $submission->getDecisions();
			$types = array_pop($decisions);
			$decision = array_pop($types);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
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
	 * @param $trackEditorId int
	 * @param $eventId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getTrackEditorSubmissionsArchives($trackEditorId, $eventId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredTrackEditorSubmissions($trackEditorId, $eventId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, false);

		while (!$result->EOF) {
			$submission = &$this->_returnTrackEditorSubmissionFromRow($result->GetRowAssoc(false));

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
	 * @param $trackEditorId int
	 * @param $eventId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array EditorSubmission
	 */
	function &getTrackEditorSubmissionsAccepted($trackEditorId, $eventId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$submissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredTrackEditorSubmissions($trackEditorId, $eventId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, false);

		while (!$result->EOF) {
			$submission = &$this->_returnTrackEditorSubmissionFromRow($result->GetRowAssoc(false));

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
	function &getTrackEditorSubmissionsCount($trackEditorId, $eventId) {

		$submissionsCount = array();
		for($i = 0; $i < 4; $i++) {
			$submissionsCount[$i] = 0;
		}

		$result = $this->getUnfilteredTrackEditorSubmissions($trackEditorId, $eventId);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$trackEditorSubmission = &$this->_returnTrackEditorSubmissionFromRow($row);

			// check if submission is still in review
			$inReview = true;
			$notDeclined = true;
			$decisions = $trackEditorSubmission->getDecisions();
			$type = array_pop($decisions);
			$decision = array_pop($type);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) {
					$inReview = false;
				} elseif ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
					$notDeclined = false;
				}
			}

			if (!$trackEditorSubmission->getSubmissionProgress()) {
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
			unset($trackEditorDecision);
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
	 * Get the editor decisions for a review round of an paper.
	 * @param $paperId int
	 * @param $round int
	 * @param $type int
	 */
	function getEditorDecisions($paperId, $type, $round) {
		$decisions = array();
		
		$params = array($paperId);
		if($type != null) $params[] = $type;
		if($round != null) $params[] = $round;
		
		$result = &$this->retrieve('
			SELECT edit_decision_id, editor_id, decision, date_decided
				FROM edit_decisions
				WHERE paper_id = ?'
					. ($type == NULL ? '' : ' AND type = ?')
					. ($round == NULL ? '' : ' AND round = ?')
				. ' ORDER BY edit_decision_id ASC',
				count($params) == 1 ? shift($params) : $params);

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
	 * @param $eventId int
	 * @param $paperId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getReviewersForPaper($eventId, $paperId, $type, $round, $searchType = null, $search = null, $searchMatch = null, $rangeInfo = null) {
		$paramArray = array($paperId, $type, $round, $eventId, RoleDAO::getRoleIdFromPath('reviewer'));
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
				AND r.event_id = ?
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

		HookRegistry::call('TrackEditorSubmissionDAO::_returnReviewerUserFromRow', array(&$user, &$row));

		return $user;
	}

	/**
	 * Retrieve a list of all reviewers not assigned to the specified paper.
	 * @param $eventId int
	 * @param $paperId int
	 * @return array matching Users
	 */
	function &getReviewersNotAssignedToPaper($eventId, $paperId) {
		$users = array();

		$result = &$this->retrieve(
			'SELECT u.* FROM users u NATURAL JOIN roles r LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.paper_id = ?) WHERE r.event_id = ? AND r.role_id = ? AND a.paper_id IS NULL ORDER BY last_name, first_name',
			array($paperId, $eventId, RoleDAO::getRoleIdFromPath('reviewer'))
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
	function getReviewerStatistics($eventId) {
		$statistics = Array();

		// Get counts of completed submissions
		$result = &$this->retrieve('select ra.reviewer_id as editor_id, max(ra.date_notified) as last_notified from review_assignments ra, papers p where ra.paper_id=p.paper_id and p.event_id=? group by ra.reviewer_id', $eventId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['editor_id']])) $statistics[$row['editor_id']] = array();
			$statistics[$row['editor_id']]['last_notified'] = $this->datetimeFromDB($row['last_notified']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get completion status
		$result = &$this->retrieve('select r.reviewer_id as reviewer_id, COUNT(*) AS incomplete from review_assignments r, papers p where r.paper_id=p.paper_id and r.date_notified is not null and r.date_completed is null and r.cancelled = 0 and p.event_id = ? group by r.reviewer_id', $eventId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Calculate time taken for completed reviews
		$result = &$this->retrieve('select r.reviewer_id as reviewer_id, r.date_notified as date_notified, r.date_completed as date_completed from review_assignments r, papers p where r.paper_id=p.paper_id and r.date_notified is not null and r.date_completed is not null and p.event_id = ?', $eventId);
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
