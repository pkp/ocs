<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for submission tracking.
 *
 * $Id$
 */

class TrackSubmissionHandler extends AuthorHandler {

	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true);

		// If the submission is incomplete, allow the author to delete it.
		if ($authorSubmission->getSubmissionProgress()!=0 && $authorSubmission->getReviewProgress()==REVIEW_PROGRESS_ABSTRACT) {
			import('file.PaperFileManager');
			$paperFileManager = &new PaperFileManager($paperId);
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

		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		AuthorAction::deletePaperFile($authorSubmission, $fileId, $revisionId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Display a summary of the status of an author's submission.
	 */
	function submission($args) {
		$user = &Request::getUser();
		$paperId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		// Set the round and current review type.
		$type = isset($args[1]) ? $args[1] : $submission->getReviewProgress();
		$round = isset($args[2]) ? $args[2] : $submission->getCurrentRound();

		$templateMgr = &TemplateManager::getManager();

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack($submission->getTrackId());
		$templateMgr->assign_by_ref('track', $track);

		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($type, $round));
		$templateMgr->assign('round', $round);
		$templateMgr->assign('type', $type);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());

		import('submission.trackEditor.TrackEditorSubmission');
		$templateMgr->assign_by_ref('editorDecisionOptions', TrackEditorSubmission::getEditorDecisionOptions());

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
		$type = (isset($args[1]) ? $args[1] : REVIEW_PROGRESS_ABSTRACT); // which item is currently under review
		$round = (isset($args[2]) ? $args[2] : 1);

		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewModifiedByRound = $reviewAssignmentDao->getLastModifiedByRound($paperId, $type);
		$reviewEarliestNotificationByRound = $reviewAssignmentDao->getEarliestNotificationByRound($paperId,$type);
		$reviewFilesByRound =& $reviewAssignmentDao->getReviewFilesByRound($paperId);
		$authorViewableFilesByRound = &$reviewAssignmentDao->getAuthorViewableFilesByRound($paperId,$type);

		$editorDecisions = $authorSubmission->getDecisions($authorSubmission->getReviewProgress(), $authorSubmission->getCurrentRound());
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1] : null;

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('submission', $authorSubmission);
		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));
		$templateMgr->assign('reviewType', $type);
		$templateMgr->assign_by_ref('reviewAssignments', $authorSubmission->getReviewAssignments($type, $round));
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('authorViewableFilesByRound', $authorViewableFilesByRound);
		$templateMgr->assign_by_ref('reviewModifiedByRound', $reviewModifiedByRound);
		$templateMgr->assign('reviewEarliestNotificationByRound', $reviewEarliestNotificationByRound);
		$templateMgr->assign_by_ref('submissionFile', $authorSubmission->getSubmissionFile());
		$templateMgr->assign_by_ref('revisedFile', $authorSubmission->getRevisedFile());
		$templateMgr->assign_by_ref('suppFiles', $authorSubmission->getSuppFiles());
		$templateMgr->assign('lastEditorDecision', $lastDecision);
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.paper.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.paper.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.paper.decision.decline'
			)
		);
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.review');
		$templateMgr->display('author/submissionReview.tpl');
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($paperId)
	 */
	function addSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($authorSubmission);

		$submitForm->initData();
		$submitForm->display();
	}

	/**
	 * Edit a supplementary file.
	 * @param $args array ($paperId, $suppFileId)
	 */
	function editSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($authorSubmission, $suppFileId);

		$submitForm->initData();
		$submitForm->display();
	}

	/**
	 * Set reviewer visibility for a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function setSuppFileVisibility($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);

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
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($authorSubmission, $suppFileId);
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
	 * Display the status and other details of an author's submission.
	 */
	function submissionEditing($args) {
		$user = &Request::getUser();
		$paperId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.editing');
		$templateMgr->display('author/submissionEditing.tpl');
	}

	/**
	 * Upload the author's revised version of an paper.
	 */
	function uploadRevisedVersion() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true);

		AuthorAction::uploadRevisedVersion($submission);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function viewMetadata($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		AuthorAction::viewMetadata($submission, ROLE_ID_AUTHOR);
	}

	function saveMetadata() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		// If abstract review is complete, disallow the author from changing the
		// metadata.
		if ($submission->getReviewProgress() != REVIEW_PROGRESS_ABSTRACT) {
			Request::redirect(null, null, null, 'submission', $paperId);

		} else {

			if(AuthorAction::saveMetadata($submission)) {
				Request::redirect(null, null, null, 'submission', $paperId);
			} else {
				AuthorAction::viewMetadata($submission, ROLE_ID_AUTHOR);
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

		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);
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

		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);
		Action::downloadFile($paperId, $fileId, $revision);
	}

	//
	// Validation
	//

	/**
	 * Validate that the user is the author for the paper.
	 * Redirects to author index page if validation fails.
	 */
	function validate($paperId) {
		parent::validate();

		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$conference = &Request::getConference();
		$event = &Request::getEvent();
		$user = &Request::getUser();

		$isValid = true;

		$authorSubmission = &$authorSubmissionDao->getAuthorSubmission($paperId);

		if ($authorSubmission == null) {
			$isValid = false;
		} else if ($authorSubmission->getEventId() != $event->getEventId()) {
			$isValid = false;
		} else {
			if ($authorSubmission->getUserId() != $user->getUserId()) {
				$isValid = false;
			}
		}

		if (!$isValid) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		return array($conference, $event, $authorSubmission);
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function viewFile($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);
		if (!AuthorAction::viewFile($paperId, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

}
?>
