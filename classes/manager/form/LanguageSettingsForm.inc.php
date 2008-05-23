<?php

/**
 * @file LanguageSettingsForm.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class LanguageSettingsForm
 *
 * Form for modifying conference language settings.
 *
 * $Id$
 */

import('form.Form');

class LanguageSettingsForm extends Form {

	/** @var array the setting names */
	var $settings;

	/** @var array set of locales available for conference use */
	var $availableLocales;

	/**
	 * Constructor.
	 */
	function LanguageSettingsForm() {
		parent::Form('manager/languageSettings.tpl');

		$this->settings = array(
			'supportedLocales' => 'object'
		);

		$site = &Request::getSite();
		$this->availableLocales = $site->getSupportedLocales();

		$localeCheck = create_function('$locale,$availableLocales', 'return in_array($locale,$availableLocales);');

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'primaryLocale', 'required', 'manager.languages.form.primaryLocaleRequired'), array('Locale', 'isLocaleValid'));
		$this->addCheck(new FormValidator($this, 'primaryLocale', 'required', 'manager.languages.form.primaryLocaleRequired'), $localeCheck, array(&$this->availableLocales));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		$templateMgr->assign('helpTopicId','conference.generalManagement.languages');
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$conference = &Request::getConference();
                foreach (array_keys($this->settings) as $settingName) {
			$this->_data[$settingName] = $conference->getSetting($settingName);
		}

		$this->setData('primaryLocale', $conference->getPrimaryLocale());

		if ($this->getData('supportedLocales') == null || !is_array($this->getData('supportedLocales'))) {
			$this->setData('supportedLocales', array());
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$vars = array_keys($this->settings);
		$vars[] = 'primaryLocale';
		$this->readUserVars($vars);

		if ($this->getData('supportedLocales') == null || !is_array($this->getData('supportedLocales'))) {
			$this->setData('supportedLocales', array());
		}
	}

	/**
	 * Save modified settings.
	 */
	function execute() {
		$conference = &Request::getConference();
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');

		// Verify additional locales
		$supportedLocales = array();
		foreach ($this->getData('supportedLocales') as $locale) {
			if (Locale::isLocaleValid($locale) && in_array($locale, $this->availableLocales)) {
				array_push($supportedLocales, $locale);
			}
		}

		$primaryLocale = $this->getData('primaryLocale');

		if ($primaryLocale != null && !empty($primaryLocale) && !in_array($primaryLocale, $supportedLocales)) {
			array_push($supportedLocales, $primaryLocale);
		}
		$this->setData('supportedLocales', $supportedLocales);

		foreach ($this->_data as $name => $value) {
			if (!in_array($name, array_keys($this->settings))) continue;
			$settingsDao->updateSetting(
				$conference->getConferenceId(),
				$name,
				$value,
				$this->settings[$name]
			);
		}

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conference->setPrimaryLocale($this->getData('primaryLocale'));
		$conferenceDao->updateConference($conference);
	}

}

?>
