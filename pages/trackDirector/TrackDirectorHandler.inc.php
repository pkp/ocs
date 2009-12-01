<?php

/**
 * @file TrackDirectorHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackDirectorHandler
 * @ingroup pages_trackDirector
 *
 * @brief Handle requests for track director functions. 
 *
 */

// $Id$


import('submission.trackDirector.TrackDirectorAction');

// Filter track
define('FILTER_TRACK_ALL', 0);

class TrackDirectorHandler extends Handler {

	/**
	 * Display track director index page.
	 */
	function index($args) {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate();

		$schedConf = &Request::getSchedConf();
		$schedConfId = $schedConf->getSchedConfId();
		$user = &Request::getUser();

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$tracks = &$trackDao->getTrackTitles($schedConfId);

		$filterTrackOptions = array(
			FILTER_TRACK_ALL => Locale::Translate('director.allTracks')
		) + $tracks;

		switch($page) {
			case 'submissionsAccepted':
				$functionName = 'getTrackDirectorSubmissionsAccepted';
				$helpTopicId = 'editorial.trackDirectorsRole.presentations';
				break;
			case 'submissionsArchives':
				$functionName = 'getTrackDirectorSubmissionsArchives';
				$helpTopicId = 'editorial.trackDirectorsRole.archives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getTrackDirectorSubmissionsInReview';
				$helpTopicId = 'editorial.trackDirectorsRole.review';
		}

		$filterTrack = Request::getUserVar('filterTrack');
		if ($filterTrack != '' && array_key_exists($filterTrack, $filterTrackOptions)) {
			$user->updateSetting('filterTrack', $filterTrack, 'int', $schedConfId);
		} else {
			$filterTrack = $user->getSetting('filterTrack', $schedConfId);
			if ($filterTrack == null) {
				$filterTrack = FILTER_TRACK_ALL;
				$user->updateSetting('filterTrack', $filterTrack, 'int', $schedConfId);
			}	
		}

		$rangeInfo = Handler::getRangeInfo('submissions', array($functionName, (string) $searchField, (string) $searchMatch, (string) $search));
		while (true) {
			$submissions =& $trackDirectorSubmissionDao->$functionName(
				$user->getUserId(),
				$schedConf->getSchedConfId(),
				$filterTrack,
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

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('trackOptions', $filterTrackOptions);
		$templateMgr->assign('filterTrack', $filterTrack);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('track', Request::getUserVar('track'));
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('trackDirector', $user->getFullName());
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
			SUBMISSION_FIELD_DIRECTOR => 'user.role.director'
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
	function setupTemplate($subclass = false, $paperId = 0, $parentPage = null) {
		$templateMgr = &TemplateManager::getManager();
		$isDirector = Validation::isDirector();
		$pageHierarchy = array();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if ($schedConf) {
			$pageHierarchy[] = array(Request::url(null, null, 'index'), $schedConf->getFullTitle(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true);
		}

		if (Request::getRequestedPage() == 'director') {
			DirectorHandler::setupTemplate(DIRECTOR_TRACK_SUBMISSIONS, $paperId, $parentPage);
			$templateMgr->assign('helpTopicId', 'editorial.directorsRole');

		} else {
			$templateMgr->assign('helpTopicId', 'editorial.trackDirectorsRole');
			$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
			if ($subclass) {
				$pageHierarchy[] = array(Request::url(null, null, $isDirector?'director':'trackDirector'), $isDirector?'user.role.director':'user.role.trackDirector');
				$pageHierarchy[] = array(Request::url(null, null, 'trackDirector'), 'paper.submissions');
			} else {
				$pageHierarchy[] = array(Request::url(null, null, $isDirector?'director':'trackDirector'), $isDirector?'user.role.director':'user.role.trackDirector');
			}

			import('submission.trackDirector.TrackDirectorAction');
			$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, 'trackDirector');
			if (isset($submissionCrumb)) {
				$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
			}
		
			$templateMgr->assign('pageHierarchy', $pageHierarchy);
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

	function submissionHistory($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::submissionHistory($args);
	}

	function changeTrack() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::changeTrack();
	}

	function changeTypeConst() {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::changeTypeConst();
	}

	function recordDecision($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::recordDecision($args);
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

	function directorReview($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::directorReview($args);
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

	function updateCommentsStatus($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::updateCommentsStatus($args);
	}


	//
	// Layout Editing
	//

	function deletePaperImage($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::deletePaperImage($args);
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

	function completePaper($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::completePaper($args);
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

	function getDurationOptions() {
		return array(
			60 * 10		=> '0:10',
			60 * 60 * 0.25	=> '0:15',
			60 * 20		=> '0:20',
			60 * 60 * 0.5	=> '0:30',
			60 * 60 * 1	=> '1:00',
			60 * 60 * 1.5	=> '1:30',
			60 * 60 * 2	=> '2:00',
			60 * 60 * 2.5	=> '2:30',
			60 * 60 * 3	=> '3:00',
			60 * 60 * 3.5	=> '3:30',
			60 * 60 * 4	=> '4:00',
			60 * 60 * 4	=> '5:00',
			60 * 60 * 5	=> '6:00',
			60 * 60 * 6	=> '7:00',
			60 * 60 * 7	=> '8:00',
		);
	}
	
	// Submission Review Form

	function clearReviewForm($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::clearReviewForm($args);
	}

	function selectReviewForm($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::selectReviewForm($args);
	}

	function previewReviewForm($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::previewReviewForm($args);
	}

	function viewReviewFormResponse($args) {
		import('pages.trackDirector.SubmissionEditHandler');
		SubmissionEditHandler::viewReviewFormResponse($args);
	}
}

?>
