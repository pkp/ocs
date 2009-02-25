<?php

/**
 * @file TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackSubmissionHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for submission tracking.
 */

//$Id$

class TrackSubmissionHandler extends AuthorHandler {

	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $authorSubmission) = TrackSubmissionHandler::validate($paperId, null, true);
		parent::setupTemplate(true);

		// If the submission is incomplete, allow the author to delete it.
		if ($authorSubmission->getSubmissionProgress()!=0) {
			import('file.PaperFileManager');
			$paperFileManager = new PaperFileManager($paperId);
			$paperFileManager->deletePaperTree();

			$paperDao = &DAORegistry::getDAO('PaperDAO');
			$paperDao->deletePaperById($args[0]);
		}

		Request::redirect(null, null, null, 'index');
	}

	/**
	 * Delete an author version file.
	 * @param $args array ($paperId, $fileId)
	 */
	function deletePaperFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$fileId = isset($args[1]) ? (int) $args[1] : 0;
		$revisionId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $schedConf, $authorSubmission) = TrackSubmissionHandler::validate($paperId, true);
		AuthorAction::deletePaperFile($authorSubmission, $fileId, $revisionId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Display a summary of the status of an author's submission.
	 */
	function submission($args) {
		$user = &Request::getUser();
		$paperId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		$stage = (isset($args[1]) ? (int) $args[1] : 1);
		$reviewMode = $submission->getReviewMode();
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

		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($submission->getPaperId());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('mayEditPaper', AuthorAction::mayEditPaper($submission));

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack($submission->getTrackId());
		$templateMgr->assign_by_ref('track', $track);

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('publishedPaper', $publishedPaper);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($stage));
		$templateMgr->assign('stage', $stage);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());

		$controlledVocabDao =& DAORegistry::getDAO('ControlledVocabDAO');
		$templateMgr->assign('sessionTypes', $controlledVocabDao->enumerateBySymbolic('sessionTypes', ASSOC_TYPE_SCHED_CONF, $schedConf->getSchedConfId()));

		import('submission.trackDirector.TrackDirectorSubmission');
		$templateMgr->assign_by_ref('directorDecisionOptions', TrackDirectorSubmission::getDirectorDecisionOptions());

		$templateMgr->assign('helpTopicId','editorial.authorsRole');
		$templateMgr->display('author/submission.tpl');
	}

	/**
	 * Display specific details of an author's submission.
	 */
	function submissionReview($args) {
		import('paper.Paper'); // for REVIEW_PROGRESS constants
		$user = &Request::getUser();
		$paperId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $schedConf, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		$stage = (isset($args[1]) ? (int) $args[1] : 1);
		$reviewMode = $authorSubmission->getReviewMode();
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
		$authorViewableFilesByStage = &$reviewAssignmentDao->getAuthorViewableFilesByStage($paperId);

		$directorDecisions = $authorSubmission->getDecisions($authorSubmission->getCurrentStage());
		$lastDecision = count($directorDecisions) >= 1 ? $directorDecisions[count($directorDecisions) - 1] : null;

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('submission', $authorSubmission);
		$templateMgr->assign_by_ref('reviewAssignments', $authorSubmission->getReviewAssignments($stage));
		$templateMgr->assign('stage', $stage);
		$templateMgr->assign_by_ref('reviewFilesByStage', $reviewFilesByStage);
		$templateMgr->assign_by_ref('authorViewableFilesByStage', $authorViewableFilesByStage);
		$templateMgr->assign_by_ref('reviewModifiedByStage', $reviewModifiedByStage);
		$templateMgr->assign('reviewEarliestNotificationByStage', $reviewEarliestNotificationByStage);
		$templateMgr->assign_by_ref('submissionFile', $authorSubmission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $authorSubmission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $authorSubmission->getSuppFiles());
		$templateMgr->assign('lastDirectorDecision', $lastDecision);

		// Bring in director decision options
		import('submission.trackDirector.TrackDirectorSubmission');
		$templateMgr->assign_by_ref('directorDecisionOptions', TrackDirectorSubmission::getDirectorDecisionOptions());

		// Determine whether or not certain features should be disabled (i.e. past deadline)
		$templateMgr->assign('mayEditPaper', AuthorAction::mayEditPaper($authorSubmission));

		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.review');
		$templateMgr->display('author/submissionReview.tpl');
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($paperId)
	 */
	function addSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $authorSubmission) = TrackSubmissionHandler::validate($paperId, true);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$submitForm =& new SuppFileForm($authorSubmission);

		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * View a supplementary file.
	 * @param $args array ($paperId, $suppFileId)
	 */
	function viewSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $authorSubmission) = TrackSubmissionHandler::validate($paperId);

		parent::setupTemplate(true, $paperId, 'summary');

		// View supplementary file only
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $paperId);

		if (!isset($suppFile)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign_by_ref('suppFile', $suppFile);
		$templateMgr->display('submission/suppFile/suppFileView.tpl');	
	}

	/**
	 * Edit a supplementary file.
	 * @param $args array ($paperId, $suppFileId)
	 */
	function editSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $authorSubmission) = TrackSubmissionHandler::validate($paperId, true);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$submitForm =& new SuppFileForm($authorSubmission, $suppFileId);

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
		list($conference, $schedConf, $authorSubmission) = TrackSubmissionHandler::validate($paperId, true);

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
		list($conference, $schedConf, $authorSubmission) = TrackSubmissionHandler::validate($paperId, true);

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('submission.form.SuppFileForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$submitForm =& new SuppFileForm($authorSubmission, $suppFileId);
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
	 * Upload the author's revised version of a paper.
	 */
	function uploadRevisedVersion() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId, true);
		parent::setupTemplate(true);

		AuthorAction::uploadRevisedVersion($submission);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function viewMetadata($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		AuthorAction::viewMetadata($submission, ROLE_ID_AUTHOR);
	}

	function saveMetadata() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId, true);
		parent::setupTemplate(true, $paperId);

		if(AuthorAction::saveMetadata($submission)) {
			Request::redirect(null, null, null, 'submission', $paperId);
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
		if (!AuthorAction::downloadAuthorFile($submission, $fileId, $revision)) {
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
	 * Validate that the user is the author for the paper.
	 * Redirects to author index page if validation fails.
	 * @param $paperId int
	 * @param $requiresEditAccess boolean True means that the author must
	 * 	  have edit access over the specified paper in order for
	 * 	  validation to be successful.
	 * @param $isDeleting boolean True iff user is deleting a paper, and is not
	 *	  coming from an old URL (e.g. submission ack email)
	 */
	function validate($paperId, $requiresEditAccess = false, $isDeleting = false) {
		parent::validate();

		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();

		$isValid = true;

		$authorSubmission = &$authorSubmissionDao->getAuthorSubmission($paperId);

		if ($authorSubmission == null) {
			$isValid = false;
		} else if ($authorSubmission->getSchedConfId() != $schedConf->getSchedConfId()) {
			$isValid = false;
		} else {
			if ($authorSubmission->getUserId() != $user->getUserId()) {
				$isValid = false;
			}
		}

		if ($isValid && !$isDeleting) {
			// The user may be coming in on an old URL e.g. from the submission
			// ack email. If OCS is awaiting the completion of the submission,
			// send them to the submit page.
			if ($authorSubmission->getSubmissionProgress() != 0) {
				Request::redirect(
					null, null, null, 'submit',
					array($authorSubmission->getSubmissionProgress()),
					array('paperId' => $authorSubmission->getPaperId())
				);
			}
		}

		if ($isValid && $requiresEditAccess) {
			if (!AuthorAction::mayEditPaper($authorSubmission)) $isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		return array($conference, $schedConf, $authorSubmission);
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
		if (!AuthorAction::viewFile($paperId, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}
}

?>
