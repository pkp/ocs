<?php

/**
 * @file ConferenceHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
		$templateMgr->assign('displayPageHeaderTitleAltText', $conference->getLocalizedSetting('homeHeaderTitleImageAltText'));
		$templateMgr->assign('displayPageHeaderLogoAltText', $conference->getLocalizedSetting('homeHeaderLogoImageAltText'));
		$templateMgr->assign('additionalHomeContent', $conference->getLocalizedSetting('additionalHomeContent'));
		$templateMgr->assign('homepageImage', $conference->getLocalizedSetting('homepageImage'));
		$templateMgr->assign('homepageImageAltText', $conference->getLocalizedSetting('homepageImageAltText'));
		$templateMgr->assign('description', $conference->getLocalizedSetting('description'));
		$templateMgr->assign('conferenceTitle', $conference->getConferenceTitle());

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$currentSchedConfs =& $schedConfDao->getCurrentSchedConfs($conference->getId());
		if ($currentSchedConfs && $currentSchedConfs->getCount() == 1) {
			// If only one sched conf exists, redirect to it.
			$singleSchedConf =& $currentSchedConfs->next();
			Request::redirect(null, $singleSchedConf->getPath());
		}
		$templateMgr->assign_by_ref('currentSchedConfs', $currentSchedConfs);

		$enableAnnouncements = $conference->getSetting('enableAnnouncements');
		if ($enableAnnouncements) {
			$enableAnnouncementsHomepage = $conference->getSetting('enableAnnouncementsHomepage');
			if ($enableAnnouncementsHomepage) {
				$numAnnouncementsHomepage = $conference->getSetting('numAnnouncementsHomepage');
				$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
				$announcements =& $announcementDao->getNumAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getId(), $numAnnouncementsHomepage);
				$templateMgr->assign('announcements', $announcements);
				$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
			}
		} 
		$templateMgr->display('conference/index.tpl');
	}
}

?>
