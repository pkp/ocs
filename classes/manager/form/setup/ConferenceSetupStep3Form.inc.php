<?php

/**
 * @file ConferenceSetupStep3Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSetupStep3Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 3 of conference setup.
 */

// $Id$

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
				'numPageLinks' => 'int',
				'homeHeaderTitleImageAltText' => 'string',
				'homeHeaderLogoImageAltText' => 'string',
				'pageHeaderTitleImageAltText' => 'string',
				'pageHeaderLogoImageAltText' => 'string'
			)
		);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('homeHeaderTitleType', 'homeHeaderTitle', 'pageHeaderTitleType', 'pageHeaderTitle', 'navItems', 'conferencePageHeader', 'conferencePageFooter', 'conferenceFavicon', 'homeHeaderTitleImageAltText', 'homeHeaderLogoImageAltText', 'pageHeaderTitleImageAltText', 'pageHeaderLogoImageAltText');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference =& Request::getConference();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array(
			'homeHeaderTitleImage' => $conference->getSetting('homeHeaderTitleImage'),
			'homeHeaderLogoImage'=> $conference->getSetting('homeHeaderLogoImage'),
			'pageHeaderTitleImage' => $conference->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $conference->getSetting('pageHeaderLogoImage'),
			'conferenceFavicon' => $conference->getSetting('conferenceFavicon')
		));

		parent::display();	   
	}
}

?>
