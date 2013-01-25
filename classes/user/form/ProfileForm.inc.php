<?php

/**
 * @file classes/user/form/ProfileForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProfileForm
 * @ingroup user_form
 *
 * @brief Form to edit user profile.
 */

import('lib.pkp.classes.user.form.PKPProfileForm');

class ProfileForm extends PKPProfileForm {
	/**
	 * Constructor.
	 * @param $user PKPUser
	 */
	function ProfileForm($user) {
		parent::PKPProfileForm('user/profile.tpl', $user);

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidator($this, 'affiliation', 'required', 'user.profile.form.affiliationRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.account.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array($user->getId(), true), true));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display($request) {
		$templateMgr = TemplateManager::getManager($request);

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$schedConfs = $schedConfDao->getAll();
		$schedConfs = $schedConfs->toArray();

		foreach ($schedConfs as $thisSchedConf) {
			if ($thisSchedConf->getSetting('enableOpenAccessNotification') == true) {
				$templateMgr->assign('displayOpenAccessNotification', true);
				$templateMgr->assign_by_ref('user', $user);
				break;
			}
		}

		$templateMgr->assign_by_ref('schedConfs', $schedConfs);
		$templateMgr->assign('helpTopicId', 'conference.users.index');

		$schedConf = $request->getSchedConf();
		if ($schedConf) {
			$roleDao = DAORegistry::getDAO('RoleDAO');
			$roles = $roleDao->getRolesByUserId($user->getId(), $schedConf->getId());
			$roleNames = array();
			foreach ($roles as $role) $roleNames[$role->getPath()] = $role->getRoleName();
			import('classes.schedConf.SchedConfAction');
			$templateMgr->assign('allowRegReviewer', SchedConfAction::allowRegReviewer($schedConf));
			$templateMgr->assign('allowRegAuthor', SchedConfAction::allowRegAuthor($schedConf));
			$templateMgr->assign('allowRegReader', SchedConfAction::allowRegReader($schedConf));
			$templateMgr->assign('roles', $roleNames);
		}

		$timeZoneDao = DAORegistry::getDAO('TimeZoneDAO');
		$timeZones = $timeZoneDao->getTimeZones();
		$templateMgr->assign_by_ref('timeZones', $timeZones);

		parent::display($request);
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$user = $this->getUser();

		import('lib.pkp.classes.user.InterestManager');
		$interestManager = new InterestManager();

		parent::initData();

		$this->_data += array(
			'timeZone' => $user->getTimeZone(),
			'isAuthor' => Validation::isAuthor(),
			'isReader' => Validation::isReader(),
			'isReviewer' => Validation::isReviewer(),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array(
			'timeZone',
			'readerRole',
			'authorRole',
			'reviewerRole'
		));
	}

	/**
	 * Save profile settings.
	 */
	function execute($request) {
		$user = $this->getUser();

		$user->setTimeZone($this->getData('timeZone'));

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');

		// Roles
		$schedConf = Request::getSchedConf();
		if ($schedConf) {
			import('classes.schedConf.SchedConfAction');
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

		$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
		$schedConfs = $schedConfDao->getAll();
		$schedConfs = $schedConfs->toArray();

		foreach ($schedConfs as $thisSchedConf) {
			if ($thisSchedConf->getSetting('enableOpenAccessNotification') == true) {
				$currentlyReceives = $user->getSetting('openAccessNotification', $thisSchedConf->getId());
				$shouldReceive = !empty($openAccessNotify) && in_array($thisSchedConf->getId(), $openAccessNotify);
				if ($currentlyReceives != $shouldReceive) {
					$userSettingsDao->updateSetting($user->getId(), 'openAccessNotification', $shouldReceive, 'bool', $thisSchedConf->getId());
				}
			}
		}

		parent::execute($request);
	}
}

?>
