<?php

/**
 * @file SchedConfsHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfsHandler
 * @ingroup pages_index
 *
 * @brief Handle conference index requests.
 *
 */

// $Id$


import('handler.Handler');

class SchedConfsHandler extends Handler {
	/**
	 * Constructor
	 **/
	function SchedConfsHandler() {
		parent::Handler();
	}

	/**
	 * Display the home page for the current conference.
	 */
	function current($args) {
		$this->addCheck(new HandlerValidatorConference($this));
		$this->validate();
		$conference =& Request::getConference();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');

		$templateMgr->assign('helpTopicId', 'user.home');

		// Assign header and content for home page
		$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
		$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));
		$templateMgr->assign('displayPageHeaderTitleAltText', $conference->getLocalizedSetting('homeHeaderTitleImageAltText'));
		$templateMgr->assign('displayPageHeaderLogoAltText', $conference->getLocalizedSetting('homeHeaderLogoImageAltText'));
		$templateMgr->assign('additionalHomeContent', $conference->getLocalizedSetting('additionalHomeContent'));
		$templateMgr->assign('homepageImage', $conference->getSetting('homepageImage'));
		$templateMgr->assign('homepageImageAltText', $conference->getLocalizedSetting('homepageImageAltText'));
		$templateMgr->assign('description', $conference->getSetting('description'));
		$templateMgr->assign('conferenceTitle', $conference->getConferenceTitle());

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$currentSchedConfs =& $schedConfDao->getCurrentSchedConfs($conference->getId());

		$templateMgr->assign_by_ref('schedConfs', $currentSchedConfs);

		$templateMgr->display('conference/current.tpl');
	}

	/**
	 * Display the home page for the current conference.
	 */
	function archive($args) {
		$this->addCheck(new HandlerValidatorConference($this));
		$this->validate();
		$conference =& Request::getConference();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');

		$templateMgr->assign('helpTopicId', 'user.home');

		// Assign header and content for home page
		$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
		$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));
		$templateMgr->assign('displayPageHeaderTitleAltText', $conference->getLocalizedSetting('homeHeaderTitleImageAltText'));
		$templateMgr->assign('displayPageHeaderLogoAltText', $conference->getLocalizedSetting('homeHeaderLogoImageAltText'));
		$templateMgr->assign('additionalHomeContent', $conference->getLocalizedSetting('additionalHomeContent'));
		$templateMgr->assign('homepageImage', $conference->getSetting('homepageImage'));
		$templateMgr->assign('homepageImageAltText', $conference->getLocalizedSetting('homepageImageAltText'));
		$templateMgr->assign('description', $conference->getSetting('description'));
		$templateMgr->assign('conferenceTitle', $conference->getConferenceTitle());

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$pastSchedConfs =& $schedConfDao->getEnabledSchedConfs($conference->getId());

		$templateMgr->assign_by_ref('schedConfs', $pastSchedConfs);

		$templateMgr->display('conference/archive.tpl');
	}
}

?>
