<?php

/**
 * DirectorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for DirectorSubmission DAO.
 * Operations for retrieving and modifying DirectorSubmission objects.
 *
 * $Id$
 */

import('submission.director.DirectorSubmission');
import('submission.presenter.PresenterSubmission'); // Bring in director decision constants

class DirectorSubmissionDAO extends DAO {

	var $paperDao;
	var $presenterDao;
	var $userDao;
	var $editAssignmentDao;

	/**
	 * Constructor.
	 */
	function DirectorSubmissionDAO() {
		parent::DAO();
		$this->paperDao = &DAORegistry::getDAO('PaperDAO');
		$this->presenterDao = &DAORegistry::getDAO('PresenterDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
	}
	
	/**
	 * Retrieve a director submission by paper ID.
	 * @param $paperId int
	 * @return DirectorSubmission
	 */
	function &getDirectorSubmission($paperId) {
		$result = &$this->retrieve(
			'SELECT
				p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM
				papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
			WHERE
				p.paper_id = ?', $paperId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Internal function to return a DirectorSubmission object from a row.
	 * @param $row array
	 * @return DirectorSubmission
	 */
	function &_returnDirectorSubmissionFromRow(&$row) {
		$directorSubmission = &new DirectorSubmission();

		// Paper attributes
		$this->paperDao->_paperFromRow($directorSubmission, $row);
		
		// Director Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$directorSubmission->setEditAssignments($editAssignments->toArray());
		
		// Director Decisions
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$directorSubmission->setDecisions($this->getDirectorDecisions($row['paper_id'], $i), $i);
		}
		
		HookRegistry::call('DirectorSubmissionDAO::_returnDirectorSubmissionFromRow', array(&$directorSubmission, &$row));

		return $directorSubmission;
	}

	/**
	 * Insert a new DirectorSubmission.
	 * @param $directorSubmission DirectorSubmission
	 */	
	function insertDirectorSubmission(&$directorSubmission) {
		$this->update(
			sprintf('INSERT INTO edit_assignments
				(paper_id, director_id, date_notified, date_completed, date_acknowledged)
				VALUES
				(?, ?, %s, %s, %s)',
				$this->datetimeToDB($directorSubmission->getDateNotified()), $this->datetimeToDB($directorSubmission->getDateCompleted()), $this->datetimeToDB($directorSubmission->getDateAcknowledged())),
			array(
				$directorSubmission->getPaperId(),
				$directorSubmission->getDirectorId()
			)
		);
		
		$directorSubmission->setEditId($this->getInsertEditId());
		
		// Insert review assignments.
		$reviewAssignments = &$directorSubmission->getReviewAssignments();
		for ($i=0, $count=count($reviewAssignments); $i < $count; $i++) {
			$reviewAssignments[$i]->setPaperId($directorSubmission->getPaperId());
			$this->reviewAssignmentDao->insertReviewAssignment($reviewAssignments[$i]);
		}
		
		return $directorSubmission->getEditId();
	}
	
	/**
	 * Update an existing paper.
	 * @param $paper Paper
	 */
	function updateDirectorSubmission(&$directorSubmission) {
		// update edit assignments
		$editAssignments = $directorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getEditId() > 0) {
				$this->editAssignmentDao->updateEditAssignment($editAssignment);
			} else {
				$this->editAssignmentDao->insertEditAssignment($editAssignment);
			}
		}
	}
	
	/**
	 * Get all submissions for a scheduled conference.
	 * @param $schedConfId int
	 * @param $status boolean true if queued, false if archived.
	 * @return array DirectorSubmission
	 */
	function &getDirectorSubmissions($schedConfId, $status = true, $trackId = 0, $rangeInfo = null) {
		if (!$trackId) {
			$result = &$this->retrieveRange(
					'SELECT p.*,
						t.title AS track_title,
						t.title_alt1 AS track_title_alt1,
						t.title_alt2 AS track_title_alt2,
						t.abbrev AS track_abbrev,
						t.abbrev_alt1 AS track_abbrev_alt1,
						t.abbrev_alt2 AS track_abbrev_alt2
					FROM papers p
						LEFT JOIN tracks t ON (t.track_id = p.track_id)
					WHERE p.sched_conf_id = ? AND p.status = ? ORDER BY paper_id ASC',
					array($schedConfId, $status),
					$rangeInfo
			);
		} else {
			$result = &$this->retrieveRange(
					'SELECT p.*,
						t.title AS track_title,
						p.title_alt1 AS track_title_alt1,
						p.title_alt2 AS track_title_alt2,
						p.abbrev AS track_abbrev,
						p.abbrev_alt1 AS track_abbrev_alt1,
						p.abbrev_alt2 AS track_abbrev_alt2
					FROM papers p LEFT JOIN tracks t ON (t.track_id = p.track_id) WHERE p.sched_conf_id = ? AND p.status = ? AND p.track_id = ?
					ORDER BY paper_id ASC',
					array($schedConfId, $status, $trackId),
					$rangeInfo
			);	
		}
		$returner = &new DAOResultFactory($result, $this, '_returnDirectorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all unfiltered submissions for a scheduled conference.
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $status boolean whether to return active or not
	 * @param $rangeInfo object
	 * @return array result
	 */
	function &getUnfilteredDirectorSubmissions($schedConfId, $trackId = 0, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, $status, $rangeInfo = null) {
		$params = array($schedConfId);
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
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'aa.', $params);
				break;
			case SUBMISSION_FIELD_DIRECTOR:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'ed.', $params);
				break;
			case SUBMISSION_FIELD_REVIEWER:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 're.', $params);
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
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM
				papers p
			INNER JOIN paper_presenters pa ON (pa.paper_id = p.paper_id)
			LEFT JOIN tracks t ON (t.track_id = p.track_id)
			LEFT JOIN edit_assignments ea ON (ea.paper_id = p.paper_id)
			LEFT JOIN users ed ON (ea.director_id = ed.user_id)
			LEFT JOIN review_assignments ra ON (ra.paper_id = p.paper_id)
			LEFT JOIN users re ON (re.user_id = ra.reviewer_id AND cancelled = 0)
			WHERE
				p.sched_conf_id = ?';

		if ($status === true) $sql .= ' AND (p.status = ' . SUBMISSION_STATUS_QUEUED . ')';
		else $sql .= ' AND (p.status <> ' . SUBMISSION_STATUS_QUEUED . ')';
		
		if ($trackId) {
			$searchSql .= ' AND p.track_id = ?';
			$params[] = $trackId;
		}

		$result = &$this->retrieveRange(
			$sql . ' ' . $searchSql . ' ORDER BY paper_id ASC',
			(count($params)>1 ? $params : array_shift($params)),
			$rangeInfo
		);
		return $result;
	}
	
	/**
	 * FIXME Move this into somewhere common (SubmissionDAO?) as this is used in several classes.
	 */
	function _generateUserNameSearchSQL($search, $searchMatch, $prefix, &$params) {
		$first_last = $this->_dataSource->Concat($prefix.'first_name', '\' \'', $prefix.'last_name');
		$first_middle_last = $this->_dataSource->Concat($prefix.'first_name', '\' \'', $prefix.'middle_name', '\' \'', $prefix.'last_name');
		$last_comma_first = $this->_dataSource->Concat($prefix.'last_name', '\', \'', $prefix.'first_name');
		$last_comma_first_middle = $this->_dataSource->Concat($prefix.'last_name', '\', \'', $prefix.'first_name', '\' \'', $prefix.'middle_name');
		if ($searchMatch === 'is') {
			$searchSql = " AND (LOWER({$prefix}last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
		} else {
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$search = '%' . $search . '%';
		}
		$params[] = $params[] = $params[] = $params[] = $params[] = $search;
		return $searchSql;
	}

	/**
	 * Get all submissions unassigned for a scheduled conference.
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
	function &getDirectorSubmissionsUnassigned($schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$directorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		while (!$result->EOF) {
			$directorSubmission = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));

			// used to check if director exists for this submission
			$editAssignments =& $directorSubmission->getEditAssignments();

			if (empty($editAssignments) && $directorSubmission->isOriginalSubmissionComplete()) {
				$directorSubmissions[] =& $directorSubmission;
			}
			unset($directorSubmission);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($directorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($directorSubmissions);
		}
		return $returner;
	}

	/**
	 * Get all submissions in review for a scheduled conference.
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
	function &getDirectorSubmissionsInReview($schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$directorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		while (!$result->EOF) {
			$directorSubmission = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));
			$paperId = $directorSubmission->getPaperId();
			for ($i = 1; $i <= $directorSubmission->getCurrentStage(); $i++) {
				$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentsByPaperId($paperId, $i);
				if (!empty($reviewAssignment)) {
					$directorSubmission->setReviewAssignments($reviewAssignment, $i);
				}
			}

			// check if submission is still in review
			$inReview = true;
			$decisions = $directorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
					$inReview = false;
				}
			}

			// used to check if director exists for this submission
			$editAssignments =& $directorSubmission->getEditAssignments();

			if (!empty($editAssignments) && $inReview) {
				$directorSubmissions[] =& $directorSubmission;
			}
			unset($directorSubmission);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($directorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($directorSubmissions);
		}
		return $returner;
	}

	/**
	 * Get all accepted submissions for a scheduled conference.
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
	function &getDirectorSubmissionsInEditing($schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$directorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, true);

		while (!$result->EOF) {
			$directorSubmission = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));
			$paperId = $directorSubmission->getPaperId();

			// check if submission is still in review
			$inEditing = false;
			$decisions = $directorSubmission->getDecisions();
			$decision = array_pop($decisions);
			if (!empty($decision)) {
				$latestDecision = array_pop($decision);
				if ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
					$inEditing = true;			
				}
			}

			// used to check if director exists for this submission
			$editAssignments = $directorSubmission->getEditAssignments();

			if ($inEditing && !empty($editAssignments)) {
				$directorSubmissions[] =& $directorSubmission;
			}
			unset($directorSubmission);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($directorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($directorSubmissions);
		}
		return $returner;
	}

	/**
	 * Get all submissions archived for a scheduled conference.
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
	function &getDirectorSubmissionsArchives($schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$directorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, false, $rangeInfo);
		while (!$result->EOF) {
			$directorSubmission = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));
			$paperId = $directorSubmission->getPaperId();

			if ($directorSubmission->getStatus() == SUBMISSION_STATUS_ARCHIVED) {
				$directorSubmissions[] =& $directorSubmission;
				unset($directorSubmission);
			}
			$result->MoveNext();
		}
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($directorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($directorSubmissions);
		}

		$result->Close();
		unset($result);
		
		return $returner;
	}

	/**
	 * Get all submissions accepted for a scheduled conference.
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
	function &getDirectorSubmissionsAccepted($schedConfId, $trackId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null) {
		$directorSubmissions = array();
	
		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, false, $rangeInfo);
		while (!$result->EOF) {
			$directorSubmission = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));
			$paperId = $directorSubmission->getPaperId();

			if ($directorSubmission->getStatus() == SUBMISSION_STATUS_PUBLISHED) {
				$directorSubmissions[] =& $directorSubmission;
				unset($directorSubmission);
			}
			$result->MoveNext();
		}
		if (isset($rangeInfo) && $rangeInfo->isValid()) {
			$returner = &new ArrayItemIterator($directorSubmissions, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$returner = &new ArrayItemIterator($directorSubmissions);
		}

		$result->Close();
		unset($result);
		
		return $returner;
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getDirectorSubmissionsCount($schedConfId) {

		$schedConfDao =& DAORegistry::getDao('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($schedConfId);
		
		// If the submission has passed this review stage, it's out of review.
		if($schedConf->getSetting('reviewMode') == REVIEW_MODE_BOTH_SEQUENTIAL)
			$finalReviewType = REVIEW_PROGRESS_PRESENTATION;
		else
			$finalReviewType = REVIEW_PROGRESS_ABSTRACT;
		
		$submissionsCount = array();
		for($i = 0; $i < 4; $i++) {
			$submissionsCount[$i] = 0;
		}

		$sql = 'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				WHERE p.sched_conf_id = ? AND p.status = ' . SUBMISSION_STATUS_QUEUED . '
			ORDER BY paper_id ASC';
		$result = &$this->retrieve($sql, $schedConfId);

		while (!$result->EOF) {
			$directorSubmission = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));

			// check if submission is still in review
			$inReview = true;
			$notDeclined = true;
			$decisions = $directorSubmission->getDecisions($finalReviewType);
			if($decisions) {
				$decision = is_array($decisions)?array_pop($decisions):null;
				if (!empty($decision)) {
					$latestDecision = array_pop($decision);
					if ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_ACCEPT) {
						$inReview = false;
					} elseif ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
						$notDeclined = false;
					}
				}
			}

			// used to check if director exists for this submission
			$editAssignments = $directorSubmission->getEditAssignments();

			if (empty($editAssignments)  && $directorSubmission->isOriginalSubmissionComplete()) {
				// unassigned submissions
				$submissionsCount[0] += 1;
			} elseif ($directorSubmission->getStatus() == SUBMISSION_STATUS_QUEUED) {
				if ($inReview) {
					if ($notDeclined) {
						// in review submissions
						$submissionsCount[1] += 1;
					}
				} else {
					// in editing submissions
					$submissionsCount[2] += 1;					
				}
			}

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
	 * Get the director decisions for a director.
	 * @param $userId int
	 */
	function transferDirectorDecisions($oldUserId, $newUserId) {
		$this->update(
			'UPDATE edit_decisions SET director_id = ? WHERE director_id = ?',
			array($newUserId, $oldUserId)
		);
	}
	
	/**
	 * Retrieve a list of all users in the specified role not assigned as directors to the specified paper.
	 * @param $schedConfId int
	 * @param $paperId int
	 * @param $roleId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getUsersNotAssignedToPaper($schedConfId, $paperId, $roleId, $searchType=null, $search=null, $searchMatch=null, $rangeInfo = null) {
		$users = array();
		
		$paramArray = array($paperId, $schedConfId, $roleId);
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
		
		$result = &$this->retrieveRange(
			'SELECT DISTINCT u.* FROM users u NATURAL JOIN roles r LEFT JOIN edit_assignments e ON (e.director_id = u.user_id AND e.paper_id = ?) WHERE r.sched_conf_id = ? AND r.role_id = ? AND (e.paper_id IS NULL) ' . $searchSql . ' ORDER BY last_name, first_name',
			$paramArray, $rangeInfo
		);
		
		$returner = &new DAOResultFactory($result, $this->userDao, '_returnUserFromRow');
		return $returner;
	}
	
	/**
	 * Get the ID of the last inserted director assignment.
	 * @return int
	 */
	function getInsertEditId() {
		return $this->getInsertId('edit_assignments', 'edit_id');
	}
}

?>
