<?php

/**
 * @file ConferenceSetupStep2Form.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 * @class ConferenceSetupStep2Form
 *
 * Form for Step 2 of conference setup.
 *
 * $Id$
 */

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
				'presenterInformation' => 'string',
				'enableAnnouncements' => 'bool',
				'enableAnnouncementsHomepage' => 'bool',
				'numAnnouncementsHomepage' => 'int',
				'paperAccess' => 'int',
				'announcementsIntroduction' => 'string',
				'schedConfRedirect' => 'int'
			)
		);
		$conference =& Request::getConference();
		$this->addCheck(new FormValidatorCustom($this, 'schedConfRedirect', 'optional', 'manager.setup.additionalContent.redirect.invalidSchedConf', create_function('$schedConfRedirect,$form,$schedConfDao,$conferenceId', 'return $schedConfDao->getSchedConf($schedConfRedirect, $conferenceId);'), array(&$this, DAORegistry::getDAO('SchedConfDAO'), $conference->getConferenceId())));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('additionalHomeContent', 'readerInformation', 'presenterInformation', 'announcementsIntroduction');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$conference = &Request::getConference();

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConfTitles =& $schedConfDao->getSchedConfTitles($conference->getConferenceId());
		$templateMgr->assign_by_ref('schedConfTitles', $schedConfTitles);

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr->assign(array(
			'homepageImage' => $conference->getSetting('homepageImage')
		));
		parent::display();	   
	}
}

?>
