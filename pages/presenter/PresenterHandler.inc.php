<?php

/**
 * @file PresenterHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.presenter
 * @class PresenterHandler
 *
 * Handle requests for conference presenter functions.
 *
 * $Id$
 */

import ('submission.presenter.PresenterAction');

class PresenterHandler extends Handler {

	/**
	 * Display conference presenter index page.
	 */
	function index($args) {
		list($conference, $schedConf) = PresenterHandler::validate();
		PresenterHandler::setupTemplate();

		$user = &Request::getUser();
		$rangeInfo = &Handler::getRangeInfo('submissions');
		$presenterSubmissionDao = &DAORegistry::getDAO('PresenterSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $presenterSubmissionDao->getPresenterSubmissions($user->getUserId(), $schedConf->getSchedConfId(), $active, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);

		$submissionsOpenDate = $schedConf->getSetting('submissionsOpenDate', false);
		$submissionsCloseDate = $schedConf->getSetting('submissionsCloseDate', false);

		if (Validation::isDirector($schedConf->getConferenceId(), $schedConf->getSchedConfId()) || Validation::isTrackDirector($schedConf->getConferenceId(), $schedConf->getSchedConfId())) {
			// Directors or track directors may always submit
			$acceptingSubmissions = true;
		} elseif (!$submissionsOpenDate || !$submissionsCloseDate || time() < $submissionsOpenDate) {
			// Too soon
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('presenter.submit.notAcceptingYet');
		} elseif (time() > $submissionsCloseDate) {
			// Too late
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('presenter.submit.submissionDeadlinePassed', array('closedDate' => strftime(Config::getVar('general', 'date_format_short'), $submissionsCloseDate)));
		} else {
			$acceptingSubmissions = true;
		}

		$templateMgr->assign('acceptingSubmissions', $acceptingSubmissions);
		if(isset($notAcceptingSubmissionsMessage))
			$templateMgr->assign('notAcceptingSubmissionsMessage', $notAcceptingSubmissionsMessage);
		$templateMgr->assign('helpTopicId', 'editorial.presentersRole.submissions');
		$templateMgr->display('presenter/index.tpl');
	}

	/**
	 * Validate that user has presenter permissions in the selected conference and
	 * scheduled conference. Redirects to login page if not properly authenticated.
	 */
	function validate($reason = null) {
		parent::validate();

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		if (!$conference || !$schedConf || !Validation::isPresenter($conference->getConferenceId(), $schedConf->getSchedConfId())) {
			Validation::redirectLogin($reason, array('requiresPresenter' => Request::getUserVar('requiresPresenter')));
		}

		return array(&$conference, &$schedConf);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $paperId = 0, $parentPage = null) {
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = $subclass ? array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'presenter'), 'user.role.presenter'), array(Request::url(null, null, 'presenter'), 'paper.submissions'))
			: array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'presenter'), 'user.role.presenter'));

		import('submission.trackDirector.TrackDirectorAction');
		$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, 'presenter');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	//
	// Paper Submission
	//

	function submit($args) {
		import('pages.presenter.SubmitHandler');
		SubmitHandler::submit($args);
	}

	function saveSubmit($args) {
		import('pages.presenter.SubmitHandler');
		SubmitHandler::saveSubmit($args);
	}

	function submitSuppFile($args) {
		import('pages.presenter.SubmitHandler');
		SubmitHandler::submitSuppFile($args);
	}

	function saveSubmitSuppFile($args) {
		import('pages.presenter.SubmitHandler');
		SubmitHandler::saveSubmitSuppFile($args);
	}

	function deleteSubmitSuppFile($args) {
		import('pages.presenter.SubmitHandler');
		SubmitHandler::deleteSubmitSuppFile($args);
	}

	function expediteSubmission($args) {
		import('pages.presenter.SubmitHandler');
		SubmitHandler::expediteSubmission($args);
	}

	//
	// Submission Tracking
	//

	function deletePaperFile($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::deletePaperFile($args);
	}

	function deleteSubmission($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::deleteSubmission($args);
	}

	function submission($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::submission($args);
	}

	function viewSuppFile($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::viewSuppFile($args);
	}

	function editSuppFile($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::editSuppFile($args);
	}

	function setSuppFileVisibility($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::setSuppFileVisibility($args);
	}

	function saveSuppFile($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::saveSuppFile($args);
	}

	function addSuppFile($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::addSuppFile($args);
	}

	function submissionReview($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::submissionReview($args);
	}

	function uploadRevisedVersion() {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::uploadRevisedVersion();
	}

	function viewMetadata($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::viewMetadata($args);
	}

	function saveMetadata() {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::saveMetadata();
	}

	//
	// Misc.
	//

	function downloadFile($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::downloadFile($args);
	}

	function viewFile($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::viewFile($args);
	}

	function download($args) {
		import('pages.presenter.TrackSubmissionHandler');
		TrackSubmissionHandler::download($args);
	}

	//
	// Submission Comments
	//

	function viewDirectorDecisionComments($args) {
		import('pages.presenter.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewDirectorDecisionComments($args);
	}

	function emailDirectorDecisionComment() {
		import('pages.presenter.SubmissionCommentsHandler');
		SubmissionCommentsHandler::emailDirectorDecisionComment();
	}

	function editComment($args) {
		import('pages.presenter.SubmissionCommentsHandler');
		SubmissionCommentsHandler::editComment($args);
	}

	function saveComment() {
		import('pages.presenter.SubmissionCommentsHandler');
		SubmissionCommentsHandler::saveComment();
	}

	function deleteComment($args) {
		import('pages.presenter.SubmissionCommentsHandler');
		SubmissionCommentsHandler::deleteComment($args);
	}
}

?>
