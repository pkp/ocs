<?php

/**
 * @file LanguageSettingsForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
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
			'primaryLocale' => 'string',
			'alternateLocale1' => 'string',
			'alternateLocale2' => 'string',
			'supportedLocales' => 'object',
			'conferenceTitleAltLanguages' => 'bool',
			'paperAltLanguages' => 'bool'
		);
		
		$site = &Request::getSite();
		$this->availableLocales = $site->getSupportedLocales();
		
		$localeCheck = create_function('$locale,$availableLocales', 'return in_array($locale,$availableLocales);');
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'primaryLocale', 'required', 'manager.languages.form.primaryLocaleRequired'), array('Locale', 'isLocaleValid'));
		$this->addCheck(new FormValidator($this, 'primaryLocale', 'required', 'manager.languages.form.primaryLocaleRequired'), $localeCheck, array(&$this->availableLocales));
		$this->addCheck(new FormValidator($this, 'alternateLocale1', 'optional', 'manager.languages.form.alternateLocale1Invalid'), array('Locale', 'isLocaleValid'));
		$this->addCheck(new FormValidator($this, 'alternateLocale1', 'optional', 'manager.languages.form.alternateLocale1Invalid'), $localeCheck, array(&$this->availableLocales));
		$this->addCheck(new FormValidator($this, 'alternateLocale2', 'optional', 'manager.languages.form.alternateLocale2Invalid'), array('Locale', 'isLocaleValid'));
		$this->addCheck(new FormValidator($this, 'alternateLocale2', 'optional', 'manager.languages.form.alternateLocale2Invalid'), $localeCheck, array(&$this->availableLocales));
		$this->addCheck(new FormValidatorPost($this));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		$templateMgr->assign('helpTopicId','conference.managementPages.languages');
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
		
		if ($this->getData('supportedLocales') == null || !is_array($this->getData('supportedLocales'))) {
			$this->setData('supportedLocales', array());
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->settings));
		
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
		
		if ($this->getData('primaryLocale') == $this->getData('alternateLocale1') || $this->getData('alternateLocale1') == '') {
			$this->setData('alternateLocale1', null);
		}
		
		if ($this->getData('primaryLocale') == $this->getData('alternateLocale2') || $this->getData('alternateLocale2') == '') {
			$this->setData('alternateLocale2', null);
		}
		
		if (!$this->getData('alternateLocale1') || $this->getData('alternateLocale1') == $this->getdata('alternateLocale2')) {
			$this->setData('alternateLocale1', $this->getData('alternateLocale2'));
			$this->setData('alternateLocale2', null);
		}
		
		// Verify additional locales
		$supportedLocales = array();
		foreach ($this->getData('supportedLocales') as $locale) {
			if (Locale::isLocaleValid($locale) && in_array($locale, $this->availableLocales)) {
				array_push($supportedLocales, $locale);
			}
		}
		
		$primaryLocale = $this->getData('primaryLocale');
		$alternateLocale1 = $this->getData('alternateLocale1');
		$alternateLocale2 = $this->getData('alternateLocale2');
		
		foreach (array($primaryLocale, $alternateLocale1, $alternateLocale2) as $locale) {
			if ($locale != null && !empty($locale) && !in_array($locale, $supportedLocales)) {
				array_push($supportedLocales, $locale);
			}
		}
		$this->setData('supportedLocales', $supportedLocales);
		
		foreach ($this->_data as $name => $value) {
			$settingsDao->updateSetting(
				$conference->getConferenceId(),
				$name,
				$value,
				$this->settings[$name]
			);
		}
	}
	
}

?>
