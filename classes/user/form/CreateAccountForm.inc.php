<?php

/**
 * @defgroup user_form
 */

/**
 * @file CreateAccountForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateAccountForm
 * @ingroup user_form
 *
 * @brief Form for user account creation.
 *
 */



import('lib.pkp.classes.form.Form');

class CreateAccountForm extends Form {
	/** @var boolean user is already registered with another conference */
	var $existingUser;

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

		$this->captchaEnabled = Config::getVar('captcha', 'captcha_on_register') && Config::getVar('captcha', 'recaptcha');

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));

		if ($this->existingUser) {
			// Existing user -- check login
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.login.loginError', create_function('$username,$form', 'return Validation::checkCredentials($form->getData(\'username\'), $form->getData(\'password\'));'), array(&$this)));
		} else {
			// New user -- check required profile fields
			$site =& Request::getSite();

			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.account.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
			$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.account.form.usernameAlphaNumeric'));
			$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.account.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.account.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
			$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
			$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
			$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
			$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailsDoNotMatch', create_function('$email,$form', 'return $email == $form->getData(\'confirmEmail\');'), array(&$this)));
			$this->addCheck(new FormValidator($this, 'affiliation', 'required', 'user.profile.form.affiliationRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.account.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(), true));
			if ($this->captchaEnabled) {
				$this->addCheck(new FormValidatorReCaptcha($this, 'recaptcha_challenge_field', 'recaptcha_response_field', Request::getRemoteAddr(), 'common.captchaField.badCaptcha'));
			}

			$authDao = DAORegistry::getDAO('AuthSourceDAO');
			$this->defaultAuth =& $authDao->getDefaultPlugin();
			if (isset($this->defaultAuth)) {
				$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.account.form.usernameExists', create_function('$username,$form,$auth', 'return (!$auth->userExists($username) || $auth->authenticate($username, $form->getData(\'password\')));'), array(&$this, $this->defaultAuth)));
			}
		}

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$site =& Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if ($this->captchaEnabled) {
			import('lib.pkp.lib.recaptcha.recaptchalib');
			$publicKey = Config::getVar('captcha', 'recaptcha_public_key');
			$useSSL = Config::getVar('security', 'force_ssl')?true:false;
			$reCaptchaHtml = recaptcha_get_html($publicKey, null, $useSSL);
			$templateMgr->assign('reCaptchaHtml', $reCaptchaHtml);
			$templateMgr->assign('captchaEnabled', true);
		}

		$countryDao = DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		import('classes.schedConf.SchedConfAction');

		$userDao = DAORegistry::getDAO('UserDAO');
		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());

		$templateMgr->assign('privacyStatement', $conference->getLocalizedSetting('privacyStatement'));
		$templateMgr->assign('enableOpenAccessNotification', $schedConf->getSetting('enableOpenAccessNotification')==1?1:0);
		$templateMgr->assign('allowRegReader', SchedConfAction::allowRegReader($schedConf));
		$templateMgr->assign('allowRegAuthor', SchedConfAction::allowRegAuthor($schedConf));
		$templateMgr->assign('allowRegReviewer', SchedConfAction::allowRegReviewer($schedConf));
		$templateMgr->assign('source', Request::getUserVar('source'));
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array(Request::url(null, null, 'index'), $schedConf->getLocalizedName(), true)));

		$site =& Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());

		$templateMgr->assign('helpTopicId', 'conference.users.index');
		parent::display();
	}

	function getLocaleFieldNames() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getLocaleFieldNames();
	}

	/**
	 * Initialize default data.
	 */
	function initData() {
		$this->setData('createAsReader', 1);
		if (Request::getUserVar('requiresAuthor')) $this->setData('createAsAuthor', 1);
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
			'salutation', 'firstName', 'middleName', 'lastName',
			'gender', 'initials', 'country',
			'affiliation', 'email', 'confirmEmail', 'userUrl', 'phone', 'fax', 'signature',
			'mailingAddress', 'billingAddress', 'biography', 'interestsTextonly', 'keywords',
			'userLocales', 'createAsReader', 'openAccessNotification', 'createAsAuthor',
			'createAsReviewer', 'existingUser', 'sendPassword'
		);
		if ($this->captchaEnabled) {
			$userVars[] = 'recaptcha_challenge_field';
			$userVars[] = 'recaptcha_response_field';
		}

		$this->readUserVars($userVars);

		if ($this->getData('userLocales') == null || !is_array($this->getData('userLocales'))) {
			$this->setData('userLocales', array());
		}

		if ($this->getData('username') != null) {
			// Usernames must be lowercase
			$this->setData('username', strtolower($this->getData('username')));
		}

		$keywords = $this->getData('keywords');
		if ($keywords != null && is_array($keywords['interests'])) {
			// The interests are coming in encoded -- Decode them for DB storage
			$this->setData('interestsKeywords', array_map('urldecode', $keywords['interests']));
		}
	}

	/**
	 * Send the registration confirmation email.
	 * @param $user object
	 */
	function sendConfirmationEmail($user, $password, $sendPassword) {
		$schedConf =& Request::getSchedConf();
		import('classes.mail.MailTemplate');
		if (Config::getVar('email', 'require_validation')) {
			// Create an access key
			import('lib.pkp.classes.security.AccessKeyManager');
			$accessKeyManager = new AccessKeyManager();
			$accessKey = $accessKeyManager->createKey('RegisterContext', $user->getId(), null, Config::getVar('email', 'validation_timeout'));

			// Send email validation request to user
			$mail = new MailTemplate('USER_VALIDATE');
			$mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
			$mail->assignParams(array(
				'userFullName' => $user->getFullName(),
				'activateUrl' => Request::url(null, null, 'user', 'activateUser', array($user->getUsername(), $accessKey))
			));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
			unset($mail);
		}
		if ($sendPassword) {
			// Send welcome email to user
			$mail = new MailTemplate('USER_REGISTER');
			$mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
			$mail->assignParams(array(
				'username' => $user->getUsername(),
				'password' => String::substr($password, 0, 30), // Prevent mailer abuse via long passwords
			));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
			unset($mail);
		}
	}

	/**
	 * Register a new user.
	 */
	function execute() {
		$requireValidation = Config::getVar('email', 'require_validation');
		if ($this->existingUser) {
			// Existing user in the system
			$userDao = DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getByUsername($this->getData('username'));
			if ($user == null) {
				return false;
			}

			$userId = $user->getId();

		} else {
			// New user
			$user = new User();

			$user->setUsername($this->getData('username'));
			$user->setSalutation($this->getData('salutation'));
			$user->setFirstName($this->getData('firstName'));
			$user->setMiddleName($this->getData('middleName'));
			$user->setInitials($this->getData('initials'));
			$user->setLastName($this->getData('lastName'));
			$user->setGender($this->getData('gender'));
			$user->setAffiliation($this->getData('affiliation'), null); // Localized
			$user->setSignature($this->getData('signature'), null); // Localized
			$user->setEmail($this->getData('email'));
			$user->setUrl($this->getData('userUrl'));
			$user->setPhone($this->getData('phone'));
			$user->setFax($this->getData('fax'));
			$user->setMailingAddress($this->getData('mailingAddress'));
			$user->setBillingAddress($this->getData('billingAddress'));
			$user->setBiography($this->getData('biography'), null); // Localized
			$user->setDateRegistered(Core::getCurrentDate());
			$user->setCountry($this->getData('country'));

			$site =& Request::getSite();
			$availableLocales = $site->getSupportedLocales();

			$locales = array();
			foreach ($this->getData('userLocales') as $locale) {
				if (AppLocale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
					array_push($locales, $locale);
				}
			}
			$user->setLocales($locales);

			if (isset($this->defaultAuth)) {
				$user->setPassword($this->getData('password'));
				// FIXME Check result and handle failures
				$this->defaultAuth->doCreateUser($user);
				$user->setAuthId($this->defaultAuth->authId);
			}
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));

			if ($requireValidation) {
				// The account should be created in a disabled
				// state.
				$user->setDisabled(true);
				$user->setDisabledReason(__('user.login.accountNotValidated'));
			}

			$userDao = DAORegistry::getDAO('UserDAO');
			$userDao->insertObject($user);
			$userId = $user->getId();
			if (!$userId) {
				return false;
			}

			// Insert the user interests
			$interests = $this->getData('interestsKeywords') ? $this->getData('interestsKeywords') : $this->getData('interestsTextOnly');
			import('lib.pkp.classes.user.InterestManager');
			$interestManager = new InterestManager();
			$interestManager->setInterestsForUser($user, $interests);

			$sessionManager =& SessionManager::getManager();
			$session =& $sessionManager->getUserSession();
			$session->setSessionVar('username', $user->getUsername());

		}

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$roleDao = DAORegistry::getDAO('RoleDAO');

		// Roles users are allowed to register themselves in
		$allowedRoles = array('reader' => 'createAsReader', 'author' => 'createAsAuthor', 'reviewer' => 'createAsReviewer');

		import('classes.schedConf.SchedConfAction');
		if (!SchedConfAction::allowRegReader($schedConf)) {
			unset($allowedRoles['reader']);
		}
		if (!SchedConfAction::allowRegAuthor($schedConf)) {
			unset($allowedRoles['author']);
		}
		if (!SchedConfAction::allowRegReviewer($schedConf)) {
			unset($allowedRoles['reviewer']);
		}

		foreach ($allowedRoles as $k => $v) {
			$roleId = $roleDao->getRoleIdFromPath($k);
			if ($this->getData($v) && !$roleDao->userHasRole($conference->getId(), $schedConf->getId(), $userId, $roleId)) {
				$role = new Role();
				$role->setConferenceId($conference->getId());
				$role->setSchedConfId($schedConf->getId());
				$role->setUserId($userId);
				$role->setRoleId($roleId);
				$roleDao->insertRole($role);
			}
		}

		if (!$this->existingUser) {
			$this->sendConfirmationEmail($user, $this->getData('password'), $this->getData('sendPassword'));
		}

		if (isset($allowedRoles['reader']) && $this->getData('openAccessNotification')) {
			$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
			$userSettingsDao->updateSetting($userId, 'openAccessNotification', true, 'bool', $conference->getId());
		}
	}

}

?>
