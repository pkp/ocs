<?php

/**
 * @defgroup trackDirector_form
 */

/**
 * @file CreateReviewerForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateReviewerForm
 * @ingroup trackDirector_form
 *
 * @brief Form for track directors to create reviewers.
 *
 */

// $Id$


import('form.Form');

class CreateReviewerForm extends Form {
	/** @var int The paper this form is for */
	var $paperId;

	/**
	 * Constructor.
	 */
	function CreateReviewerForm($paperId) {
		parent::Form('trackDirector/createReviewerForm.tpl');

		$site =& Request::getSite();
		$this->paperId = $paperId;

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.account.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(null, true), true));
		$this->addCheck(new FormValidatorAlphaNum($this, 'username', 'required', 'user.account.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.account.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(null, true), true));
		$this->addCheck(new FormValidatorPost($this));

		// Provide a default for sendNotify: If we're using one-click
		// reviewer access, it's not necessary;
		// otherwise, it should default to on.
		$schedConf =& Request::getSchedConf();
		$reviewerAccessKeysEnabled = $schedConf->getSetting('reviewerAccessKeysEnabled');
		$this->setData('sendNotify', $reviewerAccessKeysEnabled?false:true);
	}

	function getLocaleFieldNames() {
		return array('biography', 'interests', 'gossip');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$site =& Request::getSite();
		$templateMgr->assign('paperId', $this->paperId);

		$site =& Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		$userDao =& DAORegistry::getDAO('UserDAO');
		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'salutation',
			'firstName',
			'middleName',
			'lastName',
			'gender',
			'initials',
			'affiliation',
			'email',
			'userUrl',
			'phone',
			'fax',
			'mailingAddress',
			'country',
			'biography',
			'interests',
			'gossip',
			'userLocales',
			'sendNotify',
			'username'
		));

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
	 * @return $userId int
	 */
	function execute() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user = new User();

		$user->setSalutation($this->getData('salutation'));
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setGender($this->getData('gender'));
		$user->setInitials($this->getData('initials'));
		$user->setAffiliation($this->getData('affiliation'));
		$user->setEmail($this->getData('email'));
		$user->setUrl($this->getData('userUrl'));
		$user->setPhone($this->getData('phone'));
		$user->setFax($this->getData('fax'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setCountry($this->getData('country'));
		$user->setBiography($this->getData('biography'), null); // Localized
		$user->setInterests($this->getData('interests'), null); // Localized
		$user->setGossip($this->getData('gossip'), null); // Localized
		$user->setMustChangePassword($this->getData('mustChangePassword') ? 1 : 0);

		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$auth =& $authDao->getDefaultPlugin();
		$user->setAuthId($auth?$auth->getAuthId():0);

		$site =& Request::getSite();
		$availableLocales = $site->getSupportedLocales();

		$locales = array();
		foreach ($this->getData('userLocales') as $locale) {
			if (AppLocale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
				array_push($locales, $locale);
			}
		}
		$user->setLocales($locales);

		$user->setUsername($this->getData('username'));
		$password = Validation::generatePassword();
		$sendNotify = $this->getData('sendNotify');

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
		$userId = $userDao->insertUser($user);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$schedConf =& Request::getSchedConf();
		$role = new Role();
		$role->setConferenceId($schedConf->getConferenceId());
		$role->setSchedConfId($schedConf->getId());
		$role->setUserId($userId);
		$role->setRoleId(ROLE_ID_REVIEWER);
		$roleDao->insertRole($role);

		if ($sendNotify) {
			// Send welcome email to user
			import('mail.MailTemplate');
			$mail = new MailTemplate('USER_REGISTER');
			$mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
			$mail->assignParams(array('username' => $this->getData('username'), 'password' => $password));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
		}

		return $userId;
	}
}

?>
