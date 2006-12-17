<?php

/**
 * ConferenceHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.index
 *
 * Handle conference index requests.
 *
 * $Id$
 */

class ConferenceHandler extends Handler {

	/**
	 * If an event in a conference is specified, display it.
	 * If no event is specified, display a list of this conference's events.
	 * If no conference is specified, display list of conferences.
	 */
	function view($args) {
		list($conference, $event) = parent::validate(true, false);

		$templateMgr = &TemplateManager::getManager();

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');

		$templateMgr->assign('helpTopicId', 'user.home');

		// Assign header and content for home page
		$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
		$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));
		$templateMgr->assign('additionalHomeContent', $conference->getSetting('additionalHomeContent'));
		$templateMgr->assign('homepageImage', $conference->getSetting('homepageImage'));
		$templateMgr->assign('conferenceIntroduction', $conference->getSetting('conferenceIntroduction'));
		$templateMgr->assign('conferenceOverview', $conference->getSetting('conferenceOverview'));
		$templateMgr->assign('conferenceTitle', $conference->getTitle());

		$eventDao = &DAORegistry::getDAO('EventDAO');
		$displayCurrentEvent = $conference->getSetting('displayCurrentEvent');
		$currentEvent = &$eventDao->getCurrentEvent($conference->getConferenceId());
			
		if ($displayCurrentEvent && $currentEvent) {
			import('pages.event.EventHandler');
			// The current event TOC/cover page should be displayed below the custom home page.
			EventHandler::setupEventTemplate($conference, $event);
		} else {
			$events = &$eventDao->getEnabledEvents($conference->getConferenceId());
			$templateMgr->assign_by_ref('events', $events);
		}

		$enableAnnouncements = $conference->getSetting('enableAnnouncements');
		if ($enableAnnouncements) {
			$enableAnnouncementsHomepage = $conference->getSetting('enableAnnouncementsHomepage');
			if ($enableAnnouncementsHomepage) {
				$numAnnouncementsHomepage = $conference->getSetting('numAnnouncementsHomepage');
				$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
				$announcements = &$announcementDao->getNumAnnouncementsNotExpiredByConferenceId($conference->getConferenceId(), 	$numAnnouncementsHomepage);
				$templateMgr->assign('announcements', $announcements);
				$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
			}
		} 
		$templateMgr->display('conference/index.tpl');
	}
}

?>
