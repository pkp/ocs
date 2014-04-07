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

// $Id$


// Filter track
define('FILTER_TRACK_ALL', 0);

import('submission.trackDirector.TrackDirectorAction');
import('handler.Handler');

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
	function index($args) {
		$this->validate();
		$this->setupTemplate();

		$schedConf =& Request::getSchedConf();
		$schedConfId = $schedConf->getId();
		$user =& Request::getUser();

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$tracks =& $trackDao->getTrackTitles($schedConfId);
		
		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'id';
		$sortDirection = Request::getUserVar('sortDirection');

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
			unset($submissions);
			$compare = create_function('$s1, $s2', 'return strcmp($s1->getSubmissionStatus(), $s2->getSubmissionStatus());');
			usort ($submissionsArray, $compare);
			if($sortDirection == 'DESC') {
				$submissionsArray = array_reverse($submissionsArray);
			}
			// Convert submission array back to an ItemIterator class
			import('core.ArrayItemIterator');
			$submissions =& ArrayItemIterator::fromRangeInfo($submissionsArray, $rangeInfo);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('trackOptions', $filterTrackOptions);
		$templateMgr->assign('filterTrack', $filterTrack);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('track', Request::getUserVar('track'));
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('trackDirector', $user->getFullName());
		$templateMgr->assign('yearOffsetFuture', SCHED_CONF_DATE_YEAR_OFFSET_FUTURE);
		$templateMgr->assign('durationOptions', $this->getDurationOptions());
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);

		$sessionTypesArray = array();
		$paperTypeDao = DAORegistry::getDAO('PaperTypeDAO');
		$sessionTypes = $paperTypeDao->getPaperTypes($schedConfId);
		while ($sessionType = $sessionTypes->next()) {
			$sessionTypesArray[$sessionType->getId()] = $sessionType;
		}
		$templateMgr->assign('sessionTypes', $sessionTypesArray);

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('reviewType', Array(
			REVIEW_STAGE_ABSTRACT => __('submission.abstract'),
			REVIEW_STAGE_PRESENTATION => __('submission.paper')
		));

		$templateMgr->assign('fieldOptions', Array(
			SUBMISSION_FIELD_TITLE => 'paper.title',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
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
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$page = Request::getRequestedPage();

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
	function setupTemplate($subclass = false, $paperId = 0, $parentPage = null) {
		parent::setupTemplate();
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OCS_DIRECTOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OCS_AUTHOR));
		$templateMgr =& TemplateManager::getManager();
		$isDirector = Validation::isDirector();
		$pageHierarchy = array();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$templateMgr->assign('helpTopicId', $isDirector ? 'editorial.directorsRole' : 'editorial.trackDirectorsRole');

		if ($schedConf) {
			$pageHierarchy[] = array(Request::url(null, null, 'index'), $schedConf->getFullTitle(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true);
		}

		$roleSymbolic = $isDirector ? 'director' : 'trackDirector';
		$roleKey = $isDirector ? 'user.role.director' : 'user.role.trackDirector';

		$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
		$pageHierarchy[] = array(Request::url(null, null, $roleSymbolic), $roleKey);
		if ($subclass) {
			$pageHierarchy[] = array(Request::url(null, null, $roleSymbolic), 'paper.submissions');
		}

		import('submission.trackDirector.TrackDirectorAction');
		$submissionCrumb = TrackDirectorAction::submissionBreadcrumb($paperId, $parentPage, $roleSymbolic);
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
	
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
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
}

?>
