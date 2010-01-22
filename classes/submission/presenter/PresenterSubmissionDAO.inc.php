<?php

/**
 * @file PresenterSubmissionDAO.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PresenterSubmissionDAO
 * @ingroup submission
 * @see PresenterSubmission
 *
 * @brief Operations for retrieving and modifying PresenterSubmission objects.
 */

//$Id$

import('submission.presenter.PresenterSubmission');

class PresenterSubmissionDAO extends DAO {

	var $paperDao;
	var $presenterDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $paperFileDao;
	var $suppFileDao;
	var $paperCommentDao;
	var $galleyDao;

	/**
	 * Constructor.
	 */
	function PresenterSubmissionDAO() {
		parent::DAO();
		$this->paperDao = &DAORegistry::getDAO('PaperDAO');
		$this->presenterDao = &DAORegistry::getDAO('PresenterDAO');
		$this->userDao = &DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$this->paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$this->galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
	}

	/**
	 * Retrieve a presenter submission by paper ID.
	 * @param $paperId int
	 * @return PresenterSubmission
	 */
	function &getPresenterSubmission($paperId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
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
			$returner = &$this->_returnPresenterSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a PresenterSubmission object from a row.
	 * @param $row array
	 * @return PresenterSubmission
	 */
	function &_returnPresenterSubmissionFromRow(&$row) {
		$presenterSubmission = &new PresenterSubmission();

		// Paper attributes
		$this->paperDao->_paperFromRow($presenterSubmission, $row);

		// Director Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByPaperId($row['paper_id']);
		$presenterSubmission->setEditAssignments($editAssignments->toArray());

		// Director Decisions
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$presenterSubmission->setDecisions($this->getDirectorDecisions($row['paper_id'], $i), $i);
		}

		// Review Assignments
		for ($i = 1; $i <= $row['current_stage']; $i++)
			$presenterSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByPaperId($row['paper_id'], $i), $i);

		// Comments
		$presenterSubmission->setMostRecentDirectorDecisionComment($this->paperCommentDao->getMostRecentPaperComment($row['paper_id'], COMMENT_TYPE_DIRECTOR_DECISION, $row['paper_id']));

		// Files
		$presenterSubmission->setSubmissionFile($this->paperFileDao->getPaperFile($row['submission_file_id']));
		$presenterSubmission->setRevisedFile($this->paperFileDao->getPaperFile($row['revised_file_id']));
		$presenterSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$presenterSubmission->setPresenterFileRevisions($this->paperFileDao->getPaperFileRevisions($row['revised_file_id'], $i), $i);
		}
		for ($i = 1; $i <= $row['current_stage']; $i++) {
			$presenterSubmission->setDirectorFileRevisions($this->paperFileDao->getPaperFileRevisions($row['director_file_id'], $i), $i);
		}
		$presenterSubmission->setLayoutFile($this->paperFileDao->getPaperFile($row['layout_file_id']));
		$presenterSubmission->setGalleys($this->galleyDao->getGalleysByPaper($row['paper_id']));

		HookRegistry::call('PresenterSubmissionDAO::_returnPresenterSubmissionFromRow', array(&$presenterSubmission, &$row));

		return $presenterSubmission;
	}

	/**
	 * Update an existing presenter submission.
	 * @param $presenterSubmission PresenterSubmission
	 */
	function updatePresenterSubmission(&$presenterSubmission) {
		// Update paper
		if ($presenterSubmission->getPaperId()) {
			$paper = &$this->paperDao->getPaper($presenterSubmission->getPaperId());

			// Only update fields that an presenter can actually edit.
			$paper->setRevisedFileId($presenterSubmission->getRevisedFileId());
			$paper->setDateStatusModified($presenterSubmission->getDateStatusModified());
			$paper->setLastModified($presenterSubmission->getLastModified());
			// FIXME: These two are necessary for designating the
			// original as the review version, but they are probably
			// best not exposed like this.
			$paper->setReviewFileId($presenterSubmission->getReviewFileId());
			$paper->setDirectorFileId($presenterSubmission->getDirectorFileId());

			$this->paperDao->updatePaper($paper);
		}
	}

	/**
	 * Get all incomplete submissions.
	 * @return DAOResultFactory containing PresenterSubmissions
	 */
	function &getIncompleteSubmissions() {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
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
				p.status = ' . (int)SUBMISSION_STATUS_QUEUED,
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
			$incompleteSubmissions[] = &$this->_returnPresenterSubmissionFromRow($result->getRowAssoc(false));
			$result->moveNext();
		}
		return $incompleteSubmissions;
	}

	/**
	 * Get all presenter submissions for an presenter.
	 * @param $presenterId int
	 * @return DAOResultFactory containing PresenterSubmissions
	 */
	function &getPresenterSubmissions($presenterId, $schedConfId, $active = true, $rangeInfo = null) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result =& $this->retrieveRange(
			'SELECT	p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN tracks t ON (t.track_id = p.track_id)
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	p.sched_conf_id = ?
				AND p.user_id = ?' .
				($active?(' AND p.status = ' . (int) SUBMISSION_STATUS_QUEUED):(
					' AND ((p.status <> ' . (int) SUBMISSION_STATUS_QUEUED . ' AND p.submission_progress = 0) OR (p.status = ' . (int) SUBMISSION_STATUS_ARCHIVED . '))'
				)),
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$schedConfId,
				$presenterId
			),
			$rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnPresenterSubmissionFromRow');
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
	 * Get count of active and complete assignments
	 * @param presenterId int
	 * @param schedConfId int
	 */
	function getSubmissionsCount($presenterId, $schedConfId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = '
			SELECT	count(*), status
			FROM	papers p 
			WHERE	p.sched_conf_id = ? AND
				p.user_id = ?
			GROUP BY p.status';

		$result = &$this->retrieve($sql, array($schedConfId, $presenterId));

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
}

?>
