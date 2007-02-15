<?php

/**
 * DirectorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
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
		DirectorHandler::setupTemplate(DIRECTOR_TRACK_HOME, false);

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
		DirectorHandler::setupTemplate(DIRECTOR_TRACK_SUBMISSIONS, true);

		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();

		$directorSubmissionDao = &DAORegistry::getDAO('DirectorSubmissionDAO');
		$trackDao = &DAORegistry::getDAO('TrackDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$tracks = &$trackDao->getTrackTitles($schedConf->getSchedConfId());

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$rangeInfo = Handler::getRangeInfo('submissions');

		switch($page) {
			case 'submissionsUnassigned':
				$functionName = 'getDirectorSubmissionsUnassigned';
				$helpTopicId = 'editorial.directorsRole.submissions.unassigned';
				break;
			case 'submissionsInEditing':
				$functionName = 'getDirectorSubmissionsInEditing';
				$helpTopicId = 'editorial.directorsRole.submissions.inEditing';
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

		$submissions = &$directorSubmissionDao->$functionName(
			$schedConf->getSchedConfId(),
			Request::getUserVar('track'),
			$searchField,
			$searchMatch,
			$search,
			$dateSearchField,
			$fromDate,
			$toDate,
			$rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('director', $user->getFullName());
		$templateMgr->assign('trackOptions', array(0 => Locale::Translate('director.allTracks')) + $tracks);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('track', Request::getUserVar('track'));

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
			SUBMISSION_FIELD_DIRECTOR => 'user.role.director',
			SUBMISSION_FIELD_REVIEWER => 'user.role.reviewer',
			SUBMISSION_FIELD_LAYOUTEDITOR => 'user.role.layoutEditor',
		));
		$templateMgr->assign('dateFieldOptions', Array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
			SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
		));

		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->display('director/submissions.tpl');
	}

	function updateSubmissionArchive() {
		DirectorHandler::submissionArchive();
	}

	/**
	 * Set the canEdit / canReview flags for this submission's edit assignments.
	 */
	function setDirectorFlags($args) {
		DirectorHandler::validate();

		$schedConf = &Request::getSchedConf();
		$paperId = (int) Request::getUserVar('paperId');

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$paper =& $paperDao->getPaper($paperId);

		if ($paper && $paper->getSchedConfId() === $schedConf->getSchedConfId()) {
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($paperId);

			while($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getIsDirector()) continue;

				$canReview = Request::getUserVar('canReview-' . $editAssignment->getEditId()) ? 1 : 0;
				$canEdit = Request::getUserVar('canEdit-' . $editAssignment->getEditId()) ? 1 : 0;

				$editAssignment->setCanReview($canReview);
				$editAssignment->setCanEdit($canEdit);

				$editAssignmentDao->updateEditAssignment($editAssignment);
			}
		}

		Request::redirect(null, null, null, 'submission', $paperId);
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

		if (isset($directorId) && $directorId != null && (
			$roleDao->roleExists($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $directorId, ROLE_ID_TRACK_DIRECTOR) ||
			$roleDao->roleExists($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $directorId, ROLE_ID_DIRECTOR) ||
			$roleDao->roleExists($schedConf->getConferenceId(), 0, $directorId, ROLE_ID_TRACK_DIRECTOR) ||
			$roleDao->roleExists($schedConf->getConferenceId(), 0, $directorId, ROLE_ID_DIRECTOR))) {
			// A valid track director has already been chosen;
			// either prompt with a modifiable email or, if this
			// has been done, send the email and store the director
			// selection.

			DirectorHandler::setupTemplate(DIRECTOR_TRACK_SUBMISSIONS, true, $paperId, 'summary');

			// FIXME: Prompt for due date.
			if (DirectorAction::assignDirector($paperId, $directorId, Request::getUserVar('send'))) {
				Request::redirect(null, null, null, 'submission', $paperId);
			}
		} else {
			// Allow the user to choose a track director or director.
			DirectorHandler::setupTemplate(DIRECTOR_TRACK_SUBMISSIONS, true, $paperId, 'summary');

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

			$rangeInfo = &Handler::getRangeInfo('directors');
			$directorSubmissionDao = &DAORegistry::getDAO('DirectorSubmissionDAO');

			if (isset($args[0]) && $args[0] === 'director') {
				$roleName = 'user.role.director';
				$directors = &$directorSubmissionDao->getUsersNotAssignedToPaper($schedConf->getSchedConfId(), $paperId, RoleDAO::getRoleIdFromPath('director'), $searchType, $search, $searchMatch, $rangeInfo);
			} else {
				$roleName = 'user.role.trackDirector';
				$directors = &$directorSubmissionDao->getUsersNotAssignedToPaper($schedConf->getSchedConfId(), $paperId, RoleDAO::getRoleIdFromPath('trackDirector'), $searchType, $search, $searchMatch, $rangeInfo);
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
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $searchInitial);

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
		parent::setupTemplate(true);

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
	function setupTemplate($level = DIRECTOR_TRACK_HOME, $showSidebar = true, $paperId = 0, $parentPage = null) {
		$templateMgr = &TemplateManager::getManager();

		if ($level==DIRECTOR_TRACK_HOME) $pageHierarchy = array(array(Request::url(null, null, 'user'), 'navigation.user'));
		else if ($level==DIRECTOR_TRACK_SUBMISSIONS) $pageHierarchy = array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'director'), 'user.role.director'), array(Request::url(null, null, 'director', 'submissions'), 'paper.submissions'));

		import('submission.trackDirector.TrackDirectorAction');
		$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, 'director');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'director/navsidebar.tpl');

			$schedConf = &Request::getSchedConf();
			$directorSubmissionDao = &DAORegistry::getDAO('DirectorSubmissionDAO');
			$submissionsCount = &$directorSubmissionDao->getDirectorSubmissionsCount($schedConf->getSchedConfId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}
	}
}

?>
