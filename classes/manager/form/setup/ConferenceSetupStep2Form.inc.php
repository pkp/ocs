<?php

/**
 * @file ConferenceSetupStep2Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSetupStep2Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 2 of conference setup.
 */

// $Id$

import("manager.form.setup.ConferenceSetupForm");
import('schedConf.SchedConf');

class ConferenceSetupStep2Form extends ConferenceSetupForm {
	/**
	 * Constructor.
	 */
	function ConferenceSetupStep2Form() {
		parent::ConferenceSetupForm(
			2,
			array(
				'additionalHomeContent' => 'string',
				'readerInformation' => 'string',
				'authorInformation' => 'string',
				'enableAnnouncements' => 'bool',
				'enableAnnouncementsHomepage' => 'bool',
				'numAnnouncementsHomepage' => 'int',
				'announcementsIntroduction' => 'string',
				'schedConfRedirect' => 'int',
				'homepageImageAltText' => 'string'
			)
		);
		$conference =& Request::getConference();
		$this->addCheck(new FormValidatorCustom($this, 'schedConfRedirect', 'optional', 'manager.setup.additionalContent.redirect.invalidSchedConf', create_function('$schedConfRedirect,$form,$schedConfDao,$conferenceId', 'return $schedConfDao->getSchedConf($schedConfRedirect, $conferenceId);'), array(&$this, DAORegistry::getDAO('SchedConfDAO'), $conference->getId())));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('additionalHomeContent', 'readerInformation', 'authorInformation', 'announcementsIntroduction', 'homepageImageAltText');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$conference =& Request::getConference();

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConfTitles =& $schedConfDao->getSchedConfTitles($conference->getId());
		$templateMgr->assign_by_ref('schedConfTitles', $schedConfTitles);

		$templateMgr->assign(array(
			'homepageImage' => $conference->getSetting('homepageImage')
		));

		parent::display();	   
	}
}

?>
