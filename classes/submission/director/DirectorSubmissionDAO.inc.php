<?php

/**
 * @file DirectorSubmissionDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DirectorSubmissionDAO
 * @ingroup submission
 * @see DirectorSubmission
 *
 * @brief Operations for retrieving and modifying DirectorSubmission objects.
 *
 */

import('classes.submission.director.DirectorSubmission');
import('classes.submission.author.AuthorSubmission'); // Bring in director decision constants

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
		$this->paperDao = DAORegistry::getDAO('PaperDAO');
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
	}

	/**
	 * Retrieve a director submission by paper ID.
	 * @param $paperId int
	 * @return DirectorSubmission
	 */
	function &getDirectorSubmission($paperId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
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
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$directorSubmission->setDecisions($this->getDirectorDecisions($row['paper_id'], $i), $i);
		}

		// Review Rounds
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		for ($i = REVIEW_ROUND_ABSTRACT; $i <= $directorSubmission->getCurrentRound(); $i++) {
			$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($directorSubmission->getId(), $i);
			if (!empty($reviewAssignments)) {
				$directorSubmission->setReviewAssignments($reviewAssignments, $i);
			}
			unset($reviewAssignments);
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
				$directorSubmission->getId(),
				$directorSubmission->getDirectorId()
			)
		);

		$directorSubmission->setEditId($this->getInsertId());

		// Insert review assignments.
		$reviewAssignments =& $directorSubmission->getReviewAssignments();
		for ($i=0, $count=count($reviewAssignments); $i < $count; $i++) {
			$reviewAssignments[$i]->setPaperId($directorSubmission->getId());
			$this->reviewAssignmentDao->insertObject($reviewAssignments[$i]);
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
	 * @param $additionalWhereSql string Extra SQL conditions to match
	 * @param $rangeInfo object
	 * @return array result
	 */
	function &_getUnfilteredDirectorSubmissions($schedConfId, $trackId = 0, $directorId = 0, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $additionalWhereSql = '', $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$params = array(
			'title', // Track title (primary locale)
			$primaryLocale,
			'title', // Track title (current locale)
			$locale,
			'abbrev', // Track abbrev (primary locale)
			$primaryLocale,
			'abbrev', // Track abbrev (current locale)
			$locale,
			'cleanTitle', // Paper title (paper locale)
			'cleanTitle', // Paper title (current locale)
			$locale,
			$schedConfId
		);
		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_ID:
				$params[] = (int) $search;
				$searchSql = ' AND p.paper_id = ?';
				break;
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
				COALESCE(ptl.setting_value, ptpl.setting_value) AS submission_title,
				pap.last_name AS author_name,
				t.seq, pp.seq,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN authors pa ON (pa.submission_id = p.paper_id)
				LEFT JOIN authors pap ON (pap.submission_id = p.paper_id AND pap.primary_contact = 1)
				LEFT JOIN published_papers pp ON (pp.paper_id = p.paper_id)
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN edit_assignments e ON (e.paper_id = p.paper_id)
				LEFT JOIN users ed ON (e.director_id = ed.user_id)
				LEFT JOIN review_assignments ra ON (ra.submission_id = p.paper_id)
				LEFT JOIN users re ON (re.user_id = ra.reviewer_id AND cancelled = 0)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
				LEFT JOIN paper_settings ptpl ON (p.paper_id = ptpl.paper_id AND ptpl.setting_name = ? AND ptpl.locale = p.locale)
				LEFT JOIN paper_settings ptl ON (p.paper_id = ptl.paper_id AND ptl.setting_name = ? AND ptl.locale = ?)
				LEFT JOIN edit_assignments ea ON (p.paper_id = ea.paper_id)
				LEFT JOIN edit_assignments ea2 ON (p.paper_id = ea2.paper_id AND ea.edit_id < ea2.edit_id)
			WHERE	p.sched_conf_id = ?
				AND ea2.edit_id IS NULL' .
				(!empty($additionalWhereSql)?" AND ($additionalWhereSql)":'') . '
				AND (p.submission_progress = 0 OR (p.review_mode = ' . REVIEW_MODE_BOTH_SEQUENTIAL . ' AND p.submission_progress <> 1))';

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
		$result =& $this->_getUnfilteredDirectorSubmissions(
			$schedConfId, $trackId, $directorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'p.status = ' . STATUS_QUEUED . ' AND ea.edit_id IS NULL',
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnDirectorSubmissionFromRow');
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
		$result =& $this->_getUnfilteredDirectorSubmissions(
			$schedConfId, $trackId, $directorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'p.status = ' . STATUS_QUEUED . ' AND ea.edit_id IS NOT NULL',
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnDirectorSubmissionFromRow');
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
		$result =& $this->_getUnfilteredDirectorSubmissions(
			$schedConfId, $trackId, $directorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'p.status = ' . STATUS_PUBLISHED,
			$rangeInfo, $sortBy, $sortDirection
		);
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
		$result =& $this->_getUnfilteredDirectorSubmissions(
			$schedConfId, $trackId, $directorId,
			$searchField, $searchMatch, $search,
			$dateField, $dateFrom, $dateTo,
			'p.status <> ' . STATUS_QUEUED . ' AND p.status <> ' . STATUS_PUBLISHED,
			$rangeInfo, $sortBy, $sortDirection
		);
		$returner = new DAOResultFactory($result, $this, '_returnDirectorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Function used for counting purposes for right nav bar
	 */
	function &getDirectorSubmissionsCount($schedConfId) {
		$submissionsCount = array();

		// Fetch a count of unassigned submissions.
		// "e2" and "e" are used to fetch only a single assignment
		// if several exist.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS unassigned_count
			FROM	papers p
				LEFT JOIN edit_assignments e ON (p.paper_id = e.paper_id)
				LEFT JOIN edit_assignments e2 ON (p.paper_id = e2.paper_id AND e.edit_id < e2.edit_id)
			WHERE	p.sched_conf_id = ?
				AND p.status = ' . STATUS_QUEUED . '
				AND e2.edit_id IS NULL
				AND e.edit_id IS NULL
				AND (p.submission_progress = 0 OR (p.review_mode = ' . REVIEW_MODE_BOTH_SEQUENTIAL . ' AND p.submission_progress <> 1))',
			array((int) $schedConfId)
		);
		$submissionsCount[0] = $result->Fields('unassigned_count');
		$result->Close();

		// Fetch a count of submissions in review.
		// "e2" and "e" are used to fetch only a single assignment
		// if several exist.
		$result =& $this->retrieve(
			'SELECT	COUNT(*) AS review_count
			FROM	papers p
				LEFT JOIN edit_assignments e ON (p.paper_id = e.paper_id)
				LEFT JOIN edit_assignments e2 ON (p.paper_id = e2.paper_id AND e.edit_id < e2.edit_id)
			WHERE	p.sched_conf_id = ?
				AND p.status = ' . STATUS_QUEUED . '
				AND e2.edit_id IS NULL
				AND e.edit_id IS NOT NULL
				AND (p.submission_progress = 0 OR (p.review_mode = ' . REVIEW_MODE_BOTH_SEQUENTIAL . ' AND p.submission_progress <> 1))',
			array((int) $schedConfId)
		);
		$submissionsCount[1] = $result->Fields('review_count');
		$result->Close();

		return $submissionsCount;
	}

	//
	// Miscellaneous
	//

	/**
	 * Get the director decisions for a review round of a paper.
	 * @param $paperId int
	 * @param $round int
	 */
	function getDirectorDecisions($paperId, $round = null) {
		$decisions = array();
		$args = array($paperId);
		if($round) {
			$args[] = $round;
		}

		$result =& $this->retrieve(
			'SELECT	edit_decision_id,
				director_id,
				decision,
				date_decided
			FROM	edit_decisions
			WHERE	paper_id = ? ' .
			($round?' AND round = ?':'') .
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
			ASSOC_TYPE_USER,
			'interest',
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
			USER_FIELD_INTERESTS => 'cves.setting_value'
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
				LEFT JOIN controlled_vocabs cv ON (cv.assoc_type = ? AND cv.assoc_id = u.user_id AND cv.symbolic = ?)
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
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
	function getInsertId() {
		return $this->_getInsertId('edit_assignments', 'edit_id');
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
			case 'authors': return 'author_name';
			case 'title': return 'submission_title';
			case 'active': return 'p.submission_progress';
			case 'subLayout': return 'layout_completed';
			case 'status': return 'p.status';
			case 'seq': return 'pp.seq';
			default: return null;
		}
	}
}

?>
