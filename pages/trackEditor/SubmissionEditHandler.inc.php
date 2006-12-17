<?php

/**
 * SubmissionEditHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.trackEditor
 *
 * Handle requests for submission tracking.
 *
 * $Id$
 */

define('TRACK_EDITOR_ACCESS_EDIT', 0x00001);
define('TRACK_EDITOR_ACCESS_REVIEW', 0x00002);
import('paper.Paper');

class SubmissionEditHandler extends TrackEditorHandler {

	function submission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId);

		$user = &Request::getUser();

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->roleExists($conference->getConferenceId(), $event->getEventId(), $user->getUserId(), ROLE_ID_EDITOR);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack($submission->getTrackId());

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('track', $track);
		$templateMgr->assign_by_ref('authors', $submission->getAuthors());
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));
		$templateMgr->assign('userId', $user->getUserId());
		$templateMgr->assign('isEditor', $isEditor);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$tracks = &$trackDao->getTrackTitles($event->getEventId());
		$templateMgr->assign_by_ref('tracks', $tracks);

		$secondaryTracks = $trackDao->getTrackTitles($event->getEventId());
		$secondaryTracks[-1] = Locale::translate('common.none');
		$templateMgr->assign('secondaryTracks', $secondaryTracks);

		if ($isEditor) {
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole.submissionSummary');
		}

		$templateMgr->display('trackEditor/submission.tpl');
	}

	function submissionRegrets($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'review');

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$cancelsAndRegrets = $reviewAssignmentDao->getCancelsAndRegrets($paperId);
		$reviewFilesByRound = $reviewAssignmentDao->getReviewFilesByRound($paperId);

		$reviewAssignments =& $submission->getReviewAssignments();
		$editorDecisions = $submission->getDecisions();
		$numRounds = $submission->getCurrentRound();

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignments', $reviewAssignments);
		$templateMgr->assign_by_ref('cancelsAndRegrets', $cancelsAndRegrets);
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('editorDecisions', $editorDecisions);
		$templateMgr->assign('numRounds', $numRounds);
		$templateMgr->assign('rateReviewerOnQuality', $event->getSetting('rateReviewerOnQuality', true));
		
		$templateMgr->assign_by_ref('editorDecisionOptions', TrackEditorSubmission::getEditorDecisionOptions());

		import('submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$templateMgr->display('trackEditor/submissionRegrets.tpl');
	}

	function submissionReview($args) {
		$paperId = (isset($args[0]) ? $args[0] : null);
		$type = (isset($args[1]) ? $args[1] : REVIEW_PROGRESS_ABSTRACT); // which item is currently under review
		$round = (isset($args[2]) ? $args[2] : 1);

		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		parent::setupTemplate(true, $paperId);

		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$tracks = &$trackDao->getEventTracks($event->getEventId());

		$showPeerReviewOptions = $round == $submission->getCurrentRound() && $submission->getReviewFile() != null ? true : false;

		$editorDecisions = $submission->getDecisions($type, $round);
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1]['decision'] : null;

		$editAssignments =& $submission->getEditAssignments();

		$isCurrent = ($round == $submission->getCurrentRound() && $type == $submission->getReviewProgress());
		
		$allowRecommendation = $isCurrent &&
			($submission->getEditorFile() != NULL || $type != REVIEW_PROGRESS_PAPER) &&
			!empty($editAssignments);

		$allowCompleteReview = ($lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT) && $isCurrent;
		$reviewingAbstractOnly = ($event->getReviewPapers() && $type == REVIEW_PROGRESS_ABSTRACT) ||
			!$event->getAcceptPapers();

		// Prepare an array to store the 'Notify Reviewer' email logs
		$notifyReviewerLogs = array();
		if($submission->getReviewAssignments($type, $round)) {
			foreach ($submission->getReviewAssignments($type, $round) as $reviewAssignment) {
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
		$templateMgr->assign_by_ref('reviewIndexes', $reviewAssignmentDao->getReviewIndexesForRound($paperId, $type, $round));
		$templateMgr->assign('round', $round);
		$templateMgr->assign('type', $type);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($type, $round));
		$templateMgr->assign_by_ref('notifyReviewerLogs', $notifyReviewerLogs);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('editorFile', $submission->getEditorFile());
		$templateMgr->assign('rateReviewerOnQuality', $event->getSetting('rateReviewerOnQuality', true));
		$templateMgr->assign('showPeerReviewOptions', $showPeerReviewOptions);
		$templateMgr->assign_by_ref('tracks', $tracks->toArray());
		$templateMgr->assign('editorDecisionOptions',
			array(
				'' => 'common.chooseOne',
				SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.paper.decision.accept',
				SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.paper.decision.pendingRevisions',
				SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.paper.decision.decline'
			)
		);
		$templateMgr->assign_by_ref('lastDecision', $lastDecision);

		import('submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());

		$templateMgr->assign('isCurrent', $isCurrent);
		$templateMgr->assign('allowRecommendation', $allowRecommendation);
		$templateMgr->assign('allowCompleteReview', $allowCompleteReview);

		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));
		$templateMgr->assign('reviewingAbstractOnly', $reviewingAbstractOnly);

		$templateMgr->assign('helpTopicId', 'editorial.trackEditorsRole.review');
		$templateMgr->display('trackEditor/submissionReview.tpl');
	}

	function submissionEditing($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);
		parent::setupTemplate(true, $paperId);

		// check if submission is accepted
		$type = isset($args[1]) ? $args[1] : $submission->getReviewProgress();
		$round = isset($args[2]) ? $args[2] : $submission->getCurrentRound();
		$editorDecisions = $submission->getDecisions($type, $round);
		$lastDecision = count($editorDecisions) >= 1 ? $editorDecisions[count($editorDecisions) - 1]['decision'] : null;
		$submissionAccepted = ($lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT) ? true : false;

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign('submissionAccepted', $submissionAccepted);

		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));

		$templateMgr->assign('helpTopicId', 'editorial.trackEditorsRole.editing');
		$templateMgr->display('trackEditor/submissionEditing.tpl');
	}

	/**
	 * View submission history
	 */
	function submissionHistory($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		parent::setupTemplate(true, $paperId);

		// submission notes
		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');

		$rangeInfo = &Handler::getRangeInfo('submissionNotes');
		$submissionNotes =& $paperNoteDao->getPaperNotes($paperId, $rangeInfo);

		import('paper.log.PaperLog');
		$rangeInfo = &Handler::getRangeInfo('eventLogEntries');
		$eventLogEntries = &PaperLog::getEventLogEntries($paperId, $rangeInfo);
		$rangeInfo = &Handler::getRangeInfo('emailLogEntries');
		$emailLogEntries = &PaperLog::getEmailLogEntries($paperId, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));

		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
		$templateMgr->assign_by_ref('submissionNotes', $submissionNotes);

		$templateMgr->display('trackEditor/submissionHistory.tpl');
	}

	function changeTrack() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		$trackId = Request::getUserVar('trackId');

		TrackEditorAction::changeTrack($submission, $trackId);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function recordDecision() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$round = $submission->getCurrentRound();
		$type = $submission->getReviewProgress();

		$decision = Request::getUserVar('decision');

		switch ($decision) {
			case SUBMISSION_EDITOR_DECISION_ACCEPT:
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
			case SUBMISSION_EDITOR_DECISION_RESUBMIT:
			case SUBMISSION_EDITOR_DECISION_DECLINE:
				TrackEditorAction::recordDecision($submission, $decision);
				break;
		}

		Request::redirect(null, null, null, 'submissionReview', array($paperId, $type, $round));
	}

	//
	// Peer Review
	//

	function selectReviewer($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');

		if (isset($args[1]) && $args[1] != null) {
			// Assign reviewer to paper
			TrackEditorAction::addReviewer($submission, $args[1], $submission->getReviewProgress(), $submission->getCurrentRound());
			Request::redirect(null, null, null, 'submissionReview', $paperId);

			// FIXME: Prompt for due date.
		} else {
			parent::setupTemplate(true, $paperId, 'review');

			$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');

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

			$rangeInfo = &Handler::getRangeInfo('reviewers');
			$reviewers = $trackEditorSubmissionDao->getReviewersForPaper($event->getEventId(), $paperId, $submission->getReviewProgress(), $submission->getCurrentRound(), $searchType, $search, $searchMatch, $rangeInfo);

			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $searchInitial);

			$templateMgr->assign_by_ref('reviewers', $reviewers);
			$templateMgr->assign('paperId', $paperId);
			$templateMgr->assign('reviewerStatistics', $trackEditorSubmissionDao->getReviewerStatistics($event->getEventId()));
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email',
				USER_FIELD_INTERESTS => 'user.interests'
			));
			$templateMgr->assign('completedReviewCounts', $reviewAssignmentDao->getCompletedReviewCounts($event->getEventId()));
			$templateMgr->assign('rateReviewerOnQuality', $event->getSetting('rateReviewerOnQuality', true));
			$templateMgr->assign('averageQualityRatings', $reviewAssignmentDao->getAverageQualityRatings($event->getEventId()));

			$templateMgr->assign('helpTopicId', 'conference.roles.reviewer');
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->display('trackEditor/selectReviewer.tpl');
		}
	}

	/**
	 * Create a new user as a reviewer.
	 */
	function createReviewer($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		import('trackEditor.form.CreateReviewerForm');
		$createReviewerForm =& new CreateReviewerForm($paperId);
		parent::setupTemplate(true, $paperId);

		if (isset($args[1]) && $args[1] === 'create') {
			$createReviewerForm->readInputData();
			if ($createReviewerForm->validate()) {
				// Create a user and enroll them as a reviewer.
				$createReviewerForm->execute();
				Request::redirect(null, null, null, 'selectReviewer', $paperId);
			} else {
				$createReviewerForm->display();
			}
		} else {
			// Display the "create user" form.
			$createReviewerForm->display();
		}

	}

	/**
	 * Search for users to enroll as reviewers.
	 */
	function enrollSearch($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$user = &Request::getUser();

		$rangeInfo = Handler::getRangeInfo('users');
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

		$userDao = &DAORegistry::getDAO('UserDAO');
		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, false, $rangeInfo);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', $searchInitial);

		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));

		$templateMgr->assign('helpTopicId', 'conference.roles.index');
		$templateMgr->display('trackEditor/searchUsers.tpl');
	}

	function enroll($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$users = Request::getUserVar('users');
		if (!is_array($users) && Request::getUserVar('userId') != null) $users = array(Request::getUserVar('userId'));

		// Enroll reviewer
		for ($i=0; $i<count($users); $i++) {
			if (!$roleDao->roleExists($event->getConferenceId(), $event->getEventId(), $users[$i], $roleId)) {
				$role = &new Role();
				$role->setConferenceId($event->getConferenceId());
				$role->setEventId($event->getEventId());
				$role->setUserId($users[$i]);
				$role->setRoleId($roleId);

				$roleDao->insertRole($role);
			}
		}
		Request::redirect(null, null, null, 'selectReviewer', $paperId);
	}

	function notifyReviewer($args = array()) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackEditorAction::notifyReviewer($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function clearReview($args) {
		$paperId = isset($args[0])?$args[0]:0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = $args[1];

		TrackEditorAction::clearReview($submission, $reviewId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function cancelReview($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackEditorAction::cancelReview($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function remindReviewer($args = null) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackEditorAction::remindReviewer($submission, $reviewId, Request::getUserVar('send'))) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function acknowledgeReviewerUnderway($args = null) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackEditorAction::acknowledgeReviewerUnderway($submission, $reviewId, Request::getUserVar('send'))) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function thankReviewer($args = array()) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'review');

		if (TrackEditorAction::thankReviewer($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function rateReviewer() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);
		parent::setupTemplate(true, $paperId, 'review');

		$reviewId = Request::getUserVar('reviewId');
		$quality = Request::getUserVar('quality');

		TrackEditorAction::rateReviewer($paperId, $reviewId, $quality);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function acceptReviewForReviewer($args) {
		$paperId = (int) isset($args[0])?$args[0]:0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = (int) isset($args[1])?$args[1]:0;

		TrackEditorAction::acceptReviewForReviewer($reviewId);
		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function uploadReviewForReviewer($args) {
		$paperId = (int) Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = (int) Request::getUserVar('reviewId');

		TrackEditorAction::uploadReviewForReviewer($reviewId);
		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function makeReviewerFileViewable() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');
		$fileId = Request::getUserVar('fileId');
		$revision = Request::getUserVar('revision');
		$viewable = Request::getUserVar('viewable');

		TrackEditorAction::makeReviewerFileViewable($paperId, $reviewId, $fileId, $revision, $viewable);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function setDueDate($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = isset($args[1]) ? $args[1] : 0;
		$dueDate = Request::getUserVar('dueDate');
		$numWeeks = Request::getUserVar('numWeeks');

		if ($dueDate != null || $numWeeks != null) {
			TrackEditorAction::setDueDate($paperId, $reviewId, $dueDate, $numWeeks);
			Request::redirect(null, null, null, 'submissionReview', $paperId);

		} else {
			parent::setupTemplate(true, $paperId, 'review');

			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentById($reviewId);

			$settings = $event->getSettings(true);

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

			$templateMgr->display('trackEditor/setDueDate.tpl');
		}
	}

	function enterReviewerRecommendation($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$reviewId = Request::getUserVar('reviewId');

		$recommendation = Request::getUserVar('recommendation');

		if ($recommendation != null) {
			TrackEditorAction::setReviewerRecommendation($paperId, $reviewId, $recommendation, SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			parent::setupTemplate(true, $paperId, 'review');

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('paperId', $paperId);
			$templateMgr->assign('reviewId', $reviewId);

			import('submission.reviewAssignment.ReviewAssignment');
			$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

			$templateMgr->display('trackEditor/reviewerRecommendation.tpl');
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

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign('profileLocalesEnabled', $site->getProfileLocalesEnabled());
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->assign('helpTopicId', 'conference.roles.index');
			$templateMgr->display('trackEditor/userProfile.tpl');
		}
	}

	function viewMetadata($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		TrackEditorAction::viewMetadata($submission, ROLE_ID_TRACK_EDITOR);
	}

	function saveMetadata() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		if (TrackEditorAction::saveMetadata($submission)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	//
	// Editor Review
	//

	function editorReview() {
		$type = (isset($args[1]) ? $args[1] : REVIEW_PROGRESS_ABSTRACT); // which item is currently under review
		$round = (isset($args[2]) ? $args[2] : 1);
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$redirectTarget = 'submissionReview';
		$redirectArgs = array($paperId, $type, $round);

		// If the Upload button was pressed.
		$submit = Request::getUserVar('submit');
		if ($submit != null) {
			TrackEditorAction::uploadEditorVersion($submission);
		}

		if (Request::getUserVar('completeReview')) {
			// If the "mark review complete" button was pressed
			list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);

			TrackEditorAction::completeReview($event, $submission);
			unset($submission);
      
  		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
  		$submission = &$trackEditorSubmissionDao->getTrackEditorSubmission($paperId);
  		
			// If we're still reviewing, redirect to the current review pane
			if($submission->getReviewProgress() != REVIEW_PROGRESS_COMPLETE) {
				$redirectArgs = array($paperId, $submission->getReviewProgress(), $submission->getCurrentRound());
			} else {
				$redirectTarget = 'submissionEditing';
				$redirectArgs = array($paperId);
			}
		}

		Request::redirect(null, null, null, $redirectTarget, $redirectArgs);
	}

	function notifyAuthor($args = array()) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'editing');

		if (TrackEditorAction::notifyAuthor($submission, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function uploadReviewVersion() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);

		TrackEditorAction::uploadReviewVersion($submission);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($paperId)
	 */
	function addSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($submission);

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
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'summary');

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($submission, $suppFileId);

		$submitForm->initData();
		$submitForm->display();
	}

	/**
	 * Set reviewer visibility for a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function setSuppFileVisibility($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

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
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('submission.form.SuppFileForm');

		$submitForm = &new SuppFileForm($submission, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, null, 'submissionEditing', $paperId);
		} else {
			parent::setupTemplate(true, $paperId, 'summary');
			$submitForm->display();
		}
	}

	/**
	 * Delete an editor version file.
	 * @param $args array ($paperId, $fileId)
	 */
	function deletePaperFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$fileId = isset($args[1]) ? (int) $args[1] : 0;
		$revisionId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_REVIEW);
		TrackEditorAction::deletePaperFile($submission, $fileId, $revisionId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Delete a supplementary file.
	 * @param $args array ($paperId, $suppFileId)
	 */
	function deleteSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$suppFileId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		TrackEditorAction::deleteSuppFile($submission, $suppFileId);

		Request::redirect(null, null, null, 'submissionEditing', $paperId);
	}

	function archiveSubmission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		TrackEditorAction::archiveSubmission($submission);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function restoreToQueue($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		TrackEditorAction::restoreToQueue($submission);

		Request::redirect(null, null, null, 'submissionEditing', $paperId);
	}

	function unsuitableSubmission($args) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		$send = Request::getUserVar('send')?true:false;
		parent::setupTemplate(true, $paperId, 'summary');

		if (TrackEditorAction::unsuitableSubmission($submission, $send)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	/**
	 * Set track ID.
	 * @param $args array ($paperId)
	 */
	function updateTrack($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		TrackEditorAction::updateTrack($submission, Request::getUserVar('track'));
		Request::redirect(null, null, null, 'submission', $paperId);
	}

	/**
	 * Set secondary track ID.
	 * @param $args array ($paperId)
	 */
	function updateSecondaryTrack($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$track = Request::getUserVar('secondaryTrack');

		if($track == -1) $track=NULL;

		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		TrackEditorAction::updateSecondaryTrack($submission, $track);
		Request::redirect(null, null, null, 'submission', $paperId);
	}


	//
	// Layout Editing
	//

	/**
	 * Upload a layout file (either layout version, galley, or supp. file).
	 */
	function uploadLayoutFile() {
		$layoutFileType = Request::getUserVar('layoutFileType');
		if ($layoutFileType == 'submission') {
			SubmissionEditHandler::uploadLayoutVersion();

		} else if ($layoutFileType == 'galley') {
			SubmissionEditHandler::uploadGalley('layoutFile');

		} else if ($layoutFileType == 'supp') {
			SubmissionEditHandler::uploadSuppFile('layoutFile');

		} else {
			Request::redirect(null, null, null, 'submissionEditing', Request::getUserVar('paperId'));
		}
	}

	/**
	 * Upload the layout version of the submission file
	 */
	function uploadLayoutVersion() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);

		TrackEditorAction::uploadLayoutVersion($submission);

		Request::redirect(null, null, null, 'submissionEditing', $paperId);
	}

	/**
	 * Create a new galley with the uploaded file.
	 */
	function uploadGalley($fileName = null) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);

		import('submission.form.PaperGalleyForm');

		$galleyForm = &new PaperGalleyForm($paperId);
		$galleyId = $galleyForm->execute($fileName);

		Request::redirect(null, null, null, 'editGalley', array($paperId, $galleyId));
	}

	/**
	 * Edit a galley.
	 * @param $args array ($paperId, $galleyId)
	 */
	function editGalley($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);

		parent::setupTemplate(true, $paperId, 'editing');

		import('submission.form.PaperGalleyForm');

		$submitForm = &new PaperGalleyForm($paperId, $galleyId);

		$submitForm->initData();
		$submitForm->display();
	}

	/**
	 * Save changes to a galley.
	 * @param $args array ($paperId, $galleyId)
	 */
	function saveGalley($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);

		import('submission.form.PaperGalleyForm');

		$submitForm = &new PaperGalleyForm($paperId, $galleyId);
		$submitForm->readInputData();

		if (Request::getUserVar('uploadImage')) {
			// Attach galley image
			$submitForm->uploadImage();

			parent::setupTemplate(true, $paperId, 'editing');
			$submitForm->display();

		} else if(($deleteImage = Request::getUserVar('deleteImage')) && count($deleteImage) == 1) {
			// Delete galley image
			list($imageId) = array_keys($deleteImage);
			$submitForm->deleteImage($imageId);

			parent::setupTemplate(true, $paperId, 'editing');
			$submitForm->display();

		} else if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, null, 'submissionEditing', $paperId);

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
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);

		TrackEditorAction::orderGalley($submission, Request::getUserVar('galleyId'), Request::getUserVar('d'));

		Request::redirect(null, null, null, 'submissionEditing', $paperId);
	}

	/**
	 * Delete a galley file.
	 * @param $args array ($paperId, $galleyId)
	 */
	function deleteGalley($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);

		TrackEditorAction::deleteGalley($submission, $galleyId);

		Request::redirect(null, null, null, 'submissionEditing', $paperId);
	}

	/**
	 * Upload a new supplementary file.
	 */
	function uploadSuppFile($fileName = null) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		import('submission.form.SuppFileForm');

		$suppFileForm = &new SuppFileForm($submission);
		$suppFileForm->setData('title', Locale::translate('common.untitled'));
		$suppFileId = $suppFileForm->execute($fileName);

		Request::redirect(null, null, null, 'editSuppFile', array($paperId, $suppFileId));
	}

	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		TrackEditorAction::orderSuppFile($submission, Request::getUserVar('suppFileId'), Request::getUserVar('d'));

		Request::redirect(null, null, null, 'submissionEditing', $paperId);
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
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao = &DAORegistry::getDAO('PaperEventLogDAO');
			$logEntry = &$logDao->getLogEntry($logId, $paperId);
		}

		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));

		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('trackEditor/submissionEventLogEntry.tpl');

		} else {
			$rangeInfo = &Handler::getRangeInfo('eventLogEntries');

			import('paper.log.PaperLog');
			$eventLogEntries = &PaperLog::getEventLogEntries($paperId, $rangeInfo);
			$templateMgr->assign('eventLogEntries', $eventLogEntries);
			$templateMgr->display('trackEditor/submissionEventLog.tpl');
		}
	}

	/**
	 * View submission event log by record type.
	 */
	function submissionEventLogType($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$assocType = isset($args[1]) ? (int) $args[1] : null;
		$assocId = isset($args[2]) ? (int) $args[2] : null;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$rangeInfo = &Handler::getRangeInfo('eventLogEntries');
		$logDao = &DAORegistry::getDAO('PaperEventLogDAO');
		$eventLogEntries = &$logDao->getPaperLogEntriesByAssoc($paperId, $assocType, $assocId, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		$templateMgr->display('trackEditor/submissionEventLog.tpl');
	}

	/**
	 * Clear submission event log entries.
	 */
	function clearSubmissionEventLog($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

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
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao = &DAORegistry::getDAO('PaperEmailLogDAO');
			$logEntry = &$logDao->getLogEntry($logId, $paperId);
		}

		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));

		if (isset($logEntry)) {
			$templateMgr->assign_by_ref('logEntry', $logEntry);
			$templateMgr->display('trackEditor/submissionEmailLogEntry.tpl');

		} else {
			$rangeInfo = &Handler::getRangeInfo('emailLogEntries');

			import('paper.log.PaperLog');
			$emailLogEntries = &PaperLog::getEmailLogEntries($paperId, $rangeInfo);
			$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
			$templateMgr->display('trackEditor/submissionEmailLog.tpl');
		}
	}

	/**
	 * View submission email log by record type.
	 */
	function submissionEmailLogType($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$assocType = isset($args[1]) ? (int) $args[1] : null;
		$assocId = isset($args[2]) ? (int) $args[2] : null;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$rangeInfo = &Handler::getRangeInfo('eventLogEntries');
		$logDao = &DAORegistry::getDAO('PaperEmailLogDAO');
		$emailLogEntries = &$logDao->getPaperLogEntriesByAssoc($paperId, $assocType, $assocId, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isEditor', Validation::isEditor());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('emailLogEntries', $emailLogEntries);
		$templateMgr->display('trackEditor/submissionEmailLog.tpl');
	}

	/**
	 * Clear submission email log entries.
	 */
	function clearSubmissionEmailLog($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$logId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

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
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		TrackEditorAction::addSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Removes a submission note.
	 * Redirects to submission notes list
	 */
	function removeSubmissionNote() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		TrackEditorAction::removeSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Updates a submission note.
	 * Redirects to submission notes list
	 */
	function updateSubmissionNote() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		TrackEditorAction::updateSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Clear all submission notes.
	 * Redirects to submission notes list
	 */
	function clearAllSubmissionNotes() {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		TrackEditorAction::clearAllSubmissionNotes($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * View submission notes.
	 */
	function submissionNotes($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$noteViewType = isset($args[1]) ? $args[1] : '';
		$noteId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		parent::setupTemplate(true, $paperId, 'history');

		$rangeInfo = &Handler::getRangeInfo('submissionNotes');
		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');
		$submissionNotes =& $paperNoteDao->getPaperNotes($paperId, $rangeInfo);

		// submission note edit
		if ($noteViewType == 'edit') {
			$paperNote = $paperNoteDao->getPaperNoteById($noteId);
		}

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('submissionNotes', $submissionNotes);
		$templateMgr->assign('noteViewType', $noteViewType);
		if (isset($paperNote)) {
			$templateMgr->assign_by_ref('paperNote', $paperNote);
		}

		$templateMgr->assign_by_ref('eventSettings', $event->getSettings(true));

		if ($noteViewType == 'edit' || $noteViewType == 'add') {
			$templateMgr->assign('showBackLink', true);
		}

		$templateMgr->display('trackEditor/submissionNotes.tpl');
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

		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		if (!TrackEditorAction::downloadFile($paperId, $fileId, $revision)) {
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

		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		if (!TrackEditorAction::viewFile($paperId, $fileId, $revision)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	/**
	 * Queue submission for scheduling
	 * @param $args array ($paperId)
	 */
	function queueForScheduling($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId, TRACK_EDITOR_ACCESS_EDIT);

		import('submission.proofreader.ProofreaderAction');
		ProofreaderAction::queueForScheduling($submission);

		Request::redirect(null, null, null, 'submissionEditing', $paperId);
	}


	//
	// Validation
	//

	/**
	 * Validate that the user is the assigned track editor for
	 * the paper, or is a managing editor.
	 * Redirects to trackEditor index page if validation fails.
	 * @param $paperId int Paper ID to validate
	 * @param $access int Optional name of access level required -- see TRACK_EDITOR_ACCESS_... constants
	 */
	function validate($paperId, $access = null) {
		parent::validate();

		$isValid = true;

		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$conference = &Request::getConference();
		$event = &Request::getEvent();
		$user = &Request::getUser();

		$trackEditorSubmission = &$trackEditorSubmissionDao->getTrackEditorSubmission($paperId);

		if ($trackEditorSubmission == null) {
			$isValid = false;

		} else if ($trackEditorSubmission->getEventId() != $event->getEventId()) {
			$isValid = false;

		} else if ($trackEditorSubmission->getDateSubmitted() == null) {
			$isValid = false;

		} else {
			$templateMgr =& TemplateManager::getManager();
			if (Validation::isEditor()) {
				// Make canReview and canEdit available to templates.
				// Since this user is an editor, both are available.
				$templateMgr->assign('canReview', true);
				$templateMgr->assign('canEdit', true);
			} else {
				// If this user isn't the submission's editor, they don't have access.
				$editAssignments =& $trackEditorSubmission->getEditAssignments();
				$wasFound = false;
				foreach ($editAssignments as $editAssignment) {
					if ($editAssignment->getEditorId() == $user->getUserId()) {
						$templateMgr->assign('canReview', $editAssignment->getCanReview());
						$templateMgr->assign('canEdit', $editAssignment->getCanEdit());
						switch ($access) {
							case TRACK_EDITOR_ACCESS_EDIT:
								if ($editAssignment->getCanEdit()) {
									$wasFound = true;
								}
								break;
							case TRACK_EDITOR_ACCESS_REVIEW:
								if ($editAssignment->getCanReview()) {
									$wasFound = true;
								}
								break;

							default:
								$wasFound = true;
						}
						break;
					}
				}

				if (!$wasFound) $isValid = false;
			}
		}

		if (!$isValid) {
			Request::redirect(null, null, null, Request::getRequestedPage());
		}

		// If necessary, note the current date and time as the "underway" date/time
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = &$trackEditorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getEditorId() == $user->getUserId() && $editAssignment->getDateUnderway() === null) {
				$editAssignment->setDateUnderway(Core::getCurrentDate());
				$editAssignmentDao->updateEditAssignment($editAssignment);
			}
		}

		return array(&$conference, &$event, &$trackEditorSubmission);
	}
}
?>
