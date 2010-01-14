<?php

/**
 * @file DirectorSubmissionDAO.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DirectorSubmissionDAO
 * @ingroup submission
 * @see DirectorSubmission
 *
 * @brief Operations for retrieving and modifying DirectorSubmission objects.
 *
 * $Id$
 */

import('submission.director.DirectorSubmission');
import('submission.author.AuthorSubmission'); // Bring in director decision constants

define('DIRECTOR_SUBMISSION_SORT_ORDER_NATURAL',	0x00000001);
define('DIRECTOR_SUBMISSION_SORT_ORDER_PUBLISHED',	0x00000002);

class DirectorSubmissionDAO extends DAO {

	var $paperDao;
	var $authorDao;
	var $userDao;
	var $editAssignmentDao;

	/**
	 * Constructor.
	 */
	function DirectorSubmissionDAO() {
		parent::DAO();
		$this->paperDao =& DAORegistry::getDAO('PaperDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
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
			$returner =& $this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));
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
		$directorSubmission = new DirectorSubmission();

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
		$reviewAssignments =& $directorSubmission->getReviewAssignments();
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
	 * @param $directorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $statusSql string Extra SQL conditions to match
	 * @param $rangeInfo object
	 * @return array result
	 */
	function &getUnfilteredDirectorSubmissions($schedConfId, $trackId = 0, $directorId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $statusSql = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
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
			'cleanTitle', // Paper title
			$primaryLocale,
			'cleanTitle', // Paper title
			$locale,
			$schedConfId
		);
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
					$search = $search . '%';
				}
				$params[] = $search;
				break;
			case SUBMISSION_FIELD_AUTHOR:
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
				COALESCE(ptl.setting_value, pptl.setting_value) AS submission_title,
				pap.last_name AS author_name,
				t.seq, pp.seq,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				INNER JOIN paper_authors pa ON (pa.paper_id = p.paper_id)
				LEFT JOIN paper_authors pap ON (pap.paper_id = p.paper_id AND pap.primary_contact = 1)
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
				LEFT JOIN paper_settings pptl ON (p.paper_id = pptl.paper_id AND pptl.setting_name = ? AND pptl.locale = ?)
				LEFT JOIN paper_settings ptl ON (p.paper_id = ptl.paper_id AND ptl.setting_name = ? AND pptl.locale = ?)
			WHERE	p.sched_conf_id = ?';

		if ($statusSql !== null) $sql .= " AND ($statusSql)";
		else $sql .= ' AND p.status = ' . STATUS_QUEUED;

		if ($trackId) {
			$searchSql .= ' AND p.track_id = ?';
			$params[] = $trackId;
		}

		if ($directorId) {
			$searchSql .= ' AND ed.user_id = ?';
			$params[] = $directorId;
		}

		$sql .= ' ' . $searchSql . ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange(
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
		} elseif ($searchMatch === 'contains') {
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$params[] = $params[] = $params[] = $params[] = $params[] = '%' . $search . '%';
		} else { // $searchMatch === 'startsWith'
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$params[] = $params[] = $params[] = $params[] = $params[] = $search . '%';
		}
		return $searchSql;
	}

	/**
	 * Get all submissions unassigned for a scheduled conference.
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $directorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getDirectorSubmissionsUnassigned($schedConfId, $trackId, $directorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$directorSubmissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $directorId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, null, null, $sortBy, $sortDirection);

		while (!$result->EOF) {
			$directorSubmission =& $this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));

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

		import('core.ArrayItemIterator');
		$returner =& ArrayItemIterator::fromRangeInfo($directorSubmissions, $rangeInfo);
		return $returner;
	}

	/**
	 * Get all submissions in review for a scheduled conference.
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $directorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getDirectorSubmissionsInReview($schedConfId, $trackId, $directorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$directorSubmissions = array();

		// FIXME Does not pass $rangeInfo else we only get partial results
		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $directorId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, null, null, $sortBy, $sortDirection);

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');

		// If the submission has passed this review stage, it's out of review.
		$schedConfDao =& DAORegistry::getDao('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($schedConfId);

		while (!$result->EOF) {
			$directorSubmission =& $this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));
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

		import('core.ArrayItemIterator');
		$returner =& ArrayItemIterator::fromRangeInfo($directorSubmissions, $rangeInfo);
		return $returner;
	}

	/**
	 * Get all submissions accepted for a scheduled conference.
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $directorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getDirectorSubmissionsAccepted($schedConfId, $trackId, $directorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = "ASC") {
		$directorSubmissions = array();

		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $directorId,  $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, 'p.status = ' . STATUS_PUBLISHED, $rangeInfo, $sortBy, $sortDirection);

		$returner = new DAOResultFactory($result, $this, '_returnDirectorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get all submissions archived for a scheduled conference.
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $directorId int
	 * @param $searchField int Symbolic SUBMISSION_FIELD_... identifier
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int Symbolic SUBMISSION_FIELD_DATE_... identifier
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @param $rangeInfo object
	 * @return array DirectorSubmission
	 */
	function &getDirectorSubmissionsArchives($schedConfId, $trackId, $directorId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = "ASC") {
		$directorSubmissions = array();

		$result = $this->getUnfilteredDirectorSubmissions($schedConfId, $trackId, $directorId, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, 'p.status <> ' . STATUS_QUEUED . ' AND p.status <> ' . STATUS_PUBLISHED, $rangeInfo, $sortBy, $sortDirection);

		$returner = new DAOResultFactory($result, $this, '_returnDirectorSubmissionFromRow');
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
			$directorSubmission =& $this->_returnDirectorSubmissionFromRow($result->GetRowAssoc(false));

			// used to check if director exists for this submission
			$editAssignments = $directorSubmission->getEditAssignments();

			if (!$directorSubmission->isOriginalSubmissionComplete()) {
				// Do not include incomplete submissions
			} elseif (empty($editAssignments)) {
				// unassigned submissions
				$submissionsCount[0] += 1;
			} elseif ($directorSubmission->getStatus() == STATUS_QUEUED) {
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

		$result =& $this->retrieve(
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

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 's.setting_value'
		);

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
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(u.last_name) LIKE LOWER(?) OR LOWER(u.username) LIKE LOWER(?))';
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

		$returner = new DAOResultFactory($result, $this->userDao, '_returnUserFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted director assignment.
	 * @return int
	 */
	function getInsertEditId() {
		return $this->getInsertId('edit_assignments', 'edit_id');
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
			case 'section': return 'section_abbrev';
			case 'authors': return 'author_name';
			case 'title': return 'submission_title';
			case 'active': return 'p.submission_progress';		
			case 'subLayout': return 'layout_completed';
			case 'status': return 'p.status';
			default: return null;
		}
	}
}

?>
