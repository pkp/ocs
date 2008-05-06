<?php

/**
 * @file DirectorSubmissionDAO.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 * @class DirectorSubmissionDAO
 *
 * Class for DirectorSubmission DAO.
 * Operations for retrieving and modifying DirectorSubmission objects.
 *
 * $Id$
 */

import('submission.director.DirectorSubmission');
import('submission.presenter.PresenterSubmission'); // Bring in director decision constants

define('DIRECTOR_SUBMISSION_SORT_ORDER_NATURAL',	0x00000001);
define('DIRECTOR_SUBMISSION_SORT_ORDER_PUBLISHED',	0x00000002);

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
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result =& $this->retrieve(
			'SELECT	p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
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
	 * Get all unfiltered submissions for a scheduled conference.
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $statusSql string Extra SQL conditions to match
	 * @param $sortOrder int DIRECTOR_SUBMISSION_SORT_ORDER_...
	 * @param $rangeInfo object
	 * @return array result
	 */
	function &getUnfilteredDirectorSubmissions($schedConfId, $trackId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $statusSql = null, $sortOrder = DIRECTOR_SUBMISSION_SORT_ORDER_NATURAL, $rangeInfo = null) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$params = array(
			'title', // Track title
			$primaryLocale,
			'title',
			$locale,
			'abbrev', // Track abbrev
			$primaryLocale,
			'abbrev',
			$locale,
			'title', // Paper title
			$schedConfId
		);
		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(ptl.setting_value) = LOWER(?)';
				} else {
					$searchSql = ' AND LOWER(ptl.setting_value) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				}
				$params[] = $search;
				break;
			case SUBMISSION_FIELD_PRESENTER:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'pa.', $params);
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
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				INNER JOIN paper_presenters pa ON (pa.paper_id = p.paper_id)
				LEFT JOIN published_papers pp ON (pp.paper_id = p.paper_id)
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN edit_assignments ea ON (ea.paper_id = p.paper_id)
				LEFT JOIN users ed ON (ea.director_id = ed.user_id)
				LEFT JOIN review_assignments ra ON (ra.paper_id = p.paper_id)
				LEFT JOIN users re ON (re.user_id = ra.reviewer_id AND cancelled = 0)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
				LEFT JOIN paper_settings ptl ON (p.paper_id = ptl.paper_id AND ptl.setting_name = ?)
			WHERE	p.sched_conf_id = ?';

		if ($statusSql !== null) $sql .= " AND ($statusSql)";
		else $sql .= ' AND p.status = ' . SUBMISSION_STATUS_QUEUED;

		if ($trackId) {
			$searchSql .= ' AND p.track_id = ?';
			$params[] = $trackId;
		}

		$sql .= ' ' . $searchSql . ' ORDER BY ';
		switch ($sortOrder) {
			case DIRECTOR_SUBMISSION_SORT_ORDER_PUBLISHED:
				$sql .= 't.seq ASC, pp.seq ASC';
				break;
			case DIRECTOR_SUBMISSION_SORT_ORDER_NATURAL:
			default:
				$sql .= 'paper_id ASC';
				break;
		}

		$result = &$this->retrieveRange(
			$sql,
			$params,
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
			$params[] = $params[] = $params[] = $params[] = $params[] = $search;
		} elseif ($searchMatch === 'initial') {
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?))";
			$params[] = $search . '%';
		} else {
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$params[] = $params[] = $params[] = $params[] = $params[] = '%' . $search . '%';
		}
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
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo);

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
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo);

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		// If the submission has passed this review stage, it's out of review.
		$schedConfDao =& DAORegistry::getDao('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($schedConfId);

		while (!$result->EOF) {
			$directorSubmission = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));
			$paperId = $directorSubmission->getPaperId();
			for ($i = 1; $i <= $directorSubmission->getCurrentStage(); $i++) {
				$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentsByPaperId($paperId, $i);
				if (!empty($reviewAssignment)) {
					$directorSubmission->setReviewAssignments($reviewAssignment, $i);
				}
			}

			// used to check if director exists for this submission
			$editAssignments =& $directorSubmission->getEditAssignments();

			if (!empty($editAssignments) && $directorSubmission->isOriginalSubmissionComplete()) {
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

		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, 'p.status = ' . SUBMISSION_STATUS_PUBLISHED, DIRECTOR_SUBMISSION_SORT_ORDER_PUBLISHED, $rangeInfo);

		$returner = &new DAOResultFactory($result, $this, '_returnDirectorSubmissionFromRow');
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

		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, 'p.status <> ' . SUBMISSION_STATUS_QUEUED . ' AND p.status <> ' . SUBMISSION_STATUS_PUBLISHED, DIRECTOR_SUBMISSION_SORT_ORDER_NATURAL, $rangeInfo);

		$returner = &new DAOResultFactory($result, $this, '_returnDirectorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getDirectorSubmissionsCount($schedConfId) {

		$schedConfDao =& DAORegistry::getDao('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($schedConfId);

		$submissionsCount = array();
		for($i = 0; $i < 2; $i++) {
			$submissionsCount[$i] = 0;
		}

		$result = $this->getUnfilteredDirectorSubmissions($schedConfId);

		while (!$result->EOF) {
			$directorSubmission = &$this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));

			// used to check if director exists for this submission
			$editAssignments = $directorSubmission->getEditAssignments();

			if (!$directorSubmission->isOriginalSubmissionComplete()) {
				// Do not include incomplete submissions
			} elseif (empty($editAssignments)) {
				// unassigned submissions
				$submissionsCount[0] += 1;
			} elseif ($directorSubmission->getStatus() == SUBMISSION_STATUS_QUEUED) {
				// in review submissions
				$submissionsCount[1] += 1;
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
			'SELECT	edit_decision_id,
				director_id,
				decision,
				date_decided
			FROM	edit_decisions
			WHERE	paper_id = ? ' .
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

		$paramArray = array(
			'interests',
			$paperId,
			$schedConfId,
			$roleId
		);

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
				$searchSql = 'AND LOWER(s.setting_value) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(last_name) LIKE LOWER(?) OR LOWER(username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}

		$result =& $this->retrieveRange(
			'SELECT DISTINCT
				u.*
			FROM	users u
				LEFT JOIN roles r ON (r.user_id = u.user_id)
				LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?)
				LEFT JOIN edit_assignments e ON (e.director_id = u.user_id AND e.paper_id = ?)
			WHERE	r.sched_conf_id = ? AND
				r.role_id = ? AND
				e.paper_id IS NULL ' .
				$searchSql . '
			ORDER BY last_name, first_name',
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
