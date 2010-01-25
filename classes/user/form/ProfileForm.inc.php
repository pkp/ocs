<?php

/**
 * @file classes/user/form/ProfileForm.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProfileForm
 * @ingroup user_form
 *
 * @brief Form to edit user profile.
 */

//$Id$


import('form.Form');

class ProfileForm extends Form {

	/** @var $user object */
	var $user;

	/**
	 * Constructor.
	 */
	function ProfileForm() {
		parent::Form('user/profile.tpl');

		$user =& Request::getUser();
		$this->user =& $user;

		$site =& Request::getSite();

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.account.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($user->getId(), true), true));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Deletes a profile image.
	 */
	function deleteProfileImage() {
		$user =& Request::getUser();
		$profileImage = $user->getSetting('profileImage');
		if (!$profileImage) return false;

		import('file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->removeSiteFile($profileImage['uploadName'])) {
			return $user->updateSetting('profileImage', null);
		} else {
			return false;
		}
	}

	function uploadProfileImage() {
		import('file.PublicFileManager');
		$fileManager = new PublicFileManager();

		$user =& $this->user;

		$type = $fileManager->getUploadedFileType('profileImage');
		$extension = $fileManager->getImageExtension($type);
		if (!$extension) return false;

		$uploadName = 'profileImage-' . (int) $user->getId() . $extension;
		if ($fileManager->uploadError('profileImage')) return false;
		if (!$fileManager->uploadSiteFile('profileImage', $uploadName)) return false;

		$filePath = $fileManager->getSiteFilesPath();
		list($width, $height) = getimagesize($filePath . '/' . $uploadName);

		if ($width > 150 || $height > 150 || $width <= 0 || $height <= 0) {
			$userSetting = null;
			$user->updateSetting('profileImage', $userSetting);
			$fileManager->removeSiteFile($filePath);
			return false;
		}

		$userSetting = array(
			'name' => $fileManager->getUploadedFileName('profileImage'),
			'uploadName' => $uploadName,
			'width' => $width,
			'height' => $height,
			'dateUploaded' => Core::getCurrentDate()
		);

		$user->updateSetting('profileImage', $userSetting);
		return true;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$user =& Request::getUser();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('username', $user->getUsername());

		$site =& Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');		

		$schedConfs =& $schedConfDao->getEnabledSchedConfs();
		$schedConfs =& $schedConfs->toArray();

		foreach ($schedConfs as $thisSchedConf) {
			if ($thisSchedConf->getSetting('enableOpenAccessNotification') == true) {
				$templateMgr->assign('displayOpenAccessNotification', true);
				$templateMgr->assign_by_ref('user', $user);
				break;
			}
		}

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		
		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());

		$templateMgr->assign_by_ref('schedConfs', $schedConfs);
		$templateMgr->assign_by_ref('countries', $countries);
		$templateMgr->assign('helpTopicId', 'conference.users.index');

		$schedConf =& Request::getSchedConf();
		if ($schedConf) {
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roles =& $roleDao->getRolesByUserId($user->getId(), $schedConf->getId());
			$roleNames = array();
			foreach ($roles as $role) $roleNames[$role->getRolePath()] = $role->getRoleName();
			import('schedConf.SchedConfAction');
			$templateMgr->assign('allowRegReviewer', SchedConfAction::allowRegReviewer($schedConf));
			$templateMgr->assign('allowRegAuthor', SchedConfAction::allowRegAuthor($schedConf));
			$templateMgr->assign('allowRegReader', SchedConfAction::allowRegReader($schedConf));
			$templateMgr->assign('roles', $roleNames);
		}

		$timeZoneDao =& DAORegistry::getDAO('TimeZoneDAO');
		$timeZones =& $timeZoneDao->getTimeZones();
		$templateMgr->assign_by_ref('timeZones', $timeZones);

		$templateMgr->assign('profileImage', $user->getSetting('profileImage'));

		parent::display();
	}

	function getLocaleFieldNames() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getLocaleFieldNames();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$user =& Request::getUser();

		$this->_data = array(
			'salutation' => $user->getSalutation(),
			'firstName' => $user->getFirstName(),
			'middleName' => $user->getMiddleName(),
			'initials' => $user->getInitials(),
			'lastName' => $user->getLastName(),
			'gender' => $user->getGender(),
			'affiliation' => $user->getAffiliation(),
			'signature' => $user->getSignature(null), // Localized
			'email' => $user->getEmail(),
			'userUrl' => $user->getUrl(),
			'phone' => $user->getPhone(),
			'fax' => $user->getFax(),
			'mailingAddress' => $user->getMailingAddress(),
			'country' => $user->getCountry(),
			'timeZone' => $user->getTimeZone(),
			'biography' => $user->getBiography(null), // Localized
			'interests' => $user->getInterests(null), // Localized
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
			'salutation',
			'firstName',
			'middleName',
			'lastName',
			'gender',
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
		$user =& Request::getUser();

		$user->setSalutation($this->getData('salutation'));
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setGender($this->getData('gender'));
		$user->setInitials($this->getData('initials'));
		$user->setAffiliation($this->getData('affiliation'));
		$user->setSignature($this->getData('signature'), null); // Localized
		$user->setEmail($this->getData('email'));
		$user->setUrl($this->getData('userUrl'));
		$user->setPhone($this->getData('phone'));
		$user->setFax($this->getData('fax'));
		$user->setMailingAddress($this->getData('mailingAddress'));
		$user->setCountry($this->getData('country'));
		$user->setTimeZone($this->getData('timeZone'));
		$user->setBiography($this->getData('biography'), null); // Localized
		$user->setInterests($this->getData('interests'), null); // Localized

		$site =& Request::getSite();
		$availableLocales = $site->getSupportedLocales();

		$locales = array();
		foreach ($this->getData('userLocales') as $locale) {
			if (Locale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
				array_push($locales, $locale);
			}
		}
		$user->setLocales($locales);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$userDao->updateObject($user);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		// Roles
		$schedConf =& Request::getSchedConf();
		if ($schedConf) {
			import('schedConf.SchedConfAction');
			$role = new Role();
			$role->setUserId($user->getId());
			$role->setConferenceId($schedConf->getConferenceId());
			$role->setSchedConfId($schedConf->getId());
			if (SchedConfAction::allowRegReviewer($schedConf)) {
				$role->setRoleId(ROLE_ID_REVIEWER);
				$hasRole = Validation::isReviewer();
				$wantsRole = Request::getUserVar('reviewerRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
			if (SchedConfAction::allowRegAuthor($schedConf)) {
				$role->setRoleId(ROLE_ID_AUTHOR);
				$hasRole = Validation::isAuthor();
				$wantsRole = Request::getUserVar('authorRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
			if (SchedConfAction::allowRegReader($schedConf)) {
				$role->setRoleId(ROLE_ID_READER);
				$hasRole = Validation::isReader();
				$wantsRole = Request::getUserVar('readerRole');
				if ($hasRole && !$wantsRole) $roleDao->deleteRole($role);
				if (!$hasRole && $wantsRole) $roleDao->insertRole($role);
			}
		}

		$openAccessNotify = Request::getUserVar('openAccessNotify');

		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
		$schedConfs =& $schedConfDao->getSchedConfs();
		$schedConfs =& $schedConfs->toArray();

		foreach ($schedConfs as $thisSchedConf) {
			if ($thisSchedConf->getSetting('enableOpenAccessNotification') == true) {
				$currentlyReceives = $user->getSetting('openAccessNotification', $thisSchedConf->getId());
				$shouldReceive = !empty($openAccessNotify) && in_array($thisSchedConf->getId(), $openAccessNotify);
				if ($currentlyReceives != $shouldReceive) {
					$userSettingsDao->updateSetting($user->getId(), 'openAccessNotification', $shouldReceive, 'bool', $thisSchedConf->getId());
				}
			}
		}

		if ($user->getAuthId()) {
			$authDao =& DAORegistry::getDAO('AuthSourceDAO');
			$auth =& $authDao->getPlugin($user->getAuthId());
		}

		if (isset($auth)) {
			$auth->doSetUserInfo($user);
		}
	}
}

?>
