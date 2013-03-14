<?php

/**
 * @file UserManagementForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserManagementForm
 * @ingroup manager_form
 *
 * @brief Form for conference managers to edit user profiles.
 *
 */



import('lib.pkp.classes.form.Form');

class UserManagementForm extends Form {

	/** The ID of the user being edited */
	var $userId;

	/**
	 * Constructor.
	 */
	function UserManagementForm($userId = null) {
		parent::Form('manager/people/userProfileForm.tpl');

		$this->userId = isset($userId) ? (int) $userId : null;
		$site =& Request::getSite();

		// Validation checks for this form
		if ($userId == null) {
			$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.account.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array($this->userId, true), true));
			$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.account.form.usernameAlphaNumeric'));
			$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));
			$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.account.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.account.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		} else {
			$this->addCheck(new FormValidatorLength($this, 'password', 'optional', 'user.account.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
			$this->addCheck(new FormValidatorCustom($this, 'password', 'optional', 'user.account.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		}
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.account.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($this->userId, true), true));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$userDao = DAORegistry::getDAO('UserDAO');
		$templateMgr =& TemplateManager::getManager();
		$site =& Request::getSite();
		$schedConf =& Request::getSchedConf();

		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$templateMgr->assign('source', Request::getUserVar('source'));
		$templateMgr->assign('userId', $this->userId);
		if (isset($this->userId)) {
			$user =& $userDao->getById($this->userId);
			$templateMgr->assign('username', $user->getUsername());
			$helpTopicId = 'conference.users.index';
		} else {
			$helpTopicId = 'conference.users.createNewUser';
		}

		if($schedConf) {
			$templateMgr->assign('roleOptions',
				array(
					'' => 'manager.people.doNotEnroll',
					'director' => 'user.role.director',
					'trackDirector' => 'user.role.trackDirector',
					'reviewer' => 'user.role.reviewer',
					'author' => 'user.role.author',
					'reader' => 'user.role.reader'
				)
			);
		} else {
			$templateMgr->assign('roleOptions',
				array(
					'' => 'manager.people.doNotEnroll',
					'manager' => 'user.role.manager',
				)
			);
		}
		$site =& Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());

		$templateMgr->assign('helpTopicId', $helpTopicId);

		$countryDao = DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		$authDao = DAORegistry::getDAO('AuthSourceDAO');
		$authSources =& $authDao->getSources();
		$authSourceOptions = array();
		foreach ($authSources->toArray() as $auth) {
			$authSourceOptions[$auth->getAuthId()] = $auth->getTitle();
		}
		if (!empty($authSourceOptions)) {
			$templateMgr->assign('authSourceOptions', $authSourceOptions);
		}
		parent::display();
	}

	/**
	 * Initialize form data from current user profile.
	 */
	function initData(&$args, &$request) {
		if (isset($this->userId)) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getById($this->userId);

			import('lib.pkp.classes.user.InterestManager');
			$interestManager = new InterestManager();

			if ($user != null) {
				$this->_data = array(
					'authId' => $user->getAuthId(),
					'username' => $user->getUsername(),
					'salutation' => $user->getSalutation(),
					'firstName' => $user->getFirstName(),
					'middleName' => $user->getMiddleName(),
					'lastName' => $user->getLastName(),
					'initials' => $user->getInitials(),
					'gender' => $user->getGender(),
					'affiliation' => $user->getAffiliation(null), // Localized
					'email' => $user->getEmail(),
					'userUrl' => $user->getUrl(),
					'phone' => $user->getPhone(),
					'fax' => $user->getFax(),
					'mailingAddress' => $user->getMailingAddress(),
					'billingAddress' => $user->getBillingAddress(),
					'country' => $user->getCountry(),
					'biography' => $user->getBiography(null), // Localized
					'interestsKeywords' => $interestManager->getInterestsForUser($user),
					'interestsTextOnly' => $interestManager->getInterestsString($user),
					'gossip' => $user->getGossip(null), // Localized
					'signature' => $user->getSignature(null), // Localized
					'userLocales' => $user->getLocales()
				);

			} else {
				$this->userId = null;
			}
		}
		if (!isset($this->userId)) {
			$roleDao = DAORegistry::getDAO('RoleDAO');
			$roleId = Request::getUserVar('roleId');
			$role =& $roleDao->newDataObject();
			$role->setId($roleId);
			$roleSymbolic = $role->getPath();

			$this->_data = array(
				'enrollAs' => array($roleSymbolic)
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'authId',
			'enrollAs',
			'password',
			'password2',
			'salutation',
			'firstName',
			'middleName',
			'lastName',
			'gender',
			'initials',
			'affiliation',
			'email',
			'phone',
			'fax',
			'mailingAddress',
			'billingAddress',
			'country',
			'userUrl',
			'biography',
			'interestsTextOnly',
			'keywords',
			'gossip',
			'signature',
			'userLocales',
			'generatePassword',
			'sendNotify',
			'mustChangePassword'
		));
		if ($this->userId == null) {
			$this->readUserVars(array('username'));
		}

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

	function getLocaleFieldNames() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getLocaleFieldNames();
	}

	/**
	 * Register a new user.
	 */
	function execute() {
		$userDao = DAORegistry::getDAO('UserDAO');
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if (isset($this->userId)) {
			$user =& $userDao->getById($this->userId);
		}

		if (!isset($user)) {
			$user = new User();
		}

		$user->setSalutation($this->getData('salutation'));
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setInitials($this->getData('initials'));
		$user->setGender($this->getData('gender'));
		$user->setAffiliation($this->getData('affiliation'), null); // Localized
		$user->setEmail($this->getData('email'));
		$user->setUrl($this->getData('userUrl'));
		$user->setPhone($this->getData('phone'));
		$user->setFax($this->getData('fax'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setBillingAddress($this->getData('billingAddress'));
		$user->setCountry($this->getData('country'));
		$user->setBiography($this->getData('biography'), null); // Localized
		$user->setGossip($this->getData('gossip'), null); // Localized
		$user->setSignature($this->getData('signature'), null); // Localized
		$user->setMustChangePassword($this->getData('mustChangePassword') ? 1 : 0);
		$user->setAuthId((int) $this->getData('authId'));

		$site =& Request::getSite();
		$availableLocales = $site->getSupportedLocales();

		$locales = array();
		foreach ($this->getData('userLocales') as $locale) {
			if (AppLocale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
				array_push($locales, $locale);
			}
		}
		$user->setLocales($locales);

		if ($user->getAuthId()) {
			$authDao = DAORegistry::getDAO('AuthSourceDAO');
			$auth =& $authDao->getPlugin($user->getAuthId());
		}

		if ($user->getId() != null) {
			$userId = $user->getId();
			if ($this->getData('password') !== '') {
				if (isset($auth)) {
					$auth->doSetUserPassword($user->getUsername(), $this->getData('password'));
					$user->setPassword(Validation::encryptCredentials($userId, Validation::generatePassword())); // Used for PW reset hash only
				} else {
					$user->setPassword(Validation::encryptCredentials($user->getUsername(), $this->getData('password')));
				}
			}

			if (isset($auth)) {
				// FIXME Should try to create user here too?
				$auth->doSetUserInfo($user);
			}

			$userDao->updateObject($user);

		} else {
			$user->setUsername($this->getData('username'));
			if ($this->getData('generatePassword')) {
				$password = Validation::generatePassword();
				$sendNotify = true;
			} else {
				$password = $this->getData('password');
				$sendNotify = $this->getData('sendNotify');
			}

			if (isset($auth)) {
				$user->setPassword($password);
				// FIXME Check result and handle failures
				$auth->doCreateUser($user);
				$user->setAuthId($auth->authId);
				$user->setPassword(Validation::encryptCredentials($user->getId(), Validation::generatePassword())); // Used for PW reset hash only
			} else {
				$user->setPassword(Validation::encryptCredentials($this->getData('username'), $password));
			}

			$user->setDateRegistered(Core::getCurrentDate());
			$userId = $userDao->insertObject($user);

			if (!empty($this->_data['enrollAs'])) {
				foreach ($this->getData('enrollAs') as $roleName) {
					// Enroll new user into an initial role
					$roleDao = DAORegistry::getDAO('RoleDAO');
					$roleId = $roleDao->getRoleIdFromPath($roleName);
					if ($roleId != null) {
						$role = new Role();
						$role->setConferenceId($conference->getId());
						$role->setSchedConfId($schedConf?$schedConf->getId():0);
						$role->setUserId($userId);
						$role->setRoleId($roleId);
						$roleDao->insertRole($role);
					}
				}
			}

			if ($sendNotify) {
				// Send welcome email to user
				import('classes.mail.MailTemplate');
				$mail = new MailTemplate('USER_REGISTER');

				if ($schedConf) $mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
				elseif ($conference) $mail->setFrom($conference->getSetting('contactEmail'), $conference->getSetting('contactName'));
				else {
					$site =& Request::getSite();
					$mail->setFrom($site->getContactEmail(), $site->getContactName());
				}

				$mail->assignParams(array('username' => $this->getData('username'), 'password' => $password));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
			}
		}

		// Insert the user interests
		$interests = $this->getData('interestsKeywords') ? $this->getData('interestsKeywords') : $this->getData('interestsTextOnly');
		import('lib.pkp.classes.user.InterestManager');
		$interestManager = new InterestManager();
		$interestManager->setInterestsForUser($user, $interests);
	}
}

?>
