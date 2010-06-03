<?php

/**
 * @file SubmissionEditHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEditHandler
 * @ingroup pages_trackDirector
 *
 * @brief Handle requests for submission tracking.
 *
 */

// $Id$


define('TRACK_DIRECTOR_ACCESS_EDIT', 0x00001);
define('TRACK_DIRECTOR_ACCESS_REVIEW', 0x00002);

import('pages.trackDirector.TrackDirectorHandler');

class SubmissionEditHandler extends TrackDirectorHandler {
	/** submission associated with the request **/
	var $submission;
	
	/**
	 * Constructor
	 **/
	function SubmissionEditHandler() {
		parent::TrackDirectorHandler();
	}
	
	function submission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId);

		// FIXME? For comments.readerComments under Status
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_READER));

		$user =& Request::getUser();

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$isDirector = $roleDao->roleExists($conference->getId(), $schedConf->getId(), $user->getId(), ROLE_ID_DIRECTOR);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($submission->getTrackId());

		$enableComments = $conference->getSetting('enableComments');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('track', $track);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('reviewMode', $submission->getReviewMode());
		$templateMgr->assign('userId', $user->getId());
		$templateMgr->assign('isDirector', $isDirector);
		$templateMgr->assign('enableComments', $enableComments);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$templateMgr->assign_by_ref('tracks', $trackDao->getTrackTitles($schedConf->getId()));
		if ($enableComments) {
			import('classes.paper.Paper');
			$templateMgr->assign('commentsStatus', $submission->getCommentsStatus());
			$templateMgr->assign_by_ref('commentsStatusOptions', Paper::getCommentsStatusOptions());
		}

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($submission->getPaperId());
		if ($publishedPaper) {
			$templateMgr->assign_by_ref('publishedPaper', $publishedPaper);
		}

		if ($isDirector) {
			$templateMgr->assign('helpTopicId', 'editorial.directorsRole.summaryPage');
		}

		$controlledVocabDao =& DAORegistry::getDAO('ControlledVocabDAO');
		$templateMgr->assign('sessionTypes', $controlledVocabDao->enumerateBySymbolic('paperType', ASSOC_TYPE_SCHED_CONF, $schedConf->getId()));

		$templateMgr->assign('mayEditPaper', true);

		$templateMgr->display('trackDirector/submission.tpl');
	}

	function submissionRegrets($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId, 'review');

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$cancelsAndRegrets = $reviewAssignmentDao->getCancelsAndRegrets($paperId);
		$reviewFilesByRound = $reviewAssignmentDao->getReviewFilesByRound($paperId);

		$rounds = $submission->getReviewAssignments();
		$numRounds = $submission->getCurrentRound();

		$directorDecisions = $submission->getDecisions();

		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponses = array();
		if (isset($rounds[$numRounds-1])) {
			foreach ($rounds[$numRounds-1] as $round) {
				$reviewFormResponses[$round->getReviewId()] = $reviewFormResponseDao->reviewFormResponseExists($round->getReviewId());
			}
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('reviewMode', $submission->getReviewMode());
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewAssignmentRounds', $rounds);
		$templateMgr->assign('reviewFormResponses', $reviewFormResponses);
		$templateMgr->assign_by_ref('cancelsAndRegrets', $cancelsAndRegrets);
		$templateMgr->assign_by_ref('reviewFilesByRound', $reviewFilesByRound);
		$templateMgr->assign_by_ref('directorDecisions', $directorDecisions);
		$templateMgr->assign_by_ref('directorDecisionOptions', TrackDirectorSubmission::getDirectorDecisionOptions());
		$templateMgr->assign('rateReviewerOnQuality', $schedConf->getSetting('rateReviewerOnQuality'));

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$templateMgr->display('trackDirector/submissionRegrets.tpl');
	}

	function submissionReview($args) {
		$paperId = (isset($args[0]) ? $args[0] : null);

		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;
		
		$round = (isset($args[1]) ? (int) $args[1] : null);
		$reviewMode = $submission->getReviewMode();
		switch ($reviewMode) {
			case REVIEW_MODE_ABSTRACTS_ALONE:
				$round = REVIEW_ROUND_ABSTRACT;
				break;
			case REVIEW_MODE_BOTH_SIMULTANEOUS:
			case REVIEW_MODE_PRESENTATIONS_ALONE:
				$round = REVIEW_ROUND_PRESENTATION;
				break;
			case REVIEW_MODE_BOTH_SEQUENTIAL:
				if ($round != REVIEW_ROUND_ABSTRACT && $round != REVIEW_ROUND_PRESENTATION) $round = $submission->getCurrentRound();
				break;
		}

		$this->setupTemplate(true, $paperId);

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$tracks =& $trackDao->getSchedConfTracks($schedConf->getId());

		$directorDecisions = $submission->getDecisions($round);
		$lastDecision = count($directorDecisions) >= 1 ? $directorDecisions[count($directorDecisions) - 1]['decision'] : null;

		$editAssignments =& $submission->getEditAssignments();
		$isCurrent = ($round == $submission->getCurrentRound());
		$showPeerReviewOptions = $isCurrent && $submission->getReviewFile() != null ? true : false;

		$allowRecommendation = ($isCurrent  || ($round == REVIEW_ROUND_ABSTRACT && $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL)) &&
			!empty($editAssignments);

		$reviewingAbstractOnly = ($reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL && $round == REVIEW_ROUND_ABSTRACT) || $reviewMode == REVIEW_MODE_ABSTRACTS_ALONE;

		// Prepare an array to store the 'Notify Reviewer' email logs
		$notifyReviewerLogs = array();
		if($submission->getReviewAssignments($round)) {
			foreach ($submission->getReviewAssignments($round) as $reviewAssignment) {
				$notifyReviewerLogs[$reviewAssignment->getId()] = array();
			}
		}

		// Parse the list of email logs and populate the array.
		import('classes.paper.log.PaperLog');
		$emailLogEntries =& PaperLog::getEmailLogEntries($paperId);
		foreach ($emailLogEntries->toArray() as $emailLog) {
			if ($emailLog->getEventType() == PAPER_EMAIL_REVIEW_NOTIFY_REVIEWER) {
				if (isset($notifyReviewerLogs[$emailLog->getAssocId()]) && is_array($notifyReviewerLogs[$emailLog->getAssocId()])) {
					array_push($notifyReviewerLogs[$emailLog->getAssocId()], $emailLog);
				}
			}
		}

		// get conference published review form titles
		$reviewFormTitles =& $reviewFormDao->getTitlesByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getId(), 1);

		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponses = array();

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormTitles = array();

		if ($submission->getReviewAssignments($round)) {
			foreach ($submission->getReviewAssignments($round) as $reviewAssignment) {
				$reviewForm =& $reviewFormDao->getReviewForm($reviewAssignment->getReviewFormId());
				if ($reviewForm) {
					$reviewFormTitles[$reviewForm->getId()] = $reviewForm->getLocalizedTitle();
				}
				unset($reviewForm);
				$reviewFormResponses[$reviewAssignment->getId()] = $reviewFormResponseDao->reviewFormResponseExists($reviewAssignment->getId());
			}
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('reviewIndexes', $reviewAssignmentDao->getReviewIndexesForRound($paperId, $round));
		$templateMgr->assign('round', $round);
		$templateMgr->assign_by_ref('reviewAssignments', $submission->getReviewAssignments($round));
		$templateMgr->assign('reviewFormResponses', $reviewFormResponses);
		$templateMgr->assign('reviewFormTitles', $reviewFormTitles);
		$templateMgr->assign_by_ref('notifyReviewerLogs', $notifyReviewerLogs);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('revisedFile', $submission->getRevisedFile());
		$templateMgr->assign_by_ref('directorFile', $submission->getDirectorFile());
		$templateMgr->assign('rateReviewerOnQuality', $schedConf->getSetting('rateReviewerOnQuality'));
		$templateMgr->assign('showPeerReviewOptions', $showPeerReviewOptions);
		$templateMgr->assign_by_ref('tracks', $tracks->toArray());
		$templateMgr->assign_by_ref('directorDecisionOptions', TrackDirectorSubmission::getDirectorDecisionOptions());
		$templateMgr->assign_by_ref('lastDecision', $lastDecision);
		$templateMgr->assign_by_ref('directorDecisions', $directorDecisions);

		if ($reviewMode != REVIEW_MODE_BOTH_SEQUENTIAL || $round == REVIEW_ROUND_PRESENTATION) {
			$templateMgr->assign('isFinalReview', true);
		}

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());
		$templateMgr->assign_by_ref('reviewerRatingOptions', ReviewAssignment::getReviewerRatingOptions());

		$templateMgr->assign('isCurrent', $isCurrent);
		$templateMgr->assign('allowRecommendation', $allowRecommendation);
		$templateMgr->assign('reviewingAbstractOnly', $reviewingAbstractOnly);

		$templateMgr->assign('helpTopicId', 'editorial.trackDirectorsRole.review');

		$controlledVocabDao =& DAORegistry::getDAO('ControlledVocabDAO');
		$templateMgr->assign('sessionTypes', $controlledVocabDao->enumerateBySymbolic('sessionTypes', ASSOC_TYPE_SCHED_CONF, $schedConf->getId()));

		$templateMgr->display('trackDirector/submissionReview.tpl');
	}

	/**
	 * View submission history
	 */
	function submissionHistory($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$this->setupTemplate(true, $paperId);

		// submission notes
		$noteDao =& DAORegistry::getDAO('NoteDAO');

		$submissionNotes =& $noteDao->getByAssoc(ASSOC_TYPE_PAPER, $paperId);

		import('classes.paper.log.PaperLog');

		$rangeInfo =& Handler::getRangeInfo('eventLogEntries', array($paperId));
		while (true) {
			$eventLogEntries =& PaperLog::getEventLogEntries($paperId, $rangeInfo);
			unset($rangeInfo);
			if ($eventLogEntries->isInBounds()) break;
			$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
			unset($eventLogEntries);
		}

		$rangeInfo =& Handler::getRangeInfo('emailLogEntries', array($paperId));
		while (true) {
			$emailLogEntries =& PaperLog::getEmailLogEntries($paperId, $rangeInfo);
			unset($rangeInfo);
			if ($emailLogEntries->isInBounds()) break;
			$rangeInfo =& $emailLogEntries->getLastPageRangeInfo();
			unset($emailLogEntries);
		}

		$templateMgr =& TemplateManager::getManager();

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
		$this->validate($paperId);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$trackId = Request::getUserVar('trackId');

		TrackDirectorAction::changeTrack($submission, $trackId);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	/**
	 * Change the session type for a submission.
	 */
	function changeSessionType() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;
		$sessionType = Request::getUserVar('sessionType');
		TrackDirectorAction::changeSessionType($submission, $sessionType);
		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function recordDecision($args) {
		$paperId = (int) Request::getUserVar('paperId');
		$decision = (int) Request::getUserVar('decision');
		$round = (int) array_shift($args);

		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		// If the director changes the decision on the first round,
		// roll back to the abstract review round.
		if (
			$submission->getCurrentRound() == REVIEW_ROUND_PRESENTATION &&
			$round == REVIEW_ROUND_ABSTRACT
		) {
			$submission->setCurrentRound(REVIEW_ROUND_ABSTRACT);

			// Now, unassign all reviewers from the paper review
			foreach ($submission->getReviewAssignments(REVIEW_ROUND_PRESENTATION) as $reviewAssignment) {
				if ($reviewAssignment->getRecommendation() !== null && $reviewAssignment->getRecommendation() !== '') {
					TrackDirectorAction::clearReview($submission, $reviewAssignment->getId());
				}
			}

			TrackDirectorAction::recordDecision($submission, $decision, $round);
		} else {
			switch ($decision) {
				case SUBMISSION_DIRECTOR_DECISION_ACCEPT:
				case SUBMISSION_DIRECTOR_DECISION_INVITE:
				case SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS:
				case SUBMISSION_DIRECTOR_DECISION_DECLINE:
					TrackDirectorAction::recordDecision($submission, $decision, $round);
					break;
			}
		}

		Request::redirect(null, null, null, 'submissionReview', array($paperId, $round));
	}

	function completePaper($args) {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER)); // manager.people.noneEnrolled FIXME?

		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = Request::getUserVar('sortDirection');

		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		if (isset($args[1]) && $args[1] != null) {
			// Assign reviewer to paper
			TrackDirectorAction::addReviewer($submission, (int) $args[1], $submission->getCurrentRound());
			Request::redirect(null, null, null, 'submissionReview', $paperId);

			// FIXME: Prompt for due date.
		} else {
			$this->setupTemplate(true, $paperId, 'review');

			$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$rangeInfo =& Handler::getRangeInfo('reviewers', array($submission->getCurrentRound(), (string) $searchType, (string) $search, (string) $searchMatch)); // Paper ID intentionally omitted
			while (true) {
				$reviewers = $trackDirectorSubmissionDao->getReviewersForPaper($schedConf->getId(), $paperId, $submission->getCurrentRound(), $searchType, $search, $searchMatch, $rangeInfo, $sort, $sortDirection);
				if ($reviewers->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $reviewers->getLastPageRangeInfo();
				unset($reviewers);
			}

			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

			$templateMgr->assign_by_ref('reviewers', $reviewers);
			$templateMgr->assign('paperId', $paperId);
			$templateMgr->assign('reviewerStatistics', $trackDirectorSubmissionDao->getReviewerStatistics($schedConf->getId()));
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_INTERESTS => 'user.interests',
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('completedReviewCounts', $reviewAssignmentDao->getCompletedReviewCounts($schedConf->getId()));
			$templateMgr->assign('rateReviewerOnQuality', $schedConf->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('averageQualityRatings', $reviewAssignmentDao->getAverageQualityRatings($schedConf->getId()));

			$templateMgr->assign('helpTopicId', 'conference.roles.reviewers');
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->assign('sort', $sort);
			$templateMgr->assign('sortDirection', $sortDirection);
			$templateMgr->display('trackDirector/selectReviewer.tpl');
		}
	}

	/**
	 * Create a new user as a reviewer.
	 */
	function createReviewer(&$args, &$request) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;


		import('classes.trackDirector.form.CreateReviewerForm');
		$createReviewerForm = new CreateReviewerForm($paperId);
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER));
		$this->setupTemplate(true, $paperId);

		if (isset($args[1]) && $args[1] === 'create') {
			$createReviewerForm->readInputData();
			if ($createReviewerForm->validate()) {
				// Create a user and enroll them as a reviewer.
				$newUserId = $createReviewerForm->execute();
				Request::redirect(null, null, null, 'selectReviewer', array($paperId, $newUserId));
			} else {
				$createReviewerForm->display($args, $request);
			}
		} else {
			// Display the "create user" form.
			if ($createReviewerForm->isLocaleResubmit()) {
				$createReviewerForm->readInputData();
			} else {
				$createReviewerForm->initData();
			}
			$createReviewerForm->display($args, $request);
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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER)); // manager.people.enrollment, manager.people.enroll
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;


		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$user =& Request::getUser();

		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo('users', array((string) $searchType, (string) $searchMatch, (string) $search)); // Paper ID intentionally omitted
		$userDao =& DAORegistry::getDAO('UserDAO');
		while (true) {
			$users =& $userDao->getUsersByField($searchType, $searchMatch, $search, false, $rangeInfo);
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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;


		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$users = Request::getUserVar('users');
		if (!is_array($users) && Request::getUserVar('userId') != null) $users = array(Request::getUserVar('userId'));

		// Enroll reviewer
		for ($i=0; $i<count($users); $i++) {
			if (!$roleDao->roleExists($schedConf->getConferenceId(), $schedConf->getId(), $users[$i], $roleId)) {
				$role = new Role();
				$role->setConferenceId($schedConf->getConferenceId());
				$role->setSchedConfId($schedConf->getId());
				$role->setUserId($users[$i]);
				$role->setRoleId($roleId);

				$roleDao->insertRole($role);
			}
		}
		Request::redirect(null, null, null, 'selectReviewer', $paperId);
	}

	function notifyReviewer($args = array()) {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;


		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		$this->setupTemplate(true, $paperId, 'review');

		if (TrackDirectorAction::notifyReviewer($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function clearReview($args) {
		$paperId = isset($args[0])?$args[0]:0;
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;


		$reviewId = $args[1];

		TrackDirectorAction::clearReview($submission, $reviewId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function cancelReview($args) {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		$this->setupTemplate(true, $paperId, 'review');

		if (TrackDirectorAction::cancelReview($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function remindReviewer($args = null) {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = Request::getUserVar('reviewId');
		$this->setupTemplate(true, $paperId, 'review');

		if (TrackDirectorAction::remindReviewer($submission, $reviewId, Request::getUserVar('send'))) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function thankReviewer($args = array()) {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = Request::getUserVar('reviewId');

		$send = Request::getUserVar('send')?true:false;
		$this->setupTemplate(true, $paperId, 'review');

		if (TrackDirectorAction::thankReviewer($submission, $reviewId, $send)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function rateReviewer() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$this->setupTemplate(true, $paperId, 'review');

		$reviewId = Request::getUserVar('reviewId');
		$quality = Request::getUserVar('quality');

		TrackDirectorAction::rateReviewer($paperId, $reviewId, $quality);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function confirmReviewForReviewer($args) {
		$paperId = (int) isset($args[0])?$args[0]:0;
		$accept = Request::getUserVar('accept')?true:false;
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = (int) isset($args[1])?$args[1]:0;

		TrackDirectorAction::confirmReviewForReviewer($reviewId);
		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function uploadReviewForReviewer($args) {
		$paperId = (int) Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = (int) Request::getUserVar('reviewId');

		TrackDirectorAction::uploadReviewForReviewer($reviewId);
		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function makeReviewerFileViewable() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = Request::getUserVar('reviewId');
		$fileId = Request::getUserVar('fileId');
		$revision = Request::getUserVar('revision');
		$viewable = Request::getUserVar('viewable');

		TrackDirectorAction::makeReviewerFileViewable($paperId, $reviewId, $fileId, $revision, $viewable);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function setDueDate($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = isset($args[1]) ? $args[1] : 0;
		$dueDate = Request::getUserVar('dueDate');
		$numWeeks = Request::getUserVar('numWeeks');

		if ($dueDate != null || $numWeeks != null) {
			TrackDirectorAction::setDueDate($paperId, $reviewId, $dueDate, $numWeeks);
			Request::redirect(null, null, null, 'submissionReview', $paperId);

		} else {
			$this->setupTemplate(true, $paperId, 'review');

			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment = $reviewAssignmentDao->getById($reviewId);

			$settings = $schedConf->getSettings();

			$templateMgr =& TemplateManager::getManager();

			if ($reviewAssignment->getDateDue() != null) {
				$templateMgr->assign('dueDate', $reviewAssignment->getDateDue());
			}

			if ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_ABSOLUTE) {
				// Get number of days from now until review deadline date
				$reviewDeadlineDate = strtotime($schedConf->getSetting('numWeeksPerReviewAbsolute'));
				$daysDiff = ($reviewDeadlineDate - strtotime(date("Y-m-d"))) / (60 * 60 * 24);
				$numWeeksPerReview = round($daysDiff / 7);
			} elseif ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_RELATIVE) {
				$numWeeksPerReview = ((int) $schedConf->getSetting('numWeeksPerReviewRelative'));
			} else $numWeeksPerReview = 0;

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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = Request::getUserVar('reviewId');

		$recommendation = Request::getUserVar('recommendation');

		if ($recommendation != null) {
			TrackDirectorAction::setReviewerRecommendation($paperId, $reviewId, $recommendation, SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			$this->setupTemplate(true, $paperId, 'review');

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('paperId', $paperId);
			$templateMgr->assign('reviewId', $reviewId);

			import('classes.submission.reviewAssignment.ReviewAssignment');
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
		$this->setupTemplate(true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::url(null, null, null, Request::getRequestedPage()));

		$userDao =& DAORegistry::getDAO('UserDAO');
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
			$site =& Request::getSite();

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
		$this->validate($paperId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId, 'summary');

		TrackDirectorAction::viewMetadata($submission, ROLE_ID_TRACK_DIRECTOR);
	}

	function saveMetadata() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId, 'summary');

		if (TrackDirectorAction::saveMetadata($submission)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	//
	// Review Form
	//

	/**
	 * Preview a review form.
	 * @param $args array ($reviewId, $reviewFormId)
	 */
	function previewReviewForm($args) {
		parent::validate();
		$this->setupTemplate(true);

		$reviewId = isset($args[0]) ? (int) $args[0] : null;
		$reviewFormId = isset($args[1]) ? (int)$args[1] : null;

		$conference =& Request::getConference();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_CONFERENCE, $conference->getId());
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'manager.reviewForms.preview');
		$templateMgr->assign_by_ref('reviewForm', $reviewForm);
		$templateMgr->assign('reviewFormElements', $reviewFormElements);
		$templateMgr->assign('reviewId', $reviewId);
		$templateMgr->assign('paperId', $reviewAssignment->getSubmissionId());
		//$templateMgr->assign('helpTopicId','conference.managementPages.reviewForms');
		$templateMgr->display('trackDirector/previewReviewForm.tpl');
	}

	/**
	 * Clear a review form, i.e. remove review form assignment to the review.
	 * @param $args array ($paperId, $reviewId)
	 */
	function clearReviewForm($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$reviewId = isset($args[1]) ? (int) $args[1] : null;
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;
		
		TrackDirectorAction::clearReviewForm($submission, $reviewId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Select a review form
	 * @param $args array ($paperId, $reviewId, $reviewFormId)
	 */
	function selectReviewForm($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		$reviewId = isset($args[1]) ? (int) $args[1] : null;
		$reviewFormId = isset($args[2]) ? (int) $args[2] : null;

		if ($reviewFormId != null) {
			TrackDirectorAction::addReviewForm($submission, $reviewId, $reviewFormId);
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			$conference =& Request::getConference();
			$rangeInfo =& Handler::getRangeInfo('reviewForms');
			$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
			$reviewForms =& $reviewFormDao->getActiveByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getId(), $rangeInfo);
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

			$this->setupTemplate(true, $paperId, 'review');
			$templateMgr =& TemplateManager::getManager();

			$templateMgr->assign('paperId', $paperId);
			$templateMgr->assign('reviewId', $reviewId);
			$templateMgr->assign('assignedReviewFormId', $reviewAssignment->getReviewFormId());
			$templateMgr->assign_by_ref('reviewForms', $reviewForms);
			//$templateMgr->assign('helpTopicId','conference.managementPages.reviewForms');
			$templateMgr->display('trackDirector/selectReviewForm.tpl');
		}
	}

	/**
	 * View review form response.
	 * @param $args array ($paperId, $reviewId)
	 */
	function viewReviewFormResponse($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId, 'summary');

		$reviewId = isset($args[1]) ? (int) $args[1] : null;

		TrackDirectorAction::viewReviewFormResponse($submission, $reviewId);
	}

	//
	// Director Review
	//

	function directorReview($args) {
		import('classes.paper.Paper');

		$round = (isset($args[0]) ? $args[0] : REVIEW_ROUND_ABSTRACT);
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$redirectArgs = array($paperId, $round);

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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		TrackDirectorAction::uploadReviewVersion($submission);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($paperId)
	 */
	function addSuppFile($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId, 'summary');

		import('classes.submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($submission);

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
		$this->validate($paperId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId, 'summary');

		import('classes.submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($submission, $suppFileId);

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
		$this->validate($paperId);
		$submission =& $this->submission;

		$suppFileId = Request::getUserVar('fileId');
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
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
		$this->validate($paperId);
		$this->setupTemplate(true, $paperId, 'summary');
		$submission =& $this->submission;

		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		import('classes.submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($submission, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('lib.pkp.classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$paperDao =& DAORegistry::getDAO('PaperDAO');
			$paper =& $paperDao->getPaper($paperId);
			$notificationUsers = $paper->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$url = Request::url(null, null, $userRole['role'], 'submissionReview', $paper->getId(), null, 'layout');
				$notificationManager->createNotification(
					$userRole['id'], 'notification.type.suppFileModified',
					$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_SUPP_FILE_MODIFIED
				);
			}

			Request::redirect(null, null, null, 'submissionReview', $paperId);
		} else {
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

		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;
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
		$this->validate($paperId);
		$submission =& $this->submission;

		TrackDirectorAction::deleteSuppFile($submission, $suppFileId);

		Request::redirect(null, null, null, 'submissionReview', $paperId);
	}

	function archiveSubmission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId);
		$submission =& $this->submission;

		TrackDirectorAction::archiveSubmission($submission);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function restoreToQueue($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId);
		$submission =& $this->submission;
		
		TrackDirectorAction::restoreToQueue($submission);

		Request::redirect(null, null, null, 'submission', $paperId);
	}

	function unsuitableSubmission($args) {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId);
		$submission =& $this->submission;
		
		$send = Request::getUserVar('send')?true:false;
		$this->setupTemplate(true, $paperId, 'summary');

		if (TrackDirectorAction::unsuitableSubmission($submission, $send)) {
			Request::redirect(null, null, null, 'submission', $paperId);
		}
	}

	/**
	 * Set RT comments status for paper.
	 * @param $args array ($paperId)
	 */
	function updateCommentsStatus($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate($paperId);
		$submission =& $this->submission;
		TrackDirectorAction::updateCommentsStatus($submission, Request::getUserVar('commentsStatus'));
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
		$round = (int) Request::getUserVar('round');

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		if ($fileManager->uploadError('layoutFile')) {
			$templateMgr =& TemplateManager::getManager();
			$this->setupTemplate(true);
			$templateMgr->assign('pageTitle', 'submission.review');
			$templateMgr->assign('message', 'common.uploadFailed');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'submissionReview', array(Request::getUserVar('paperId'))));
			$templateMgr->assign('backLinkLabel', 'submission.review');
			return $templateMgr->display('common/message.tpl');
		}
		
		if ($layoutFileType == 'galley') {
			$this->uploadGalley('layoutFile', $round);

		} else if ($layoutFileType == 'supp') {
			$this->uploadSuppFile('layoutFile', $round);

		} else {
			Request::redirect(null, null, null, 'submission', Request::getUserVar('paperId'));
		}
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

		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;

		TrackDirectorAction::deletePaperImage($submission, $fileId, $revisionId);

		Request::redirect(null, null, 'editGalley', array($paperId, $galleyId));
	}

	/**
	 * Create a new galley with the uploaded file.
	 * @param $fileName string
	 * @param $round int
	 */
	function uploadGalley($fileName = null, $round = null) {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;


		import('classes.submission.form.PaperGalleyForm');

		$galleyForm = new PaperGalleyForm($paperId);
		$galleyId = $galleyForm->execute($fileName);

		Request::redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $round));
	}

	/**
	 * Edit a galley.
	 * @param $args array ($paperId, $galleyId)
	 */
	function editGalley($args) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$round = (int) array_shift($args);
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;

		$this->setupTemplate(true, $paperId, 'review');

		import('classes.submission.form.PaperGalleyForm');

		$submitForm = new PaperGalleyForm($paperId, $galleyId, $round);

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
		$round = (int) array_shift($args);
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$this->setupTemplate(true, $paperId, 'editing');
		$submission =& $this->submission;

		import('classes.submission.form.PaperGalleyForm');

		$submitForm = new PaperGalleyForm($paperId, $galleyId, $round);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('lib.pkp.classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$paperDao =& DAORegistry::getDAO('PaperDAO');
			$paper =& $paperDao->getPaper($paperId);
			$notificationUsers = $paper->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$url = Request::url(null, null, $userRole['role'], 'submissionReview', $paper->getId(), null, 'layout');
				$notificationManager->createNotification(
					$userRole['id'], 'notification.type.galleyModified',
					$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_GALLEY_MODIFIED
				);
			}

			if (Request::getUserVar('uploadImage')) {
				$submitForm->uploadImage();
				Request::redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $round));
			} else if(($deleteImage = Request::getUserVar('deleteImage')) && count($deleteImage) == 1) {
				list($imageId) = array_keys($deleteImage);
				$submitForm->deleteImage($imageId);
				Request::redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $round));
			}
			Request::redirect(null, null, null, 'submissionReview', array($paperId, $round));
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;


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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;

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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$templateMgr =& TemplateManager::getManager();
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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$templateMgr =& TemplateManager::getManager();
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
		$this->validate($paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $paperId);

		import('classes.file.PaperFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet(Request::url(null, null, 'paper', 'viewFile', array(
						$paperId, $galleyId, $styleFile->getFileId()
					)));
				}
				$templateMgr->display('submission/layout/proofGalleyHTML.tpl');

			} else {
				// View non-HTML file inline
				$this->viewFile(array($paperId, $galley->getFileId()));
			}
		}
	}

	/**
	 * Upload a new supplementary file.
	 * @param $fileName string
	 * @param $round int
	 */
	function uploadSuppFile($fileName = null, $round = null) {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$submission =& $this->submission;

		import('classes.submission.form.SuppFileForm');

		$suppFileForm = new SuppFileForm($submission);
		$suppFileForm->setData('title', Locale::translate('common.untitled'));
		$suppFileId = $suppFileForm->execute($fileName);

		Request::redirect(null, null, null, 'editSuppFile', array($paperId, $suppFileId));
	}

	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId);
		$submission =& $this->submission;

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
		$this->validate($paperId);
		$submission =& $this->submission;

		$this->setupTemplate(true, $paperId, 'history');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao =& DAORegistry::getDAO('PaperEventLogDAO');
			$logEntry =& $logDao->getLogEntry($logId, $paperId);
		}

		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('trackDirector/submissionEventLogEntry.tpl');

		} else {
			$rangeInfo =& Handler::getRangeInfo('eventLogEntries', array($paperId));

			import('classes.paper.log.PaperLog');
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
		$this->validate($paperId);
		$submission =& $this->submission;

		$this->setupTemplate(true, $paperId, 'history');

		$rangeInfo =& Handler::getRangeInfo('eventLogEntries', array($paperId, $assocType, $assocId));
		$logDao =& DAORegistry::getDAO('PaperEventLogDAO');
		while (true) {
			$eventLogEntries =& $logDao->getPaperLogEntriesByAssoc($paperId, $assocType, $assocId, $rangeInfo);
			if ($eventLogEntries->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
			unset($eventLogEntries);
		}

		$templateMgr =& TemplateManager::getManager();

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
		$this->validate($paperId);
		$submission =& $this->submission;

		$logDao =& DAORegistry::getDAO('PaperEventLogDAO');

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
		$this->validate($paperId);
		$submission =& $this->submission;
		
		$this->setupTemplate(true, $paperId, 'history');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao =& DAORegistry::getDAO('PaperEmailLogDAO');
			$logEntry =& $logDao->getLogEntry($logId, $paperId);
		}

		if (isset($logEntry)) {
			$templateMgr->assign_by_ref('logEntry', $logEntry);
			$templateMgr->display('trackDirector/submissionEmailLogEntry.tpl');

		} else {
			$rangeInfo =& Handler::getRangeInfo('emailLogEntries', array($paperId));

			import('classes.paper.log.PaperLog');
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
		$this->validate($paperId);
		$submission =& $this->submission;
		$this->setupTemplate(true, $paperId, 'history');

		$rangeInfo =& Handler::getRangeInfo('eventLogEntries', array($paperId, $assocType, $assocId));
		$logDao =& DAORegistry::getDAO('PaperEmailLogDAO');
		while (true) {
			$emailLogEntries =& $logDao->getPaperLogEntriesByAssoc($paperId, $assocType, $assocId, $rangeInfo);
			if ($emailLogEntries->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $emailLogEntries->getLastPageRangeInfo();
			unset($emailLogEntries);
		}

		$templateMgr =& TemplateManager::getManager();

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
		$this->validate($paperId);

		$logDao =& DAORegistry::getDAO('PaperEmailLogDAO');

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
		$this->validate($paperId);

		TrackDirectorAction::addSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Removes a submission note.
	 * Redirects to submission notes list
	 */
	function removeSubmissionNote() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId);

		TrackDirectorAction::removeSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Updates a submission note.
	 * Redirects to submission notes list
	 */
	function updateSubmissionNote() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId);

		TrackDirectorAction::updateSubmissionNote($paperId);
		Request::redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Clear all submission notes.
	 * Redirects to submission notes list
	 */
	function clearAllSubmissionNotes() {
		$paperId = Request::getUserVar('paperId');
		$this->validate($paperId);

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

		$this->validate($paperId);
		$this->setupTemplate(true, $paperId, 'history');
		$submission =& $this->submission;

		$noteDao =& DAORegistry::getDAO('NoteDAO');

		// submission note edit
		if ($noteViewType == 'edit') {
			$note = $noteDao->getById($noteId);
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign('noteViewType', $noteViewType);
		if (isset($note)) {
			$templateMgr->assign_by_ref('paperNote', $note);
		}

		if ($noteViewType == 'edit' || $noteViewType == 'add') {
			$templateMgr->assign('showBackLink', true);
		} else {
			$submissionNotes =& $noteDao->getByAssoc(ASSOC_TYPE_PAPER, $paperId);
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

		$this->validate($paperId);
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

		$this->validate($paperId);
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

		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();

		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paperId);

		if ($trackDirectorSubmission == null) {
			$isValid = false;

		} else if ($trackDirectorSubmission->getSchedConfId() != $schedConf->getId()) {
			$isValid = false;

		} else if ($trackDirectorSubmission->getDateSubmitted() == null) {
			$isValid = false;

		} else {
			$templateMgr =& TemplateManager::getManager();
			// If this user isn't the submission's director, they don't have access.
			$editAssignments =& $trackDirectorSubmission->getEditAssignments();
			$wasFound = false;
			foreach ($editAssignments as $editAssignment) {
				if ($editAssignment->getDirectorId() == $user->getId()) {
					$wasFound = true;
				}
			}
			if (!$wasFound && !Validation::isDirector()) $isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, null, null, Request::getRequestedPage());
		}

		// If necessary, note the current date and time as the "underway" date/time
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $trackDirectorSubmission->getEditAssignments();
		foreach ($editAssignments as $editAssignment) {
			if ($editAssignment->getDirectorId() == $user->getId() && $editAssignment->getDateUnderway() === null) {
				$editAssignment->setDateUnderway(Core::getCurrentDate());
				$editAssignmentDao->updateEditAssignment($editAssignment);
			}
		}

		$this->submission =& $trackDirectorSubmission;
		return true;
	}
}
?>
