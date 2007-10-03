<?php

/**
 * @file TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.presenter
 * @class TrackSubmissionHandler
 *
 * Handle requests for submission tracking.
 *
 * $Id$
 */

class TrackSubmissionHandler extends PresenterHandler {

	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true);

		// If the submission is incomplete, allow the presenter to delete it.
		if ($presenterSubmission->getSubmissionProgress()!=0 && $presenterSubmission->getCurrentStage()==REVIEW_STAGE_ABSTRACT) {
			import('file.PaperFileManager');
			$paperFileManager = &new PaperFileManager($paperId);
			$paperFileManager->deletePaperTree();

			$paperDao = &DAORegistry::getDAO('PaperDAO');
			$paperDao->deletePaperById($args[0]);
		}

		Request::redirect(null, null, null, 'index');
	}

	/**
	 * Delete an presenter version file.
	 * @param $args array ($paperId, $fileId)
	 */
	function deletePaperFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$fileId = isset($args[1]) ? (int) $args[1] : 0;
		$revisionId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		PresenterAction::deletePaperFile($presenterSubmission, $fileId, $revisionId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Display a summary of the status of an presenter's submission.
	 */
	function submission($args) {
		$user = &Request::getUser();
		$paperId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		$stage = isset($args[1]) ? $args[1] : $submission->getCurrentStage();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('mayEditMetadata', PresenterAction::mayEditMetadata($submission));

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack($submission->getTrackId());
		$templateMgr->assign_by_ref('track', $track);

		$templateMgr->assign_by_ref('schedConfSettings', $schedConf->getSettings(true));
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($stage));
		$templateMgr->assign('stage', $stage);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());

		import('submission.trackDirector.TrackDirectorSubmission');
		$templateMgr->assign_by_ref('directorDecisionOptions', TrackDirectorSubmission::getDirectorDecisionOptions());

		$templateMgr->assign('helpTopicId','editorial.presentersRole');
		$templateMgr->display('presenter/submission.tpl');
	}

	/**
	 * Display specific details of an presenter's submission.
	 */
	function submissionReview($args) {
		import('paper.Paper'); // for REVIEW_PROGRESS constants
		$user = &Request::getUser();
		$paperId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		$stage = (isset($args[1]) ? (int) $args[1] : 1);
		$reviewMode = $schedConf->getSetting('reviewMode');
		switch ($reviewMode) {
			case REVIEW_MODE_ABSTRACTS_ALONE:
				$stage = REVIEW_STAGE_ABSTRACT;
				break;
			case REVIEW_MODE_BOTH_SIMULTANEOUS:
			case REVIEW_MODE_PRESENTATIONS_ALONE:
				$stage = REVIEW_STAGE_PRESENTATION;
				break;
			case REVIEW_MODE_BOTH_SEQUENTIAL:
				if ($stage != REVIEW_STAGE_ABSTRACT && $stage != REVIEW_STAGE_PRESENTATION) $stage = $submission->getCurrentStage();
				break;
		}

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewModifiedByStage = $reviewAssignmentDao->getLastModifiedByStage($paperId);
		$reviewEarliestNotificationByStage = $reviewAssignmentDao->getEarliestNotificationByStage($paperId);
		$reviewFilesByStage =& $reviewAssignmentDao->getReviewFilesByStage($paperId);
		$presenterViewableFilesByStage = &$reviewAssignmentDao->getPresenterViewableFilesByStage($paperId);

		$directorDecisions = $presenterSubmission->getDecisions($presenterSubmission->getCurrentStage());
		$lastDecision = count($directorDecisions) >= 1 ? $directorDecisions[count($directorDecisions) - 1] : null;

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('submission', $presenterSubmission);
		$templateMgr->assign_by_ref('schedConfSettings', $schedConf->getSettings(true));
		$templateMgr->assign_by_ref('reviewAssignments', $presenterSubmission->getReviewAssignments($stage));
		$templateMgr->assign('stage', $stage);
		$templateMgr->assign_by_ref('reviewFilesByStage', $reviewFilesByStage);
		$templateMgr->assign_by_ref('reviewFilesByStage', $reviewFilesByStage);
		$templateMgr->assign_by_ref('presenterViewableFilesByStage', $presenterViewableFilesByStage);
		$templateMgr->assign_by_ref('reviewModifiedByStage', $reviewModifiedByStage);
		$templateMgr->assign('reviewEarliestNotificationByStage', $reviewEarliestNotificationByStage);
		$templateMgr->assign_by_ref('submissionFile', $presenterSubmission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $presenterSubmission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $presenterSubmission->getSuppFiles());
		$templateMgr->assign('lastDirectorDecision', $lastDecision);

		// Bring in director decision options
		import('submission.trackDirector.TrackDirectorSubmission');
		$templateMgr->assign_by_ref('directorDecisionOptions', TrackDirectorSubmission::getDirectorDecisionOptions());

		$templateMgr->assign('helpTopicId', 'editorial.presentersRole.review');
		$templateMgr->display('presenter/submissionReview.tpl');
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($paperId)
	 */
	function addSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($presenterSubmission);

		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Edit a supplementary file.
	 * @param $args array ($paperId, $suppFileId)
	 */
	function editSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($presenterSubmission, $suppFileId);

		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Set reviewer visibility for a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function setSuppFileVisibility($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);

		$suppFileId = Request::getUserVar('fileId');
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $paperId);

		if (isset($suppFile) && $suppFile != null) {
			$suppFile->setShowReviewers(Request::getUserVar('hide')==1?0:1);
			$suppFileDao->updateSuppFile($suppFile);
		}
		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($presenterSubmission, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, null, 'submission', $paperId);
		} else {
			parent::setupTemplate(true, $paperId, 'summary');
			$submitForm->display();
		}
	}

	/**
	 * Upload the presenter's revised version of a paper.
	 */
	function uploadRevisedVersion() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true);

		PresenterAction::uploadRevisedVersion($submission);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function viewMetadata($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		PresenterAction::viewMetadata($submission, ROLE_ID_PRESENTER);
	}

	function saveMetadata() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		// If submissions are closed, the author may not edit metadata.
		if (!PresenterAction::mayEditMetadata($submission)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		} else {

			if(PresenterAction::saveMetadata($submission)) {
				Request::redirect(null, null, null, 'submission', $paperId);
			}
		}
	}

	//
	// Misc
	//

	/**
	 * Download a file.
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function downloadFile($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		if (!PresenterAction::downloadPresenterFile($submission, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	/**
	 * Download a file.
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function download($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		Action::downloadFile($paperId, $fileId, $revision);
	}

	//
	// Validation
	//

	/**
	 * Validate that the user is the presenter for the paper.
	 * Redirects to presenter index page if validation fails.
	 */
	function validate($paperId) {
		parent::validate();

		$presenterSubmissionDao = &DAORegistry::getDAO('PresenterSubmissionDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();

		$isValid = true;

		$presenterSubmission = &$presenterSubmissionDao->getPresenterSubmission($paperId);

		if ($presenterSubmission == null) {
			$isValid = false;
		} else if ($presenterSubmission->getSchedConfId() != $schedConf->getSchedConfId()) {
			$isValid = false;
		} else {
			if ($presenterSubmission->getUserId() != $user->getUserId()) {
				$isValid = false;
			}
		}

		if (!$isValid) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		return array($conference, $schedConf, $presenterSubmission);
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function viewFile($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		if (!PresenterAction::viewFile($paperId, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

}
?>
