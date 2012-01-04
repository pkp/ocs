<?php

/**
 * @file AuthorSubmissionDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmissionDAO
 * @ingroup submission
 * @see AuthorSubmission
 *
 * @brief Operations for retrieving and modifying AuthorSubmission objects.
 */

//$Id$

import('submission.author.AuthorSubmission');

class AuthorSubmissionDAO extends DAO {

	var $paperDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $paperFileDao;
	var $suppFileDao;
	var $paperCommentDao;
	var $galleyDao;

	/**
	 * Constructor.
	 */
	function AuthorSubmissionDAO() {
		parent::DAO();
		$this->paperDao =& DAORegistry::getDAO('PaperDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$this->paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$this->galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
	}

	/**
	 * Retrieve a author submission by paper ID.
	 * @param $paperId int
	 * @return AuthorSubmission
	 */
	function &getAuthorSubmission($paperId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result =& $this->retrieve(
			'SELECT
				p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
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
			$returner =& $this->_returnAuthorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a AuthorSubmission object from a row.
	 * @param $row array
	 * @return AuthorSubmission
	 */
	function &_returnAuthorSubmissionFromRow(&$row) {
		$authorSubmission = new AuthorSubmission();

		// Paper attributes
		$this->paperDao->_paperFromRow($authorSubmission, $row);

		// Director Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$authorSubmission->setEditAssignments($editAssignments->toArray());

		// Director Decisions
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$authorSubmission->setDecisions($this->getDirectorDecisions($row['paper_id'], $i), $i);
		}

		// Review Assignments
		for ($i = 1; $i <= $row['current_stage']; $i++)
			$authorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByPaperId($row['paper_id'], $i), $i);

		// Comments
		$authorSubmission->setMostRecentDirectorDecisionComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_DIRECTOR_DECISION, $row['paper_id']));

		// Files
		$authorSubmission->setSubmissionFile($this->paperFileDao->getPaperFile($row['submission_file_id']));
		$authorSubmission->setRevisedFile($this->paperFileDao->getPaperFile($row['revised_file_id']));
		$authorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$authorSubmission->setAuthorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['revised_file_id'], $i), $i);
		}
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$authorSubmission->setDirectorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['director_file_id'], $i), $i);
		}
		$authorSubmission->setLayoutFile($this->paperFileDao->getPaperFile($row['layout_file_id']));
		$authorSubmission->setGalleys($this->galleyDao->getGalleysByPaper($row['paper_id']));

		HookRegistry::call('AuthorSubmissionDAO::_returnAuthorSubmissionFromRow', array(&$authorSubmission, &$row));

		return $authorSubmission;
	}

	/**
	 * Update an existing author submission.
	 * @param $authorSubmission AuthorSubmission
	 */
	function updateAuthorSubmission(&$authorSubmission) {
		// Update paper
		if ($authorSubmission->getPaperId()) {
			$paper =& $this->paperDao->getPaper($authorSubmission->getPaperId());

			// Only update fields that an author can actually edit.
			$paper->setRevisedFileId($authorSubmission->getRevisedFileId());
			$paper->setDateStatusModified($authorSubmission->getDateStatusModified());
			$paper->setLastModified($authorSubmission->getLastModified());
			// FIXME: These two are necessary for designating the
			// original as the review version, but they are probably
			// best not exposed like this.
			$paper->setReviewFileId($authorSubmission->getReviewFileId());
			$paper->setDirectorFileId($authorSubmission->getDirectorFileId());

			$this->paperDao->updatePaper($paper);
		}
	}

	/**
	 * Get all incomplete submissions.
	 * @return DAOResultFactory containing AuthorSubmissions
	 */
	function &getIncompleteSubmissions() {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$incompleteSubmissions = array();
		$result =& $this->retrieve(
			'SELECT	p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	p.submission_progress != 0 AND
				p.status = ' . (int)STATUS_QUEUED,
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale
			)
		);

		while(!$result->EOF) {
			$incompleteSubmissions[] =& $this->_returnAuthorSubmissionFromRow($result->getRowAssoc(false));
			$result->moveNext();
		}
		return $incompleteSubmissions;
	}

	/**
	 * Get all author submissions for an author.
	 * @param $authorId int
	 * @return DAOResultFactory containing AuthorSubmissions
	 */
	function &getAuthorSubmissions($authorId, $schedConfId, $active = true, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$result =& $this->retrieveRange(
			'SELECT	p.*,
				COALESCE(ptl.setting_value, pptl.setting_value) AS submission_title,
				pa.last_name AS author_name,
				(SELECT SUM(g.views) FROM paper_galleys g WHERE (g.paper_id = p.paper_id AND g.locale = ?)) AS galley_views,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN paper_authors pa ON (pa.paper_id = p.paper_id AND pa.primary_contact = 1)
				LEFT JOIN paper_settings pptl ON (p.paper_id = pptl.paper_id AND pptl.setting_name = ? AND pptl.locale = ?)
				LEFT JOIN paper_settings ptl ON (p.paper_id = ptl.paper_id AND ptl.setting_name = ? AND ptl.locale = ?)
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	p.sched_conf_id = ?
				AND p.user_id = ?' .
				($active?(' AND p.status = ' . (int) STATUS_QUEUED):(
					' AND ((p.status <> ' . (int) STATUS_QUEUED . ' AND p.submission_progress = 0) OR (p.status = ' . (int) STATUS_ARCHIVED . '))'
				)) .
				($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : ''),
			array(
				$locale,
				'cleanTitle',
				$primaryLocale,
				'cleanTitle',
				$locale,
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$schedConfId,
				$authorId
			),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAuthorSubmissionFromRow');
		return $returner;
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
	 * Get count of active and complete assignments
	 * @param authorId int
	 * @param schedConfId int
	 */
	function getSubmissionsCount($authorId, $schedConfId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = '
			SELECT	count(*), status
			FROM	papers p 
			WHERE	p.sched_conf_id = ? AND
				p.user_id = ?
			GROUP BY p.status';

		$result =& $this->retrieve($sql, array($schedConfId, $authorId));

		while (!$result->EOF) {
			if ($result->fields['status'] != 1) {
				$submissionsCount[1] += $result->fields[0];
			} else {
				$submissionsCount[0] += $result->fields[0];
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
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
			case 'views': return 'galley_views';
			case 'status': return 'p.status';
			default: return null;
		}
	}
}

?>
