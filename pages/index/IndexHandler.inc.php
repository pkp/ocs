<?php

/**
 * @file IndexHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.index
 * @class IndexHandler
 *
 * Handle site index requests.
 *
 * $Id$
 */

class IndexHandler extends Handler {

	/**
	 * If a scheduled conference in a conference is specified, display it.
	 * If no scheduled conference is specified, display a list of scheduled conferences.
	 * If no conference is specified, display list of conferences.
	 */
	function index($args) {
		list($conference, $schedConf) = parent::validate(false, false);

		if ($schedConf && $conference) {

			// A scheduled conference was specified; display it.
			import('pages.schedConf.SchedConfHandler');
			SchedConfHandler::index($args);

		} elseif ($conference) {
			$redirect = $conference->getSetting('schedConfRedirect');
			if (!empty($redirect)) {
				$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
				$redirectSchedConf =& $schedConfDao->getSchedConf($redirect, $conference->getConferenceId());
				if ($redirectSchedConf) Request::redirect($conference->getPath(), $redirectSchedConf->getPath());
			}

			// A scheduled conference was specified; display it.
			import('pages.conference.ConferenceHandler');
			ConferenceHandler::index($args);

		} else {

			// Otherwise, display a list of conferences to choose from.
			$templateMgr = &TemplateManager::getManager();

			$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
			$templateMgr->assign('helpTopicId', 'user.home');

			// If the site specifies that we should redirect to a specific conference
			// by default, do it.

			$siteDao = &DAORegistry::getDAO('SiteDAO');
			$site = &$siteDao->getSite();
			$conference = $conferenceDao->getConference($site->getConferenceRedirect());

			if ($site->getConferenceRedirect() && $conference) {
				Request::redirect($conference->getPath());
			}

			// Otherwise, show a list of hosted conferences.

			$templateMgr->assign('intro', $site->getSiteIntro());
			$conferences = &$conferenceDao->getEnabledConferences();
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
			$templateMgr->display('index/site.tpl');
		}
	}
}

?>
