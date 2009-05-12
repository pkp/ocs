<?php

/**
 * @file ConferenceHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceHandler
 * @ingroup pages_index
 *
 * @brief Handle conference index requests.
 */

//$Id$


import('handler.Handler');

class ConferenceHandler extends Handler {
	/**
	 * Constructor
	 **/
	function ConferenceHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));		
	}

	/**
	 * Display the home page for the current conference.
	 */
	function index($args) {
		$this->validate();
		$this->setupTemplate();
		
		$conference =& Request::getConference();

		$templateMgr =& TemplateManager::getManager();

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');

		$templateMgr->assign('helpTopicId', 'user.home');

		// Assign header and content for home page
		$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
		$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));
		$templateMgr->assign('additionalHomeContent', $conference->getLocalizedSetting('additionalHomeContent'));
		$templateMgr->assign('homepageImage', $conference->getLocalizedSetting('homepageImage'));
		$templateMgr->assign('description', $conference->getLocalizedSetting('description'));
		$templateMgr->assign('conferenceTitle', $conference->getConferenceTitle());

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$currentSchedConfs =& $schedConfDao->getCurrentSchedConfs($conference->getConferenceId());
//		$pastSchedConfs =& $schedConfDao->getEnabledSchedConfs($conference->getConferenceId());

		$templateMgr->assign_by_ref('currentSchedConfs', $currentSchedConfs);
//		$templateMgr->assign_by_ref('pastSchedConfs', $pastSchedConfs);

		$enableAnnouncements = $conference->getSetting('enableAnnouncements');
		if ($enableAnnouncements) {
			$enableAnnouncementsHomepage = $conference->getSetting('enableAnnouncementsHomepage');
			if ($enableAnnouncementsHomepage) {
				$numAnnouncementsHomepage = $conference->getSetting('numAnnouncementsHomepage');
				$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
				$announcements =& $announcementDao->getNumAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getConferenceId(), $numAnnouncementsHomepage);
				$templateMgr->assign('announcements', $announcements);
				$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
			}
		} 
		$templateMgr->display('conference/index.tpl');
	}
}

?>
