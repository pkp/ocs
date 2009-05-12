<?php

/**
 * @file ConferenceSetupStep3Form.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
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
	var $images;
	var $image_settings;

	/**
	 * Constructor.
	 */
	function ConferenceSetupStep3Form() {
		$this->images = array(
			'homeHeaderTitleImage',
			'homeHeaderLogoImage',
			'pageHeaderTitleImage',
			'pageHeaderLogoImage'
		);

		$this->image_settings = array(
			'homeHeaderTitleImage' => 'homeHeaderTitleImageAltText',
			'homeHeaderLogoImage' => 'homeHeaderLogoImageAltText',
			'pageHeaderTitleImage' => 'pageHeaderTitleImageAltText',
			'pageHeaderLogoImage' => 'pageHeaderLogoImageAltText'
		);

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

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array_values($this->image_settings));
		parent::readInputData();
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
		));

		parent::display();	   
	}

	function execute() {
		// Save alt text for images
		$conference =& Request::getConference();
		$conferenceId = $conference->getConferenceId();
		$locale = $this->getFormLocale();
		$settingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$images = $this->images;

		foreach($images as $settingName) {
			$value = $conference->getSetting($settingName);
			if (!empty($value)) {
				$imageAltText = $this->getData($this->image_settings[$settingName]);
				$value[$locale]['altText'] = $imageAltText[$locale];
				$settingsDao->updateSetting($conferenceId, $settingName, $value, 'object', true);
			}
		}

		// Save remaining settings
		return parent::execute();
	}
}

?>
