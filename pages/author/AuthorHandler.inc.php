<?php

/**
 * @file AuthorHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
import('handler.Handler');
import('handler.validation.HandlerValidatorConference');
import('handler.validation.HandlerValidatorSchedConf');
import('handler.validation.HandlerValidatorRoles');

class AuthorHandler extends Handler {
	/**
	 * Constructor
	 **/
	function AuthorHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, array('requiresAuthor' => Request::getUserVar('requiresAuthor')), array(ROLE_ID_AUTHOR)));
	}

	/**
	 * Display conference author index page.
	 */
	function index($args) {
		$this->validate();
		$this->setupTemplate();
		
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$user =& Request::getUser();
		$rangeInfo =& Handler::getRangeInfo('submissions');
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}
		
		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'id';
		$sortDirection = Request::getUserVar('sortDirection');

		if ($sort == 'status') {
			// FIXME Does not pass $rangeInfo else we only get partial results
			$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getId(), $schedConf->getId(), $active, null, $sort, $sortDirection);

			// Sort all submissions by status, which is too complex to do in the DB
			$submissionsArray = $submissions->toArray();
			if($sortDirection == 'DESC') {
				$submissionsArray = array_reverse($submissionsArray);
			}
			$compare = create_function('$s1, $s2', 'return strcmp($s1->getSubmissionStatus(), $s2->getSubmissionStatus());');
			usort ($submissionsArray, $compare);
			
			// Convert submission array back to an ItemIterator class
			import('core.ArrayItemIterator');
			$submissions =& ArrayItemIterator::fromRangeInfo($submissionsArray, $rangeInfo);
		} else {
			$submissions = $authorSubmissionDao->getAuthorSubmissions($user->getId(), $schedConf->getId(), $active, $rangeInfo, $sort, $sortDirection);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);

		$submissionsOpenDate = $schedConf->getSetting('submissionsOpenDate');
		$submissionsCloseDate = $schedConf->getSetting('submissionsCloseDate');

		if (Validation::isDirector($schedConf->getConferenceId(), $schedConf->getId()) || Validation::isTrackDirector($schedConf->getConferenceId(), $schedConf->getId())) {
			// Directors or track directors may always submit
			$acceptingSubmissions = true;
		} elseif (!$submissionsOpenDate || !$submissionsCloseDate || time() < $submissionsOpenDate) {
			// Too soon
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = __('author.submit.notAcceptingYet');
		} elseif (time() > $submissionsCloseDate) {
			// Too late
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = __('author.submit.submissionDeadlinePassed', array('closedDate' => strftime(Config::getVar('general', 'date_format_short'), $submissionsCloseDate)));
		} else {
			$acceptingSubmissions = true;
		}

		$templateMgr->assign('acceptingSubmissions', $acceptingSubmissions);
		if(isset($notAcceptingSubmissionsMessage))
			$templateMgr->assign('notAcceptingSubmissionsMessage', $notAcceptingSubmissionsMessage);
		$templateMgr->assign('helpTopicId', 'editorial.authorsRole.submissions');
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('author/index.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $paperId = 0, $parentPage = null) {
		parent::setupTemplate();
		AppLocale::requireComponents(array(
			LOCALE_COMPONENT_OCS_AUTHOR,
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_OCS_DIRECTOR, // FIXME?
			LOCALE_COMPONENT_OCS_MANAGER // manager.schedConfSetup.submissions.typeOfSubmission.* FIXME
		));
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
}

?>
