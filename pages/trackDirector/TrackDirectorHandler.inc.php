<?php

/**
 * TrackDirectorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.trackDirector
 *
 * Handle requests for track director functions. 
 *
 * $Id$
 */

import('submission.trackDirector.TrackDirectorAction');

class TrackDirectorHandler extends Handler {

	/**
	 * Display track director index page.
	 */
	function index($args) {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate();

		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();

		$rangeInfo = Handler::getRangeInfo('submissions');

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$tracks = &$trackDao->getTrackTitles($schedConf->getSchedConfId());

		switch($page) {
			case 'submissionsInEditing':
				$functionName = 'getTrackDirectorSubmissionsInEditing';
				$helpTopicId = 'editorial.trackDirectorsRole.submissions.inEditing';
				break;
			case 'submissionsAccepted':
				$functionName = 'getTrackDirectorSubmissionsAccepted';
				$helpTopicId = 'editorial.trackDirectorsRole.submissions.accepted';
				break;
			case 'submissionsArchives':
				$functionName = 'getTrackDirectorSubmissionsArchives';
				$helpTopicId = 'editorial.trackDirectorsRole.submissions.archives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getTrackDirectorSubmissionsInReview';
				$helpTopicId = 'editorial.trackDirectorsRole.submissions.inReview';
		}

		$submissions = &$trackDirectorSubmissionDao->$functionName(
			$user->getUserId(),
			$schedConf->getSchedConfId(),
			Request::getUserVar('track'),
			$searchField,
			$searchMatch,
			$search,
			$dateSearchField,
			$fromDate,
			$toDate,
			$rangeInfo
		);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('trackOptions', array(0 => Locale::Translate('director.allTracks')) + $tracks);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('track', Request::getUserVar('track'));
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('trackDirector', $user->getFullName());

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('reviewType', Array(
			REVIEW_PROGRESS_ABSTRACT => Locale::translate('submission.abstract'),
			REVIEW_PROGRESS_PAPER => Locale::translate('submission.paper')
		));
		
		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', Array(
			SUBMISSION_FIELD_TITLE => 'paper.title',
			SUBMISSION_FIELD_PRESENTER => 'user.role.presenter',
			SUBMISSION_FIELD_DIRECTOR => 'user.role.director'
		));
		$templateMgr->assign('dateFieldOptions', Array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted'
		));

		$templateMgr->display('trackDirector/index.tpl');
	}

	/**
	 * Validate that user is a track director in the selected conference.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		$page = Request::getRequestedPage();

		if (!isset($conference) || !isset($schedConf)) {
			Validation::redirectLogin();
		}
		
		if($page == ROLE_PATH_TRACK_DIRECTOR && !Validation::isTrackDirector($conference->getConferenceId(), $schedConf->getSchedConfId())) {
			Validation::redirectLogin();
		}
		
		if($page == ROLE_PATH_DIRECTOR && !Validation::isDirector($conference->getConferenceId(), $schedConf->getSchedConfId())) {
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $paperId = 0, $parentPage = null, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$isDirector = Validation::isDirector();

		if (Request::getRequestedPage() == 'director') {
			DirectorHandler::setupTemplate(DIRECTOR_TRACK_SUBMISSIONS, $showSidebar, $paperId, $parentPage);
			$templateMgr->assign('helpTopicId', 'editorial.directorsRole');
			
		} else {
			$templateMgr->assign('helpTopicId', 'editorial.trackDirectorsRole');

			$pageHierarchy = $subclass ? array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, $isDirector?'director':'trackDirector'), $isDirector?'user.role.director':'user.role.trackDirector'), array(Request::url(null, null, $isDirector?'director':'trackDirector'), 'paper.submissions'))
				: array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, $isDirector?'director':'trackDirector'), $isDirector?'user.role.director':'user.role.trackDirector'));

			import('submission.trackDirector.TrackDirectorAction');
			$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, 'trackDirector');
			if (isset($submissionCrumb)) {
				$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
			}
			$templateMgr->assign('pageHierarchy', $pageHierarchy);

			if ($showSidebar) {
				$templateMgr->assign('sidebarTemplate', 'trackDirector/navsidebar.tpl');
				$schedConf = &Request::getSchedConf();
				$user = &Request::getUser();

				$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
				$submissionsCount = &$trackDirectorSubmissionDao->getTrackDirectorSubmissionsCount($user->getUserId(), $schedConf->getSchedConfId());
				$templateMgr->assign('submissionsCount', $submissionsCount);
			}
		}
	}
	
	/**
	 * Display submission management instructions.
	 * @param $args (type)
	 */
	function instructions($args) {
		import('submission.common.Action');
		if (!isset($args[0]) || !Action::instructions($args[0])) {
			Request::redirect(null, null, Request::getRequestedPage());
		}
	}

	//
	// Timeline Management
	//
	
	function timeline($args) {
		import('pages.trackDirector.TimelineHandler');
		TimelineHandler::timeline($args);
	}

	function updateTimeline($args) {
		import('pages.trackDirector.TimelineHandler');
		TimelineHandler::updateTimeline($args);
	}

	//
	// Submission Tracking
	//

	function enrollSearch($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::enrollSearch($args);
	}

	function createReviewer($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::createReviewer($args);
	}

	function enroll($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::enroll($args);
	}

	function submission($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submission($args);
	}

	function submissionRegrets($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionRegrets($args);
	}
	
	function submissionReview($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionReview($args);
	}
	
	function submissionEditing($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionEditing($args);
	}
	
	function submissionHistory($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionHistory($args);
	}
	
	function changeTrack() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::changeTrack();
	}
	
	function recordDecision() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::recordDecision();
	}
	
	function selectReviewer($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::selectReviewer($args);
	}
	
	function notifyReviewer($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::notifyReviewer($args);
	}
	
	function notifyAllReviewers($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::notifyAllReviewers($args);
	}
	
	function userProfile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::userProfile($args);
	}
	
	function clearReview($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::clearReview($args);
	}
	
	function cancelReview($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::cancelReview($args);
	}
	
	function remindReviewer($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::remindReviewer($args);
	}

	function thankReviewer($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::thankReviewer($args);
	}
	
	function rateReviewer() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::rateReviewer();
	}
	
	function confirmReviewForReviewer($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::confirmReviewForReviewer($args);
	}
	
	function uploadReviewForReviewer($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::uploadReviewForReviewer($args);
	}
	
	function enterReviewerRecommendation($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::enterReviewerRecommendation($args);
	}
	
	function makeReviewerFileViewable() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::makeReviewerFileViewable();
	}
	
	function setDueDate($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::setDueDate($args);
	}
	
	function viewMetadata($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::viewMetadata($args);
	}
	
	function saveMetadata() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::saveMetadata();
	}

	function directorReview() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::directorReview();
	}

	function uploadReviewVersion() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::uploadReviewVersion();
	}
	
	function addSuppFile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::addSuppFile($args);
	}

	function setSuppFileVisibility($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::setSuppFileVisibility($args);
	}

	function editSuppFile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::editSuppFile($args);
	}
	
	function saveSuppFile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::saveSuppFile($args);
	}

	function deleteSuppFile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::deleteSuppFile($args);
	}
	
	function deletePaperFile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::deletePaperFile($args);
	}
	
	function archiveSubmission($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::archiveSubmission($args);
	}

	function unsuitableSubmission($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::unsuitableSubmission($args);
	}

	function restoreToQueue($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::restoreToQueue($args);
	}
	
	
	//
	// Layout Editing
	//
	
	function deleteArticleImage($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::deleteArticleImage($args);
	}
	
	function uploadLayoutFile() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::uploadLayoutFile();
	}
	
	function uploadLayoutVersion() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::uploadLayoutVersion();
	}
	
	function uploadGalley() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::uploadGalley();
	}
	
	function editGalley($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::editGalley($args);
	}
	
	function saveGalley($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::saveGalley($args);
	}
	
	function orderGalley() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::orderGalley();
	}

	function deleteGalley($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::deleteGalley($args);
	}
	
	function proofGalley($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::proofGalley($args);
	}
	
	function proofGalleyTop($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::proofGalleyTop($args);
	}
	
	function proofGalleyFile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::proofGalleyFile($args);
	}	
	
	function uploadSuppFile() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::uploadSuppFile();
	}
	
	function orderSuppFile() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::orderSuppFile();
	}
	
	
	//
	// Submission History
	//

	function submissionEventLog($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionEventLog($args);
	}		

	function submissionEventLogType($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionEventLogType($args);
	}
	
	function clearSubmissionEventLog($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::clearSubmissionEventLog($args);
	}
	
	function submissionEmailLog($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionEmailLog($args);
	}
	
	function submissionEmailLogType($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionEmailLogType($args);
	}
	
	function clearSubmissionEmailLog($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::clearSubmissionEmailLog($args);
	}

	function addSubmissionNote() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::addSubmissionNote();
	}

	function removeSubmissionNote() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::removeSubmissionNote();
	}		

	function updateSubmissionNote() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::updateSubmissionNote();
	}

	function clearAllSubmissionNotes() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::clearAllSubmissionNotes();
	}

	function submissionNotes($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionNotes($args);
	}
	
	
	//
	// Misc.
	//

	function downloadFile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::downloadFile($args);
	}
	
	function viewFile($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::viewFile($args);
	}
	
	//
	// Submission Comments
	//
	
	function viewPeerReviewComments($args) {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewPeerReviewComments($args);
	}
	
	function postPeerReviewComment() {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postPeerReviewComment();
	}
	
	function viewDirectorDecisionComments($args) {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::viewDirectorDecisionComments($args);
	}
	
	function blindCcReviewsToReviewers($args) {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::blindCcReviewsToReviewers($args);
	}
	
	function postDirectorDecisionComment() {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::postDirectorDecisionComment();
	}
	
	function emailDirectorDecisionComment() {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::emailDirectorDecisionComment();
	}
	
	function editComment($args) {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::editComment($args);
	}
	
	function saveComment() {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::saveComment();
	}
	
	function deleteComment($args) {
		import('pages.trackDirector.SubmissionCommentsHandler');
		SubmissionCommentsHandler::deleteComment($args);
	}
}

?>
