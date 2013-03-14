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


import('pages.user.UserHandler');

class CreateAccountHandler extends UserHandler {
	/**
	 * Constructor
	 */
	function CreateAccountHandler() {
		parent::UserHandler();
	}

	/**
	 * Alias for account creation (for consistency with other apps)
	 */
	function register($args, $request) {
		$this->account($args, $request);
	}

	/**
	 * Display account form for new users.
	 */
	function account($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);
		
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		if ($conference != null && $schedConf != null) {

			// We're trying to create an account for a specific scheduled conference
			import('classes.user.form.CreateAccountForm');

			$regForm = new CreateAccountForm();
			if ($regForm->isLocaleResubmit()) {
				$regForm->readInputData();
			} else {
				$regForm->initData();
			}
			$regForm->display();

		} elseif ($conference != null) {

			// We have the conference, but need to select a scheduled conference
			$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
			$schedConfs = $schedConfDao->getAll(true, $conference->getId());

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('pageHierarchy', array(
				array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true)));
			$templateMgr->assign('source', $request->getUserVar('source'));
			$templateMgr->assign_by_ref('schedConfs', $schedConfs);
			$templateMgr->display('user/createAccountConference.tpl');

		} else {

			// We have neither conference nor scheduled conference; start by selecting a
			// conference and we'll end up above after a redirect.

			$conferencesDao = DAORegistry::getDAO('ConferenceDAO');
			$conferences =& $conferencesDao->getConferences(true);

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('source', $request->getUserVar('source'));
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->display('user/createAccountSite.tpl');
		}
	}

	/**
	 * Validate user information and create new user.
	 */
	function createAccount($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);
		import('classes.user.form.CreateAccountForm');

		$regForm = new CreateAccountForm();
		$regForm->readInputData();

		if ($regForm->validate()) {
			$regForm->execute();
			if (Config::getVar('email', 'require_validation')) {
				// Send them home; they need to deal with the
				// registration email.
				$request->redirect(null, 'index');
			}
			Validation::login($regForm->getData('username'), $regForm->getData('password'), $reason);
			if ($reason !== null) {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->assign('pageTitle', 'user.login');
				$templateMgr->assign('errorMsg', $reason==''?'user.login.accountDisabled':'user.login.accountDisabledWithReason');
				$templateMgr->assign('errorParams', array('reason' => $reason));
				$templateMgr->assign('backLink', $request->url(null, null, null, 'login'));
				$templateMgr->assign('backLinkLabel', 'user.login');
				return $templateMgr->display('common/error.tpl');
			}
			if($source = $request->getUserVar('source'))
				$request->redirectUrl($source);

			else $request->redirect(null, null, 'login');

		} else {
			$regForm->display();
		}
	}

	/**
	 * Show error message if user account creation is not allowed.
	 */
	function createAccountDisabled($args, &$request) {
		$this->setupTemplate($request, true);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('pageTitle', 'navigation.account');
		$templateMgr->assign('errorMsg', 'user.account.createAccountDisabled');
		$templateMgr->assign('backLink', $request->url(null, null, null, 'login'));
		$templateMgr->assign('backLinkLabel', 'user.login');
		$templateMgr->display('common/error.tpl');
	}

	/**
	 * Check credentials and activate a new user
	 * @author Marc Bria <marc.bria@uab.es>
	 */
	function activateUser($args, &$request) {
		$username = array_shift($args);
		$accessKeyCode = array_shift($args);

		$userDao = DAORegistry::getDAO('UserDAO');
		$user =& $userDao->getByUsername($username);
		if (!$user) $request->redirect(null, 'login');

		// Checks user & token
		import('lib.pkp.classes.security.AccessKeyManager');
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

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('message', 'user.login.activated');
			return $templateMgr->display('common/message.tpl');
		}
		$request->redirect(null, 'login');
	}

	/**
	 * Validation check.
	 * Checks if conference allows user account creation.
	 */	
	function validate() {
		parent::validate(false);
		$conference =& Request::getConference();

		if ($conference != null) {
			$conferenceSettingsDao = DAORegistry::getDAO('ConferenceSettingsDAO');
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
