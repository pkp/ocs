<?php

/**
 * @file RegistrationPolicyForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationPolicyForm
 * @ingroup registration_form
 *
 * @brief Form for managers to setup registration policies.
 */

//$Id$

define('REGISTRATION_EXPIRY_REMINDER_BEFORE_MONTHS_MIN', '1');
define('REGISTRATION_EXPIRY_REMINDER_BEFORE_MONTHS_MAX', '12');
define('REGISTRATION_EXPIRY_REMINDER_BEFORE_WEEKS_MIN', '0');
define('REGISTRATION_EXPIRY_REMINDER_BEFORE_WEEKS_MAX', '3');
define('REGISTRATION_EXPIRY_REMINDER_AFTER_MONTHS_MIN', '1');
define('REGISTRATION_EXPIRY_REMINDER_AFTER_MONTHS_MAX', '12');
define('REGISTRATION_EXPIRY_REMINDER_AFTER_WEEKS_MIN', '0');
define('REGISTRATION_EXPIRY_REMINDER_AFTER_WEEKS_MAX', '3');

import('form.Form');


class RegistrationPolicyForm extends Form {

	/** @var validNumMonthsBeforeExpiry array keys are valid expiry reminder months */	
	var $validNumMonthsBeforeExpiry;

	/** @var validNumWeeksBeforeExpiry array keys are valid expiry reminder weeks */	
	var $validNumWeeksBeforeExpiry;

	/** @var validNumMonthsAfterExpiry array keys are valid expiry reminder months */	
	var $validNumMonthsAfterExpiry;

	/** @var validNumWeeksAfterExpiry array keys are valid expiry reminder weeks */	
	var $validNumWeeksAfterExpiry;

	/**
	 * Constructor
	 */
	function RegistrationPolicyForm() {
		for ($i=REGISTRATION_EXPIRY_REMINDER_BEFORE_MONTHS_MIN; $i<=REGISTRATION_EXPIRY_REMINDER_BEFORE_MONTHS_MAX; $i++) {
			$this->validNumMonthsBeforeExpiry[$i] = $i;
		}

		for ($i=REGISTRATION_EXPIRY_REMINDER_BEFORE_WEEKS_MIN; $i<=REGISTRATION_EXPIRY_REMINDER_BEFORE_WEEKS_MAX; $i++) {
			$this->validNumWeeksBeforeExpiry[$i] = $i;
		}

		for ($i=REGISTRATION_EXPIRY_REMINDER_AFTER_MONTHS_MIN; $i<=REGISTRATION_EXPIRY_REMINDER_AFTER_MONTHS_MAX; $i++) {
			$this->validNumMonthsAfterExpiry[$i] = $i;
		}

		for ($i=REGISTRATION_EXPIRY_REMINDER_AFTER_WEEKS_MIN; $i<=REGISTRATION_EXPIRY_REMINDER_AFTER_WEEKS_MAX; $i++) {
			$this->validNumWeeksAfterExpiry[$i] = $i;
		}

		parent::Form('registration/registrationPolicyForm.tpl');

		// If provided, registration contact email is valid
		$this->addCheck(new FormValidatorEmail($this, 'registrationEmail', 'optional', 'manager.registrationPolicies.registrationContactEmailValid'));

		// If provided expiry reminder months before value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsBeforeRegistrationExpiryReminder', 'optional', 'manager.registrationPolicies.numMonthsBeforeRegistrationExpiryReminderValid', array_keys($this->validNumMonthsBeforeExpiry)));

		// If provided expiry reminder weeks before value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksBeforeRegistrationExpiryReminder', 'optional', 'manager.registrationPolicies.numWeeksBeforeRegistrationExpiryReminderValid', array_keys($this->validNumWeeksBeforeExpiry)));

		// If provided expiry reminder months after value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsAfterRegistrationExpiryReminder', 'optional', 'manager.registrationPolicies.numMonthsAfterRegistrationExpiryReminderValid', array_keys($this->validNumMonthsAfterExpiry)));

		// If provided expiry reminder weeks after value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksAfterRegistrationExpiryReminder', 'optional', 'manager.registrationPolicies.numWeeksAfterRegistrationExpiryReminderValid', array_keys($this->validNumWeeksAfterExpiry)));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('validNumMonthsBeforeExpiry', $this->validNumMonthsBeforeExpiry);
		$templateMgr->assign('validNumWeeksBeforeExpiry', $this->validNumWeeksBeforeExpiry);
		$templateMgr->assign('validNumMonthsAfterExpiry', $this->validNumMonthsAfterExpiry);
		$templateMgr->assign('validNumWeeksAfterExpiry', $this->validNumWeeksAfterExpiry);

		parent::display();
	}

	/**
	 * Initialize form data from current registration policies.
	 */
	function initData() {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConf =& Request::getSchedConf();
		$schedConfId = $schedConf->getId();

		$this->_data = array(
			'registrationName' => $schedConfSettingsDao->getSetting($schedConfId, 'registrationName'),
			'registrationEmail' => $schedConfSettingsDao->getSetting($schedConfId, 'registrationEmail'),
			'registrationPhone' => $schedConfSettingsDao->getSetting($schedConfId, 'registrationPhone'),
			'registrationFax' => $schedConfSettingsDao->getSetting($schedConfId, 'registrationFax'),
			'registrationMailingAddress' => $schedConfSettingsDao->getSetting($schedConfId, 'registrationMailingAddress'),
			'registrationAdditionalInformation' => $schedConfSettingsDao->getSetting($schedConfId, 'registrationAdditionalInformation'),
			'delayedOpenAccessPolicy' => $schedConfSettingsDao->getSetting($schedConfId, 'delayedOpenAccessPolicy'),
			'enableOpenAccessNotification' => $schedConfSettingsDao->getSetting($schedConfId, 'enableOpenAccessNotification'),
			'enableAuthorSelfArchive' => $schedConfSettingsDao->getSetting($schedConfId, 'enableAuthorSelfArchive'),
			'authorSelfArchivePolicy' => $schedConfSettingsDao->getSetting($schedConfId, 'authorSelfArchivePolicy'),
			'enableRegistrationExpiryReminderBeforeMonths' => $schedConfSettingsDao->getSetting($schedConfId, 'enableRegistrationExpiryReminderBeforeMonths'),
			'numMonthsBeforeRegistrationExpiryReminder' => $schedConfSettingsDao->getSetting($schedConfId, 'numMonthsBeforeRegistrationExpiryReminder'),
			'enableRegistrationExpiryReminderBeforeWeeks' => $schedConfSettingsDao->getSetting($schedConfId, 'enableRegistrationExpiryReminderBeforeWeeks'),
			'numWeeksBeforeRegistrationExpiryReminder' => $schedConfSettingsDao->getSetting($schedConfId, 'numWeeksBeforeRegistrationExpiryReminder'),
			'enableRegistrationExpiryReminderAfterMonths' => $schedConfSettingsDao->getSetting($schedConfId, 'enableRegistrationExpiryReminderAfterMonths'),
			'numMonthsAfterRegistrationExpiryReminder' => $schedConfSettingsDao->getSetting($schedConfId, 'numMonthsAfterRegistrationExpiryReminder'),
			'enableRegistrationExpiryReminderAfterWeeks' => $schedConfSettingsDao->getSetting($schedConfId, 'enableRegistrationExpiryReminderAfterWeeks'),
			'numWeeksAfterRegistrationExpiryReminder' => $schedConfSettingsDao->getSetting($schedConfId, 'numWeeksAfterRegistrationExpiryReminder')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('registrationName', 'registrationEmail', 'registrationPhone', 'registrationFax', 'registrationMailingAddress', 'registrationAdditionalInformation', 'enableDelayedOpenAccess', 'delayedOpenAccessDuration', 'delayedOpenAccessPolicy', 'enableOpenAccessNotification', 'enableAuthorSelfArchive', 'authorSelfArchivePolicy', 'enableRegistrationExpiryReminderBeforeMonths', 'numMonthsBeforeRegistrationExpiryReminder', 'enableRegistrationExpiryReminderBeforeWeeks', 'numWeeksBeforeRegistrationExpiryReminder', 'enableRegistrationExpiryReminderAfterWeeks', 'numWeeksAfterRegistrationExpiryReminder', 'enableRegistrationExpiryReminderAfterMonths', 'numMonthsAfterRegistrationExpiryReminder'));

		// If expiry reminder before months is selected, ensure a valid month value is provided
		if ($this->_data['enableRegistrationExpiryReminderBeforeMonths'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numMonthsBeforeRegistrationExpiryReminder', 'required', 'manager.registrationPolicies.numMonthsBeforeRegistrationExpiryReminderValid', array_keys($this->validNumMonthsBeforeExpiry)));
		}

		// If expiry reminder before weeks is selected, ensure a valid week value is provided
		if ($this->_data['enableRegistrationExpiryReminderBeforeWeeks'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numWeeksBeforeRegistrationExpiryReminder', 'required', 'manager.registrationPolicies.numWeeksBeforeRegistrationExpiryReminderValid', array_keys($this->validNumWeeksBeforeExpiry)));
		}

		// If expiry reminder after months is selected, ensure a valid month value is provided
		if ($this->_data['enableRegistrationExpiryReminderAfterMonths'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numMonthsAfterRegistrationExpiryReminder', 'required', 'manager.registrationPolicies.numMonthsAfterRegistrationExpiryReminderValid', array_keys($this->validNumMonthsAfterExpiry)));
		}

		// If expiry reminder after weeks is selected, ensure a valid week value is provided
		if ($this->_data['enableRegistrationExpiryReminderAfterWeeks'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numWeeksAfterRegistrationExpiryReminder', 'required', 'manager.registrationPolicies.numWeeksAfterRegistrationExpiryReminderValid', array_keys($this->validNumWeeksAfterExpiry)));
		}
	}

	/**
	 * Get the names of the fields for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('registrationAdditionalInformation', 'delayedOpenAccessPolicy', 'authorSelfArchivePolicy');
	}

	/**
	 * Save registration policies. 
	 */
	function execute() {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConf =& Request::getSchedConf();
		$schedConfId = $schedConf->getId();

		$schedConfSettingsDao->updateSetting($schedConfId, 'registrationName', $this->getData('registrationName'), 'string');
		$schedConfSettingsDao->updateSetting($schedConfId, 'registrationEmail', $this->getData('registrationEmail'), 'string');
		$schedConfSettingsDao->updateSetting($schedConfId, 'registrationPhone', $this->getData('registrationPhone'), 'string');
		$schedConfSettingsDao->updateSetting($schedConfId, 'registrationFax', $this->getData('registrationFax'), 'string');
		$schedConfSettingsDao->updateSetting($schedConfId, 'registrationMailingAddress', $this->getData('registrationMailingAddress'), 'string');
		$schedConfSettingsDao->updateSetting($schedConfId, 'registrationAdditionalInformation', $this->getData('registrationAdditionalInformation'), 'string', true); // Localized
		$schedConfSettingsDao->updateSetting($schedConfId, 'delayedOpenAccessPolicy', $this->getData('delayedOpenAccessPolicy'), 'string', true); // Localized
		$schedConfSettingsDao->updateSetting($schedConfId, 'enableOpenAccessNotification', $this->getData('enableOpenAccessNotification') == null ? 0 : $this->getData('enableOpenAccessNotification'), 'bool');
		$schedConfSettingsDao->updateSetting($schedConfId, 'enableAuthorSelfArchive', $this->getData('enableAuthorSelfArchive') == null ? 0 : $this->getData('enableAuthorSelfArchive'), 'bool');
		$schedConfSettingsDao->updateSetting($schedConfId, 'authorSelfArchivePolicy', $this->getData('authorSelfArchivePolicy'), 'string', true); // Localized
		$schedConfSettingsDao->updateSetting($schedConfId, 'enableRegistrationExpiryReminderBeforeMonths', $this->getData('enableRegistrationExpiryReminderBeforeMonths') == null ? 0 : $this->getData('enableRegistrationExpiryReminderBeforeMonths'), 'bool');
		$schedConfSettingsDao->updateSetting($schedConfId, 'numMonthsBeforeRegistrationExpiryReminder', $this->getData('numMonthsBeforeRegistrationExpiryReminder'), 'int');
		$schedConfSettingsDao->updateSetting($schedConfId, 'enableRegistrationExpiryReminderBeforeWeeks', $this->getData('enableRegistrationExpiryReminderBeforeWeeks') == null ? 0 : $this->getData('enableRegistrationExpiryReminderBeforeWeeks'), 'bool');
		$schedConfSettingsDao->updateSetting($schedConfId, 'numWeeksBeforeRegistrationExpiryReminder', $this->getData('numWeeksBeforeRegistrationExpiryReminder'), 'int');
		$schedConfSettingsDao->updateSetting($schedConfId, 'enableRegistrationExpiryReminderAfterMonths', $this->getData('enableRegistrationExpiryReminderAfterMonths') == null ? 0 : $this->getData('enableRegistrationExpiryReminderAfterMonths'), 'bool');
		$schedConfSettingsDao->updateSetting($schedConfId, 'numMonthsAfterRegistrationExpiryReminder', $this->getData('numMonthsAfterRegistrationExpiryReminder'), 'int');
		$schedConfSettingsDao->updateSetting($schedConfId, 'enableRegistrationExpiryReminderAfterWeeks', $this->getData('enableRegistrationExpiryReminderAfterWeeks') == null ? 0 : $this->getData('enableRegistrationExpiryReminderAfterWeeks'), 'bool');
		$schedConfSettingsDao->updateSetting($schedConfId, 'numWeeksAfterRegistrationExpiryReminder', $this->getData('numWeeksAfterRegistrationExpiryReminder'), 'int');
	}
}

?>
