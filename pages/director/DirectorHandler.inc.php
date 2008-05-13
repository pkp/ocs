<?php

/**
 * @file DirectorHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 * @class DirectorHandler
 *
 * Handle requests for director functions.
 *
 * $Id$
 */

import('trackDirector.TrackDirectorHandler');

define('DIRECTOR_TRACK_HOME', 0);
define('DIRECTOR_TRACK_SUBMISSIONS', 1);

import ('submission.director.DirectorAction');

class DirectorHandler extends TrackDirectorHandler {

	/**
	 * Displays the director role selection page.
	 */

	function index($args) {
		DirectorHandler::validate();
		DirectorHandler::setupTemplate(DIRECTOR_TRACK_HOME);

		$templateMgr = &TemplateManager::getManager();
		$schedConf = &Request::getSchedConf();
		$directorSubmissionDao = &DAORegistry::getDAO('DirectorSubmissionDAO');
		$submissionsCount = &$directorSubmissionDao->getDirectorSubmissionsCount($schedConf->getSchedConfId());
		$templateMgr->assign('submissionsCount', $submissionsCount);
		$templateMgr->assign('helpTopicId', 'editorial.directorsRole');
		$templateMgr->display('director/index.tpl');
	}

	/**
	 * Display director submission queue pages.
	 */
	function submissions($args) {
		DirectorHandler::validate();
		DirectorHandler::setupTemplate(DIRECTOR_TRACK_SUBMISSIONS);

		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();

		$directorSubmissionDao = &DAORegistry::getDAO('DirectorSubmissionDAO');
		$trackDao = &DAORegistry::getDAO('TrackDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$tracks = &$trackDao->getTrackTitles($schedConf->getSchedConfId());

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		switch($page) {
			case 'submissionsUnassigned':
				$functionName = 'getDirectorSubmissionsUnassigned';
				$helpTopicId = 'editorial.directorsRole.submissions.unassigned';
				break;
			case 'submissionsAccepted':
				$functionName = 'getDirectorSubmissionsAccepted';
				$helpTopicId = 'editorial.directorsRole.submissions.accepted';
				break;
			case 'submissionsArchives':
				$functionName = 'getDirectorSubmissionsArchives';
				$helpTopicId = 'editorial.directorsRole.submissions.archives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getDirectorSubmissionsInReview';
				$helpTopicId = 'editorial.directorsRole.submissions.inReview';
		}

		$rangeInfo =& Handler::getRangeInfo('submissions', array($functionName, (string) $searchField, (string) $searchMatch, (string) $search));
		while (true) {
			$submissions =& $directorSubmissionDao->$functionName(
				$schedConf->getSchedConfId(),
				Request::getUserVar('track'),
				$searchField,
				$searchMatch,
				$search,
				null,
				null,
				null,
				$rangeInfo
			);
			if ($submissions->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $submissions->getLastPageRangeInfo();
			unset($submissions);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('director', $user->getFullName());
		$templateMgr->assign('trackOptions', array(0 => Locale::Translate('director.allTracks')) + $tracks);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('track', Request::getUserVar('track'));
		$templateMgr->assign('yearOffsetFuture', SCHED_CONF_DATE_YEAR_OFFSET_FUTURE);
		$templateMgr->assign('durationOptions', TrackDirectorHandler::getDurationOptions());

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('reviewType', Array(
			REVIEW_STAGE_ABSTRACT => Locale::translate('submission.abstract'),
			REVIEW_STAGE_PRESENTATION => Locale::translate('submission.paper')
		));

		$templateMgr->assign('fieldOptions', Array(
			SUBMISSION_FIELD_TITLE => 'paper.title',
			SUBMISSION_FIELD_PRESENTER => 'user.role.presenter',
			SUBMISSION_FIELD_DIRECTOR => 'user.role.director',
			SUBMISSION_FIELD_REVIEWER => 'user.role.reviewer'
		));

		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->display('director/submissions.tpl');
	}

	function updateSubmissionArchive() {
		DirectorHandler::submissionArchive();
	}

	/**
	 * Delete the specified edit assignment.
	 */
	function deleteEditAssignment($args) {
		DirectorHandler::validate();

		$schedConf = &Request::getSchedConf();
		$editId = (int) (isset($args[0])?$args[0]:0);

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignment =& $editAssignmentDao->getEditAssignment($editId);

		if ($editAssignment) {
			$paperDao =& DAORegistry::getDAO('PaperDAO');
			$paper =& $paperDao->getPaper($editAssignment->getPaperId());

			if ($paper && $paper->getSchedConfId() === $schedConf->getSchedConfId()) {
				$editAssignmentDao->deleteEditAssignmentById($editAssignment->getEditId());
				Request::redirect(null, null, null, 'submission', $paper->getPaperId());
			}
		}

		Request::redirect(null, null, null, 'submissions');
	}

	/**
	 * Assigns the selected director to the submission.
	 */
	function assignDirector($args) {
		DirectorHandler::validate();

		$schedConf = &Request::getSchedConf();
		$paperId = Request::getUserVar('paperId');
		$directorId = Request::getUserVar('directorId');
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$isDirector = $roleDao->roleExists($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $directorId, ROLE_ID_DIRECTOR) || $roleDao->roleExists($schedConf->getConferenceId(), 0, $directorId, ROLE_ID_DIRECTOR);
		$isTrackDirector = $roleDao->roleExists($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $directorId, ROLE_ID_TRACK_DIRECTOR) || $roleDao->roleExists($schedConf->getConferenceId(), 0, $directorId, ROLE_ID_TRACK_DIRECTOR);

		if (isset($directorId) && $directorId != null && ($isDirector || $isTrackDirector)) {
			// A valid track director has already been chosen;
			// either prompt with a modifiable email or, if this
			// has been done, send the email and store the director
			// selection.

			DirectorHandler::setupTemplate(DIRECTOR_TRACK_SUBMISSIONS, $paperId, 'summary');

			// FIXME: Prompt for due date.
			if (DirectorAction::assignDirector($paperId, $directorId, $isDirector, Request::getUserVar('send'))) {
				Request::redirect(null, null, null, 'submission', $paperId);
			}
		} else {
			// Allow the user to choose a track director or director.
			DirectorHandler::setupTemplate(DIRECTOR_TRACK_SUBMISSIONS, $paperId, 'summary');

			$searchType = null;
			$searchMatch = null;
			$search = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');

			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$forDirectors = isset($args[0]) && $args[0] === 'director';
			$rangeInfo =& Handler::getRangeInfo('directors', array($forDirectors, (string) $searchType, (string) $search, (string) $searchMatch));
			$directorSubmissionDao = &DAORegistry::getDAO('DirectorSubmissionDAO');

			if ($forDirectors) {
				$roleName = 'user.role.director';
				while (true) {
					$directors =& $directorSubmissionDao->getUsersNotAssignedToPaper($schedConf->getSchedConfId(), $paperId, RoleDAO::getRoleIdFromPath('director'), $searchType, $search, $searchMatch, $rangeInfo);
					if ($directors->isInBounds()) break;
					unset($rangeInfo);
					$rangeInfo =& $directors->getLastPageRangeInfo();
					unset($directors);
				}
			} else {
				$roleName = 'user.role.trackDirector';
				while (true) {
					$directors =& $directorSubmissionDao->getUsersNotAssignedToPaper($schedConf->getSchedConfId(), $paperId, RoleDAO::getRoleIdFromPath('trackDirector'), $searchType, $search, $searchMatch, $rangeInfo);
					if ($directors->isInBounds()) break;
					unset($rangeInfo);
					$rangeInfo =& $directors->getLastPageRangeInfo();
					unset($directors);
				}
			}

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign_by_ref('directors', $directors);
			$templateMgr->assign('roleName', $roleName);
			$templateMgr->assign('paperId', $paperId);

			$trackDao = &DAORegistry::getDAO('TrackDAO');
			$trackDirectorTracks = &$trackDao->getDirectorTracks($schedConf->getSchedConfId());

			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$directorStatistics = $editAssignmentDao->getDirectorStatistics($schedConf->getSchedConfId());

			$templateMgr->assign_by_ref('directorTracks', $trackDirectorTracks);
			$templateMgr->assign('directorStatistics', $directorStatistics);

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $search);
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->assign('helpTopicId', 'editorial.directorsRole.submissionSummary.submissionManagement');
			$templateMgr->display('director/selectTrackDirector.tpl');
		}
	}

	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		DirectorHandler::validate($paperId);

		$schedConf = &Request::getSchedConf();

		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paper = &$paperDao->getPaper($paperId);

		$status = $paper->getStatus();

		if ($paper->getSchedConfId() == $schedConf->getSchedConfId() && ($status == SUBMISSION_STATUS_DECLINED || $status == SUBMISSION_STATUS_ARCHIVED)) {
			// Delete paper files
			import('file.PaperFileManager');
			$paperFileManager = &new PaperFileManager($paperId);
			$paperFileManager->deletePaperTree();

			// Delete paper database entries
			$paperDao->deletePaperById($paperId);
		}

		Request::redirect(null, null, null, 'submissions', 'submissionsArchives');
	}

	/**
	 * Change the sequence of the papers.
	 */
	function movePaper($args) {
		$paperId = Request::getUserVar('paperId');
		$schedConf =& Request::getSchedConf();
		DirectorHandler::validate($paperId);

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($paperId);

		if ($publishedPaper != null && $publishedPaper->getSchedConfId() == $schedConf->getSchedConfId()) {
			$publishedPaper->setSeq($publishedPaper->getSeq() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$publishedPaperDao->updatePublishedPaper($publishedPaper);
			$publishedPaperDao->resequencePublishedPapers($publishedPaper->getTrackId(), $schedConf->getSchedConfId());
		}

		Request::redirect(null, null, null, 'submissions', 'submissionsAccepted');
	}

	/**
	 * Allows directors to write emails to users associated with the conference.
	 */
	function notifyUsers($args) {
		DirectorHandler::validate();
		DirectorHandler::setupTemplate(DIRECTOR_TRACK_HOME);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$presenterDao =& DAORegistry::getDAO('PresenterDAO');
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');

		$conference =& Request::getConference();
		$conferenceId = $conference->getConferenceId();
		$schedConf =& Request::getSchedConf();
		$schedConfId = $schedConf->getSchedConfId();

		$user =& Request::getUser();
		$templateMgr =& TemplateManager::getManager();

		import('mail.MassMail');
		$email =& new MassMail('PUBLISH_NOTIFY');

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->addRecipient($user->getEmail(), $user->getFullName());

			switch (Request::getUserVar('whichUsers')) {
				case 'allReaders':
					$recipients =& $roleDao->getUsersByRoleId(
						ROLE_ID_READER,
						$conferenceId,
						$schedConfId
					);
					break;
				case 'allPaidRegistrants':
					$recipients =& $registrationDao->getRegisteredUsers($schedConfId);
					break;
				case 'allRegistrants':
					$recipients =& $registrationDao->getRegisteredUsers($schedConfId, false);
					break;
				case 'allPresenters':
					$recipients =& $presenterDao->getPresentersAlphabetizedBySchedConf($schedConfId);
					break;
				case 'allUsers':
					$recipients =& $roleDao->getUsersBySchedConfId($schedConfId);
					break;
				case 'interestedUsers':
				default:
					$recipients = $notificationStatusDao->getNotifiableUsersBySchedConfId($schedConfId);
					break;
			}
			while (!$recipients->eof()) {
				$recipient = &$recipients->next();
				$email->addRecipient($recipient->getEmail(), $recipient->getFullName());
				unset($recipient);
			}

			if (Request::getUserVar('includeToc')=='1') {
				$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
				$publishedPapers =& $publishedPaperDao->getPublishedPapersInTracks($schedConfId);

				$templateMgr->assign_by_ref('conference', $conference);
				$templateMgr->assign_by_ref('schedConf', $schedConf);
				$templateMgr->assign('body', $email->getBody());
				$templateMgr->assign_by_ref('publishedPapers', $publishedPapers);

				$email->setBody($templateMgr->fetch('director/notifyUsersEmail.tpl'));
			}

			$callback = array(&$email, 'send');
			$templateMgr->setProgressFunction($callback);
			unset($callback);

			$email->setFrequency(10); // 10 emails per callback
			$callback = array('TemplateManager', 'updateProgressBar');
			$email->setCallback($callback);
			unset($callback);

			$templateMgr->assign('message', 'common.inProgress');
			$templateMgr->display('common/progress.tpl');
			echo '<script type="text/javascript">window.location = "' . Request::url(null, 'director') . '";</script>';
		} else {
			if (!Request::getUserVar('continued')) {
				$email->assignParams(array(
					'editorialContactSignature' => $user->getContactSignature()
				));
			}

			$email->displayEditForm(
				Request::url(null, null, null, 'notifyUsers'),
				array(),
				'director/notifyUsers.tpl',
				array(
					'allReadersCount' => $roleDao->getSchedConfUsersCount($schedConfId, ROLE_ID_READER),
					'allPresentersCount' => $presenterDao->getPresenterCount($schedConfId),
					'allPaidRegistrantsCount' => $registrationDao->getRegisteredUserCount($schedConfId),
					'allRegistrantsCount' => $registrationDao->getRegisteredUserCount($schedConfId, false),
					'notifiableCount' => $notificationStatusDao->getNotifiableUsersCount($schedConfId),
					'allUsersCount' => $roleDao->getSchedConfUsersCount($schedConfId)
				)
			);
		}
	}

	/**
	 * Validate that user is a director in the selected conferences.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		if(!isset($schedConf) || !isset($conference)) {
			Validation::redirectLogin();
		}

		if($schedConf->getConferenceId() != $conference->getConferenceId()) {
			Validation::redirectLogin();
		}

		if (!Validation::isDirector($conference->getConferenceId(), $schedConf->getSchedConfId())) {
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $level int set to 0 if caller is at the same level as this handler in the hierarchy; otherwise the number of levels below this handler
	 */
	function setupTemplate($level = DIRECTOR_TRACK_HOME, $paperId = 0, $parentPage = null) {
		$templateMgr = &TemplateManager::getManager();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$pageHierarchy = array();

		if ($schedConf) {
			$pageHierarchy[] = array(Request::url(null, null, 'index'), $schedConf->getFullTitle(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true);
		}

		$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
		if ($level==DIRECTOR_TRACK_SUBMISSIONS) {
			$pageHierarchy[] = array(Request::url(null, null, 'director'), 'user.role.director');
			$pageHierarchy[] = array(Request::url(null, null, 'director', 'submissions'), 'paper.submissions');
		}

		import('submission.trackDirector.TrackDirectorAction');
		$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, 'director');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
