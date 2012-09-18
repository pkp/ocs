<?php

/**
 * @file TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackSubmissionHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for submission tracking.
 */

//$Id$

import('pages.author.AuthorHandler');

class TrackSubmissionHandler extends AuthorHandler {
	/** submission associated with the request **/
	var $submission;
		
	/**
	 * Constructor
	 **/
	function TrackSubmissionHandler() {
		parent::AuthorHandler();
	}

	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		$paperId = (int) array_shift($args);
		$this->validate($paperId, null, true);
		$authorSubmission =& $this->submission;

		$this->setupTemplate(true);

		// If the submission is incomplete, allow the author to delete it.
		if ($authorSubmission->getSubmissionProgress()!=0) {
			import('file.PaperFileManager');
			$paperFileManager = new PaperFileManager($paperId);
			$paperFileManager->deletePaperTree();

			$paperDao =& DAORegistry::getDAO('PaperDAO');
			$paperDao->deletePaperById($paperId);
		}

		Request::redirect(null, null, null, 'index');
	}

	/**
	 * Delete an author version file.
	 * @param $args array ($paperId, $fileId)
	 */
	function deletePaperFile($args) {
		$paperId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revisionId = (int) array_shift($args);

		$this->validate($paperId, true);
		$authorSubmission =& $this->submission;
				
		AuthorAction::deletePaperFile($authorSubmission, $fileId, $revisionId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Display a summary of the status of an author's submission.
	 */
	function submission($args) {
		$user =& Request::getUser();
		$paperId = (int) array_shift($args);
		$stage = (int) array_shift($args);
		$schedConf =& Request::getSchedConf();

		$this->validate($paperId);
		$submission =& $this->submission;

		// The user may be coming in on an old URL e.g. from the submission
		// ack email. If OCS is awaiting the completion of the submission,
		// send them to the submit page.
		if ($submission->getSubmissionProgress() != 0) {
			Request::redirect(
				null, null, null, 'submit',
				array($submission->getSubmissionProgress()),
				array('paperId' => $paperId)
			);
		}

		$this->setupTemplate(true, $paperId);

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

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($submission->getPaperId());

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('mayEditPaper', AuthorAction::mayEditPaper($submission));

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($submission->getTrackId());
		$templateMgr->assign_by_ref('track', $track);

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('publishedPaper', $publishedPaper);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($stage));
		$templateMgr->assign('stage', $stage);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());

		$controlledVocabDao =& DAORegistry::getDAO('ControlledVocabDAO');
		$templateMgr->assign('sessionTypes', $controlledVocabDao->enumerateBySymbolic('sessionTypes', ASSOC_TYPE_SCHED_CONF, $schedConf->getId()));

		// FIXME: Author code should not use track director object
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($submission->getPaperId());
		$templateMgr->assign_by_ref('directorDecisionOptions', $trackDirectorSubmission->getDirectorDecisionOptions());

		$templateMgr->assign('helpTopicId','editorial.authorsRole');
		$templateMgr->display('author/submission.tpl');
	}

	/**
	 * Display specific details of an author's submission.
	 */
	function submissionReview($args) {
		import('paper.Paper'); // for REVIEW_PROGRESS constants
		$user =& Request::getUser();
		$paperId = (int) array_shift($args);
		$stage = (int) array_shift($args);

		$this->validate($paperId);
		$authorSubmission =& $this->submission;
		$this->setupTemplate(true, $paperId);
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OCS_DIRECTOR)); // FIXME?

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

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewModifiedByStage = $reviewAssignmentDao->getLastModifiedByStage($paperId);
		$reviewEarliestNotificationByStage = $reviewAssignmentDao->getEarliestNotificationByStage($paperId);
		$reviewFilesByStage =& $reviewAssignmentDao->getReviewFilesByStage($paperId);
		$authorViewableFilesByStage =& $reviewAssignmentDao->getAuthorViewableFilesByStage($paperId);

		$directorDecisions = $authorSubmission->getDecisions($authorSubmission->getCurrentStage());
		$lastDecision = count($directorDecisions) >= 1 ? $directorDecisions[count($directorDecisions) - 1] : null;

		$templateMgr =& TemplateManager::getManager();
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

		// FIXME: Author code should not use track director object
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($authorSubmission->getPaperId());
		$templateMgr->assign_by_ref('directorDecisionOptions', $trackDirectorSubmission->getDirectorDecisionOptions());

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
		$paperId = (int) array_shift($args);
		$this->validate($paperId, true);
		$authorSubmission =& $this->submission;
		$this->setupTemplate(true, $paperId, 'summary');
		
		import('submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($authorSubmission);

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
		$paperId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$this->validate($paperId);

		$this->setupTemplate(true, $paperId, 'summary');

		// View supplementary file only
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile =& $suppFileDao->getSuppFile($suppFileId, $paperId);

		if (!isset($suppFile)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign_by_ref('suppFile', $suppFile);
		$templateMgr->display('submission/suppFile/suppFileView.tpl');	
	}

	/**
	 * Edit a supplementary file.
	 * @param $args array ($paperId, $suppFileId)
	 */
	function editSuppFile($args) {
		$paperId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$this->validate($paperId, true);
		$authorSubmission =& $this->submission;

		$this->setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($authorSubmission, $suppFileId);

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
		$paperId = (int) array_shift($args);
		$this->validate($paperId, true);
		$authorSubmission =& $this->submission;

		$suppFileId = (int) Request::getUserVar('fileId');
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
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
		$paperId = (int) Request::getUserVar('paperId');
		$this->validate($paperId, true);
		$authorSubmission =& $this->submission;
		parent::setupTemplate(true, $paperId, 'summary');

		$suppFileId = (int) array_shift($args);

		import('submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($authorSubmission, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, null, 'submission', $paperId);
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Upload the author's revised version of a paper.
	 */
	function uploadRevisedVersion() {
		$paperId = (int) Request::getUserVar('paperId');
		$this->validate($paperId, true);
		$submission =& $this->submission;

		AuthorAction::uploadRevisedVersion($submission);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * View/edit metadata.
	 */
	function viewMetadata($args) {
		$paperId = (int) array_shift($args);
		$this->validate($paperId);
		$submission =& $this->submission;

		$this->setupTemplate(true, $paperId, 'summary');

		AuthorAction::viewMetadata($submission, ROLE_ID_AUTHOR);
	}

	/**
	 * Save metadata modifications.
	 */
	function saveMetadata() {
		$paperId = (int) Request::getUserVar('paperId');
		$this->validate($paperId, true);
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId);

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
		$paperId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);

		$this->validate($paperId);
		$submission =& $this->submission;
				
		if (!AuthorAction::downloadAuthorFile($submission, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	/**
	 * Download a file.
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function download($args) {
		$paperId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);

		$this->validate($paperId);
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
	 * @param $isDeleting boolean True iff user is deleting a paper
	 */
	function validate($paperId, $requiresEditAccess = false, $isDeleting = false) {
		parent::validate();

		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();

		$isValid = true;

		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($paperId);

		if ($authorSubmission == null) {
			$isValid = false;
		} else if ($authorSubmission->getSchedConfId() != $schedConf->getId()) {
			$isValid = false;
		} else {
			if ($authorSubmission->getUserId() != $user->getId()) {
				$isValid = false;
			}
		}

		if ($isValid && $requiresEditAccess) {
			if (!AuthorAction::mayEditPaper($authorSubmission)) $isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		$this->submission =& $authorSubmission;
		return true;
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function viewFile($args) {
		$paperId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);

		$this->validate($paperId);
		if (!AuthorAction::viewFile($paperId, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}
}

?>
