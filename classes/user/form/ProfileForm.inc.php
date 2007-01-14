<?php

/**
 * ProfileForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user.form
 *
 * Form to edit user profile.
 *
 * $Id$
 */

import('form.Form');

class ProfileForm extends Form {

	/** @var boolean Include a user's working languages in their profile */
	var $profileLocalesEnabled;
	
	/**
	 * Constructor.
	 */
	function ProfileForm() {
		parent::Form('user/profile.tpl');
		
		$user = &Request::getUser();
		
		$site = &Request::getSite();
		$this->profileLocalesEnabled = $site->getProfileLocalesEnabled();
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($user->getUserId(), true), true));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$user = &Request::getUser();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('username', $user->getUsername());
		$templateMgr->assign('profileLocalesEnabled', $this->profileLocalesEnabled);
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		}

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');
		$userSettingsDao = &DAORegistry::getDAO('UserSettingsDAO');

		$events = &$eventDao->getEvents();
		$events = &$events->toArray();

		foreach ($events as $thisEvent) {
			if ($thisEvent->getSetting('enableRegistration') == true && $thisEvent->getSetting('enableOpenAccessNotification') == true) {
				$templateMgr->assign('displayOpenAccessNotification', true);
				$templateMgr->assign_by_ref('user', $user);
				break;
			}
		}

		$eventNotifications = &$notificationStatusDao->getEventNotifications($user->getUserId());

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();

		$templateMgr->assign_by_ref('events', $events);
		$templateMgr->assign_by_ref('countries', $countries);
		$templateMgr->assign_by_ref('eventNotifications', $eventNotifications);
		$templateMgr->assign('helpTopicId', 'user.registerAndProfile');		

		$event =& Request::getEvent();
		if ($event) {
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roles =& $roleDao->getRolesByUserId($user->getUserId(), $event->getEventId());
			$roleNames = array();
			foreach ($roles as $role) $roleNames[$role->getRolePath()] = $role->getRoleName();
			import('event.EventAction');
			$templateMgr->assign('allowRegReviewer', EventAction::allowRegReviewer($event));
			$templateMgr->assign('allowRegAuthor', EventAction::allowRegAuthor($event));
			$templateMgr->assign('allowRegReader', EventAction::allowRegReader($event));
			$templateMgr->assign('roles', $roleNames);
		}
		
		$timeZones = TimeZone::getTimeZones();
		$templateMgr->assign_by_ref('timeZones', $timeZones);
		
		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$user = &Request::getUser();

		$this->_data = array(
			'firstName' => $user->getFirstName(),
			'middleName' => $user->getMiddleName(),
			'initials' => $user->getInitials(),
			'lastName' => $user->getLastName(),
			'affiliation' => $user->getAffiliation(),
			'signature' => $user->getSignature(),
			'email' => $user->getEmail(),
			'userUrl' => $user->getUrl(),
			'phone' => $user->getPhone(),
			'fax' => $user->getFax(),
			'mailingAddress' => $user->getMailingAddress(),
			'country' => $user->getCountry(),
			'timeZone' => $user->getTimeZone(),
			'biography' => $user->getBiography(),
			'interests' => $user->getInterests(),
			'userLocales' => $user->getLocales(),
			'isAuthor' => Validation::isAuthor(),
			'isReader' => Validation::isReader(),
			'isReviewer' => Validation::isReviewer()
		);
		
		
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'firstName',
			'middleName',
			'lastName',
			'initials',
			'affiliation',
			'signature',
			'email',
			'userUrl',
			'phone',
			'fax',
			'mailingAddress',
			'country',
			'timeZone',
			'biography',
			'interests',
			'userLocales',
			'readerRole',
			'authorRole',
			'reviewerRole'
		));
		
		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}
	}
	
	/**
	 * Save profile settings.
	 */
	function execute() {
		$user = &Request::getUser();

		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setInitials($this->getData('initials'));
		$user->setLastName($this->getData('lastName'));
		$user->setAffiliation($this->getData('affiliation'));
		$user->setSignature($this->getData('signature'));
		$user->setEmail($this->getData('email'));
		$user->setUrl($this->getData('userUrl'));
		$user->setPhone($this->getData('phone'));
		$user->setFax($this->getData('fax'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setCountry($this->getData('country'));
		$user->setTimeZone($this->getData('timeZone'));
		$user->setBiography($this->getData('biography'));
		$user->setInterests($this->getData('interests'));
		
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$availableLocales = $site->getSupportedLocales();
			
			$locales = array();
			foreach ($this->getData('userLocales') as $locale) {
				if (Locale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
					array_push($locales, $locale);
				}
			}
			$user->setLocales($locales);
		}
 		
		$userDao = &DAORegistry::getDAO('UserDAO');
		$userDao->updateUser($user);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');

		// Roles
		$event =& Request::getEvent();
		if ($event) {
			import('event.EventAction');
			$role =& new Role();
			$role->setUserId($user->getUserId());
			$role->setConferenceId($event->getConferenceId());
			$role->setEventId($event->getEventId());
			if (EventAction::allowRegReviewer($event)) {
				$role->setRoleId(ROLE_ID_REVIEWER);
				$hasRole = Validation::isReviewer();
				$wantsRole = Request::getUserVar('reviewerRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
			if (EventAction::allowRegAuthor($event)) {
				$role->setRoleId(ROLE_ID_AUTHOR);
				$hasRole = Validation::isAuthor();
				$wantsRole = Request::getUserVar('authorRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
			if (EventAction::allowRegReader($event)) {
				$role->setRoleId(ROLE_ID_READER);
				$hasRole = Validation::isReader();
				$wantsRole = Request::getUserVar('readerRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
		}
		
		$events = &$eventDao->getEvents();
		$events = &$events->toArray();
		$eventNotifications = $notificationStatusDao->getEventNotifications($user->getUserId());

		$readerNotify = Request::getUserVar('eventNotify');

		foreach ($events as $thisEvent) {
			$thisEventId = $thisEvent->getEventId();
			$currentlyReceives = !empty($eventNotifications[$thisEventId]);
			$shouldReceive = !empty($readerNotify) && in_array($thisEvent->getEventId(), $readerNotify);
			if ($currentlyReceives != $shouldReceive) {
				$notificationStatusDao->setEventNotifications($thisEventId, $user->getUserId(), $shouldReceive);
			}
		}

		$openAccessNotify = Request::getUserVar('openAccessNotify');

		$userSettingsDao = &DAORegistry::getDAO('UserSettingsDAO');
		$events = &$eventDao->getEvents();
		$events = &$events->toArray();

		foreach ($events as $thisEvent) {
			if (($thisEvent->getSetting('enableRegistration') == true) && ($thisEvent->getSetting('enableOpenAccessNotification') == true)) {
				$currentlyReceives = $user->getSetting('openAccessNotification', $thisEvent->getEventId());
				$shouldReceive = !empty($openAccessNotify) && in_array($thisEvent->getEventId(), $openAccessNotify);
				if ($currentlyReceives != $shouldReceive) {
					$userSettingsDao->updateSetting($user->getUserId(), 'openAccessNotification', $shouldReceive, 'bool', $thisEvent->getEventId());
				}
			}
		}

		if ($user->getAuthId()) {
			$authDao = &DAORegistry::getDAO('AuthSourceDAO');
			$auth = &$authDao->getPlugin($user->getAuthId());
		}
		
		if (isset($auth)) {
			$auth->doSetUserInfo($user);
		}
	}
	
}

?>
