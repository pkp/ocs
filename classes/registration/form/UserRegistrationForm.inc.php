<?php

/**
 * @file UserRegistrationForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package registration.form
 * @class UserRegistrationForm
 *
 * Form for users to self-register.
 *
 * $Id$
 */

import('form.Form');

class UserRegistrationForm extends Form {

	/** @var boolean Include a user's working languages in their profile */
	var $profileLocalesEnabled;
	
	/** @var boolean whether or not captcha is enabled for this form */
	var $captchaEnabled;

	/**
	 * Constructor
	 */
	function UserRegistrationForm() {
		$schedConf = &Request::getSchedConf();

		parent::Form('registration/userRegistrationForm.tpl');
	
		// Registration type is provided and valid
		$this->addCheck(new FormValidator($this, 'registrationTypeId', 'required', 'manager.registration.form.typeIdRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'registrationTypeId', 'required', 'manager.registration.form.typeIdValid', create_function('$registrationTypeId, $schedConfId', '$registrationTypeDao = &DAORegistry::getDAO(\'RegistrationTypeDAO\'); return $registrationTypeDao->openRegistrationTypeExistsByTypeId($registrationTypeId, $schedConfId);'), array($schedConf->getSchedConfId())));

		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		$this->captchaEnabled = ($captchaManager->isEnabled() && Config::getVar('captcha', 'captcha_on_register'))?true:false;

		$user =& Request::getUser();
		if (!$user) {
			$site = &Request::getSite();
			$this->profileLocalesEnabled = $site->getProfileLocalesEnabled();

			$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
			$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));

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

		$this->addCheck(new FormValidatorPost($this));
	}

	function validate() {
		$schedConf =& Request::getSchedConf();
		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationType =& $registrationTypeDao->getRegistrationType($this->getData('registrationTypeId'));
		if ($registrationType && $registrationType->getCode() != '') {
			$this->addCheck(new FormValidatorCustom($this, 'feeCode', 'required', 'manager.registration.form.feeCodeValid', create_function('$feeCode, $schedConfId, $form', '$registrationTypeDao = &DAORegistry::getDAO(\'RegistrationTypeDAO\'); return $registrationTypeDao->checkCode($form->getData(\'registrationTypeId\'), $schedConfId, $feeCode);'), array($schedConf->getSchedConfId(), $this)));
		}
		return parent::validate();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$user =& Request::getUser();
		$schedConf =& Request::getSchedConf();
		$site =& Request::getSite();

		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationTypes = &$registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getSchedConfId());

		$templateMgr->assign_by_ref('registrationTypes', $registrationTypes);
		$templateMgr->assign('userLoggedIn', $user?true:false);
		$templateMgr->assign('requestUri', $_SERVER['REQUEST_URI']);
		if ($user) {
			$templateMgr->assign('userFullName', $user->getFullName());

		}

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

		$templateMgr->assign('profileLocalesEnabled', $this->profileLocalesEnabled);
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		if ($this->profileLocalesEnabled) {
			$site = &Request::getSite();
			$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		}

		$templateMgr->assign('schedConfSettings', $schedConf->getSettings());
		$templateMgr->assign_by_ref('user', $user);
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array('registrationTypeId', 'specialRequests', 'feeCode');

		$user =& Request::getUser();
		if (!$user) {
			$userVars[] = 'username';
			$userVars[] = 'password';
			$userVars[] = 'password2';
			$userVars[] = 'firstName';
			$userVars[] = 'middleName';
			$userVars[] = 'captcha';
			$userVars[] = 'lastName';
			$userVars[] = 'initials';
			$userVars[] = 'affiliation';
			$userVars[] = 'signature';
			$userVars[] = 'email';
			$userVars[] = 'userUrl';
			$userVars[] = 'phone';
			$userVars[] = 'fax';
			$userVars[] = 'mailingAddress';
			$userVars[] = 'country';
			$userVars[] = 'biography';
			$userVars[] = 'userLocales';
		}

		$this->readUserVars($userVars);

		// If registration type requires it, membership is provided
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$needMembership = $registrationTypeDao->getRegistrationTypeMembership($this->getData('typeId'));
	}
	
	/**
	 * Save registration. 
	 */
	function execute() {
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();
	
		if (!$user) {
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
			
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));

			$userDao = &DAORegistry::getDAO('UserDAO');
			$userDao->insertUser($user);
			$userId = $user->getUserId();
			if (!$userId) {
				return false;
			}

			$conference =& Request::getConference();
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$role =& new Role();
			$role->setRoleId(ROLE_ID_READER);
			$role->setSchedConfId($schedConf->getSchedConfId());
			$role->setConferenceId($conference->getConferenceId());
			$role->setUserId($user->getUserId());
			$roleDao->insertRole($role);
			
			$sessionManager = &SessionManager::getManager();
			$session = &$sessionManager->getUserSession();
			$session->setSessionVar('username', $user->getUsername());

			// Make sure subsequent requests to Request::getUser work
			Validation::login($this->getData('username'), $this->getData('password'), $reason);
		}

		// Get the registration type
		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationType =& $registrationTypeDao->getRegistrationType($this->getData('registrationTypeId'));
		if (!$registrationType || $registrationType->getSchedConfId() != $schedConf->getSchedConfId()) {
			Request::redirect('index');
		}

		import('payment.ocs.OCSPaymentManager');
		$paymentManager =& OCSPaymentManager::getManager();

		if (!$paymentManager->isConfigured()) return false;

		import('registration.Registration');
		$registration = &new Registration();
		
		$registration->setSchedConfId($schedConf->getSchedConfId());
		$registration->setUserId($user->getUserId());
		$registration->setTypeId($this->getData('registrationTypeId'));
		$registration->setSpecialRequests($this->getData('specialRequests') ? $this->getData('specialRequests') : null);
		$registration->setDateRegistered(time());

		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$registrationId = $registrationDao->insertRegistration($registration);

		$queuedPayment =& $paymentManager->createQueuedPayment($schedConf->getConferenceId(), $schedConf->getSchedConfId(), QUEUED_PAYMENT_TYPE_REGISTRATION, $user->getUserId(), $registrationId, $registrationType->getCost(), $registrationType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);

		return true;
	}
}

?>
