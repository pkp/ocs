<?php

/**
 * @file TrackDirectorSubmissionDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackDirectorSubmissionDAO
 * @ingroup submission
 * @see TrackDirectorSubmission
 *
 * @brief Operations for retrieving and modifying TrackDirectorSubmission objects.
 *
 */

// $Id$


import('submission.trackDirector.TrackDirectorSubmission');

// Bring in director decision constants
import('submission.common.Action');
import('submission.author.AuthorSubmission');
import('submission.reviewer.ReviewerSubmission');

class TrackDirectorSubmissionDAO extends DAO {
	var $paperDao;
	var $authorDao;
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
		$this->paperDao =& DAORegistry::getDAO('PaperDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$this->paperEmailLogDao =& DAORegistry::getDAO('PaperEmailLogDAO');
		$this->paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
	}

	/**
	 * Retrieve a track director submission by paper ID.
	 * @param $paperId int
	 * @return DirectorSubmission
	 */
	function &getTrackDirectorSubmission($paperId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result =& $this->retrieve(
			'SELECT	p.*,
				r2.review_revision,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN review_stages r2 ON (p.paper_id = r2.paper_id AND p.current_stage = r2.stage)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
				LEFT JOIN paper_settings sts ON (p.paper_id = sts.paper_id AND sts.setting_name = \'sessionType\')
			WHERE	p.paper_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$paperId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnTrackDirectorSubmissionFromRow($result->GetRowAssoc(false));
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
		$trackDirectorSubmission = new TrackDirectorSubmission();

		// Paper attributes
		$this->paperDao->_paperFromRow($trackDirectorSubmission, $row);

		// Director Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$trackDirectorSubmission->setEditAssignments($editAssignments->toArray());

		// Director Decisions
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$trackDirectorSubmission->setDecisions($this->getDirectorDecisions($row['paper_id'], $i), $i);
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

		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$trackDirectorSubmission->setDirectorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['director_file_id'], $i), $i);
			$trackDirectorSubmission->setAuthorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['revised_file_id'], $i), $i);
		}

		// Review Stages
		$trackDirectorSubmission->setReviewRevision($row['review_revision']);

		// Review Assignments
		for ($i = 1; $i <= $row['current_stage']; $i++)
			$trackDirectorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByPaperId($row['paper_id'], $i), $i);

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
		foreach (array(REVIEW_STAGE_ABSTRACT, REVIEW_STAGE_PRESENTATION) as $i) {
			$directorDecisions = $trackDirectorSubmission->getDecisions($i);
			$insertedDecision = false;
			if (is_array($directorDecisions)) {
				for ($j = 0; $j < count($directorDecisions); $j++) {
					$directorDecision =& $directorDecisions[$j];
					if ($directorDecision['editDecisionId'] == null) {
						$this->update(
							sprintf('INSERT INTO edit_decisions
								(paper_id, stage, director_id, decision, date_decided)
								VALUES (?, ?, ?, ?, %s)',
									$this->datetimeToDB($directorDecision['dateDecided'])),
							array($trackDirectorSubmission->getPaperId(),
								$i,
								$directorDecision['directorId'],
								$directorDecision['decision']
							)
						);
						$insertId = $this->getInsertId('edit_decisions', 'edit_decision_id');
						$directorDecision['editDecisionId'] = $insertId;
						$insertedDecision = true;
					}
					unset($directorDecision);
				}
			}
			if ($insertedDecision) {
				$trackDirectorSubmission->setDecisions($directorDecisions, $i);
			}
		}
		if ($this->reviewStageExists($trackDirectorSubmission->getPaperId(), $trackDirectorSubmission->getCurrentStage())) {
			$this->update(
				'UPDATE	review_stages
				SET	review_revision = ?
				WHERE	paper_id = ? AND
					stage = ?',
				array(
					$trackDirectorSubmission->getReviewRevision(),
					$trackDirectorSubmission->getPaperId(),
					$trackDirectorSubmission->getCurrentStage()
				)
			);
		} elseif ($trackDirectorSubmission->getReviewRevision()!=null) {
			$this->createReviewStage(
				$trackDirectorSubmission->getPaperId(),
				$trackDirectorSubmission->getCurrentStage() === null ? 1 : $trackDirectorSubmission->getCurrentStage(),
				$trackDirectorSubmission->getReviewRevision()
			);
		}

		// update review assignments
		foreach ($trackDirectorSubmission->getReviewAssignments(null) as $stageReviewAssignments) {
			foreach ($stageReviewAssignments as $reviewAssignment) {
				if ($reviewAssignment->getId() > 0) {
					$this->reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				} else {
					$this->reviewAssignmentDao->insertReviewAssignment($reviewAssignment);
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

			$paper =& $this->paperDao->getPaper($trackDirectorSubmission->getPaperId());

			// Only update fields that can actually be edited.
			$paper->setTrackId($trackDirectorSubmission->getTrackId());
			$paper->setData('sessionType', $trackDirectorSubmission->getData('sessionType'));
			$paper->setTrackId($trackDirectorSubmission->getTrackId());
			$paper->setCurrentStage($trackDirectorSubmission->getCurrentStage());
			$paper->setReviewFileId($trackDirectorSubmission->getReviewFileId());
			$paper->setLayoutFileId($trackDirectorSubmission->getLayoutFileId());
			$paper->setDirectorFileId($trackDirectorSubmission->getDirectorFileId());
			$paper->setStatus($trackDirectorSubmission->getStatus());
			$paper->setDateStatusModified($trackDirectorSubmission->getDateStatusModified());
			$paper->setDateToPresentations($trackDirectorSubmission->getDateToPresentations());
			$paper->setDateToArchive($trackDirectorSubmission->getDateToArchive());
			$paper->setLastModified($trackDirectorSubmission->getLastModified());
			$paper->setCommentsStatus($trackDirectorSubmission->getCommentsStatus());

			$this->paperDao->updatePaper($paper);
		}

	}

	function createReviewStage($paperId, $stage, $reviewRevision) {
		$this->update(
			'INSERT INTO review_stages
				(paper_id, stage, review_revision)
			VALUES
				(?, ?, ?)',
			array($paperId, $stage, $reviewRevision)
		);
	}

	/**
	 * Retrieve unfiltered track director submissions
	 */
	function &_getUnfilteredTrackDirectorSubmissions($trackDirectorId, $schedConfId, $trackId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $additionalWhereSql = '', $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$params = array(
			'title', // Track title
			$primaryLocale,
			'title',
			$locale,
			'abbrev', // Track abbrev
			$primaryLocale,
			'abbrev',
			$locale,
			'cleanTitle', // Paper title
			'cleanTitle', // Paper title
			$primaryLocale,
			$schedConfId,
			$trackDirectorId
		);

		// set up the search filters based on what the user selected
		$searchSql = '';
		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(ptl.setting_value) = LOWER(?)';
				} elseif ($searchMatch === 'contains') {
					$searchSql = ' AND LOWER(ptl.setting_value) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = ' AND LOWER(ptl.setting_value) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				}
				$params[] = $search;
				break;
			case SUBMISSION_FIELD_AUTHOR:
				$first_last = $this->_dataSource->Concat('pa.first_name', '\' \'', 'pa.last_name');
				$first_middle_last = $this->_dataSource->Concat('pa.first_name', '\' \'', 'pa.middle_name', '\' \'', 'pa.last_name');
				$last_comma_first = $this->_dataSource->Concat('pa.last_name', '\', \'', 'pa.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('pa.last_name', '\', \'', 'pa.first_name', '\' \'', 'pa.middle_name');

				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(pa.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} elseif ($searchMatch === 'contains') {
					$searchSql = " AND (LOWER(pa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = " AND (LOWER(pa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = $search . '%';
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
				} elseif ($searchMatch === 'contains') {
					$searchSql = " AND (LOWER(ed.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = " AND (LOWER(ed.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
		}

		// filter on date range, if requested
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

		// filter for post from only one specific track, if requested
		if ($trackId) {
			$params[] = $trackId;
			$searchSql .= ' AND p.track_id = ?';
		}

		$sql = 'SELECT DISTINCT
				p.*,
				r2.review_revision,
				COALESCE(ptl.setting_value, pptl.setting_value) AS submission_title,
				pap.last_name AS author_name,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				INNER JOIN paper_authors pa ON (pa.paper_id = p.paper_id)
				LEFT JOIN paper_authors pap ON (pap.paper_id = p.paper_id AND pap.primary_contact = 1)
				LEFT JOIN edit_assignments e ON (e.paper_id = p.paper_id)
				LEFT JOIN users ed ON (e.director_id = ed.user_id)
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN review_stages r2 ON (p.paper_id = r2.paper_id and p.current_stage = r2.stage)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
				LEFT JOIN paper_settings ptl ON (p.paper_id = ptl.paper_id AND ptl.setting_name = ?)
				LEFT JOIN paper_settings pptl ON (p.paper_id = pptl.paper_id AND pptl.setting_name = ? AND pptl.locale = ?)
				LEFT JOIN paper_settings sts ON (p.paper_id = sts.paper_id AND sts.setting_name = \'sessionType\')
			WHERE	p.sched_conf_id = ?
				' . (!empty($additionalWhereSql)?" AND ($additionalWhereSql)":'') . '
				AND e.director_id = ? ' .
			$searchSql .
			($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange($sql,
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
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getTrackDirectorSubmissionsInReview($trackDirectorId, $schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result = $this->_getUnfilteredTrackDirectorSubmissions(
			$trackDirectorId, $schedConfId, $trackId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'p.status = ' . STATUS_QUEUED,
			$rangeInfo, $sortBy, $sortDirection
		);

		$returner = new DAOResultFactory($result, $this, '_returnTrackDirectorSubmissionFromRow');
		return $returner;

	}

	/**
	 * Get all submissions accepted to a conference.
	 * @param $trackDirectorId int
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getTrackDirectorSubmissionsAccepted($trackDirectorId, $schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result = $this->_getUnfilteredTrackDirectorSubmissions(
			$trackDirectorId, $schedConfId, $trackId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'p.status = ' . STATUS_PUBLISHED,
			$rangeInfo, $sortBy, $sortDirection
		);

		$returner = new DAOResultFactory($result, $this, '_returnTrackDirectorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all submissions in archives for a conference.
	 * @param $trackDirectorId int
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getTrackDirectorSubmissionsArchives($trackDirectorId, $schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$result = $this->_getUnfilteredTrackDirectorSubmissions(
			$trackDirectorId, $schedConfId, $trackId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'p.status <> ' . STATUS_QUEUED . ' AND p.status <> ' . STATUS_PUBLISHED,
			$rangeInfo, $sortBy, $sortDirection
		);

		$returner = new DAOResultFactory($result, $this, '_returnTrackDirectorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getTrackDirectorSubmissionsCount($trackDirectorId, $schedConfId) {
		$submissionsCount = array();

		// Fetch a count of submissions in review.
		// "d2" and "d" are used to fetch the single most recent
		// editor decision.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS review_count
			FROM	papers p
				LEFT JOIN edit_assignments e ON (p.paper_id = e.paper_id)
			WHERE	p.sched_conf_id = ?
				AND e.director_id = ?
				AND p.status = ' . STATUS_QUEUED,
			array((int) $schedConfId, (int) $trackDirectorId)
		);
		$submissionsCount[0] = $result->Fields('review_count');
		$result->Close();

		// Fetch a count of submissions in editing.
		// "d2" and "d" are used to fetch the single most recent
		// editor decision.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS editing_count
			FROM	papers p
				LEFT JOIN edit_assignments e ON (p.paper_id = e.paper_id)
				LEFT JOIN edit_decisions d ON (p.paper_id = d.paper_id)
				LEFT JOIN edit_decisions d2 ON (p.paper_id = d2.paper_id AND d.edit_decision_id < d2.edit_decision_id)
			WHERE	p.sched_conf_id = ?
				AND e.director_id = ?
				AND p.status = ' . STATUS_QUEUED . '
				AND d2.edit_decision_id IS NULL
				AND d.decision = ' . SUBMISSION_DIRECTOR_DECISION_ACCEPT,
			array((int) $schedConfId, (int) $trackDirectorId)
		);
		$submissionsCount[1] = $result->Fields('editing_count');
		$result->Close();
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
	 * Delete review stages by paper.
	 * @param $paperId int
	 */
	function deleteReviewStagesByPaper($paperId) {
		return $this->update(
			'DELETE FROM review_stages WHERE paper_id = ?',
			$paperId
		);
	}

	/**
	 * Get the director decisions for a review stage of a paper.
	 * @param $paperId int
	 * @param $stage int
	 */
	function getDirectorDecisions($paperId, $stage) {
		$decisions = array();

		$params = array($paperId);
		if($stage != null) $params[] = $stage;

		$result =& $this->retrieve('
			SELECT edit_decision_id, director_id, decision, date_decided
				FROM edit_decisions
				WHERE paper_id = ?'
					. ($stage == NULL ? '' : ' AND stage = ?')
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
	 * Get the highest review stage.
	 * @param $paperId int
	 * @return int
	 */
	function getMaxReviewStage($paperId) {
		$result =& $this->retrieve(
			'SELECT MAX(stage) FROM review_stages WHERE paper_id = ?', array($paperId)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a review stage exists for a specified paper.
	 * @param $paperId int
	 * @param $stage int
	 * @return boolean
	 */
	function reviewStageExists($paperId, $stage) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM review_stages WHERE paper_id = ? AND stage = ?', array($paperId, $stage)
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
	function reviewerExists($paperId, $reviewerId, $stage) {
		$result =& $this->retrieve('
			SELECT COUNT(*)
			FROM review_assignments
			WHERE paper_id = ?
				AND reviewer_id = ?
				AND stage = ?
				AND cancelled = 0',
			array($paperId, $reviewerId, $stage)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a list of all reviewers with respect to a submission's current round.
	 * @param $schedConfId int
	 * @param $paperId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getReviewersForPaper($schedConfId, $paperId, $stage, $searchType = null, $search = null, $searchMatch = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		// Convert the field being searched for to a DB element to select on
		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 's.setting_value'
		);

		// Add the "interests" parameter only if the user searched for reviwer interests
		$paramArray = array((int)$paperId, (int)$stage);
		$joinInterests = false;
		if($searchType == USER_FIELD_INTERESTS) {
			$paramArray[] = "interests";
			$joinInterests = true;
		}

		$paramArray[] = (int)$schedConfId;
		$paramArray[] = ROLE_ID_REVIEWER;

		// Generate the SQL used to filter the results based on what the user is searching for
		$searchSql = '';
		if (!empty($search) && isset($searchTypeMap[$searchType])) {
			$fieldName = $searchTypeMap[$searchType];
			switch ($searchMatch) {
				case 'is':
					$searchSql = "AND LOWER($fieldName) = LOWER(?)";
					$paramArray[] = $search;
					break;
				case 'contains':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = '%' . $search . '%';
					break;
				case 'startsWith':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = $search . '%';
					break;
			}
		} elseif (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(last_name) LIKE LOWER(?) OR LOWER(username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}

		// If we are sorting a column, we'll need to configure the additional join conditions
		$sortSelect = '';
		$joinComplete = $joinIncomplete = false;
		$selectQuality = $selectLatest = $selectComplete = $selectAverage = $selectIncomplete = false;
		if($sortBy) switch($sortBy) {
			case 'quality':
				$selectQuality = true;
				break;
			case 'latest':
				$selectLatest = $joinComplete = true;
				break;
			case 'done':
				$selectComplete = $joinComplete = true;
				break;
			case 'average':
				$selectAverage = $joinComplete = true;
				break;
			case 'active':
				$selectIncomplete = $joinIncomplete = true;
				break;
		}

		$sql = 'SELECT DISTINCT
					u.user_id,
					u.last_name,
					ar.review_id ' .
					($selectQuality ? ', AVG(ar.quality) AS average_quality ' : '') .
					($selectLatest ? ', MAX(ac.date_notified) AS latest ' : '') .
					($selectComplete ? ', COUNT(ac.review_id) AS completed ' : '') .
					($selectAverage ? ', AVG(ac.date_completed-ac.date_notified) AS average ' : '') .
					($selectIncomplete ? ', COUNT(ai.review_id) AS incomplete ' : '') .
				'FROM roles r, users u
					LEFT JOIN review_assignments ar ON (ar.reviewer_id = u.user_id AND ar.cancelled = 0 AND ar.paper_id = ? AND ar.stage = ?) ' .
					($joinComplete ? 'LEFT JOIN review_assignments ac ON (ac.reviewer_id = u.user_id AND ac.date_completed IS NOT NULL) ' : '') .
					($joinIncomplete ? 'LEFT JOIN review_assignments ai ON (ai.reviewer_id = u.user_id AND ai.date_completed IS NULL) ' : '') .
					($joinInterests ? 'LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?) ' : '') . 
				'WHERE u.user_id = r.user_id AND
					r.sched_conf_id = ? AND
					r.role_id = ? ' . $searchSql . ' ' .
				'GROUP BY u.user_id, u.last_name, ar.review_id ' .
				($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange(
			$sql,$paramArray, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;
	}

	function &_returnReviewerUserFromRow(&$row) { // FIXME
		$user =& $this->userDao->getUser($row['user_id']);
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

		$result =& $this->retrieve(
			'SELECT u.* FROM users u NATURAL JOIN roles r LEFT JOIN review_assignments a ON (a.reviewer_id = u.user_id AND a.paper_id = ?) WHERE r.sched_conf_id = ? AND r.role_id = ? AND a.paper_id IS NULL ORDER BY last_name, first_name',
			array($paperId, $schedConfId, RoleDAO::getRoleIdFromPath('reviewer'))
		);

		while (!$result->EOF) {
			$users[] =& $this->userDao->_returnUserFromRowWithData($result->GetRowAssoc(false));
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

		// Get latest review request date
		$result =& $this->retrieve('SELECT ra.reviewer_id AS reviewer_id, MAX(ra.date_notified) AS last_notified FROM review_assignments ra, papers p WHERE ra.paper_id = p.paper_id AND p.sched_conf_id = ? GROUP BY ra.reviewer_id', $schedConfId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['last_notified'] = $this->datetimeFromDB($row['last_notified']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get completion status
		$result =& $this->retrieve('SELECT r.reviewer_id AS reviewer_id, COUNT(*) AS incomplete FROM review_assignments r, papers p WHERE r.paper_id = p.paper_id AND r.date_notified IS NOT NULL AND r.date_completed IS NULL AND r.cancelled = 0 AND p.sched_conf_id = ? GROUP BY r.reviewer_id', $schedConfId);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();
			$statistics[$row['reviewer_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Calculate time taken for completed reviews
		$result =& $this->retrieve('SELECT r.reviewer_id, r.date_notified, r.date_completed FROM review_assignments r, papers p WHERE r.paper_id = p.paper_id AND r.date_notified IS NOT NULL AND r.date_completed IS NOT NULL AND p.sched_conf_id = ?', $schedConfId);
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
	
	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'id': return 'p.paper_id';
			case 'submitDate': return 'p.date_submitted';
			case 'track': return 'track_abbrev';
			case 'sessionType': return 'sts.setting_value';
			case 'authors': return 'author_name';
			case 'title': return 'submission_title';
			case 'status': return 'p.status';
			case 'active': return 'incomplete';		
			case 'reviewerName': return 'u.last_name';
			case 'quality': return 'average_quality';
			case 'done': return 'completed';
			case 'latest': return 'latest';
			case 'average': return 'average';
			case 'name': return 'u.last_name';
			default: return null;
		}
	}
}

?>
