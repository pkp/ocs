<?php

/**
 * RegistrationForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package registration.form
 *
 * Form for event directors to create/edit registrations.
 *
 * $Id$
 */

import('form.Form');

class RegistrationForm extends Form {

	/** @var registrationId int the ID of the registration being edited */
	var $registrationId;

	/**
	 * Constructor
	 * @param registrationId int leave as default for new registration
	 */
	function RegistrationForm($registrationId = null, $userId = null) {

		$this->registrationId = isset($registrationId) ? (int) $registrationId : null;
		$this->userId = isset($userId) ? (int) $userId : null;
		$event = &Request::getEvent();

		parent::Form('registration/registrationForm.tpl');
	
		// User is provided and valid
		$this->addCheck(new FormValidator($this, 'userId', 'required', 'director.registrations.form.userIdRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'director.registrations.form.userIdValid', create_function('$userId', '$userDao = &DAORegistry::getDAO(\'UserDAO\'); return $userDao->userExistsById($userId);')));

		// Ensure that user does not already have a registration for this event
		if ($this->registrationId == null) {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'director.registrations.form.registrationExists', array(DAORegistry::getDAO('RegistrationDAO'), 'registrationExistsByUser'), array($event->getEventId()), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'userId', 'required', 'director.registrations.form.registrationExists', create_function('$userId, $eventId, $registrationId', '$registrationDao = &DAORegistry::getDAO(\'RegistrationDAO\'); $checkId = $registrationDao->getRegistrationIdByUser($userId, $eventId); return ($checkId == 0 || $checkId == $registrationId) ? true : false;'), array($event->getEventId(), $this->registrationId)));
		}

		// Registration type is provided and valid
		$this->addCheck(new FormValidator($this, 'typeId', 'required', 'director.registrations.form.typeIdRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'director.registrations.form.typeIdValid', create_function('$typeId, $eventId', '$registrationTypeDao = &DAORegistry::getDAO(\'RegistrationTypeDAO\'); return $registrationTypeDao->registrationTypeExistsByTypeId($typeId, $eventId);'), array($event->getEventId())));

		/*
		// If provided, domain is valid
		$this->addCheck(new FormValidatorRegExp($this, 'domain', 'optional', 'director.registrations.form.domainValid', '/^' .
				'[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
				'\.' .
				'[A-Z]{2,4}' .
			'$/i'));

		// If provided, IP range has IP address format; IP addresses may contain wildcards
		$this->addCheck(new FormValidatorRegExp($this, 'ipRange', 'optional', 'director.registrations.form.ipRangeValid','/^' .
				// IP4 address (with or w/o wildcards) or IP4 address range (with or w/o wildcards) or CIDR IP4 address
				'((([0-9]{1,3}|[' . REGISTRATION_IP_RANGE_WILDCARD . '])([.]([0-9]{1,3}|[' . REGISTRATION_IP_RANGE_WILDCARD . '])){3}((\s)*[' . REGISTRATION_IP_RANGE_RANGE . '](\s)*([0-9]{1,3}|[' . REGISTRATION_IP_RANGE_WILDCARD . '])([.]([0-9]{1,3}|[' . REGISTRATION_IP_RANGE_WILDCARD . '])){3}){0,1})|(([0-9]{1,3})([.]([0-9]{1,3})){3}([\/](([3][0-2]{0,1})|([1-2]{0,1}[0-9])))))' .
				// followed by 0 or more delimited IP4 addresses (with or w/o wildcards) or IP4 address ranges
				// (with or w/o wildcards) or CIDR IP4 addresses
				'((\s)*' . REGISTRATION_IP_RANGE_SEPERATOR . '(\s)*' .
				'((([0-9]{1,3}|[' . REGISTRATION_IP_RANGE_WILDCARD . '])([.]([0-9]{1,3}|[' . REGISTRATION_IP_RANGE_WILDCARD . '])){3}((\s)*[' . REGISTRATION_IP_RANGE_RANGE . '](\s)*([0-9]{1,3}|[' . REGISTRATION_IP_RANGE_WILDCARD . '])([.]([0-9]{1,3}|[' . REGISTRATION_IP_RANGE_WILDCARD . '])){3}){0,1})|(([0-9]{1,3})([.]([0-9]{1,3})){3}([\/](([3][0-2]{0,1})|([1-2]{0,1}[0-9])))))' .
				')*' .
			'$/i'));
		*/
		// Notify email flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'notifyEmail', 'optional', 'director.registrations.form.notifyEmailValid', array('1')));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$event = &Request::getEvent();

		$templateMgr->assign('registrationId', $this->registrationId);
		$templateMgr->assign('yearOffsetPast', REGISTRATION_YEAR_OFFSET_PAST);
		$templateMgr->assign('yearOffsetFuture', REGISTRATION_YEAR_OFFSET_FUTURE);

		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &$userDao->getUser(isset($this->userId)?$this->userId:$this->getData('userId'));

		$templateMgr->assign_by_ref('user', $user);

		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationTypes = &$registrationTypeDao->getRegistrationTypesByEventId($event->getEventId());
		$templateMgr->assign('registrationTypes', $registrationTypes);
		$templateMgr->assign('helpTopicId', 'event.managementPages.registrations');
	
		parent::display();
	}
	
	/**
	 * Initialize form data from current registration.
	 */
	function initData() {
		if (isset($this->registrationId)) {
			$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
			$registration = &$registrationDao->getRegistration($this->registrationId);
			
			if ($registration != null) {
				$this->_data = array(
					'userId' => $registration->getUserId(),
					'typeId' => $registration->getTypeId()
					/*'membership' => $registration->getMembership(),
					'domain' => $registration->getDomain(),
					'ipRange' => $registration->getIPRange()*/
				);

			} else {
				$this->registrationId = null;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('userId', 'typeId', 'notifyEmail'));

		// If registration type requires it, membership is provided
		/*$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$needMembership = $registrationTypeDao->getRegistrationTypeMembership($this->getData('typeId'));

		if ($needMembership) { 
			$this->addCheck(new FormValidator($this, 'membership', 'required', 'director.registrations.form.membershipRequired'));
		}

		// If registration type requires it, domain and/or IP range is provided
		$isInstitutional = $registrationTypeDao->getRegistrationTypeInstitutional($this->getData('typeId'));

		if ($isInstitutional) { 
			$this->addCheck(new FormValidatorCustom($this, 'domain', 'required', 'director.registrations.form.domainIPRangeRequired', create_function('$domain, $ipRange', 'return $domain != \'\' || $ipRange != \'\' ? true : false;'), array($this->getData('ipRange'))));
		}*/

		// If notify email is requested, ensure registration contact name and email exist.
		if ($this->_data['notifyEmail'] == 1) {
			$this->addCheck(new FormValidatorCustom($this, 'notifyEmail', 'required', 'director.registrations.form.registrationContactRequired', create_function('', '$event = &Request::getEvent(); $eventSettingsDao = &DAORegistry::getDAO(\'EventSettingsDAO\'); $registrationName = $eventSettingsDao->getSetting($event->getEventId(), \'registrationName\'); $registrationEmail = $eventSettingsDao->getSetting($event->getEventId(), \'registrationEmail\'); return $registrationName != \'\' && $registrationEmail != \'\' ? true : false;'), array()));
		}
	}
	
	/**
	 * Save registration. 
	 */
	function execute() {
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
		$event = &Request::getEvent();
	
		if (isset($this->registrationId)) {
			$registration = &$registrationDao->getRegistration($this->registrationId);
		}
		
		if (!isset($registration)) {
			$registration = &new Registration();
		}
		
		$registration->setEventId($event->getEventId());
		$registration->setUserId($this->getData('userId'));
		$registration->setTypeId($this->getData('typeId'));
		/*$registration->setDateStart($this->getData('dateStartYear') . '-' . $this->getData('dateStartMonth'). '-' . $this->getData('dateStartDay'));
		$registration->setDateEnd($this->getData('dateEndYear') . '-' . $this->getData('dateEndMonth'). '-' . $this->getData('dateEndDay'));
		$registration->setMembership($this->getData('membership') ? $this->getData('membership') : null);
		$registration->setDomain($this->getData('domain') ? $this->getData('domain') : null);
		$registration->setIPRange($this->getData('ipRange') ? $this->getData('ipRange') : null);*/
		$registration->setDateRegistered(time());

		// FIXME: integrate payment module...
		$registration->setDatePaid(time());

		// Update or insert registration
		if ($registration->getRegistrationId() != null) {
			$registrationDao->updateRegistration($registration);
		} else {
			$registrationDao->insertRegistration($registration);
		}

		if ($this->getData('notifyEmail')) {
			// Send user registration notification email
			$userDao = &DAORegistry::getDAO('UserDAO');
			$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
			$eventSettingsDao = &DAORegistry::getDAO('EventSettingsDAO');

			$eventName = $event->getTitle();
			$eventId = $event->getEventId();
			$user = &$userDao->getUser($this->getData('userId'));
			$registrationType = &$registrationTypeDao->getRegistrationType($this->getData('typeId'));

			$registrationName = $eventSettingsDao->getSetting($eventId, 'registrationName');
			$registrationEmail = $eventSettingsDao->getSetting($eventId, 'registrationEmail');
			$registrationPhone = $eventSettingsDao->getSetting($eventId, 'registrationPhone');
			$registrationFax = $eventSettingsDao->getSetting($eventId, 'registrationFax');
			$registrationMailingAddress = $eventSettingsDao->getSetting($eventId, 'registrationMailingAddress');
			$registrationContactSignature = $registrationName;

			if ($registrationMailingAddress != '') {
				$registrationContactSignature .= "\n" . $registrationMailingAddress;
			}
			if ($registrationPhone != '') {
				$registrationContactSignature .= "\n" . Locale::Translate('user.phone') . ': ' . $registrationPhone;
			}
			if ($registrationFax != '') {
				$registrationContactSignature .= "\n" . Locale::Translate('user.fax') . ': ' . $registrationFax;
			}

			$registrationContactSignature .= "\n" . Locale::Translate('user.email') . ': ' . $registrationEmail;

			$paramArray = array(
				'registrantName' => $user->getFullName(),
				'eventName' => $eventName,
				'registrationType' => $registrationType->getSummaryString(),
				'username' => $user->getUsername(),
				'registrationContactSignature' => $registrationContactSignature 
			);

			import('mail.MailTemplate');
			$mail = &new MailTemplate('REGISTRATION_NOTIFY');
			$mail->setFrom($registrationEmail, $registrationName);
			$mail->assignParams($paramArray);
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
		}
	}
	
}

?>
