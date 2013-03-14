<?php

/**
 * @file UserRegistrationForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserRegistrationForm
 * @ingroup registration_form
 *
 * @brief Form for users to self-register.
 */


import('lib.pkp.classes.form.Form');

define('REGISTRATION_SUCCESSFUL',	1);
define('REGISTRATION_FAILED',		2);
define('REGISTRATION_NO_PAYMENT',	3);
define('REGISTRATION_FREE',		4);

class UserRegistrationForm extends Form {
	/** @var $request PKPRequest */
	var $request;

	/** @var captchaEnabled boolean whether or not captcha is enabled for this form */
	var $captchaEnabled;

	/** @var $typeId int The registration type ID for this registration */
	var $typeId;

	/** @var $_registration object */
	var $_registration;

	/** @var $_queuedPayment object */
	var $_queuedPayment;

	/**
	 * Constructor
	 * @param $typeId int Registration type to use
	 * @param $registration object optional registration option if one already exists
	 * @param $request PKPRequest
	 */
	function UserRegistrationForm($typeId, $registration, &$request) {
		$schedConf =& $request->getSchedConf();

		$this->typeId = (int) $typeId;
		$this->_registration = $registration;
		$this->request =& $request;

		parent::Form('registration/userRegistrationForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'registrationTypeId', 'required', 'manager.registration.form.typeIdValid', create_function('$registrationTypeId, $schedConfId, $typeId', '$registrationTypeDao = DAORegistry::getDAO(\'RegistrationTypeDAO\'); return $registrationTypeDao->openRegistrationTypeExistsByTypeId($typeId, $schedConfId);'), array($schedConf->getId(), $typeId)));

		$this->captchaEnabled = Config::getVar('captcha', 'captcha_on_register') && Config::getVar('captcha', 'recaptcha');

		$user =& $request->getUser();
		if (!$user) {
			$site =& $request->getSite();
			$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
			$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));

			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.account.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
			$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.account.form.usernameAlphaNumeric'));
			$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.account.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.account.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
			$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
			$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
			$this->addCheck(new FormValidator($this, 'country', 'required', 'user.profile.form.countryRequired'));
			$this->addCheck(new FormValidator($this, 'mailingAddress', 'required', 'user.profile.form.mailingAddressRequired'));
			$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
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

	function validate() {
		$schedConf =& $this->request->getSchedConf();
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationType =& $registrationTypeDao->getRegistrationType($this->getData('registrationTypeId'));
		if ($registrationType && $registrationType->getCode() != '') {
			$this->addCheck(new FormValidatorCustom($this, 'feeCode', 'required', 'manager.registration.form.feeCodeValid', create_function('$feeCode, $schedConfId, $form', '$registrationTypeDao = DAORegistry::getDAO(\'RegistrationTypeDAO\'); return $registrationTypeDao->checkCode($form->getData(\'registrationTypeId\'), $schedConfId, $feeCode);'), array($schedConf->getId(), $this)));
		}
		return parent::validate();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$user =& $this->request->getUser();
		$schedConf =& $this->request->getSchedConf();
		$site =& $this->request->getSite();

		$userDao = DAORegistry::getDAO('UserDAO');
		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());
		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationOptions =& $registrationOptionDao->getRegistrationOptionsBySchedConfId($schedConf->getId());
		$templateMgr->assign_by_ref('registrationOptions', $registrationOptions);
		$templateMgr->assign('registrationTypeId', $this->typeId);
		$templateMgr->assign('registration', $this->_registration);

		$templateMgr->assign('userLoggedIn', $user?true:false);
		$templateMgr->assign('requestUri', $_SERVER['REQUEST_URI']);
		if ($user) {
			$templateMgr->assign('userFullName', $user->getFullName());

		}
		if ($this->captchaEnabled) {
			import('lib.pkp.lib.recaptcha.recaptchalib');
			$publicKey = Config::getVar('captcha', 'recaptcha_public_key');
			$useSSL = Config::getVar('security', 'force_ssl')?true:false;
			$reCaptchaHtml = recaptcha_get_html($publicKey, null, $useSSL);
			$templateMgr->assign('reCaptchaHtml', $reCaptchaHtml);
			$templateMgr->assign('captchaEnabled', true);
		}

		// Provide countries to template
		$countryDao = DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		// Provide registration option costs to template
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationOptionCosts = $registrationTypeDao->getRegistrationOptionCosts($this->typeId);
		$templateMgr->assign('registrationOptionCosts', $registrationOptionCosts);

		// Provide registration type to template
		$registrationType =& $registrationTypeDao->getRegistrationType($this->typeId);
		$templateMgr->assign_by_ref('registrationType', $registrationType);

		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$templateMgr->assign_by_ref('user', $user);

		parent::display();
	}

	function getLocaleFieldNames() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getLocaleFieldNames();
	}

	function initData() {
		parent::initData();

		// Provide preselected registration options to template, if available
		if ($this->_registration) {
			$registration =& $this->_registration;
			$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
			$registrationOptionIds = $registrationOptionDao->getRegistrationOptions($registration->getRegistrationId());
			$this->_data['registrationOptionId'] = $registrationOptionIds;
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array('registrationTypeId', 'specialRequests', 'feeCode', 'registrationOptionId');

		$user =& $this->request->getUser();
		if (!$user) {
			$userVars[] = 'username';
			$userVars[] = 'salutation';
			$userVars[] = 'password';
			$userVars[] = 'password2';
			$userVars[] = 'firstName';
			$userVars[] = 'middleName';
			$userVars[] = 'captcha';
			$userVars[] = 'lastName';
			$userVars[] = 'initials';
			$userVars[] = 'gender';
			$userVars[] = 'affiliation';
			$userVars[] = 'signature';
			$userVars[] = 'email';
			$userVars[] = 'userUrl';
			$userVars[] = 'phone';
			$userVars[] = 'fax';
			$userVars[] = 'mailingAddress';
			$userVars[] = 'billingAddress';
			$userVars[] = 'country';
			$userVars[] = 'biography';
			$userVars[] = 'userLocales';
		}

		if ($this->captchaEnabled) {
			$userVars[] = 'recaptcha_challenge_field';
			$userVars[] = 'recaptcha_response_field';
		}

		$this->readUserVars($userVars);

		// If registration type requires it, membership is provided
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
		$needMembership = $registrationTypeDao->getRegistrationTypeMembership($this->getData('typeId'));
	}

	/**
	 * Save registration.
	 */
	function execute() {
		$schedConf =& $this->request->getSchedConf();
		$user =& $this->request->getUser();

		$registrationOptionIds = (array) $this->getData('registrationOptionId');

		if (!$user) {
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

			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));

			$userDao = DAORegistry::getDAO('UserDAO');
			$userId = $userDao->insertObject($user);
			if (!$userId) {
				return REGISTRATION_FAILED;
			}

			$conference =& $this->request->getConference();
			$roleDao = DAORegistry::getDAO('RoleDAO');
			$role = new Role();
			$role->setRoleId(ROLE_ID_READER);
			$role->setSchedConfId($schedConf->getId());
			$role->setConferenceId($conference->getId());
			$role->setUserId($user->getId());
			$roleDao->insertRole($role);

			$sessionManager =& SessionManager::getManager();
			$session =& $sessionManager->getUserSession();
			$session->setSessionVar('username', $user->getUsername());

			// Make sure subsequent requests to Request::getUser work
			Validation::login($this->getData('username'), $this->getData('password'), $reason);

			import('classes.user.form.CreateAccountForm');
			CreateAccountForm::sendConfirmationEmail($user, $this->getData('password'), true);
		}

		// Get the registration type
		$registrationDao = DAORegistry::getDAO('RegistrationDAO');
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationType =& $registrationTypeDao->getRegistrationType($this->getData('registrationTypeId'));
		if (!$registrationType || $registrationType->getSchedConfId() != $schedConf->getId()) {
			$this->request->redirect('index');
		}

		import('classes.payment.ocs.OCSPaymentManager');
		$paymentManager = new OCSPaymentManager($this->request);

		if (!$paymentManager->isConfigured()) return REGISTRATION_NO_PAYMENT;

		if ($this->_registration) {
			// An existing registration was already in place. Compare and notify someone.
			$oldRegistration =& $this->_registration;
			$oldRegistrationType =& $registrationTypeDao->getRegistrationType($oldRegistration->getTypeId());
			unset($this->_registration);

			import('mail.MailTemplate');
			$mail = new MailTemplate('USER_REGISTRATION_CHANGE');
			$mail->setFrom($schedConf->getSetting('registrationEmail'), $schedConf->getSetting('registrationName'));
			$mail->addRecipient($schedConf->getSetting('registrationEmail'), $schedConf->getSetting('registrationName'));

			$optionsDiffer = '';
			$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
			$registrationOptionIterator =& $registrationOptionDao->getRegistrationOptionsBySchedConfId($schedConf->getId());
			$oldRegistrationOptionIds = $registrationOptionDao->getRegistrationOptions($oldRegistration->getRegistrationId());
			while ($registrationOption =& $registrationOptionIterator->next()) {
				$optionId = $registrationOption->getOptionId();
				$previouslyChosen = in_array($optionId, $oldRegistrationOptionIds);
				$newlyChosen = in_array($optionId, $registrationOptionIds);
				if ($previouslyChosen && !$newlyChosen) {
					$optionsDiffer .= __('schedConf.registrationOptions.removed', array('option' => $registrationOption->getRegistrationOptionName())) . "\n";
				} elseif (!$previouslyChosen && $newlyChosen) {
					$optionsDiffer .= __('schedConf.registrationOptions.added', array('option' => $registrationOption->getRegistrationOptionName())) . "\n";
				}
				unset($registrationOption);
			}

			$mail->assignParams(array(
				'managerName' => $schedConf->getSetting('registrationName'),
				'registrationId' => $oldRegistration->getRegistrationId(),
				'registrantName' => $user->getFullName(),
				'oldRegistrationType' => $oldRegistrationType->getSummaryString(),
				'newRegistrationType' => $registrationType->getSummaryString(),
				'differingOptions' => $optionsDiffer,
				'username' => $user->getUsername(),
				'registrationContactSignature' => $schedConf->getSetting('registrationName')
			));
			$mail->send();

			$registrationDao->deleteRegistrationById($oldRegistration->getRegistrationId());
		}

		import('classes.registration.Registration');
		$registration = new Registration();

		$registration->setSchedConfId($schedConf->getId());
		$registration->setUserId($user->getId());
		$registration->setTypeId($this->getData('registrationTypeId'));
		$registration->setSpecialRequests($this->getData('specialRequests') ? $this->getData('specialRequests') : null);
		$registration->setDateRegistered(time());

		$registrationId = $registrationDao->insertRegistration($registration);

		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationOptions =& $registrationOptionDao->getRegistrationOptionsBySchedConfId($schedConf->getId());

		$cost = $registrationType->getCost();
		$registrationOptionCosts = $registrationTypeDao->getRegistrationOptionCosts($this->getData('registrationTypeId'));

		while ($registrationOption =& $registrationOptions->next()) {
			if (
				in_array($registrationOption->getOptionId(), $registrationOptionIds) &&
				strtotime($registrationOption->getOpeningDate()) < time() &&
				strtotime($registrationOption->getClosingDate()) > time() &&
				$registrationOption->getPublic()
			) {
				$registrationOptionDao->insertRegistrationOptionAssoc($registrationId, $registrationOption->getOptionId());
				$cost += $registrationOptionCosts[$registrationOption->getOptionId()];
			}
			unset($registrationOption);
		}

		$queuedPayment =& $paymentManager->createQueuedPayment($schedConf->getConferenceId(), $schedConf->getId(), QUEUED_PAYMENT_TYPE_REGISTRATION, $user->getId(), $registrationId, $cost, $registrationType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment, time() + (60 * 60 * 24 * 30)); // 30 days to complete

		if ($cost == 0) {
			$paymentManager->fulfillQueuedPayment($this->request, $queuedPaymentId, $queuedPayment);
			return REGISTRATION_FREE;
		} else {
			$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
		}

		$this->_registration =& $registration;
		$this->_queuedPayment =& $queuedPayment;

		return REGISTRATION_SUCCESSFUL;
	}

	/**
	 * After a successful registration, get the registration object.
	 * @return object Registration
	 */
	function &getRegistration() {
		return $this->_registration;
	}

	/**
	 * After a successful registration, get the queued payment object.
	 * @return object Registration
	 */
	function &getQueuedPayment() {
		return $this->_queuedPayment;
	}
}

?>
