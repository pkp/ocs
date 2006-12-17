<?php

/**
 * SubmissionReviewHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

class SubmissionReviewHandler extends ReviewerHandler {
	
	function submission($args) {
		$reviewId = $args[0];

		list($event, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentById($reviewId);
	
		if ($submission->getDateConfirmed() == null) {
			$confirmedStatus = 0;
		} else {
			$confirmedStatus = 1;
		}

		ReviewerHandler::setupTemplate(true, $submission->getPaperId());
	
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignment', $reviewAssignment);
		$templateMgr->assign('confirmedStatus', $confirmedStatus);
		$templateMgr->assign('declined', $submission->getDeclined());
		$templateMgr->assign_by_ref('reviewFile', $reviewAssignment->getReviewFile());
		$templateMgr->assign_by_ref('reviewerFile', $submission->getReviewerFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('event', $event);
		$templateMgr->assign_by_ref('reviewGuidelines', $event->getSetting('reviewGuidelines', true));

		// The reviewer instructions differ depending on what is reviewed, and when.
		if($submission->getReviewProgress()==REVIEW_PROGRESS_ABSTRACT && !$event->getCollectPapersWithAbstracts())
			$templateMgr->assign('reviewerInstruction3', 'reviewer.paper.reviewerInstruction3AbstractOnly');
		else
			$templateMgr->assign('reviewerInstruction3', 'reviewer.paper.reviewerInstruction3Submission');

		import('submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$templateMgr->assign('helpTopicId', 'editorial.reviewersRole.review');		
		$templateMgr->display('reviewer/submission.tpl');
	}
	
	function confirmReview($args = null) {
		$reviewId = Request::getUserVar('reviewId');
		$declineReview = Request::getUserVar('declineReview');
		
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');

		list($event, $reviewerSubmission, $user) = SubmissionReviewHandler::validate($reviewId);

		ReviewerHandler::setupTemplate();

		$decline = isset($declineReview) ? 1 : 0;
		
		if (!$reviewerSubmission->getCancelled()) {
			if (ReviewerAction::confirmReview($reviewerSubmission, $decline, Request::getUserVar('send'))) {
				Request::redirect(null, null, null, 'submission', $reviewId);
			}
		} else {
			Request::redirect(null, null, null, 'submission', $reviewId);
		}
	}
	
	function recordRecommendation() {
		$reviewId = Request::getUserVar('reviewId');
		$recommendation = Request::getUserVar('recommendation');

		list($event, $reviewerSubmission, $user) = SubmissionReviewHandler::validate($reviewId);

		ReviewerHandler::setupTemplate(true);

		if (!$reviewerSubmission->getCancelled()) {
			if (ReviewerAction::recordRecommendation($reviewerSubmission, $recommendation, Request::getUserVar('send'))) {
				Request::redirect(null, null, null, 'submission', $reviewId);
			}
		} else {
			Request::redirect(null, null, null, 'submission', $reviewId);
		}
	}
	
	function viewMetadata($args) {
		$reviewId = $args[0];
		$paperId = $args[1];
		
		list($event, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);

		parent::setupTemplate(true, $paperId, 'review');

		ReviewerAction::viewMetadata($reviewerSubmission, ROLE_ID_REVIEWER);
	}
	
	/**
	 * Upload the reviewer's annotated version of an paper.
	 */
	function uploadReviewerVersion() {
		$reviewId = Request::getUserVar('reviewId');
		
		list($event, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);

		ReviewerHandler::setupTemplate(true);
		ReviewerAction::uploadReviewerVersion($reviewId);
		Request::redirect(null, null, null, 'submission', $reviewId);
	}

	/*
	 * Delete one of the reviewer's annotated versions of an paper.
	 */
	function deleteReviewerVersion($args) {		
                $reviewId = isset($args[0]) ? (int) $args[0] : 0;
		$fileId = isset($args[1]) ? (int) $args[1] : 0;
		$revision = isset($args[2]) ? (int) $args[2] : null;
		
		list($event, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);

		if (!$reviewerSubmission->getCancelled()) ReviewerAction::deleteReviewerVersion($reviewId, $fileId, $revision);
		Request::redirect(null, null, null, 'submission', $reviewId);
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file.
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function downloadFile($args) {
		$reviewId = isset($args[0]) ? $args[0] : 0;
		$paperId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? $args[2] : 0;
		$revision = isset($args[3]) ? $args[3] : null;

		list($event, $reviewerSubmission) = SubmissionReviewHandler::validate($reviewId);
		if (!ReviewerAction::downloadReviewerFile($reviewId, $reviewerSubmission, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $reviewId);
		}
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is an assigned reviewer for
	 * the paper.
	 * Redirects to reviewer index page if validation fails.
	 */
	function validate($reviewId) {
		$reviewerSubmissionDao = &DAORegistry::getDAO('ReviewerSubmissionDAO');
		$event = &Request::getEvent();
		$user = &Request::getUser();
		
		$isValid = true;
		$newKey = Request::getUserVar('key');
		
		$reviewerSubmission = &$reviewerSubmissionDao->getReviewerSubmission($reviewId);
		
		if (!$reviewerSubmission || $reviewerSubmission->getEventId() != $event->getEventId()) {
			$isValid = false;
		} elseif ($user && empty($newKey)) {
			if ($reviewerSubmission->getReviewerId() != $user->getUserId()) {
				$isValid = false;
			}
		} else {
			$user =& SubmissionReviewHandler::validateAccessKey($reviewerSubmission->getReviewerId(), $reviewId, $newKey);
			if (!$user) $isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		return array($event, $reviewerSubmission, $user);
	}
}
?>
