<?php

/**
 * @file pages/reviewer/SubmissionReviewHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionReviewHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for submission tracking. 
 */

import('pages.reviewer.ReviewerHandler');

class SubmissionReviewHandler extends ReviewerHandler {
	/** submission associated with the request **/
	var $submission;
	
	/** user associated with the request **/
	var $user;
		
	/**
	 * Constructor
	 **/
	function SubmissionReviewHandler() {
		parent::ReviewerHandler();
	}

	function submission($args, $request) {
		$reviewId = (int) array_shift($args);

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;
		$user =& $this->user;
		$schedConf =& $request->getSchedConf();
		
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getById($reviewId);
		
		$reviewFormResponseDao = DAORegistry::getDAO('ReviewFormResponseDAO');

		if ($reviewAssignment->getDateConfirmed() == null) {
			$confirmedStatus = 0;
		} else {
			$confirmedStatus = 1;
		}

		$this->setupTemplate($request, true, $reviewerSubmission->getId(), $reviewId);

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->assign_by_ref('submission', $reviewerSubmission);
		$templateMgr->assign_by_ref('reviewAssignment', $reviewAssignment);
		$templateMgr->assign('confirmedStatus', $confirmedStatus);
		$templateMgr->assign('declined', $reviewerSubmission->getDeclined());
		$templateMgr->assign('reviewFormResponseExists', $reviewFormResponseDao->reviewFormResponseExists($reviewId));
		$templateMgr->assign_by_ref('reviewFile', $reviewAssignment->getReviewFile());
		$templateMgr->assign_by_ref('reviewerFile', $reviewerSubmission->getReviewerFile());
		$templateMgr->assign_by_ref('suppFiles', $reviewerSubmission->getSuppFiles());
		$templateMgr->assign_by_ref('schedConf', $schedConf);
		$templateMgr->assign_by_ref('reviewGuidelines', $schedConf->getLocalizedSetting('reviewGuidelines'));

		// The reviewer instructions differ depending on what is reviewed, and when.
		if($reviewAssignment->getRound()==REVIEW_ROUND_ABSTRACT && $reviewerSubmission->getReviewMode() != REVIEW_MODE_BOTH_SIMULTANEOUS)
			$templateMgr->assign('reviewerInstruction3', 'reviewer.paper.downloadSubmissionAbstractOnly');
		else
			$templateMgr->assign('reviewerInstruction3', 'reviewer.paper.downloadSubmissionSubmission');

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');
		$templateMgr->assign('sessionTypes', $controlledVocabDao->enumerateBySymbolic('sessionTypes', ASSOC_TYPE_SCHED_CONF, $schedConf->getId()));

		$templateMgr->assign('helpTopicId', 'editorial.reviewersRole.review');		
		$templateMgr->display('reviewer/submission.tpl');
	}

	function confirmReview($args, $request) {
		$reviewId = (int) $request->getUserVar('reviewId');
		$declineReview = $request->getUserVar('declineReview');
		$decline = isset($declineReview) ? 1 : 0;

		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;
		$this->setupTemplate($request);

		if (!$reviewerSubmission->getCancelled()) {
			if (ReviewerAction::confirmReview($reviewerSubmission, $decline, $request->getUserVar('send'))) {
				$request->redirect(null, null, null, 'submission', $reviewId);
			}
		} else {
			$request->redirect(null, null, null, 'submission', $reviewId);
		}
	}

	function recordRecommendation($args, $request) {
		$reviewId = (int) $request->getUserVar('reviewId');
		$recommendation = (int) $request->getUserVar('recommendation');

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;
		$this->setupTemplate($request, true);

		if (!$reviewerSubmission->getCancelled()) {
			if (ReviewerAction::recordRecommendation($reviewerSubmission, $recommendation, $request->getUserVar('send'))) {
				$request->redirect(null, null, null, 'submission', $reviewId);
			}
		} else {
			$request->redirect(null, null, null, 'submission', $reviewId);
		}
	}

	function viewMetadata($args, $request) {
		$reviewId = (int) array_shift($args);
		$paperId = (int) array_shift($args);

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;

		$this->setupTemplate($request, true, $paperId, $reviewId);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_AUTHOR); // author.submit.agencies

		ReviewerAction::viewMetadata($reviewerSubmission, ROLE_ID_REVIEWER);
	}

	/**
	 * Upload the reviewer's annotated version of a paper.
	 */
	function uploadReviewerVersion($args, $request) {
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->validate($request, $reviewId);
		$this->setupTemplate($request, true);
		
		if (!ReviewerAction::uploadReviewerVersion($reviewId)) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('pageTitle', 'submission.uploadFile');
			$templateMgr->assign('message', 'common.uploadFailed');
			$templateMgr->assign('backLink', $request->url(null, null, null, 'submission', array($reviewId)));
			$templateMgr->assign('backLinkLabel', 'common.back');
			return $templateMgr->display('common/message.tpl');
		}
		$request->redirect(null, null, null, 'submission', $reviewId);
	}

	/*
	 * Delete one of the reviewer's annotated versions of a paper.
	 */
	function deleteReviewerVersion($args, $request) {		
		$reviewId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);
		if (!$revision) $revision = null;

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;

		if (!$reviewerSubmission->getCancelled()) ReviewerAction::deleteReviewerVersion($reviewId, $fileId, $revision);
		$request->redirect(null, null, null, 'submission', $reviewId);
	}

	//
	// Misc
	//

	/**
	 * Download a file.
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function downloadFile($args, $request) {
		$reviewId = (int) array_shift($args);
		$paperId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);
		if (!$revision) $revision = null;

		$this->validate($request, $reviewId);
		$reviewerSubmission =& $this->submission;
		if (!ReviewerAction::downloadReviewerFile($reviewId, $reviewerSubmission, $fileId, $revision)) {
			$request->redirect(null, null, null, 'submission', $reviewId);
		}
	}
	
	//
	// Review Form
	//

	/**
	 * Edit or preview review form response.
	 * @param $args array
	 */
	function editReviewFormResponse($args, &$request) {
		$reviewId = isset($args[0]) ? $args[0] : 0;
		
		$this->validate($reviewId);

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewFormId = $reviewAssignment->getReviewFormId();
		if ($reviewFormId != null) {
			ReviewerAction::editReviewFormResponse($request, $reviewId, $reviewFormId);		
		}
	}

	/**
	 * Save review form response
	 * @param $args array
	 */
	function saveReviewFormResponse($args, $request) {
		$reviewId = (int) array_shift($args);
		$reviewFormId = (int) array_shift($args);

		$this->validate($reviewId);
		$this->setupTemplate($request, true);

		if (ReviewerAction::saveReviewFormResponse($request, $reviewId, $reviewFormId)) {
			$request->redirect(null, null, null, 'submission', $reviewId);
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
	function validate($request, $reviewId) {
		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$schedConf =& $request->getSchedConf();
		$user =& $request->getUser();

		$isValid = true;
		$newKey = $request->getUserVar('key');

		$reviewerSubmission =& $reviewerSubmissionDao->getReviewerSubmission($reviewId);

		if (!$reviewerSubmission || $reviewerSubmission->getSchedConfId() != $schedConf->getId()) {
			$isValid = false;
		} elseif ($user && empty($newKey)) {
			if ($reviewerSubmission->getReviewerId() != $user->getId()) {
				$isValid = false;
			}
		} else {
			$user =& SubmissionReviewHandler::_validateAccessKey($request, $reviewerSubmission->getReviewerId(), $reviewId, $newKey);
			if (!$user) $isValid = false;
		}

		if (!$isValid) {
			$request->redirect(null, null, $request->getRequestedPage());
		}

		$this->submission =& $reviewerSubmission;
		$this->user =& $user;
		return true;
	}
}

?>
