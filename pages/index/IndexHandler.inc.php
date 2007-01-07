<?php

/**
 * IndexHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.index
 *
 * Handle site index requests.
 *
 * $Id$
 */

class IndexHandler extends Handler {

	/**
	 * If an event in a conference is specified, display it.
	 * If no event is specified, display a list of this conference's events.
	 * If no conference is specified, display list of conferences.
	 */
	function index($args) {
		list($conference, $event) = parent::validate(false, false);

		if($event && $conference) {

			// An event was specified; display it.
			import('pages.event.EventHandler');
			EventHandler::index($args);

		} elseif($conference) {

			// An event was specified; display it.
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
			
			$templateMgr->assign('intro', $site->getIntro());
			$conferences = &$conferenceDao->getEnabledConferences();
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->display('index/site.tpl');
		}
	}
}

?>
