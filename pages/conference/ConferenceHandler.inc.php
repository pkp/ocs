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
	 * Display the home page for the current conference.
	 */
	function index($args) {
		list($conference, $schedConf) = parent::validate(true, false);
		
		$templateMgr = &TemplateManager::getManager();

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');

		$templateMgr->assign('helpTopicId', 'user.home');

		// Assign header and content for home page
		$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
		$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));
		$templateMgr->assign('additionalHomeContent', $conference->getSetting('additionalHomeContent'));
		$templateMgr->assign('homepageImage', $conference->getSetting('homepageImage'));
		$templateMgr->assign('conferenceDescription', $conference->getSetting('conferenceDescription'));
		$templateMgr->assign('conferenceOverview', $conference->getSetting('conferenceOverview'));
		$templateMgr->assign('conferenceTitle', $conference->getTitle());

		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$currentSchedConfs = &$schedConfDao->getCurrentSchedConfs($conference->getConferenceId());
//		$pastSchedConfs = &$schedConfDao->getEnabledSchedConfs($conference->getConferenceId());

		$templateMgr->assign_by_ref('currentSchedConfs', $currentSchedConfs);
//		$templateMgr->assign_by_ref('pastSchedConfs', $pastSchedConfs);

		$enableAnnouncements = $conference->getSetting('enableAnnouncements');
		if ($enableAnnouncements) {
			$enableAnnouncementsHomepage = $conference->getSetting('enableAnnouncementsHomepage');
			if ($enableAnnouncementsHomepage) {
				$numAnnouncementsHomepage = $conference->getSetting('numAnnouncementsHomepage');
				$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
				$announcements = &$announcementDao->getNumAnnouncementsNotExpiredByConferenceId($conference->getConferenceId(), 0, $numAnnouncementsHomepage);
				$templateMgr->assign('announcements', $announcements);
				$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
			}
		} 
		$templateMgr->display('conference/index.tpl');
	}
}

?>
