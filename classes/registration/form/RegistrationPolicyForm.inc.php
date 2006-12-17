<?php

/**
 * RegistrationPolicyForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package registration.form
 *
 * Form for directors to setup registration policies.
 *
 * $Id$
 */

define('REGISTRATION_OPEN_ACCESS_DELAY_MIN', '0');
define('REGISTRATION_OPEN_ACCESS_DELAY_MAX', '24');
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

	/** @var validDuration array keys are valid open access delay months */	
	var $validDuration;

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

		for ($i=REGISTRATION_OPEN_ACCESS_DELAY_MIN; $i<=REGISTRATION_OPEN_ACCESS_DELAY_MAX; $i++) {
			$this->validDuration[$i] = $i;
		}

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
		$this->addCheck(new FormValidatorEmail($this, 'registrationEmail', 'optional', 'director.registrationPolicies.registrationContactEmailValid'));

		// If provided delayed open access duration is valid value
		$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'optional', 'director.registrationPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));

		// If provided expiry reminder months before value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsBeforeRegistrationExpiryReminder', 'optional', 'director.registrationPolicies.numMonthsBeforeRegistrationExpiryReminderValid', array_keys($this->validNumMonthsBeforeExpiry)));

		// If provided expiry reminder weeks before value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksBeforeRegistrationExpiryReminder', 'optional', 'director.registrationPolicies.numWeeksBeforeRegistrationExpiryReminderValid', array_keys($this->validNumWeeksBeforeExpiry)));

		// If provided expiry reminder months after value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numMonthsAfterRegistrationExpiryReminder', 'optional', 'director.registrationPolicies.numMonthsAfterRegistrationExpiryReminderValid', array_keys($this->validNumMonthsAfterExpiry)));

		// If provided expiry reminder weeks after value is valid value
		$this->addCheck(new FormValidatorInSet($this, 'numWeeksAfterRegistrationExpiryReminder', 'optional', 'director.registrationPolicies.numWeeksAfterRegistrationExpiryReminderValid', array_keys($this->validNumWeeksAfterExpiry)));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('validDuration', $this->validDuration);
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
		$eventSettingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		$event = &Request::getEvent();
		$eventId = $event->getEventId();

		$this->_data = array(
			'registrationName' => $eventSettingsDao->getSetting($eventId, 'registrationName'),
			'registrationEmail' => $eventSettingsDao->getSetting($eventId, 'registrationEmail'),
			'registrationPhone' => $eventSettingsDao->getSetting($eventId, 'registrationPhone'),
			'registrationFax' => $eventSettingsDao->getSetting($eventId, 'registrationFax'),
			'registrationMailingAddress' => $eventSettingsDao->getSetting($eventId, 'registrationMailingAddress'),
			'registrationAdditionalInformation' => $eventSettingsDao->getSetting($eventId, 'registrationAdditionalInformation'),
			'enableDelayedOpenAccess' => $eventSettingsDao->getSetting($eventId, 'enableDelayedOpenAccess'),
			'delayedOpenAccessDuration' => $eventSettingsDao->getSetting($eventId, 'delayedOpenAccessDuration'),
			'delayedOpenAccessPolicy' => $eventSettingsDao->getSetting($eventId, 'delayedOpenAccessPolicy'),
			'enableOpenAccessNotification' => $eventSettingsDao->getSetting($eventId, 'enableOpenAccessNotification'),
			'enableAuthorSelfArchive' => $eventSettingsDao->getSetting($eventId, 'enableAuthorSelfArchive'),
			'authorSelfArchivePolicy' => $eventSettingsDao->getSetting($eventId, 'authorSelfArchivePolicy'),
			'enableRegistrationExpiryReminderBeforeMonths' => $eventSettingsDao->getSetting($eventId, 'enableRegistrationExpiryReminderBeforeMonths'),
			'numMonthsBeforeRegistrationExpiryReminder' => $eventSettingsDao->getSetting($eventId, 'numMonthsBeforeRegistrationExpiryReminder'),
			'enableRegistrationExpiryReminderBeforeWeeks' => $eventSettingsDao->getSetting($eventId, 'enableRegistrationExpiryReminderBeforeWeeks'),
			'numWeeksBeforeRegistrationExpiryReminder' => $eventSettingsDao->getSetting($eventId, 'numWeeksBeforeRegistrationExpiryReminder'),
			'enableRegistrationExpiryReminderAfterMonths' => $eventSettingsDao->getSetting($eventId, 'enableRegistrationExpiryReminderAfterMonths'),
			'numMonthsAfterRegistrationExpiryReminder' => $eventSettingsDao->getSetting($eventId, 'numMonthsAfterRegistrationExpiryReminder'),
			'enableRegistrationExpiryReminderAfterWeeks' => $eventSettingsDao->getSetting($eventId, 'enableRegistrationExpiryReminderAfterWeeks'),
			'numWeeksAfterRegistrationExpiryReminder' => $eventSettingsDao->getSetting($eventId, 'numWeeksAfterRegistrationExpiryReminder')
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('registrationName', 'registrationEmail', 'registrationPhone', 'registrationFax', 'registrationMailingAddress', 'registrationAdditionalInformation', 'enableDelayedOpenAccess', 'delayedOpenAccessDuration', 'delayedOpenAccessPolicy', 'enableOpenAccessNotification', 'enableAuthorSelfArchive', 'authorSelfArchivePolicy', 'enableRegistrationExpiryReminderBeforeMonths', 'numMonthsBeforeRegistrationExpiryReminder', 'enableRegistrationExpiryReminderBeforeWeeks', 'numWeeksBeforeRegistrationExpiryReminder', 'enableRegistrationExpiryReminderAfterWeeks', 'numWeeksAfterRegistrationExpiryReminder', 'enableRegistrationExpiryReminderAfterMonths', 'numMonthsAfterRegistrationExpiryReminder'));

		// If delayed open access selected, ensure a valid duration is provided
		if ($this->_data['enableDelayedOpenAccess'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'delayedOpenAccessDuration', 'required', 'director.registrationPolicies.delayedOpenAccessDurationValid', array_keys($this->validDuration)));
		}

		// If expiry reminder before months is selected, ensure a valid month value is provided
		if ($this->_data['enableRegistrationExpiryReminderBeforeMonths'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numMonthsBeforeRegistrationExpiryReminder', 'required', 'director.registrationPolicies.numMonthsBeforeRegistrationExpiryReminderValid', array_keys($this->validNumMonthsBeforeExpiry)));
		}

		// If expiry reminder before weeks is selected, ensure a valid week value is provided
		if ($this->_data['enableRegistrationExpiryReminderBeforeWeeks'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numWeeksBeforeRegistrationExpiryReminder', 'required', 'director.registrationPolicies.numWeeksBeforeRegistrationExpiryReminderValid', array_keys($this->validNumWeeksBeforeExpiry)));
		}

		// If expiry reminder after months is selected, ensure a valid month value is provided
		if ($this->_data['enableRegistrationExpiryReminderAfterMonths'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numMonthsAfterRegistrationExpiryReminder', 'required', 'director.registrationPolicies.numMonthsAfterRegistrationExpiryReminderValid', array_keys($this->validNumMonthsAfterExpiry)));
		}

		// If expiry reminder after weeks is selected, ensure a valid week value is provided
		if ($this->_data['enableRegistrationExpiryReminderAfterWeeks'] == 1) {
			$this->addCheck(new FormValidatorInSet($this, 'numWeeksAfterRegistrationExpiryReminder', 'required', 'director.registrationPolicies.numWeeksAfterRegistrationExpiryReminderValid', array_keys($this->validNumWeeksAfterExpiry)));
		}
	}
	
	/**
	 * Save registration policies. 
	 */
	function execute() {
		$eventSettingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		$event = &Request::getEvent();
		$eventId = $event->getEventId();
	
		$eventSettingsDao->updateSetting($eventId, 'registrationName', $this->getData('registrationName'), 'string');
		$eventSettingsDao->updateSetting($eventId, 'registrationEmail', $this->getData('registrationEmail'), 'string');
		$eventSettingsDao->updateSetting($eventId, 'registrationPhone', $this->getData('registrationPhone'), 'string');
		$eventSettingsDao->updateSetting($eventId, 'registrationFax', $this->getData('registrationFax'), 'string');
		$eventSettingsDao->updateSetting($eventId, 'registrationMailingAddress', $this->getData('registrationMailingAddress'), 'string');
		$eventSettingsDao->updateSetting($eventId, 'registrationAdditionalInformation', $this->getData('registrationAdditionalInformation'), 'string');
		$eventSettingsDao->updateSetting($eventId, 'enableDelayedOpenAccess', $this->getData('enableDelayedOpenAccess') == null ? 0 : $this->getData('enableDelayedOpenAccess'), 'bool');
		$eventSettingsDao->updateSetting($eventId, 'delayedOpenAccessDuration', $this->getData('delayedOpenAccessDuration'), 'int');
		$eventSettingsDao->updateSetting($eventId, 'delayedOpenAccessPolicy', $this->getData('delayedOpenAccessPolicy'), 'string');
		$eventSettingsDao->updateSetting($eventId, 'enableOpenAccessNotification', $this->getData('enableOpenAccessNotification') == null ? 0 : $this->getData('enableOpenAccessNotification'), 'bool');
		$eventSettingsDao->updateSetting($eventId, 'enableAuthorSelfArchive', $this->getData('enableAuthorSelfArchive') == null ? 0 : $this->getData('enableAuthorSelfArchive'), 'bool');
		$eventSettingsDao->updateSetting($eventId, 'authorSelfArchivePolicy', $this->getData('authorSelfArchivePolicy'), 'string');
		$eventSettingsDao->updateSetting($eventId, 'enableRegistrationExpiryReminderBeforeMonths', $this->getData('enableRegistrationExpiryReminderBeforeMonths') == null ? 0 : $this->getData('enableRegistrationExpiryReminderBeforeMonths'), 'bool');
		$eventSettingsDao->updateSetting($eventId, 'numMonthsBeforeRegistrationExpiryReminder', $this->getData('numMonthsBeforeRegistrationExpiryReminder'), 'int');
		$eventSettingsDao->updateSetting($eventId, 'enableRegistrationExpiryReminderBeforeWeeks', $this->getData('enableRegistrationExpiryReminderBeforeWeeks') == null ? 0 : $this->getData('enableRegistrationExpiryReminderBeforeWeeks'), 'bool');
		$eventSettingsDao->updateSetting($eventId, 'numWeeksBeforeRegistrationExpiryReminder', $this->getData('numWeeksBeforeRegistrationExpiryReminder'), 'int');
		$eventSettingsDao->updateSetting($eventId, 'enableRegistrationExpiryReminderAfterMonths', $this->getData('enableRegistrationExpiryReminderAfterMonths') == null ? 0 : $this->getData('enableRegistrationExpiryReminderAfterMonths'), 'bool');
		$eventSettingsDao->updateSetting($eventId, 'numMonthsAfterRegistrationExpiryReminder', $this->getData('numMonthsAfterRegistrationExpiryReminder'), 'int');
		$eventSettingsDao->updateSetting($eventId, 'enableRegistrationExpiryReminderAfterWeeks', $this->getData('enableRegistrationExpiryReminderAfterWeeks') == null ? 0 : $this->getData('enableRegistrationExpiryReminderAfterWeeks'), 'bool');
		$eventSettingsDao->updateSetting($eventId, 'numWeeksAfterRegistrationExpiryReminder', $this->getData('numWeeksAfterRegistrationExpiryReminder'), 'int');
	}
	
}

?>
