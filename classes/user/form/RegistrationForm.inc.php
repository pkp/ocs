<?php

/**
 * RegistrationForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user.form
 *
 * Form for user registration.
 *
 * $Id$
 */

import('form.Form');

class RegistrationForm extends Form {

	/** @var boolean user is already registered with another conference */
	var $existingUser;

	/** @var boolean Include a user's working languages in their profile */
	var $profileLocalesEnabled;
	
	/** @var AuthPlugin default authentication source, if specified */
	var $defaultAuth;
	
	/**
	 * Constructor.
	 */
	function RegistrationForm() {
		parent::Form('user/register.tpl');
		
		$this->existingUser = Request::getUserVar('existingUser') ? 1 : 0;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));

		if ($this->existingUser) {
			// Existing user -- check login
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.login.loginError', create_function('$username,$form', 'return Validation::checkCredentials($form->getData(\'username\'), $form->getData(\'password\'));'), array(&$this)));
		} else {
			// New user -- check required profile fields
			$site = &Request::getSite();
			$this->profileLocalesEnabled = $site->getProfileLocalesEnabled();
			
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
			$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
			$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.register.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
			$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
			$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
	$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(), true));

			$authDao = &DAORegistry::getDAO('AuthSourceDAO');
			$this->defaultAuth = &$authDao->getDefaultPlugin();
			if (isset($this->defaultAuth)) {
				$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', create_function('$username,$form,$auth', 'return (!$auth->userExists($username) || $auth->authenticate($username, $form->getData(\'password\')));'), array(&$this, $this->defaultAuth)));
			}
		}
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$site = &Request::getSite();
		$conference = &Request::getConference();
		$event = &Request::getEvent();
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$templateMgr->assign('privacyStatement', $event->getSetting('privacyStatement', true));
		$templateMgr->assign('enableRegistration', $event->getSetting('enableRegistration', true)==1?1:0);
		$templateMgr->assign('enableOpenAccessNotification', $event->getSetting('enableOpenAccessNotification', true)==1?1:0);
		$templateMgr->assign('allowRegReader', EventAction::allowRegReader($event));
		$templateMgr->assign('allowRegAuthor', EventAction::allowRegAuthor($event));
		$templateMgr->assign('allowRegReviewer', EventAction::allowRegReviewer($event));
		$templateMgr->assign('profileLocalesEnabled', $this->profileLocalesEnabled);
		$templateMgr->assign('source', Request::getUserVar('source'));
		$templateMgr->assign('registrationMessage', Request::getUserVar('registrationMessage'));
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $event->getTitle(), true)));
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		}
		$templateMgr->assign('helpTopicId', 'user.registerAndProfile');		
		parent::display();
	}
	
	/**
	 * Initialize default data.
	 */
	function initData() {
		$this->setData('registerAsReader', 1);
		$this->setData('existingUser', $this->existingUser);
		$this->setData('userLocales', array());
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'username', 'password', 'password2',
				'firstName', 'middleName', 'lastName', 'initials',
				'affiliation', 'email', 'userUrl', 'phone', 'fax',
				'mailingAddress', 'biography', 'interests', 'userLocales',
				'registerAsReader', 'openAccessNotification', 'registerAsAuthor',
				'registerAsReviewer', 'existingUser'
			)
		);
		
		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}
		
		if ($this->getData('username') != null) {
			// Usernames must be lowercase
			$this->setData('username', strtolower($this->getData('username')));
		}
	}
	
	/**
	 * Register a new user.
	 */
	function execute() {
		if ($this->existingUser) {
			// Existing user in the system
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUserByUsername($this->getData('username'));
			if ($user == null) {
				return false;
			}
			
			$userId = $user->getUserId();
			
		} else {
			// New user
			$user = &new User();
			
			$user->setUsername($this->getData('username'));
			$user->setFirstName($this->getData('firstName'));
			$user->setMiddleName($this->getData('middleName'));
			$user->setInitials($this->getData('initials'));
			$user->setLastName($this->getData('lastName'));
			$user->setAffiliation($this->getData('affiliation'));
			$user->setEmail($this->getData('email'));
			$user->setUrl($this->getData('userUrl'));
			$user->setPhone($this->getData('phone'));
			$user->setFax($this->getData('fax'));
			$user->setMailingAddress($this->getData('mailingAddress'));
			$user->setBiography($this->getData('biography'));
			$user->setInterests($this->getData('interests'));
			$user->setDateRegistered(Core::getCurrentDate());
		
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
			
			if (isset($this->defaultAuth)) {
				$user->setPassword($this->getData('password'));
				// FIXME Check result and handle failures
				$this->defaultAuth->doCreateUser($user);
				$user->setAuthId($this->defaultAuth->authId);
				$user->setPassword(Validation::encryptCredentials($user->getUserId(), Validation::generatePassword())); // Used for PW reset hash only
			} else {
				$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));
			}
			
			$userDao = &DAORegistry::getDAO('UserDAO');
			$userDao->insertUser($user);
			$userId = $user->getUserId();
			if (!$userId) {
				return false;
			}
			
			$sessionManager = &SessionManager::getManager();
			$session = &$sessionManager->getUserSession();
			$session->setSessionVar('username', $user->getUsername());
		}

		$conference = &Request::getConference();
		$event = &Request::getEvent();
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		// Roles users are allowed to register themselves in
		$allowedRoles = array('reader' => 'registerAsReader', 'author' => 'registerAsAuthor', 'reviewer' => 'registerAsReviewer');

		if (!EventAction::allowRegReader($event)) {
			unset($allowedRoles['reader']);
		}
		if (!EventAction::allowRegAuthor($event)) {
			unset($allowedRoles['author']);
		}
		if (!EventAction::allowRegReviewer($event)) {
			unset($allowedRoles['reviewer']);
		}
		
		foreach ($allowedRoles as $k => $v) {
			$roleId = $roleDao->getRoleIdFromPath($k);
			if ($this->getData($v) && !$roleDao->roleExists($conference->getConferenceId(), $event->getEventId(), $userId, $roleId)) {
				$role = &new Role();
				$role->setConferenceId($conference->getConferenceId());
				$role->setEventId($event->getEventId());
				$role->setUserId($userId);
				$role->setRoleId($roleId);
				$roleDao->insertRole($role);

			}
		}
		
		if (!$this->existingUser) {
			// Send welcome email to user
			import('mail.MailTemplate');
			$mail = &new MailTemplate('USER_REGISTER');
			$mail->setFrom($conference->getSetting('contactEmail'), $conference->getSetting('contactName'));
			$mail->assignParams(array('username' => $this->getData('username'), 'password' => $this->getData('password')));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
		}

		// By default, self-registering readers will receive
		// conference updates. (The double set is here to prevent a
		// duplicate insert error msg if there was a notification entry
		// left over from a previous role.)
		if (isset($allowedRoles['reader']) && $this->getData($allowedRoles['reader'])) {
			$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');
			$notificationStatusDao->setEventNotifications($event->getEventId(), $userId, false);
			$notificationStatusDao->setEventNotifications($event->getEventId(), $userId, true);
		}

		if (isset($allowedRoles['reader']) && $this->getData('openAccessNotification')) {
			$userSettingsDao = &DAORegistry::getDAO('UserSettingsDAO');
			$userSettingsDao->updateSetting($userId, 'openAccessNotification', true, 'bool', $conference->getConferenceId());
		}
	}
	
}

?>
