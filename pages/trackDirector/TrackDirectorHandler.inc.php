<?php

/**
 * @file TrackDirectorHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackDirectorHandler
 * @ingroup pages_trackDirector
 *
 * @brief Handle requests for track director functions. 
 *
 */



// Filter track
define('FILTER_TRACK_ALL', 0);

import('classes.submission.trackDirector.TrackDirectorAction');
import('classes.handler.Handler');

class TrackDirectorHandler extends Handler {
	/**
	 * Constructor
	 **/
	function TrackDirectorHandler() {
		parent::Handler();
	}

	/**
	 * Synonym for "index". WARNING: This is used by some of the requests
	 * shared between Director and Track Director, i.e. completePaper,
	 * which assumes the same URLs are used by both roles.
	 */
	function submissions($args, &$request) {
		$this->index($args, $request);
	}	

	/**
	 * Display track director index page.
	 */
	function index($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request);

		$schedConf =& $request->getSchedConf();
		$schedConfId = $schedConf->getId();
		$user =& $request->getUser();

		// Get the user's search conditions, if any
		$searchField = $request->getUserVar('searchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		$trackDao = DAORegistry::getDAO('TrackDAO');
		$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$tracks =& $trackDao->getTrackTitles($schedConfId);
		
		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'id';
		$sortDirection = $request->getUserVar('sortDirection');

		$filterTrackOptions = array(
			FILTER_TRACK_ALL => AppLocale::Translate('director.allTracks')
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

		$filterTrack = $request->getUserVar('filterTrack');
		if ($filterTrack != '' && array_key_exists($filterTrack, $filterTrackOptions)) {
			$user->updateSetting('filterTrack', $filterTrack, 'int', $schedConfId);
		} else {
			$filterTrack = $user->getSetting('filterTrack', $schedConfId);
			if ($filterTrack == null) {
				$filterTrack = FILTER_TRACK_ALL;
				$user->updateSetting('filterTrack', $filterTrack, 'int', $schedConfId);
			}	
		}

		$rangeInfo = $this->getRangeInfo($request, 'submissions', array($functionName, (string) $searchField, (string) $searchMatch, (string) $search));
		while (true) {
			$submissions =& $trackDirectorSubmissionDao->$functionName(
				$user->getId(),
				$schedConf->getId(),
				$filterTrack,
				$searchField,
				$searchMatch,
				$search,
				null,
				null,
				null,
				$sort=='status'?null:$rangeInfo,
				$sort,
				$sortDirection
			);
			if ($submissions->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $submissions->getLastPageRangeInfo();
			unset($submissions);
		}

		if ($sort == 'status') {
			// Sort all submissions by status, which is too complex to do in the DB
			$submissionsArray = $submissions->toArray();
			$compare = create_function('$s1, $s2', 'return strcmp($s1->getSubmissionStatus(), $s2->getSubmissionStatus());');
			usort ($submissionsArray, $compare);
			if($sortDirection == 'DESC') {
				$submissionsArray = array_reverse($submissionsArray);
			}
			// Convert submission array back to an ItemIterator class
			import('lib.pkp.classes.core.ArrayItemIterator');
			$submissions =& ArrayItemIterator::fromRangeInfo($submissionsArray, $rangeInfo);
		}

		// If only result is returned from a search, fast-forward to it
		if ($search && $submissions && $submissions->getCount() == 1) {
			$submission =& $submissions->next();
			$request->redirect(null, null, null, 'submission', array($submission->getId()));
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('trackOptions', $filterTrackOptions);
		$templateMgr->assign('filterTrack', $filterTrack);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('track', $request->getUserVar('track'));
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('trackDirector', $user->getFullName());
		$templateMgr->assign('yearOffsetFuture', SCHED_CONF_DATE_YEAR_OFFSET_FUTURE);
		$templateMgr->assign('durationOptions', $this->_getDurationOptions());
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, $request->getUserVar($param));

		$templateMgr->assign('reviewType', Array(
			REVIEW_ROUND_ABSTRACT => __('submission.abstract'),
			REVIEW_ROUND_PRESENTATION => __('submission.paper')
		));

		$templateMgr->assign('fieldOptions', Array(
			SUBMISSION_FIELD_TITLE => 'paper.title',
			SUBMISSION_FIELD_ID => 'paper.submissionId',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
			SUBMISSION_FIELD_DIRECTOR => 'user.role.director'
		));

		$templateMgr->display('trackDirector/index.tpl');
	}

	/**
	 * Validate that user is a track director in the selected conference.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate($request) {
		parent::validate();
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$page = $request->getRequestedPage();

		if (!isset($conference) || !isset($schedConf)) {
			Validation::redirectLogin();
		}

		if($page == ROLE_PATH_TRACK_DIRECTOR && !Validation::isTrackDirector($conference->getId(), $schedConf->getId())) {
			Validation::redirectLogin();
		}

		if($page == ROLE_PATH_DIRECTOR && !Validation::isDirector($conference->getId(), $schedConf->getId())) {
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($request, $subclass = false, $paperId = 0, $parentPage = null) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_AUTHOR);
		$templateMgr =& TemplateManager::getManager($request);
		$isDirector = Validation::isDirector();
		$pageHierarchy = array();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr->assign('helpTopicId', $isDirector ? 'editorial.directorsRole' : 'editorial.trackDirectorsRole');

		if ($schedConf) {
			$pageHierarchy[] = array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true);
		}

		$roleSymbolic = $isDirector ? 'director' : 'trackDirector';
		$roleKey = $isDirector ? 'user.role.director' : 'user.role.trackDirector';

		$pageHierarchy[] = array($request->url(null, null, 'user'), 'navigation.user');
		$pageHierarchy[] = array($request->url(null, null, $roleSymbolic), $roleKey);
		if ($subclass) {
			$pageHierarchy[] = array($request->url(null, null, $roleSymbolic), 'paper.submissions');
		}

		import('classes.submission.trackDirector.TrackDirectorAction');
		$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, $roleSymbolic);
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
	
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display submission management instructions.
	 * @param $args array
	 * @param $request object
	 */
	function instructions($args, $request) {
		import('classes.submission.common.Action');
		if (!isset($args[0]) || !Action::instructions($args[0])) {
			$request->redirect(null, null, $request->getRequestedPage());
		}
	}

	function _getDurationOptions() {
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
}

?>
