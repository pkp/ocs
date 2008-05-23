<?php

/**
 * @file SubmissionEditHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.trackDirector
 * @class SubmissionEditHandler
 *
 * Handle requests for submission tracking.
 *
 * $Id$
 */

define('TRACK_DIRECTOR_ACCESS_EDIT', 0x00001);
define('TRACK_DIRECTOR_ACCESS_REVIEW', 0x00002);

class SubmissionEditHandler extends TrackDirectorHandler {
	function submission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		$user = &Request::getUser();

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isDirector = $roleDao->roleExists($conference->getConferenceId(), $schedConf->getSchedConfId(), $user->getUserId(), ROLE_ID_DIRECTOR);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack($submission->getTrackId());

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('track', $track);
		$templateMgr->assign_by_ref('presenters', $submission->getPresenters());
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('reviewMode', $submission->getReviewMode());
		$templateMgr->assign('userId', $user->getUserId());
		$templateMgr->assign('isDirector', $isDirector);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$templateMgr->assign_by_ref('tracks', $trackDao->getTrackTitles($schedConf->getSchedConfId()));

		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper = &$publishedPaperDao->getPublishedPaperByPaperId($submission->getPaperId());
		if ($publishedPaper) {
			$templateMgr->assign_by_ref('publishedPaper', $publishedPaper);
		}

		if ($isDirector) {
			$templateMgr->assign('helpTopicId', 'editorial.directorsRole.summaryPage');
		}

		$templateMgr->display('trackDirector/submission.tpl');
	}

	function submissionRegrets($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'review');

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$cancelsAndRegrets = $reviewAssignmentDao->getCancelsAndRegrets($paperId);
		$reviewFilesByStage = $reviewAssignmentDao->getReviewFilesByStage($paperId);

		$stages = $submission->getReviewAssignments();

		$directorDecisions = $submission->getDecisions();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('reviewMode', $submission->getReviewMode());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignmentStages', $stages);
		$templateMgr->assign_by_ref('cancelsAndRegrets', $cancelsAndRegrets);
		$templateMgr->assign_by_ref('reviewFilesByStage', $reviewFilesByStage);
		$templateMgr->assign_by_ref('directorDecisions', $directorDecisions);
		$templateMgr->assign_by_ref('directorDecisionOptions', TrackDirectorSubmission::getDirectorDecisionOptions());
		$templateMgr->assign('rateReviewerOnQuality', $schedConf->getSetting('rateReviewerOnQuality', true));

		import('submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$templateMgr->display('trackDirector/submissionRegrets.tpl');
	}

	function submissionReview($args) {
		$paperId = (isset($args[0]) ? $args[0] : null);

		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$stage = (isset($args[1]) ? (int) $args[1] : null);
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

		parent::setupTemplate(true, $paperId);

		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$tracks = &$trackDao->getSchedConfTracks($schedConf->getSchedConfId());


		$directorDecisions = $submission->getDecisions($stage);
		$lastDecision = count($directorDecisions) >= 1 ? $directorDecisions[count($directorDecisions) - 1]['decision'] : null;

		$editAssignments =& $submission->getEditAssignments();
		$isCurrent = ($stage == $submission->getCurrentStage());
		$showPeerReviewOptions = $isCurrent && $submission->getReviewFile() != null ? true : false;

		$allowRecommendation = $isCurrent &&
			($submission->getReviewFileId() || $stage != REVIEW_STAGE_PRESENTATION) &&
			!empty($editAssignments);

		$reviewingAbstractOnly = ($reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL && $stage == REVIEW_STAGE_ABSTRACT) || $reviewMode == REVIEW_MODE_ABSTRACTS_ALONE;

		// Prepare an array to store the 'Notify Reviewer' email logs
		$notifyReviewerLogs = array();
		if($submission->getReviewAssignments($stage)) {
			foreach ($submission->getReviewAssignments($stage) as $reviewAssignment) {
				$notifyReviewerLogs[$reviewAssignment->getReviewId()] = array();
			}
		}

		// Parse the list of email logs and populate the array.
		import('paper.log.PaperLog');
		$emailLogEntries = &PaperLog::getEmailLogEntries($paperId);
		foreach ($emailLogEntries->toArray() as $emailLog) {
			if ($emailLog->getEventType() == PAPER_EMAIL_REVIEW_NOTIFY_REVIEWER) {
				if (isset($notifyReviewerLogs[$emailLog->getAssocId()]) && is_array($notifyReviewerLogs[$emailLog->getAssocId()])) {
					array_push($notifyReviewerLogs[$emailLog->getAssocId()], $emailLog);
				}
			}
		}

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewIndexes', $reviewAssignmentDao->getReviewIndexesForStage($paperId, $stage));
		$templateMgr->assign('stage', $stage);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($stage));
		$templateMgr->assign_by_ref('notifyReviewerLogs', $notifyReviewerLogs);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('directorFile', $submission->getDirectorFile());
		$templateMgr->assign('rateReviewerOnQuality', $schedConf->getSetting('rateReviewerOnQuality', true));
		$templateMgr->assign('showPeerReviewOptions', $showPeerReviewOptions);
		$templateMgr->assign_by_ref('tracks', $tracks->toArray());
		$templateMgr->assign_by_ref('directorDecisionOptions', TrackDirectorSubmission::getDirectorDecisionOptions());
		$templateMgr->assign_by_ref('lastDecision', $lastDecision);
		$templateMgr->assign_by_ref('directorDecisions', $directorDecisions);

		if ($reviewMode != REVIEW_MODE_BOTH_SEQUENTIAL || $stage == REVIEW_STAGE_PRESENTATION) {
			$templateMgr->assign('isFinalReview', true);
		}

		import('submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());

		$templateMgr->assign('isCurrent', $isCurrent);
		$templateMgr->assign('allowRecommendation', $allowRecommendation);
		$templateMgr->assign('reviewingAbstractOnly', $reviewingAbstractOnly);

		$templateMgr->assign('helpTopicId', 'editorial.trackDirectorsRole.review');
		$templateMgr->display('trackDirector/submissionReview.tpl');
	}

	/**
	 * View submission history
	 */
	function submissionHistory($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		parent::setupTemplate(true, $paperId);

		// submission notes
		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');

		$rangeInfo = &Handler::getRangeInfo('submissionNotes', array($paperId));
		while (true) {
			$submissionNotes =& $paperNoteDao->getPaperNotes($paperId, $rangeInfo);
			unset($rangeInfo);
			if ($submissionNotes->isInBounds()) break;
			$rangeInfo =& $submissionNotes->getLastPageRangeInfo();
			unset($submissionNotes);
		}

		import('paper.log.PaperLog');

		$rangeInfo = &Handler::getRangeInfo('eventLogEntries', array($paperId));
		while (true) {
			$eventLogEntries =& PaperLog::getEventLogEntries($paperId, $rangeInfo);
			unset($rangeInfo);
			if ($eventLogEntries->isInBounds()) break;
			$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
			unset($eventLogEntries);
		}

		$rangeInfo = &Handler::getRangeInfo('emailLogEntries', array($paperId));
		while (true) {
			$emailLogEntries =& PaperLog::getEmailLogEntries($paperId, $rangeInfo);
			unset($rangeInfo);
			if ($emailLogEntries->isInBounds()) break;
			$rangeInfo =& $emailLogEntries->getLastPageRangeInfo();
			unset($emailLogEntries);
		}

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('reviewMode', $submission->getReviewMode());

		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
		$templateMgr->assign_by_ref('submissionNotes', $submissionNotes);

		$templateMgr->display('trackDirector/submissionHistory.tpl');
	}

	/**
	 * Change the track a submission is currently in.
	 */
	function changeTrack() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		$trackId = Request::getUserVar('trackId');

		TrackDirectorAction::changeTrack($submission, $trackId);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	/**
	 * Change the submission type (panel, single) for a submission.
	 */
	function changeTypeConst() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		$typeConst = Request::getUserVar('typeConst');

		TrackDirectorAction::changeTypeConst($submission, $typeConst);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function recordDecision() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$stage = $submission->getCurrentStage();

		$decision = Request::getUserVar('decision');

		switch ($decision) {
			case SUBMISSION_DIRECTOR_DECISION_ACCEPT:
			case SUBMISSION_DIRECTOR_DECISION_INVITE:
			case SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS:
			case SUBMISSION_DIRECTOR_DECISION_DECLINE:
				TrackDirectorAction::recordDecision($submission, $decision);
				break;
		}

		Request::redirect(null, null, null, 'submissionReview', array($paperId, $stage));
	}

	function completePaper($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		if (Request::getUserVar('complete')) $complete = true;
		elseif (Request::getUserVar('remove')) $complete = false;
		else Request::redirect(null, null, null, 'index');

		TrackDirectorAction::completePaper($submission, $complete);

		Request::redirect(null, null, null, 'submissions', array($complete?'submissionsAccepted':'submissionsInReview'));
	}

	//
	// Peer Review
	//

	function selectReviewer($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		if (isset($args[1]) && $args[1] != null) {
			// Assign reviewer to paper
			TrackDirectorAction::addReviewer($submission, (int) $args[1], $submission->getCurrentStage());
			Request::redirect(null, null, null, 'submissionReview', $paperId);

			// FIXME: Prompt for due date.
		} else {
			parent::setupTemplate(true, $paperId, 'review');

			$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');

			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$rangeInfo = &Handler::getRangeInfo('reviewers', array($submission->getCurrentStage(), (string) $searchType, (string) $search, (string) $searchMatch)); // Paper ID intentionally omitted
			while (true) {
			$reviewers = $trackDirectorSubmissionDao->getReviewersForPaper($schedConf->getSchedConfId(), $paperId, $submission->getCurrentStage(), $searchType, $search, $searchMatch, $rangeInfo);
				if ($reviewers->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $reviewers->getLastPageRangeInfo();
				unset($reviewers);
			}

			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

			$templateMgr->assign_by_ref('reviewers', $reviewers);
			$templateMgr->assign('paperId', $paperId);
			$templateMgr->assign('reviewerStatistics', $trackDirectorSubmissionDao->getReviewerStatistics($schedConf->getSchedConfId()));
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_INTERESTS => 'user.interests',
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('completedReviewCounts', $reviewAssignmentDao->getCompletedReviewCounts($schedConf->getSchedConfId()));
			$templateMgr->assign('rateReviewerOnQuality', $schedConf->getSetting('rateReviewerOnQuality', true));
			$templateMgr->assign('averageQualityRatings', $reviewAssignmentDao->getAverageQualityRatings($schedConf->getSchedConfId()));

			$templateMgr->assign('helpTopicId', 'conference.roles.reviewers');
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->display('trackDirector/selectReviewer.tpl');
		}
	}

	/**
	 * Create a new user as a reviewer.
	 */
	function createReviewer($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		import('trackDirector.form.CreateReviewerForm');
		$createReviewerForm =& new CreateReviewerForm($paperId);
		parent::setupTemplate(true, $paperId);

		if (isset($args[1]) && $args[1] === 'create') {
			$createReviewerForm->readInputData();
			if ($createReviewerForm->validate()) {
				// Create a user and enroll them as a reviewer.
				$newUserId = $createReviewerForm->execute();
				Request::redirect(null, null, null, 'selectReviewer', array($paperId, $newUserId));
			} else {
				$createReviewerForm->display();
			}
		} else {
			// Display the "create user" form.
			if ($createReviewerForm->isLocaleResubmit()) {
				$createReviewerForm->readInputData();
			} else {
				$createReviewerForm->initData();
			}
			$createReviewerForm->display();
		}

	}

	/**
	 * Get a suggested username, making sure it's not
	 * already used by the system. (Poor-man's AJAX.)
	 */
	function suggestUsername() {
		parent::validate();
		$suggestion = Validation::suggestUsername(
			Request::getUserVar('firstName'),
			Request::getUserVar('lastName')
		);
		echo $suggestion;
	}

	/**
	 * Search for users to enroll as reviewers.
	 */
	function enrollSearch($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$user = &Request::getUser();

		$templateMgr = &TemplateManager::getManager();
		parent::setupTemplate(true);

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo('users', array((string) $searchType, (string) $searchMatch, (string) $search)); // Paper ID intentionally omitted
		$userDao = &DAORegistry::getDAO('UserDAO');
		while (true) {
			$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, false, $rangeInfo);
			if ($users->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $users->getLastPageRangeInfo();
			unset($users);
		}

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_INTERESTS => 'user.interests',
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));

		$templateMgr->assign('helpTopicId', 'conference.roles.index');
		$templateMgr->display('trackDirector/searchUsers.tpl');
	}

	function enroll($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$users = Request::getUserVar('users');
		if (!is_array($users) && Request::getUserVar('userId') != null) $users = array(Request::getUserVar('userId'));

		// Enroll reviewer
		for ($i=0; $i<count($users); $i++) {
			if (!$roleDao->roleExists($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $users[$i], $roleId)) {
				$role = &new Role();
				$role->setConferenceId($schedConf->getConferenceId());
				$role->setSchedConfId($schedConf->getSchedConfId());
				$role->setUserId($users[$i]);
				$role->setRoleId($roleId);

				$roleDao->insertRole($role);
			}
		}
		Request::redirect(null, null, null, 'selectReviewer', $paperId);
	}

	function notifyReviewer($args = array()) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackDirectorAction::notifyReviewer($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function clearReview($args) {
		$paperId = isset($args[0])?$args[0]:0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = $args[1];

		TrackDirectorAction::clearReview($submission, $reviewId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function cancelReview($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackDirectorAction::cancelReview($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function remindReviewer($args = null) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackDirectorAction::remindReviewer($submission, $reviewId, Request::getUserVar('send'))) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function thankReviewer($args = array()) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackDirectorAction::thankReviewer($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function rateReviewer() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		parent::setupTemplate(true, $paperId, 'review');

		$reviewId = Request::getUserVar('reviewId');
		$quality = Request::getUserVar('quality');

		TrackDirectorAction::rateReviewer($paperId, $reviewId, $quality);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function confirmReviewForReviewer($args) {
		$paperId = (int) isset($args[0])?$args[0]:0;
		$accept = Request::getUserVar('accept')?true:false;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = (int) isset($args[1])?$args[1]:0;

		TrackDirectorAction::confirmReviewForReviewer($reviewId);
		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function uploadReviewForReviewer($args) {
		$paperId = (int) Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = (int) Request::getUserVar('reviewId');

		TrackDirectorAction::uploadReviewForReviewer($reviewId);
		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function makeReviewerFileViewable() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');
		$fileId = Request::getUserVar('fileId');
		$revision = Request::getUserVar('revision');
		$viewable = Request::getUserVar('viewable');

		TrackDirectorAction::makeReviewerFileViewable($paperId, $reviewId, $fileId, $revision, $viewable);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function setDueDate($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = isset($args[1]) ? $args[1] : 0;
		$dueDate = Request::getUserVar('dueDate');
		$numWeeks = Request::getUserVar('numWeeks');

		if ($dueDate != null || $numWeeks != null) {
			TrackDirectorAction::setDueDate($paperId, $reviewId, $dueDate, $numWeeks);
			Request::redirect(null, null, null, 'submissionReview', $paperId);

		} else {
			parent::setupTemplate(true, $paperId, 'review');

			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentById($reviewId);

			$settings = $schedConf->getSettings(true);

			$templateMgr = &TemplateManager::getManager();

			if ($reviewAssignment->getDateDue() != null) {
				$templateMgr->assign('dueDate', $reviewAssignment->getDateDue());
			}

			$numWeeksPerReview = $settings['numWeeksPerReview'] == null ? 0 : $settings['numWeeksPerReview'];

			$templateMgr->assign('paperId', $paperId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('todaysDate', date('Y-m-d'));
			$templateMgr->assign('numWeeksPerReview', $numWeeksPerReview);
			$templateMgr->assign('actionHandler', 'setDueDate');

			$templateMgr->display('trackDirector/setDueDate.tpl');
		}
	}

	function enterReviewerRecommendation($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');

		$recommendation = Request::getUserVar('recommendation');

		if ($recommendation != null) {
			TrackDirectorAction::setReviewerRecommendation($paperId, $reviewId, $recommendation, SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			parent::setupTemplate(true, $paperId, 'review');

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('paperId', $paperId);
			$templateMgr->assign('reviewId', $reviewId);

			import('submission.reviewAssignment.ReviewAssignment');
			$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

			$templateMgr->display('trackDirector/reviewerRecommendation.tpl');
		}
	}

	/**
	 * Display a user's profile.
	 * @param $args array first parameter is the ID or username of the user to display
	 */
	function userProfile($args) {
		parent::validate();
		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::url(null, null, null, Request::getRequestedPage()));

		$userDao = &DAORegistry::getDAO('UserDAO');
		$userId = isset($args[0]) ? $args[0] : 0;
		if (is_numeric($userId)) {
			$userId = (int) $userId;
			$user = $userDao->getUser($userId);
		} else {
			$user = $userDao->getUserByUsername($userId);
		}


		if ($user == null) {
			// Non-existent user requested
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.invalidUser');
			$templateMgr->display('common/error.tpl');

		} else {
			$site = &Request::getSite();

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$country = null;
			if ($user->getCountry() != '') {
				$country = $countryDao->getCountry($user->getCountry());
			}
			$templateMgr->assign('country', $country);

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->assign('helpTopicId', 'conference.roles.index');
			$templateMgr->display('trackDirector/userProfile.tpl');
		}
	}

	function viewMetadata($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		TrackDirectorAction::viewMetadata($submission, ROLE_ID_TRACK_DIRECTOR);
	}

	function saveMetadata() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		if (TrackDirectorAction::saveMetadata($submission)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	//
	// Director Review
	//

	function directorReview($args) {
		import('paper.Paper');

		$stage = (isset($args[0]) ? $args[0] : REVIEW_STAGE_ABSTRACT);
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		$redirectArgs = array($paperId, $stage);

		// If the Upload button was pressed.
		if (Request::getUserVar('submit')) {
			TrackDirectorAction::uploadDirectorVersion($submission);
		} elseif (Request::getUserVar('setEditingFile')) {
			// If the Send To Editing button was pressed
			$file = explode(',', Request::getUserVar('directorDecisionFile'));
			$submission->stampDateToPresentations();
			$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($submission);

			if (isset($file[0]) && isset($file[1])) {
				TrackDirectorAction::setEditingFile($submission, $file[0], $file[1], Request::getUserVar('createGalley'));
			}

		}

		Request::redirect(null, null, null, 'submissionReview', $redirectArgs);
	}

	function uploadReviewVersion() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);

		TrackDirectorAction::uploadReviewVersion($submission);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($paperId)
	 */
	function addSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($submission);

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
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($submission, $suppFileId);

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
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		$suppFileId = Request::getUserVar('fileId');
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $paperId);

		if (isset($suppFile) && $suppFile != null) {
			$suppFile->setShowReviewers(Request::getUserVar('show')==1?1:0);
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
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($submission, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			parent::setupTemplate(true, $paperId, 'summary');
			$submitForm->display();
		}
	}

	/**
	 * Delete a director version file.
	 * @param $args array ($paperId, $fileId)
	 */
	function deletePaperFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$fileId = isset($args[1]) ? (int) $args[1] : 0;
		$revisionId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		TrackDirectorAction::deletePaperFile($submission, $fileId, $revisionId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Delete a supplementary file.
	 * @param $args array ($paperId, $suppFileId)
	 */
	function deleteSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		TrackDirectorAction::deleteSuppFile($submission, $suppFileId);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function archiveSubmission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		TrackDirectorAction::archiveSubmission($submission);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function restoreToQueue($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		TrackDirectorAction::restoreToQueue($submission);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function unsuitableSubmission($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'summary');

		if (TrackDirectorAction::unsuitableSubmission($submission, $send)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}


	//
	// Layout Editing
	//

	/**
	 * Upload a layout file (either layout version, galley, or supp. file).
	 */
	function uploadLayoutFile() {
		$layoutFileType = Request::getUserVar('layoutFileType');
		$stage = (int) Request::getUserVar('stage');
		if ($layoutFileType == 'submission') {
			SubmissionEditHandler::uploadLayoutVersion($stage);

		} else if ($layoutFileType == 'galley') {
			SubmissionEditHandler::uploadGalley('layoutFile', $stage);

		} else if ($layoutFileType == 'supp') {
			SubmissionEditHandler::uploadSuppFile('layoutFile', $stage);

		} else {
			Request::redirect(null, null, null, 'submission', Request::getUserVar('paperId'));
		}
	}

	/**
	 * Upload the layout version of the submission file
	 * @var $stage int The current review stage to redirect back to
	 */
	function uploadLayoutVersion($stage) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		TrackDirectorAction::uploadLayoutVersion($submission);

		Request::redirect(null, null, null, 'submissionReview', array($paperId, $stage));
	}

	/**
	 * Delete a paper image.
	 * @param $args array ($paperId, $fileId)
	 */
	function deletePaperImage($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;
		$revisionId = isset($args[3]) ? (int) $args[3] : 0;

		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		TrackDirectorAction::deletePaperImage($submission, $fileId, $revisionId);

		Request::redirect(null, null, 'editGalley', array($paperId, $galleyId));
	}

	/**
	 * Create a new galley with the uploaded file.
	 * @param $fileName string
	 * @param $stage int
	 */
	function uploadGalley($fileName = null, $stage = null) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		import('submission.form.PaperGalleyForm');

		$galleyForm = &new PaperGalleyForm($paperId);
		$galleyId = $galleyForm->execute($fileName);

		Request::redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $stage));
	}

	/**
	 * Edit a galley.
	 * @param $args array ($paperId, $galleyId)
	 */
	function editGalley($args) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$stage = (int) array_shift($args);
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		parent::setupTemplate(true, $paperId, 'review');

		import('submission.form.PaperGalleyForm');

		$submitForm = &new PaperGalleyForm($paperId, $galleyId, $stage);

		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Save changes to a galley.
	 * @param $args array ($paperId, $galleyId)
	 */
	function saveGalley($args) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$stage = (int) array_shift($args);
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		import('submission.form.PaperGalleyForm');

		$submitForm = &new PaperGalleyForm($paperId, $galleyId, $stage);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			if (Request::getUserVar('uploadImage')) {
				$submitForm->uploadImage();
				Request::redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $stage));
			} else if(($deleteImage = Request::getUserVar('deleteImage')) && count($deleteImage) == 1) {
				list($imageId) = array_keys($deleteImage);
				$submitForm->deleteImage($imageId);
				Request::redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $stage));
			}
			Request::redirect(null, null, null, 'submissionReview', array($paperId, $stage));
		} else {
			parent::setupTemplate(true, $paperId, 'editing');
			$submitForm->display();
		}
	}

	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		TrackDirectorAction::orderGalley($submission, Request::getUserVar('galleyId'), Request::getUserVar('d'));

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Delete a galley file.
	 * @param $args array ($paperId, $galleyId)
	 */
	function deleteGalley($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		TrackDirectorAction::deleteGalley($submission, $galleyId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($paperId, $galleyId)
	 */
	function proofGalley($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}

	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($paperId, $galleyId)
	 */
	function proofGalleyTop($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submissionReview');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}

	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($paperId, $galleyId)
	 */
	function proofGalleyFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $paperId);

		import('file.PaperFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet(Request::url(null, 'paper', 'viewFile', array(
						$paperId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');

			} else {
				// View non-HTML file inline
				SubmissionEditHandler::viewFile(array($paperId, $galley->getFileId()));
			}
		}
	}

	/**
	 * Upload a new supplementary file.
	 * @param $fileName string
	 * @param $stage int
	 */
	function uploadSuppFile($fileName = null, $stage = null) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		import('submission.form.SuppFileForm');

		$suppFileForm = &new SuppFileForm($submission);
		$suppFileForm->setData('title', Locale::translate('common.untitled'));
		$suppFileId = $suppFileForm->execute($fileName);

		Request::redirect(null, null, null, ($stage===null?'submission':'submissionReview'), array($paperId, $suppFileId, $stage));
	}

	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		TrackDirectorAction::orderSuppFile($submission, Request::getUserVar('suppFileId'), Request::getUserVar('d'));

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}


	//
	// Submission History (FIXME Move to separate file?)
	//

	/**
	 * View submission event log.
	 */
	function submissionEventLog($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao = &DAORegistry::getDAO('PaperEventLogDAO');
			$logEntry = &$logDao->getLogEntry($logId, $paperId);
		}

		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('trackDirector/submissionEventLogEntry.tpl');

		} else {
			$rangeInfo = &Handler::getRangeInfo('eventLogEntries', array($paperId));

			import('paper.log.PaperLog');
			while (true) {
				$eventLogEntries =& PaperLog::getEventLogEntries($paperId, $rangeInfo);
				if ($eventLogEntries->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
				unset($eventLogEntries);
			}
			$templateMgr->assign('eventLogEntries', $eventLogEntries);
			$templateMgr->display('trackDirector/submissionEventLog.tpl');
		}
	}

	/**
	 * View submission event log by record type.
	 */
	function submissionEventLogType($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$assocType = isset($args[1]) ? (int) $args[1] : null;
		$assocId = isset($args[2]) ? (int) $args[2] : null;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$rangeInfo =& Handler::getRangeInfo('eventLogEntries', array($paperId, $assocType, $assocId));
		$logDao = &DAORegistry::getDAO('PaperEventLogDAO');
		while (true) {
			$eventLogEntries =& $logDao->getPaperLogEntriesByAssoc($paperId, $assocType, $assocId, $rangeInfo);
			if ($eventLogEntries->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
			unset($eventLogEntries);
		}

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		$templateMgr->display('trackDirector/submissionEventLog.tpl');
	}

	/**
	 * Clear submission event log entries.
	 */
	function clearSubmissionEventLog($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		$logDao = &DAORegistry::getDAO('PaperEventLogDAO');

		if ($logId) {
			$logDao->deleteLogEntry($logId, $paperId);

		} else {
			$logDao->deletePaperLogEntries($paperId);
		}

		Request::redirect(null, null, null, 'submissionEventLog', $paperId);
	}

	/**
	 * View submission email log.
	 */
	function submissionEmailLog($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao = &DAORegistry::getDAO('PaperEmailLogDAO');
			$logEntry = &$logDao->getLogEntry($logId, $paperId);
		}

		if (isset($logEntry)) {
			$templateMgr->assign_by_ref('logEntry', $logEntry);
			$templateMgr->display('trackDirector/submissionEmailLogEntry.tpl');

		} else {
			$rangeInfo = &Handler::getRangeInfo('emailLogEntries', array($paperId));

			import('paper.log.PaperLog');
			while (true) {
				$emailLogEntries =& PaperLog::getEmailLogEntries($paperId, $rangeInfo);
				if ($emailLogEntries->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $emailLogEntries->getLastPageRangeInfo();
				unset($emailLogEntries);
			}
			$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
			$templateMgr->display('trackDirector/submissionEmailLog.tpl');
		}
	}

	/**
	 * View submission email log by record type.
	 */
	function submissionEmailLogType($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$assocType = isset($args[1]) ? (int) $args[1] : null;
		$assocId = isset($args[2]) ? (int) $args[2] : null;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$rangeInfo =& Handler::getRangeInfo('eventLogEntries', array($paperId, $assocType, $assocId));
		$logDao = &DAORegistry::getDAO('PaperEmailLogDAO');
		while (true) {
			$emailLogEntries =& $logDao->getPaperLogEntriesByAssoc($paperId, $assocType, $assocId, $rangeInfo);
			if ($emailLogEntries->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $emailLogEntries->getLastPageRangeInfo();
			unset($emailLogEntries);
		}

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
		$templateMgr->display('trackDirector/submissionEmailLog.tpl');
	}

	/**
	 * Clear submission email log entries.
	 */
	function clearSubmissionEmailLog($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		$logDao = &DAORegistry::getDAO('PaperEmailLogDAO');

		if ($logId) {
			$logDao->deleteLogEntry($logId, $paperId);

		} else {
			$logDao->deletePaperLogEntries($paperId);
		}

		Request::redirect(null, null, null, 'submissionEmailLog', $paperId);
	}

	// Submission Notes Functions

	/**
	 * Creates a submission note.
	 * Redirects to submission notes list
	 */
	function addSubmissionNote() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		TrackDirectorAction::addSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Removes a submission note.
	 * Redirects to submission notes list
	 */
	function removeSubmissionNote() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		TrackDirectorAction::removeSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Updates a submission note.
	 * Redirects to submission notes list
	 */
	function updateSubmissionNote() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		TrackDirectorAction::updateSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Clear all submission notes.
	 * Redirects to submission notes list
	 */
	function clearAllSubmissionNotes() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		TrackDirectorAction::clearAllSubmissionNotes($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * View submission notes.
	 */
	function submissionNotes($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$noteViewType = isset($args[1]) ? $args[1] : '';
		$noteId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');

		// submission note edit
		if ($noteViewType == 'edit') {
			$paperNote = $paperNoteDao->getPaperNoteById($noteId);
		}

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign('noteViewType', $noteViewType);
		if (isset($paperNote)) {
			$templateMgr->assign_by_ref('paperNote', $paperNote);
		}

		if ($noteViewType == 'edit' || $noteViewType == 'add') {
			$templateMgr->assign('showBackLink', true);
		} else {
			$rangeInfo =& Handler::getRangeInfo('submissionNotes', array($paperId));
			while (true) {
				$submissionNotes =& $paperNoteDao->getPaperNotes($paperId, $rangeInfo);
				if ($submissionNotes->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $submissionNotes->getLastPageRangeInfo();
				unset($submissionNotes);
			}
			$templateMgr->assign_by_ref('submissionNotes', $submissionNotes);
		}

		$templateMgr->display('trackDirector/submissionNotes.tpl');
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

		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		if (!TrackDirectorAction::downloadFile($paperId, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function viewFile($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$fileId = isset($args[1]) ? $args[1] : 0;
		$revision = isset($args[2]) ? $args[2] : null;

		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		if (!TrackDirectorAction::viewFile($paperId, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}


	//
	// Validation
	//

	/**
	 * Validate that the user is the assigned track director for
	 * the paper, or is a managing director.
	 * Redirects to trackDirector index page if validation fails.
	 * @param $paperId int Paper ID to validate
	 * @param $access int Optional name of access level required -- see TRACK_DIRECTOR_ACCESS_... constants
	 */
	function validate($paperId, $access = null) {
		parent::validate();

		$isValid = true;

		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();

		$trackDirectorSubmission = &$trackDirectorSubmissionDao->getTrackDirectorSubmission($paperId);

		if ($trackDirectorSubmission == null) {
			$isValid = false;

		} else if ($trackDirectorSubmission->getSchedConfId() != $schedConf->getSchedConfId()) {
			$isValid = false;

		} else if ($trackDirectorSubmission->getDateSubmitted() == null) {
			$isValid = false;

		} else {
			$templateMgr =& TemplateManager::getManager();
			// If this user isn't the submission's director, they don't have access.
			$editAssignments =& $trackDirectorSubmission->getEditAssignments();
			$wasFound = false;
			foreach ($editAssignments as $editAssignment) {
				if ($editAssignment->getDirectorId() == $user->getUserId()) {
					$wasFound = true;
				}
			}
		}

		if (!$isValid) {
			Request::redirect(null, null, null, Request::getRequestedPage());
		}

		// If necessary, note the current date and time as the "underway" date/time
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = &$trackDirectorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getDirectorId() == $user->getUserId() && $editAssignment->getDateUnderway() === null) {
				$editAssignment->setDateUnderway(Core::getCurrentDate());
				$editAssignmentDao->updateEditAssignment($editAssignment);
			}
		}

		return array(&$conference, &$schedConf, &$trackDirectorSubmission);
	}
}
?>
