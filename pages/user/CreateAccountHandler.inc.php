<?php

/**
 * @file CreateAccountHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateAccountHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user account creation. 
 */

//$Id$

import('pages.user.UserHandler');

class CreateAccountHandler extends UserHandler {
	/**
	 * Constructor
	 **/
	function CreateAccountHandler() {
		parent::UserHandler();
	}

	/**
	 * Display account form for new users.
	 */
	function account() {
		$this->validate();
		$this->setupTemplate(true);
		
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if ($conference != null && $schedConf != null) {

			// We're trying to create an account for a specific scheduled conference
			import('user.form.CreateAccountForm');

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$regForm = new CreateAccountForm();
			} else {
				$regForm =& new CreateAccountForm();
			}
			if ($regForm->isLocaleResubmit()) {
				$regForm->readInputData();
			} else {
				$regForm->initData();
			}
			$regForm->display();

		} elseif ($conference != null) {

			// We have the conference, but need to select a scheduled conference
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConfs =& $schedConfDao->getEnabledSchedConfs($conference->getId());

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('pageHierarchy', array(
				array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true)));
			$templateMgr->assign('source', Request::getUserVar('source'));
			$templateMgr->assign_by_ref('schedConfs', $schedConfs);
			$templateMgr->display('user/createAccountConference.tpl');

		} else {

			// We have neither conference nor scheduled conference; start by selecting a
			// conference and we'll end up above after a redirect.

			$conferencesDao =& DAORegistry::getDAO('ConferenceDAO');
			$conferences =& $conferencesDao->getEnabledConferences();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('source', Request::getUserVar('source'));
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->display('user/createAccountSite.tpl');
		}
	}

	/**
	 * Validate user information and create new user.
	 */
	function createAccount() {
		$this->validate();
		$this->setupTemplate(true);
		import('user.form.CreateAccountForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$regForm = new CreateAccountForm();
		} else {
			$regForm =& new CreateAccountForm();
		}
		$regForm->readInputData();

		if ($regForm->validate()) {
			$regForm->execute();
			if (Config::getVar('email', 'require_validation')) {
				// Send them home; they need to deal with the
				// registration email.
				Request::redirect(null, 'index');
			}
			Validation::login($regForm->getData('username'), $regForm->getData('password'), $reason);
			if ($reason !== null) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'user.login');
				$templateMgr->assign('errorMsg', $reason==''?'user.login.accountDisabled':'user.login.accountDisabledWithReason');
				$templateMgr->assign('errorParams', array('reason' => $reason));
				$templateMgr->assign('backLink', Request::url(null, null, null, 'login'));
				$templateMgr->assign('backLinkLabel', 'user.login');
				return $templateMgr->display('common/error.tpl');
			}
			if($source = Request::getUserVar('source'))
				Request::redirectUrl($source);

			else Request::redirect(null, null, 'login');

		} else {
			$regForm->display();
		}
	}

	/**
	 * Show error message if user account creation is not allowed.
	 */
	function createAccountDisabled() {
		$this->setupTemplate(true);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'navigation.account');
		$templateMgr->assign('errorMsg', 'user.account.createAccountDisabled');
		$templateMgr->assign('backLink', Request::url(null, null, null, 'login'));
		$templateMgr->assign('backLinkLabel', 'user.login');
		$templateMgr->display('common/error.tpl');
	}

	/**
	 * Check credentials and activate a new user
	 * @author Marc Bria <marc.bria@uab.es>
	 */
	function activateUser($args) {
		$username = array_shift($args);
		$accessKeyCode = array_shift($args);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getUserByUsername($username);
		if (!$user) Request::redirect(null, 'login');

		// Checks user & token
		import('security.AccessKeyManager');
		$accessKeyManager = new AccessKeyManager();
		$accessKeyHash = AccessKeyManager::generateKeyHash($accessKeyCode);
		$accessKey =& $accessKeyManager->validateKey(
			'RegisterContext',
			$user->getId(),
			$accessKeyHash
		);

		if ($accessKey != null && $user->getDateValidated() === null) {
			// Activate user
			$user->setDisabled(false);
			$user->setDisabledReason('');
			$user->setDateValidated(Core::getCurrentDate());
			$userDao->updateObject($user);

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('message', 'user.login.activated');
			return $templateMgr->display('common/message.tpl');
		}
		Request::redirect(null, 'login');
	}

	/**
	 * Validation check.
	 * Checks if conference allows user account creation.
	 */	
	function validate() {
		parent::validate(false);
		$conference =& Request::getConference();

		if ($conference != null) {
			$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
			if ($conferenceSettingsDao->getSetting($conference->getId(), 'disableUserReg')) {
				// Users cannot create accounts for this conference
				$this->createAccountDisabled();
				exit;
			}
		}

		return true;
	}

}

?>
