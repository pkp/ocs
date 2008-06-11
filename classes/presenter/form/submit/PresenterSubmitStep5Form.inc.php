<?php

/**
 * @file PresenterSubmitStep5Form.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package presenter.form.submit
 * @class PresenterSubmitStep5Form
 *
 * Form for Step 5 of presenter paper submission.
 *
 * $Id$
 */

import("presenter.form.submit.PresenterSubmitForm");

class PresenterSubmitStep5Form extends PresenterSubmitForm {

	/**
	 * Constructor.
	 */
	function PresenterSubmitStep5Form($paper) {
		parent::PresenterSubmitForm($paper, 5);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();

		// Get paper file for this paper
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$paperFiles =& $paperFileDao->getPaperFilesByPaper($this->paperId);

		$templateMgr->assign_by_ref('files', $paperFiles);
		$templateMgr->assign_by_ref('conference', Request::getConference());

		parent::display();
	}

	/**
	 * Save changes to paper.
	 */
	function execute() {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		$conference = Request::getConference();
		$schedConf = Request::getSchedConf();

		// Update paper
		$paper = &$this->paper;
		$paper->setDateSubmitted(Core::getCurrentDate());
		$paper->setSubmissionProgress(0);
		$paper->stampStatusModified();

		// We've collected the paper now -- bump the review progress
		$paper->setCurrentStage(REVIEW_STAGE_PRESENTATION);

		$paperDao->updatePaper($paper);

		// Designate this as the review version by default.
		$presenterSubmissionDao =& DAORegistry::getDAO('PresenterSubmissionDAO');
		$presenterSubmission =& $presenterSubmissionDao->getPresenterSubmission($paper->getPaperId());
		PresenterAction::designateReviewVersion($presenterSubmission);
		unset($presenterSubmission);

		// Update any review assignments so they may access the file
		$presenterSubmission =& $presenterSubmissionDao->getPresenterSubmission($paper->getPaperId());
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByPaperId($paper->getPaperId(), REVIEW_STAGE_PRESENTATION);
		foreach($reviewAssignments as $reviewAssignment) {
			$reviewAssignment->setReviewFileId($presenterSubmission->getReviewFileId());
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		}

		$reviewMode = $presenterSubmission->getReviewMode();
		$user =& Request::getUser();

		$trackDirectors = array();
		if ($reviewMode == REVIEW_MODE_BOTH_SIMULTANEOUS || $reviewMode == REVIEW_MODE_PRESENTATIONS_ALONE) {
			// Editors have not yet been assigned; assign them.
			$trackDirectors = $this->assignDirectors($paper);
		}

		$this->confirmSubmission($paper, $user, $schedConf, $conference, $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL?'SUBMISSION_UPLOAD_ACK':'SUBMISSION_ACK', $trackDirectors);

		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($this->paperId, PAPER_LOG_PRESENTATION_SUBMIT, LOG_TYPE_PRESENTER, $user->getUserId(), 'log.presenter.presentationSubmitted', array('submissionId' => $paper->getPaperId(), 'presenterName' => $user->getFullName()));

		return $this->paperId;
	}

}

?>
