<?php

/**
 * @file ConferenceSetupStep3Form.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 * @class ConferenceSetupStep3Form
 *
 * Form for Step 3 of conference setup.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep3Form extends ConferenceSetupForm {
	/**
	 * Constructor.
	 */
	function ConferenceSetupStep3Form() {
		parent::ConferenceSetupForm(
			3,
			array(
				'homeHeaderTitleType' => 'int',
				'homeHeaderTitle' => 'string',
				'pageHeaderTitleType' => 'int',
				'pageHeaderTitle' => 'string',
				'navItems' => 'object',
				'conferencePageHeader' => 'string',
				'conferencePageFooter' => 'string',
				'itemsPerPage' => 'int',
				'numPageLinks' => 'int'
			)
		);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('homeHeaderTitleType', 'homeHeaderTitle', 'pageHeaderTitleType', 'pageHeaderTitle', 'navItems', 'conferencePageHeader', 'conferencePageFooter');
	}

	function readInputData() {
		parent::readInputData();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference = &Request::getConference();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'homeHeaderTitleImage' => $conference->getSetting('homeHeaderTitleImage'),
			'homeHeaderLogoImage'=> $conference->getSetting('homeHeaderLogoImage'),
			'pageHeaderTitleImage' => $conference->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $conference->getSetting('pageHeaderLogoImage'),
			'homepageImage' => $conference->getSetting('homepageImage')
		));

		parent::display();	   
	}
}

?>
