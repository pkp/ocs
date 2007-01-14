<?php

/**
 * AuthorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for conference author functions.
 *
 * $Id$
 */

import ('submission.author.AuthorAction');

class AuthorHandler extends Handler {

	/**
	 * Display conference author index page.
	 */
	function index($args) {
		list($conference, $event) = AuthorHandler::validate();
		AuthorHandler::setupTemplate();

		$user = &Request::getUser();
		$rangeInfo = &Handler::getRangeInfo('submissions');
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getUserId(), $event->getEventId(), $active, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);

		$submissionsOpenDate = $event->getSetting('proposalsOpenDate', false);
		$submissionsCloseDate = $event->getSetting('proposalsCloseDate', false);

		if(!$submissionsOpenDate || !$submissionsCloseDate || time() < $submissionsOpenDate) {
			// Too soon
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('author.submit.notAcceptingYet');
		} elseif (time() > $submissionsCloseDate) {
			// Too late
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('author.submit.submissionDeadlinePassed');
		} else {
			$acceptingSubmissions = true;
		}
				
		$templateMgr->assign('acceptingSubmissions', $acceptingSubmissions);
		if(isset($notAcceptingSubmissionsMessage))
			$templateMgr->assign('notAcceptingSubmissionsMessage', $notAcceptingSubmissionsMessage);
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.submissions');
		$templateMgr->display('author/index.tpl');
	}

	/**
	 * Validate that user has author permissions in the selected conference and
	 * event. Redirects to login page if not properly authenticated.
	 */
	function validate($reason = null) {
		parent::validate();

		$conference = &Request::getConference();
		$event = &Request::getEvent();

		if (!$conference || !$event || !Validation::isAuthor($conference->getConferenceId(), $event->getEventId())) {
			Validation::redirectLogin($reason);
		}

		return array(&$conference, &$event);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $paperId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = $subclass ? array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'author'), 'user.role.author'), array(Request::url(null, null, 'author'), 'paper.submissions'))
			: array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'author'), 'user.role.author'));

		import('submission.trackEditor.TrackEditorAction');
		$submissionCrumb = TrackEditorAction::submissionBreadcrumb($paperId, $parentPage, 'author');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'author/navsidebar.tpl');

			$event = &Request::getEvent();
			$user = &Request::getUser();
			$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
			$submissionsCount = $authorSubmissionDao->getSubmissionsCount($user->getUserId(), $event->getEventId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}
	}

	//
	// Paper Submission
	//

	function submit($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::submit($args);
	}

	function saveSubmit($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::saveSubmit($args);
	}

	function submitSuppFile($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::submitSuppFile($args);
	}

	function saveSubmitSuppFile($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::saveSubmitSuppFile($args);
	}

	function deleteSubmitSuppFile($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::deleteSubmitSuppFile($args);
	}

	function expediteSubmission($args) {
		import('pages.author.SubmitHandler');
		SubmitHandler::expediteSubmission($args);
	}

	//
	// Submission Tracking
	//

	function deletePaperFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::deletePaperFile($args);
	}

	function deleteSubmission($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::deleteSubmission($args);
	}

	function submission($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::submission($args);
	}

	function editSuppFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::editSuppFile($args);
	}

	function setSuppFileVisibility($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::setSuppFileVisibility($args);
	}

	function saveSuppFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::saveSuppFile($args);
	}

	function addSuppFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::addSuppFile($args);
	}

	function submissionReview($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::submissionReview($args);
	}

	function submissionEditing($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::submissionEditing($args);
	}

	function uploadRevisedVersion() {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::uploadRevisedVersion();
	}

	function viewMetadata($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::viewMetadata($args);
	}

	function saveMetadata() {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::saveMetadata();
	}

	//
	// Misc.
	//

	function downloadFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::downloadFile($args);
	}

	function viewFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::viewFile($args);
	}

	function download($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::download($args);
	}

	//
	// Submission Comments
	//

	function viewEditorDecisionComments($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewEditorDecisionComments($args);
	}

	function emailEditorDecisionComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::emailEditorDecisionComment();
	}

	function viewLayoutComments($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewLayoutComments($args);
	}

	function postLayoutComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postLayoutComment();
	}
	
	function editComment($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::editComment($args);
	}

	function saveComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::saveComment();
	}

	function deleteComment($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::deleteComment($args);
	}
}

?>
