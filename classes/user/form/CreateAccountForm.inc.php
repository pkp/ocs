<?php

/**
 * CreateAccountForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user.form
 *
 * Form for user account creation.
 *
 * $Id$
 */

import('form.Form');

class CreateAccountForm extends Form {

	/** @var boolean user is already registered with another conference */
	var $existingUser;

	/** @var boolean Include a user's working languages in their profile */
	var $profileLocalesEnabled;
	
	/** @var AuthPlugin default authentication source, if specified */
	var $defaultAuth;

	/** @var boolean whether or not captcha is enabled for this form */
	var $captchaEnabled;

	/**
	 * Constructor.
	 */
	function CreateAccountForm() {
		parent::Form('user/createAccount.tpl');
		
		$this->existingUser = Request::getUserVar('existingUser') ? 1 : 0;

		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		$this->captchaEnabled = ($captchaManager->isEnabled() && Config::getVar('captcha', 'captcha_on_register'))?true:false;

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
			
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.account.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
			$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.account.form.usernameAlphaNumeric'));
			$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.account.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.account.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
			$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
			$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
	$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.account.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(), true));
			if ($this->captchaEnabled) {
				$this->addCheck(new FormValidatorCaptcha($this, 'captcha', 'captchaId', 'common.captchaField.badCaptcha'));
			}

			$authDao = &DAORegistry::getDAO('AuthSourceDAO');
			$this->defaultAuth = &$authDao->getDefaultPlugin();
			if (isset($this->defaultAuth)) {
				$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.account.form.usernameExists', create_function('$username,$form,$auth', 'return (!$auth->userExists($username) || $auth->authenticate($username, $form->getData(\'password\')));'), array(&$this, $this->defaultAuth)));
			}
		}
	}
	
	/**
	 * Display the form.
	 */
	function display() {

		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();
		
		if ($this->captchaEnabled) {
			import('captcha.CaptchaManager');
			$captchaManager =& new CaptchaManager();
			$captcha =& $captchaManager->createCaptcha();
			if ($captcha) {
				$templateMgr->assign('captchaEnabled', $this->captchaEnabled);
				$this->setData('captchaId', $captcha->getCaptchaId());
			}
		}

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		import('schedConf.SchedConfAction');

		$templateMgr->assign('privacyStatement', $schedConf->getSetting('privacyStatement', true));
		$templateMgr->assign('enableOpenAccessNotification', $schedConf->getSetting('enableOpenAccessNotification', true)==1?1:0);
		$templateMgr->assign('allowRegReader', SchedConfAction::allowRegReader($schedConf));
		$templateMgr->assign('allowRegPresenter', SchedConfAction::allowRegPresenter($schedConf));
		$templateMgr->assign('allowRegReviewer', SchedConfAction::allowRegReviewer($schedConf));
		$templateMgr->assign('profileLocalesEnabled', $this->profileLocalesEnabled);
		$templateMgr->assign('source', Request::getUserVar('source'));
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		}
		$templateMgr->assign('helpTopicId', 'user.accountAndProfile');		
		parent::display();
	}
	
	/**
	 * Initialize default data.
	 */
	function initData() {
		$this->setData('createAsReader', 1);
		if (Request::getUserVar('requiresPresenter')) $this->setData('createAsPresenter', 1);
		$this->setData('existingUser', $this->existingUser);
		$this->setData('userLocales', array());
		$this->setData('sendPassword', 1);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array(
			'username', 'password', 'password2',
			'firstName', 'middleName', 'lastName', 'initials', 'country',
			'affiliation', 'email', 'userUrl', 'phone', 'fax', 'signature',
			'mailingAddress', 'biography', 'interests', 'userLocales',
			'createAsReader', 'openAccessNotification', 'createAsPresenter',
			'createAsReviewer', 'existingUser', 'sendPassword'
		);
		if ($this->captchaEnabled) {
			$userVars[] = 'captchaId';
			$userVars[] = 'captcha';
		}

		$this->readUserVars($userVars);
		
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
			$user->setSignature($this->getData('signature'));
			$user->setEmail($this->getData('email'));
			$user->setUrl($this->getData('userUrl'));
			$user->setPhone($this->getData('phone'));
			$user->setFax($this->getData('fax'));
			$user->setMailingAddress($this->getData('mailingAddress'));
			$user->setBiography($this->getData('biography'));
			$user->setInterests($this->getData('interests'));
			$user->setDateRegistered(Core::getCurrentDate());
			$user->setCountry($this->getData('country'));
		
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
		$schedConf = &Request::getSchedConf();
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		// Roles users are allowed to register themselves in
		$allowedRoles = array('reader' => 'createAsReader', 'presenter' => 'createAsPresenter', 'reviewer' => 'createAsReviewer');

		import('schedConf.SchedConfAction');
		if (!SchedConfAction::allowRegReader($schedConf)) {
			unset($allowedRoles['reader']);
		}
		if (!SchedConfAction::allowRegPresenter($schedConf)) {
			unset($allowedRoles['presenter']);
		}
		if (!SchedConfAction::allowRegReviewer($schedConf)) {
			unset($allowedRoles['reviewer']);
		}
		
		foreach ($allowedRoles as $k => $v) {
			$roleId = $roleDao->getRoleIdFromPath($k);
			if ($this->getData($v) && !$roleDao->roleExists($conference->getConferenceId(), $schedConf->getSchedConfId(), $userId, $roleId)) {
				$role = &new Role();
				$role->setConferenceId($conference->getConferenceId());
				$role->setSchedConfId($schedConf->getSchedConfId());
				$role->setUserId($userId);
				$role->setRoleId($roleId);
				$roleDao->insertRole($role);

			}
		}
		
		if (!$this->existingUser && $this->getData('sendPassword')) {
			// Send welcome email to user
			import('mail.MailTemplate');
			$mail = &new MailTemplate('USER_REGISTER');
			$mail->setFrom($schedConf->getSetting('contactEmail', true), $schedConf->getSetting('contactName', true));
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
			$notificationStatusDao->setSchedConfNotifications($schedConf->getSchedConfId(), $userId, false);
			$notificationStatusDao->setSchedConfNotifications($schedConf->getSchedConfId(), $userId, true);
		}

		if (isset($allowedRoles['reader']) && $this->getData('openAccessNotification')) {
			$userSettingsDao = &DAORegistry::getDAO('UserSettingsDAO');
			$userSettingsDao->updateSetting($userId, 'openAccessNotification', true, 'bool', $conference->getConferenceId());
		}
	}
	
}

?>
