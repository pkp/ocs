<?php

/**
 * @file ConferenceHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceHandler
 * @ingroup pages_index
 *
 * @brief Handle conference index requests.
 */

//$Id$


import('core.PKPHandler');

class ConferenceHandler extends PKPHandler {

	/**
	 * Display the home page for the current conference.
	 */
	function index($args) {
		list($conference, $schedConf) = parent::validate(true, false);
		parent::setupTemplate();

		$templateMgr = &TemplateManager::getManager();

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');

		$templateMgr->assign('helpTopicId', 'user.home');

		// Assign header and content for home page
		$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
		$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));
		$templateMgr->assign('additionalHomeContent', $conference->getLocalizedSetting('additionalHomeContent'));
		$templateMgr->assign('homepageImage', $conference->getLocalizedSetting('homepageImage'));
		$templateMgr->assign('description', $conference->getLocalizedSetting('description'));
		$templateMgr->assign('conferenceTitle', $conference->getConferenceTitle());

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
