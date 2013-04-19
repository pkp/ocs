<?php

/**
 * @file pages/trackDirector/SubmissionEditHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEditHandler
 * @ingroup pages_trackDirector
 *
 * @brief Handle requests for submission tracking.
 *
 */



define('TRACK_DIRECTOR_ACCESS_EDIT', 0x00001);
define('TRACK_DIRECTOR_ACCESS_REVIEW', 0x00002);

import('pages.trackDirector.TrackDirectorHandler');

class SubmissionEditHandler extends TrackDirectorHandler {
	/** submission associated with the request **/
	var $submission;

	/**
	 * Constructor
	 */
	function SubmissionEditHandler() {
		parent::TrackDirectorHandler();
	}

	function submission($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $paperId);

		// FIXME? For comments.readerComments under Status
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER);

		$user =& $request->getUser();

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$isDirector = $roleDao->userHasRole($conference->getId(), $schedConf->getId(), $user->getId(), ROLE_ID_DIRECTOR);

		$trackDao = DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($submission->getTrackId());

		$enableComments = $conference->getSetting('enableComments');

		$templateMgr =& TemplateManager::getManager($request);

		$templateMgr->assign_by_ref('submission', $submission);
		$templateMgr->assign_by_ref('track', $track);
		$templateMgr->assign_by_ref('submissionFile', $submission->getSubmissionFile());
		$templateMgr->assign_by_ref('suppFiles', $submission->getSuppFiles());
		$templateMgr->assign_by_ref('reviewFile', $submission->getReviewFile());
		$templateMgr->assign_by_ref('reviewMode', $submission->getReviewMode());
		$templateMgr->assign('userId', $user->getId());
		$templateMgr->assign('isDirector', $isDirector);
		$templateMgr->assign('enableComments', $enableComments);

		$trackDao = DAORegistry::getDAO('TrackDAO');
		$templateMgr->assign_by_ref('tracks', $trackDao->getTrackTitles($schedConf->getId()));
		if ($enableComments) {
			import('classes.paper.Paper');
			$templateMgr->assign('commentsStatus', $submission->getCommentsStatus());
			$templateMgr->assign_by_ref('commentsStatusOptions', Paper::getCommentsStatusOptions());
		}

		$publishedPaperDao = DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($submission->getId());
		if ($publishedPaper) {
			$templateMgr->assign_by_ref('publishedPaper', $publishedPaper);
		}

		if ($isDirector) {
			$templateMgr->assign('helpTopicId', 'editorial.directorsRole.summaryPage');
		}

		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');
		$templateMgr->assign('sessionTypes', $controlledVocabDao->enumerateBySymbolic('paperType', ASSOC_TYPE_SCHED_CONF, $schedConf->getId()));

		$templateMgr->assign('mayEditPaper', true);

		$templateMgr->display('trackDirector/submission.tpl');
	}

	function submissionRegrets($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $paperId, 'review');

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$cancelsAndRegrets = $reviewAssignmentDao->getCancelsAndRegrets($paperId);
		$reviewFilesByRound = $reviewAssignmentDao->getReviewFilesByRound($paperId);

		$rounds = $submission->getReviewAssignments();
		$numRounds = $submission->getCurrentRound();

		$directorDecisions = $submission->getDecisions();

		$reviewFormResponseDao = DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponses = array();
		if (isset($rounds[$numRounds-1])) {
			foreach ($rounds[$numRounds-1] as $round) {
				$reviewFormResponses[$round->getReviewId()] = $reviewFormResponseDao->reviewFormResponseExists($round->getReviewId());
			}
		}

		$templateMgr =& TemplateManager::getManager($request);
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

	function submissionReview($args, $request) {
		$paperId = (int) array_shift($args);

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$round = (int) array_shift($args);
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

		$this->setupTemplate($request, true, $paperId);

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');

		$trackDao = DAORegistry::getDAO('TrackDAO');
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

		$reviewFormResponseDao = DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponses = array();

		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
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

		$templateMgr =& TemplateManager::getManager($request);

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

		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');
		$templateMgr->assign('sessionTypes', $controlledVocabDao->enumerateBySymbolic('sessionTypes', ASSOC_TYPE_SCHED_CONF, $schedConf->getId()));

		$templateMgr->display('trackDirector/submissionReview.tpl');
	}

	/**
	 * View submission history
	 */
	function submissionHistory($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$this->setupTemplate($request, true, $paperId);

		// submission notes
		$noteDao = DAORegistry::getDAO('NoteDAO');

		$submissionNotes =& $noteDao->getByAssoc(ASSOC_TYPE_PAPER, $paperId);

		import('classes.paper.log.PaperLog');

		$rangeInfo = $this->getRangeInfo($request, 'eventLogEntries', array($paperId));
		while (true) {
			$eventLogEntries =& PaperLog::getEventLogEntries($paperId, $rangeInfo);
			unset($rangeInfo);
			if ($eventLogEntries->isInBounds()) break;
			$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
			unset($eventLogEntries);
		}

		$rangeInfo = $this->getRangeInfo($request, 'emailLogEntries', array($paperId));
		while (true) {
			$emailLogEntries =& PaperLog::getEmailLogEntries($paperId, $rangeInfo);
			unset($rangeInfo);
			if ($emailLogEntries->isInBounds()) break;
			$rangeInfo =& $emailLogEntries->getLastPageRangeInfo();
			unset($emailLogEntries);
		}

		$templateMgr =& TemplateManager::getManager($request);

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
	function changeTrack($args, $request) {
		$paperId = $request->getUserVar('paperId');
		$this->validate($request, $paperId);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$trackId = $request->getUserVar('trackId');

		TrackDirectorAction::changeTrack($submission, $trackId);

		$request->redirect(null, null, null, 'submission', $paperId);
	}

	/**
	 * Change the session type for a submission.
	 */
	function changeSessionType($args, $request) {
		$paperId = $request->getUserVar('paperId');
		$this->validate($request, $paperId);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;
		$sessionType = $request->getUserVar('sessionType');
		TrackDirectorAction::changeSessionType($submission, $sessionType);
		$request->redirect(null, null, null, 'submission', $paperId);
	}

	function recordDecision($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$decision = (int) $request->getUserVar('decision');
		$round = (int) array_shift($args);

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
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

		$request->redirect(null, null, null, 'submissionReview', array($paperId, $round));
	}

	function completePaper($args, $request) {
		$paperId = $request->getUserVar('paperId');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		if ($request->getUserVar('complete')) $complete = true;
		elseif ($request->getUserVar('remove')) $complete = false;
		else $request->redirect(null, null, null, 'index');

		TrackDirectorAction::completePaper($submission, $complete);

		$request->redirect(null, null, null, 'submissions', array($complete?'submissionsAccepted':'submissionsInReview'));
	}

	//
	// Peer Review
	//

	function selectReviewer($args, $request) {
		$paperId = (int) array_shift($args);
		$reviewerId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER); // manager.people.noneEnrolled FIXME?

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = $request->getUserVar('sortDirection');

		$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		if ($reviewerId) {
			// Assign reviewer to paper
			TrackDirectorAction::addReviewer($submission, $reviewerId, $submission->getCurrentRound());
			$request->redirect(null, null, null, 'submissionReview', $paperId);

			// FIXME: Prompt for due date.
		} else {
			$this->setupTemplate($request, true, $paperId, 'review');

			$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');

			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = $request->getUserVar('search');
			$searchInitial = $request->getUserVar('searchInitial');
			if (!empty($search)) {
				$searchType = $request->getUserVar('searchField');
				$searchMatch = $request->getUserVar('searchMatch');

			} elseif (!empty($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$rangeInfo = $this->getRangeInfo($request, 'reviewers', array($submission->getCurrentRound(), (string) $searchType, (string) $search, (string) $searchMatch)); // Paper ID intentionally omitted
			while (true) {
				$reviewers = $trackDirectorSubmissionDao->getReviewersForPaper($schedConf->getId(), $paperId, $submission->getCurrentRound(), $searchType, $search, $searchMatch, $rangeInfo, $sort, $sortDirection);
				if ($reviewers->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $reviewers->getLastPageRangeInfo();
				unset($reviewers);
			}

			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');

			$templateMgr =& TemplateManager::getManager($request);

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

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
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign('sort', $sort);
			$templateMgr->assign('sortDirection', $sortDirection);
			$templateMgr->display('trackDirector/selectReviewer.tpl');
		}
	}

	/**
	 * Create a new user as a reviewer.
	 */
	function createReviewer($args, &$request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;


		import('classes.trackDirector.form.CreateReviewerForm');
		$createReviewerForm = new CreateReviewerForm($paperId);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
		$this->setupTemplate($request, true, $paperId);

		if (isset($args[1]) && $args[1] === 'create') {
			$createReviewerForm->readInputData();
			if ($createReviewerForm->validate()) {
				// Create a user and enroll them as a reviewer.
				$newUserId = $createReviewerForm->execute();
				$request->redirect(null, null, null, 'selectReviewer', array($paperId, $newUserId));
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
	function suggestUsername($args, $request) {
		$this->validate($request);
		$suggestion = Validation::suggestUsername(
			$request->getUserVar('firstName'),
			$request->getUserVar('lastName')
		);
		echo $suggestion;
	}

	/**
	 * Search for users to enroll as reviewers.
	 */
	function enrollSearch($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER); // manager.people.enrollment, manager.people.enroll
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;


		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$user =& $request->getUser();

		$templateMgr =& TemplateManager::getManager($request);
		$this->setupTemplate($request, true);

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = $this->getRangeInfo($request, 'users', array((string) $searchType, (string) $searchMatch, (string) $search)); // Paper ID intentionally omitted
		$userDao = DAORegistry::getDAO('UserDAO');
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
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('fieldOptions', array(
			USER_FIELD_INTERESTS => 'user.interests',
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));

		$templateMgr->assign('helpTopicId', 'conference.roles.index');
		$templateMgr->display('trackDirector/searchUsers.tpl');
	}

	function enroll($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath('reviewer');

		$users = $request->getUserVar('users');
		if (!is_array($users) && $request->getUserVar('userId') != null) $users = array($request->getUserVar('userId'));

		// Enroll reviewer
		for ($i=0; $i<count($users); $i++) {
			if (!$roleDao->userHasRole($schedConf->getConferenceId(), $schedConf->getId(), $users[$i], $roleId)) {
				$role = new Role();
				$role->setConferenceId($schedConf->getConferenceId());
				$role->setSchedConfId($schedConf->getId());
				$role->setUserId($users[$i]);
				$role->setRoleId($roleId);

				$roleDao->insertRole($role);
			}
		}
		$request->redirect(null, null, null, 'selectReviewer', $paperId);
	}

	function notifyReviewer($args, $request) {
		$paperId = $request->getUserVar('paperId');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$reviewId = $request->getUserVar('reviewId');

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate($request, true, $paperId, 'review');

		if (TrackDirectorAction::notifyReviewer($submission, $reviewId, $send)) {
			$request->redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function clearReview($args, $request) {
		$paperId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		TrackDirectorAction::clearReview($submission, $reviewId);

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	function cancelReview($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate($request, true, $paperId, 'review');

		if (TrackDirectorAction::cancelReview($submission, $reviewId, $send)) {
			$request->redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function remindReviewer($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$this->setupTemplate($request, true, $paperId, 'review');

		if (TrackDirectorAction::remindReviewer($submission, $reviewId, $request->getUserVar('send'))) {
			$request->redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	/*
	 * Reassign a reviewer to the current round of review
	 * @param $args array
	 * @param $request object
	 */
	function reassignReviewer($args, $request) {
			$paperId = isset($args[0]) ? (int) $args[0] : 0;
			$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
			$userId = isset($args[1]) ? (int) $args[1] : 0;

			$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');
			$submission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paperId);
			$round = $submission->getCurrentRound();

			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment =& $reviewAssignmentDao->getReviewAssignment($paperId, $userId, $submission->getCurrentRound()); /* @var $reviewAssignment ReviewAssignment */
			if($reviewAssignment && !$reviewAssignment->getDateCompleted() && $reviewAssignment->getDeclined()) {
					$reviewAssignment->setDeclined(false);
					$reviewAssignment->setDateAssigned(Core::getCurrentDate());
					$reviewAssignment->setDateNotified(null);
					$reviewAssignment->setDateConfirmed(null);
					$reviewAssignment->setRound($submission->getCurrentRound());

					$reviewAssignmentDao->updateObject($reviewAssignment);
			}
			$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	function thankReviewer($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate($request, true, $paperId, 'review');

		if (TrackDirectorAction::thankReviewer($submission, $reviewId, $send)) {
			$request->redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	function rateReviewer($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$reviewId = (int) $request->getUserVar('reviewId');
		$quality = (int) $request->getUserVar('quality');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$this->setupTemplate($request, true, $paperId, 'review');
		TrackDirectorAction::rateReviewer($paperId, $reviewId, $quality);
		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	function confirmReviewForReviewer($args, $request) {
		$paperId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);
		$accept = $request->getUserVar('accept')?true:false;
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		TrackDirectorAction::confirmReviewForReviewer($reviewId, $accept);
		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	function uploadReviewForReviewer($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$reviewId = (int) $request->getUserVar('reviewId');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		TrackDirectorAction::uploadReviewForReviewer($reviewId);
		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	function makeReviewerFileViewable($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$reviewId = (int) $request->getUserVar('reviewId');
		$fileId = (int) $request->getUserVar('fileId');
		$revision = (int) $request->getUserVar('revision');
		$viewable = (int) $request->getUserVar('viewable');

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		TrackDirectorAction::makeReviewerFileViewable($paperId, $reviewId, $fileId, $revision, $viewable);

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	function setDueDate($args, $request) {
		$paperId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		$dueDate = $request->getUserVar('dueDate');
		$numWeeks = $request->getUserVar('numWeeks');

		if ($dueDate != null || $numWeeks != null) {
			TrackDirectorAction::setDueDate($paperId, $reviewId, $dueDate, $numWeeks);
			$request->redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			$this->setupTemplate($request, true, $paperId, 'review');

			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment = $reviewAssignmentDao->getById($reviewId);

			$settings = $schedConf->getSettings();

			$templateMgr =& TemplateManager::getManager($request);

			if ($reviewAssignment->getDateDue() != null) {
				$templateMgr->assign('dueDate', $reviewAssignment->getDateDue());
			}

			if ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_ABSOLUTE) {
				// Get number of days from now until review deadline date
				$reviewDeadlineDate = $schedConf->getSetting('numWeeksPerReviewAbsolute');
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

	function enterReviewerRecommendation($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$reviewId = (int) $request->getUserVar('reviewId');
		$recommendation = (int) $request->getUserVar('recommendation');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		if ($recommendation != null) {
			TrackDirectorAction::setReviewerRecommendation($paperId, $reviewId, $recommendation, SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
			$request->redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			$this->setupTemplate($request, true, $paperId, 'review');

			$templateMgr =& TemplateManager::getManager($request);

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
	function userProfile($args, $request) {
		$userId = array_shift($args);

		$this->validate($request);
		$this->setupTemplate($request, true);

		// For manager.people at top of user profile
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);


		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('currentUrl', $request->url(null, null, null, $request->getRequestedPage()));

		$userDao = DAORegistry::getDAO('UserDAO');
		if (is_numeric($userId)) {
			$userId = (int) $userId;
			$user = $userDao->getById($userId);
		} else {
			$user = $userDao->getByUsername($userId);
		}

		if ($user == null) {
			// Non-existent user requested
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.invalidUser');
			$templateMgr->display('common/error.tpl');
		} else {
			$site =& $request->getSite();

			$countryDao = DAORegistry::getDAO('CountryDAO');
			$country = null;
			if ($user->getCountry() != '') {
				$country = $countryDao->getCountry($user->getCountry());
			}
			$templateMgr->assign('country', $country);

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign('localeNames', AppLocale::getAllLocales());
			$templateMgr->assign('helpTopicId', 'conference.roles.index');
			$templateMgr->display('trackDirector/userProfile.tpl');
		}
	}

	function viewMetadata($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $paperId, 'summary');

		TrackDirectorAction::viewMetadata($submission, ROLE_ID_TRACK_DIRECTOR);
	}

	function saveMetadata($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $paperId, 'summary');

		if (TrackDirectorAction::saveMetadata($request, $submission)) {
			$request->redirect(null, null, null, 'submission', $paperId);
		}
	}

	//
	// Review Form
	//

	/**
	 * Preview a review form.
	 * @param $args array ($reviewId, $reviewFormId)
	 */
	function previewReviewForm($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request, true);

		$reviewId = (int) array_shift($args);
		$reviewFormId = (int) array_shift($args);

		$conference =& $request->getConference();
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_CONFERENCE, $conference->getId());
		$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		$templateMgr =& TemplateManager::getManager($request);
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
	function clearReviewForm($args, $request) {
		$paperId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		TrackDirectorAction::clearReviewForm($submission, $reviewId);

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Select a review form
	 * @param $args array ($paperId, $reviewId, $reviewFormId)
	 */
	function selectReviewForm($args, $request) {
		$paperId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);
		$reviewFormId = (int) array_shift($args);

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		if ($reviewFormId != null) {
			TrackDirectorAction::addReviewForm($submission, $reviewId, $reviewFormId);
			$request->redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			$conference =& $request->getConference();
			$rangeInfo = $this->getRangeInfo($request, 'reviewForms');
			$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
			$reviewForms =& $reviewFormDao->getActiveByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getId(), $rangeInfo);
			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

			$this->setupTemplate($request, true, $paperId, 'review');
			$templateMgr =& TemplateManager::getManager($request);

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
	function viewReviewFormResponse($args, $request) {
		$paperId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $paperId, 'summary');

		TrackDirectorAction::viewReviewFormResponse($submission, $reviewId);
	}

	//
	// Director Review
	//

	function directorReview($args, $request) {
		import('classes.paper.Paper');
		$round = (int) array_shift($args);
		if (!$round) $round = REVIEW_ROUND_ABSTRACT;
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		$redirectArgs = array($paperId, $round);

		// If the Upload button was pressed.
		if ($request->getUserVar('submit')) {
			TrackDirectorAction::uploadDirectorVersion($submission);
		} elseif ($request->getUserVar('setEditingFile')) {
			// If the Send To Editing button was pressed
			$file = explode(',', $request->getUserVar('directorDecisionFile'));
			$submission->stampDateToPresentations();
			$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($submission);

			if (isset($file[0]) && isset($file[1])) {
				TrackDirectorAction::setEditingFile($submission, $file[0], $file[1], $request->getUserVar('createGalley'));
			}
		}

		$request->redirect(null, null, null, 'submissionReview', $redirectArgs);
	}

	function uploadReviewVersion($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;

		TrackDirectorAction::uploadReviewVersion($submission);

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Add a supplementary file.
	 * @param $args array ($paperId)
	 */
	function addSuppFile($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $paperId, 'summary');

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
	function editSuppFile($args, $request) {
		$paperId = (int) array_shift($request);
		$suppFileId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;
		$this->setupTemplate($request, true, $paperId, 'summary');

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
	function setSuppFileVisibility($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		$suppFileId = $request->getUserVar('fileId');
		$suppFileDao = DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $paperId);

		if (isset($suppFile) && $suppFile != null) {
			$suppFile->setShowReviewers($request->getUserVar('show')==1?1:0);
			$suppFileDao->updateSuppFile($suppFile);
		}
		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Save a supplementary file.
	 * @param $args array ($suppFileId)
	 */
	function saveSuppFile($args, $request) {
		$paperId = $request->getUserVar('paperId');
		$suppFileId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$this->setupTemplate($request, true, $paperId, 'summary');
		$submission =& $this->submission;

		import('classes.submission.form.SuppFileForm');

		$submitForm = new SuppFileForm($submission, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$paperDao = DAORegistry::getDAO('PaperDAO');
			$paper =& $paperDao->getPaper($paperId);
			$conference =& $request->getConference();
			$notificationUsers = $paper->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_SUPP_FILE_MODIFIED,
					$conference->getId(), ASSOC_TYPE_PAPER, $paper->getId()
				);
			}

			$request->redirect(null, null, null, 'submissionReview', $paperId);
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Delete a director version file.
	 * @param $args array ($paperId, $fileId)
	 */
	function deletePaperFile($args, $request) {
		$paperId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revisionId = (int) array_shift($args);

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_REVIEW);
		$submission =& $this->submission;
		TrackDirectorAction::deletePaperFile($submission, $fileId, $revisionId);

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Delete a supplementary file.
	 * @param $args array ($paperId, $suppFileId)
	 */
	function deleteSuppFile($args, $request) {
		$paperId = (int) array_shift($args);
		$suppFileId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		TrackDirectorAction::deleteSuppFile($submission, $suppFileId);

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	function archiveSubmission($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		TrackDirectorAction::archiveSubmission($submission);

		$request->redirect(null, null, null, 'submission', $paperId);
	}

	function restoreToQueue($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		TrackDirectorAction::restoreToQueue($submission);

		$request->redirect(null, null, null, 'submission', $paperId);
	}

	function unsuitableSubmission($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		$send = $request->getUserVar('send')?true:false;
		$this->setupTemplate($request, true, $paperId, 'summary');

		if (TrackDirectorAction::unsuitableSubmission($submission, $send)) {
			$request->redirect(null, null, null, 'submission', $paperId);
		}
	}

	/**
	 * Set RT comments status for paper.
	 * @param $args array ($paperId)
	 */
	function updateCommentsStatus($args, $request) {
		$paperId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;
		TrackDirectorAction::updateCommentsStatus($submission, $request->getUserVar('commentsStatus'));
		$request->redirect(null, null, null, 'submission', $paperId);
	}


	//
	// Layout Editing
	//

	/**
	 * Upload a layout file (either layout version, galley, or supp. file).
	 */
	function uploadLayoutFile($args, $request) {
		$layoutFileType = $request->getUserVar('layoutFileType');
		$round = (int) $request->getUserVar('round');

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		if ($fileManager->uploadError('layoutFile')) {
			$templateMgr =& TemplateManager::getManager($request);
			$this->setupTemplate($request, true);
			$templateMgr->assign('pageTitle', 'submission.review');
			$templateMgr->assign('message', 'common.uploadFailed');
			$templateMgr->assign('backLink', $request->url(null, null, null, 'submissionReview', array($request->getUserVar('paperId'))));
			$templateMgr->assign('backLinkLabel', 'submission.review');
			return $templateMgr->display('common/message.tpl');
		}

		if ($layoutFileType == 'galley') {
			$this->_uploadGalley($request, 'layoutFile', $round);

		} else if ($layoutFileType == 'supp') {
			$this->_uploadSuppFile($request, 'layoutFile', $round);

		} else {
			$request->redirect(null, null, null, 'submission', $request->getUserVar('paperId'));
		}
	}

	/**
	 * Delete a paper image.
	 * @param $args array ($paperId, $fileId)
	 */
	function deletePaperImage($args, $request) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revisionId = (int) array_shift($args);

		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;

		TrackDirectorAction::deletePaperImage($submission, $fileId, $revisionId);

		$request->redirect(null, null, 'editGalley', array($paperId, $galleyId));
	}

	/**
	 * Create a new galley with the uploaded file.
	 * @param $fileName string
	 * @param $round int
	 */
	function _uploadGalley($request, $fileName = null, $round = null) {
		$paperId = $request->getUserVar('paperId');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;


		import('classes.submission.form.PaperGalleyForm');

		$galleyForm = new PaperGalleyForm($paperId);
		$galleyId = $galleyForm->execute($fileName);

		$request->redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $round));
	}

	/**
	 * Edit a galley.
	 * @param $args array ($paperId, $galleyId)
	 */
	function editGalley($args, $request) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$round = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;

		$this->setupTemplate($request, true, $paperId, 'review');

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
	function saveGalley($args, $request) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$round = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$this->setupTemplate($request, true, $paperId, 'editing');
		$submission =& $this->submission;

		import('classes.submission.form.PaperGalleyForm');

		$submitForm = new PaperGalleyForm($paperId, $galleyId, $round);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();

			// Send a notification to associated users
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$paperDao = DAORegistry::getDAO('PaperDAO');
			$paper =& $paperDao->getPaper($paperId);
			$conference =& $request->getConference();
			$notificationUsers = $paper->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_GALLEY_MODIFIED,
					$conference->getId(), ASSOC_TYPE_PAPER, $paper->getId()
				);
			}

			if ($request->getUserVar('uploadImage')) {
				$submitForm->uploadImage();
				$request->redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $round));
			} else if(($deleteImage = $request->getUserVar('deleteImage')) && count($deleteImage) == 1) {
				list($imageId) = array_keys($deleteImage);
				$submitForm->deleteImage($imageId);
				$request->redirect(null, null, null, 'editGalley', array($paperId, $galleyId, $round));
			}
			$request->redirect(null, null, null, 'submissionReview', array($paperId, $round));
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Change the sequence order of a galley.
	 */
	function orderGalley($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;


		TrackDirectorAction::orderGalley($submission, $request->getUserVar('galleyId'), $request->getUserVar('d'));

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Delete a galley file.
	 * @param $args array ($paperId, $galleyId)
	 */
	function deleteGalley($args, $request) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);
		$submission =& $this->submission;

		TrackDirectorAction::deleteGalley($submission, $galleyId);

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}

	/**
	 * Proof / "preview" a galley.
	 * @param $args array ($paperId, $galleyId)
	 */
	function proofGalley($args, $request) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('submission/layout/proofGalley.tpl');
	}

	/**
	 * Proof galley (shows frame header).
	 * @param $args array ($paperId, $galleyId)
	 */
	function proofGalleyTop($args, $request) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('backHandler', 'submissionReview');
		$templateMgr->display('submission/layout/proofGalleyTop.tpl');
	}

	/**
	 * Proof galley (outputs file contents).
	 * @param $args array ($paperId, $galleyId)
	 */
	function proofGalleyFile($args, $request) {
		$paperId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);
		$this->validate($request, $paperId, TRACK_DIRECTOR_ACCESS_EDIT);

		$galleyDao = DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $paperId);

		import('classes.file.PaperFileManager'); // FIXME

		if (isset($galley)) {
			if ($galley->isHTMLGalley()) {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->assign_by_ref('galley', $galley);
				if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
					$templateMgr->addStyleSheet($request->url(null, null, 'paper', 'viewFile', array(
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
	function _uploadSuppFile($request, $fileName = null, $round = null) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$submission =& $this->submission;

		import('classes.submission.form.SuppFileForm');

		$suppFileForm = new SuppFileForm($submission);
		$suppFileForm->setData('title', array($submission->getLocale() => __('common.untitled')));
		$suppFileId = $suppFileForm->execute($fileName);

		$request->redirect(null, null, null, 'editSuppFile', array($paperId, $suppFileId));
	}

	/**
	 * Change the sequence order of a supplementary file.
	 */
	function orderSuppFile($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		TrackDirectorAction::orderSuppFile($submission, $request->getUserVar('suppFileId'), $request->getUserVar('d'));

		$request->redirect(null, null, null, 'submissionReview', $paperId);
	}


	//
	// Submission History (FIXME Move to separate file?)
	//

	/**
	 * View submission event log.
	 */
	function submissionEventLog($args, $request) {
		$paperId = (int) array_shift($args);
		$logId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		$this->setupTemplate($request, true, $paperId, 'history');

		$templateMgr =& TemplateManager::getManager($request);

		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao = DAORegistry::getDAO('PaperEventLogDAO');
			$logEntry =& $logDao->getLogEntry($logId, $paperId);
		}

		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('trackDirector/submissionEventLogEntry.tpl');

		} else {
			$rangeInfo = $this->getRangeInfo($request, 'eventLogEntries', array($paperId));

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
	 * Clear submission event log entries.
	 */
	function clearSubmissionEventLog($args, $request) {
		$paperId = (int) array_shift($args);
		$logId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		$logDao = DAORegistry::getDAO('PaperEventLogDAO');

		if ($logId) {
			$logDao->deleteLogEntry($logId, $paperId);
		} else {
			$logDao->deletePaperLogEntries($paperId);
		}

		$request->redirect(null, null, null, 'submissionEventLog', $paperId);
	}

	/**
	 * View submission email log.
	 */
	function submissionEmailLog($args, $request) {
		$paperId = (int) array_shift($args);
		$logId = (int) array_shift($args);
		$this->validate($request, $paperId);
		$submission =& $this->submission;

		$this->setupTemplate($request, true, $paperId, 'history');

		$templateMgr =& TemplateManager::getManager($request);

		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('submission', $submission);

		if ($logId) {
			$logDao = DAORegistry::getDAO('PaperEmailLogDAO');
			$logEntry =& $logDao->getLogEntry($logId, $paperId);
		}

		if (isset($logEntry)) {
			$templateMgr->assign_by_ref('logEntry', $logEntry);
			$templateMgr->display('trackDirector/submissionEmailLogEntry.tpl');

		} else {
			$rangeInfo = $this->getRangeInfo($request, 'emailLogEntries', array($paperId));

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
	 * Clear submission email log entries.
	 */
	function clearSubmissionEmailLog($args, $request) {
		$paperId = (int) array_shift($args);
		$logId = (int) array_shift($args);
		$this->validate($request, $paperId);

		$logDao = DAORegistry::getDAO('PaperEmailLogDAO');

		if ($logId) {
			$logDao->deleteLogEntry($logId, $paperId);
		} else {
			$logDao->deletePaperLogEntries($paperId);
		}

		$request->redirect(null, null, null, 'submissionEmailLog', $paperId);
	}

	// Submission Notes Functions

	/**
	 * Creates a submission note.
	 * Redirects to submission notes list
	 */
	function addSubmissionNote($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);

		TrackDirectorAction::addSubmissionNote($paperId);
		$request->redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Removes a submission note.
	 * Redirects to submission notes list
	 */
	function removeSubmissionNote($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);

		TrackDirectorAction::removeSubmissionNote($paperId);
		$request->redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Updates a submission note.
	 * Redirects to submission notes list
	 */
	function updateSubmissionNote($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);

		TrackDirectorAction::updateSubmissionNote($paperId);
		$request->redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * Clear all submission notes.
	 * Redirects to submission notes list
	 */
	function clearAllSubmissionNotes($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId);

		TrackDirectorAction::clearAllSubmissionNotes($paperId);
		$request->redirect(null, null, null, 'submissionNotes', $paperId);
	}

	/**
	 * View submission notes.
	 */
	function submissionNotes($args, $request) {
		$paperId = (int) array_shift($args);
		$noteViewType = array_shift($args);
		$noteId = (int) array_shift($args);

		$this->validate($request, $paperId);
		$this->setupTemplate($request, true, $paperId, 'history');
		$submission =& $this->submission;

		$noteDao = DAORegistry::getDAO('NoteDAO');

		// submission note edit
		if ($noteViewType == 'edit') {
			$note = $noteDao->getById($noteId);
		}

		$templateMgr =& TemplateManager::getManager($request);

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
	function downloadFile($args, $request) {
		$paperId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);

		$this->validate($request, $paperId);
		if (!TrackDirectorAction::downloadFile($paperId, $fileId, $revision)) {
			$request->redirect(null, null, null, 'submission', $paperId);
		}
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($paperId, $fileId, [$revision])
	 */
	function viewFile($args, $request) {
		$paperId = (int) array_shift($args);
		$fileId = (int) array_shift($args);
		$revision = (int) array_shift($args);

		$this->validate($request, $paperId);
		if (!TrackDirectorAction::viewFile($paperId, $fileId, $revision)) {
			$request->redirect(null, null, null, 'submission', $paperId);
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
	function validate($request, $paperId, $access = null) {
		parent::validate($request);

		$isValid = true;

		$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$user =& $request->getUser();

		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paperId);

		if ($trackDirectorSubmission == null) {
			$isValid = false;

		} else if ($trackDirectorSubmission->getSchedConfId() != $schedConf->getId()) {
			$isValid = false;

		} else {
			$templateMgr =& TemplateManager::getManager($request);
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
			$request->redirect(null, null, null, $request->getRequestedPage());
		}

		// If necessary, note the current date and time as the "underway" date/time
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
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
