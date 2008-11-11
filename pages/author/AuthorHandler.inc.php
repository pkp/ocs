<?php

/**
 * @file AuthorHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for conference author functions.
 *
 */

// $Id$


import ('submission.author.AuthorAction');
import('core.PKPHandler');

class AuthorHandler extends PKPHandler {

	/**
	 * Display conference author index page.
	 */
	function index($args) {
		list($conference, $schedConf) = AuthorHandler::validate();
		AuthorHandler::setupTemplate();

		$user = &Request::getUser();
		$rangeInfo =& PKPHandler::getRangeInfo('submissions');
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

		$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getUserId(), $schedConf->getSchedConfId(), $active, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);

		$submissionsOpenDate = $schedConf->getSetting('submissionsOpenDate');
		$submissionsCloseDate = $schedConf->getSetting('submissionsCloseDate');

		if (Validation::isDirector($schedConf->getConferenceId(), $schedConf->getSchedConfId()) || Validation::isTrackDirector($schedConf->getConferenceId(), $schedConf->getSchedConfId())) {
			// Directors or track directors may always submit
			$acceptingSubmissions = true;
		} elseif (!$submissionsOpenDate || !$submissionsCloseDate || time() < $submissionsOpenDate) {
			// Too soon
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('author.submit.notAcceptingYet');
		} elseif (time() > $submissionsCloseDate) {
			// Too late
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('author.submit.submissionDeadlinePassed', array('closedDate' => strftime(Config::getVar('general', 'date_format_short'), $submissionsCloseDate)));
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
	 * scheduled conference. Redirects to login page if not properly authenticated.
	 */
	function validate($reason = null) {
		parent::validate();

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		if (!$conference || !$schedConf || !Validation::isAuthor($conference->getConferenceId(), $schedConf->getSchedConfId())) {
			Validation::redirectLogin($reason, array('requiresAuthor' => Request::getUserVar('requiresAuthor')));
		}

		return array(&$conference, &$schedConf);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $paperId = 0, $parentPage = null) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_OCS_AUTHOR, LOCALE_COMPONENT_PKP_SUBMISSION));
		$templateMgr =& TemplateManager::getManager();

		$pageHierarchy = $subclass ? array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'author'), 'user.role.author'), array(Request::url(null, null, 'author'), 'paper.submissions'))
			: array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'author'), 'user.role.author'));

		import('submission.trackDirector.TrackDirectorAction');
		$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, 'author');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
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

	function viewSuppFile($args) {
		import('pages.author.TrackSubmissionHandler');
		TrackSubmissionHandler::viewSuppFile($args);
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

	function viewDirectorDecisionComments($args) {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewDirectorDecisionComments($args);
	}

	function emailDirectorDecisionComment() {
		import('pages.author.SubmissionCommentsHandler');
		SubmissionCommentsHandler::emailDirectorDecisionComment();
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
