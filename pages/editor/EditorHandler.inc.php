<?php

/**
 * EditorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
 *
 * Handle requests for editor functions.
 *
 * $Id$
 */

import('trackDirector.TrackDirectorHandler');

define('EDITOR_TRACK_HOME', 0);
define('EDITOR_TRACK_SUBMISSIONS', 1);

import ('submission.editor.EditorAction');

class EditorHandler extends TrackDirectorHandler {

	/**
	 * Displays the editor role selection page.
	 */

	function index($args) {
		EditorHandler::validate();
		EditorHandler::setupTemplate(EDITOR_TRACK_HOME, false);

		$templateMgr = &TemplateManager::getManager();
		$schedConf = &Request::getSchedConf();
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$submissionsCount = &$editorSubmissionDao->getEditorSubmissionsCount($schedConf->getSchedConfId());
		$templateMgr->assign('submissionsCount', $submissionsCount);
		$templateMgr->assign('helpTopicId', 'editorial.editorsRole');
		$templateMgr->display('editor/index.tpl');
	}

	/**
	 * Display editor submission queue pages.
	 */
	function submissions($args) {
		EditorHandler::validate();
		EditorHandler::setupTemplate(EDITOR_TRACK_SUBMISSIONS, true);

		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();

		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
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
				$functionName = 'getEditorSubmissionsUnassigned';
				$helpTopicId = 'editorial.editorsRole.submissions.unassigned';
				break;
			case 'submissionsInEditing':
				$functionName = 'getEditorSubmissionsInEditing';
				$helpTopicId = 'editorial.editorsRole.submissions.inEditing';
				break;
			case 'submissionsAccepted':
				$functionName = 'getEditorSubmissionsAccepted';
				$helpTopicId = 'editorial.editorsRole.submissions.accepted';
				break;
			case 'submissionsArchives':
				$functionName = 'getEditorSubmissionsArchives';
				$helpTopicId = 'editorial.editorsRole.submissions.archives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getEditorSubmissionsInReview';
				$helpTopicId = 'editorial.editorsRole.submissions.inReview';
		}

		$submissions = &$editorSubmissionDao->$functionName(
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
		$templateMgr->assign('editor', $user->getFullName());
		$templateMgr->assign('trackOptions', array(0 => Locale::Translate('editor.allTracks')) + $tracks);
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
			SUBMISSION_FIELD_EDITOR => 'user.role.editor',
			SUBMISSION_FIELD_REVIEWER => 'user.role.reviewer',
			SUBMISSION_FIELD_LAYOUTEDITOR => 'user.role.layoutEditor',
		));
		$templateMgr->assign('dateFieldOptions', Array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
			SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
		));

		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->display('editor/submissions.tpl');
	}

	function updateSubmissionArchive() {
		EditorHandler::submissionArchive();
	}

	/**
	 * Set the canEdit / canReview flags for this submission's edit assignments.
	 */
	function setEditorFlags($args) {
		EditorHandler::validate();

		$schedConf = &Request::getSchedConf();
		$paperId = (int) Request::getUserVar('paperId');

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$paper =& $paperDao->getPaper($paperId);

		if ($paper && $paper->getSchedConfId() === $schedConf->getSchedConfId()) {
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($paperId);

			while($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getIsEditor()) continue;

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
		EditorHandler::validate();

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
	 * Assigns the selected editor to the submission.
	 */
	function assignEditor($args) {
		EditorHandler::validate();

		$schedConf = &Request::getSchedConf();
		$paperId = Request::getUserVar('paperId');
		$editorId = Request::getUserVar('editorId');
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		if (isset($editorId) && $editorId != null && (
			$roleDao->roleExists($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $editorId, ROLE_ID_TRACK_EDITOR) ||
			$roleDao->roleExists($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $editorId, ROLE_ID_EDITOR) ||
			$roleDao->roleExists($schedConf->getConferenceId(), 0, $editorId, ROLE_ID_TRACK_EDITOR) ||
			$roleDao->roleExists($schedConf->getConferenceId(), 0, $editorId, ROLE_ID_EDITOR))) {
			// A valid track director has already been chosen;
			// either prompt with a modifiable email or, if this
			// has been done, send the email and store the editor
			// selection.

			EditorHandler::setupTemplate(EDITOR_TRACK_SUBMISSIONS, true, $paperId, 'summary');

			// FIXME: Prompt for due date.
			if (EditorAction::assignEditor($paperId, $editorId, Request::getUserVar('send'))) {
				Request::redirect(null, null, null, 'submission', $paperId);
			}
		} else {
			// Allow the user to choose a track director or editor.
			EditorHandler::setupTemplate(EDITOR_TRACK_SUBMISSIONS, true, $paperId, 'summary');

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

			$rangeInfo = &Handler::getRangeInfo('editors');
			$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');

			if (isset($args[0]) && $args[0] === 'editor') {
				$roleName = 'user.role.editor';
				$editors = &$editorSubmissionDao->getUsersNotAssignedToPaper($schedConf->getSchedConfId(), $paperId, RoleDAO::getRoleIdFromPath('editor'), $searchType, $search, $searchMatch, $rangeInfo);
			} else {
				$roleName = 'user.role.trackDirector';
				$editors = &$editorSubmissionDao->getUsersNotAssignedToPaper($schedConf->getSchedConfId(), $paperId, RoleDAO::getRoleIdFromPath('trackDirector'), $searchType, $search, $searchMatch, $rangeInfo);
			}

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign_by_ref('editors', $editors);
			$templateMgr->assign('roleName', $roleName);
			$templateMgr->assign('paperId', $paperId);

			$trackDao = &DAORegistry::getDAO('TrackDAO');
			$trackDirectorTracks = &$trackDao->getEditorTracks($schedConf->getSchedConfId());

			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$editorStatistics = $editAssignmentDao->getEditorStatistics($schedConf->getSchedConfId());

			$templateMgr->assign_by_ref('editorTracks', $trackDirectorTracks);
			$templateMgr->assign('editorStatistics', $editorStatistics);

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
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole.submissionSummary.submissionManagement');
			$templateMgr->display('editor/selectTrackDirector.tpl');
		}
	}

	/**
	 * Delete a submission.
	 */
	function deleteSubmission($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		EditorHandler::validate($paperId);
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
	 * Validate that user is an editor in the selected conferences.
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

		if (!Validation::isEditor($conference->getConferenceId(), $schedConf->getSchedConfId())) {
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $level int set to 0 if caller is at the same level as this handler in the hierarchy; otherwise the number of levels below this handler
	 */
	function setupTemplate($level = EDITOR_TRACK_HOME, $showSidebar = true, $paperId = 0, $parentPage = null) {
		$templateMgr = &TemplateManager::getManager();

		if ($level==EDITOR_TRACK_HOME) $pageHierarchy = array(array(Request::url(null, null, 'user'), 'navigation.user'));
		else if ($level==EDITOR_TRACK_SUBMISSIONS) $pageHierarchy = array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'editor'), 'user.role.editor'), array(Request::url(null, null, 'editor', 'submissions'), 'paper.submissions'));

		import('submission.trackDirector.TrackDirectorAction');
		$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, 'editor');
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);

		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'editor/navsidebar.tpl');

			$schedConf = &Request::getSchedConf();
			$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
			$submissionsCount = &$editorSubmissionDao->getEditorSubmissionsCount($schedConf->getSchedConfId());
			$templateMgr->assign('submissionsCount', $submissionsCount);
		}
	}
}

?>
