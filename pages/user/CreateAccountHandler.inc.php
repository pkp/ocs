<?php

/**
 * CreateAccountHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 *
 * Handle requests for user account creation. 
 *
 * $Id$
 */

class CreateAccountHandler extends UserHandler {

	/**
	 * Display account form for new users.
	 */
	function account() {
		list($conference, $schedConf) = CreateAccountHandler::validate();
		parent::setupTemplate(true);
		
		if ($conference != null && $schedConf != null) {

			// We're trying to create an account for a specific scheduled conference
			import('user.form.CreateAccountForm');
		
			$regForm = &new CreateAccountForm();
			$regForm->initData();
			$regForm->display();

		} elseif ($conference != null) {

			// We have the conference, but need to select a scheduled conference
			$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
			$schedConfs = &$schedConfDao->getEnabledSchedConfs($conference->getConferenceId());

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('pageHierarchy', array(
				array(Request::url(null, 'index', 'index'), $conference->getTitle(), true)));
			$templateMgr->assign('source', Request::getUserVar('source'));
			$templateMgr->assign_by_ref('schedConfs', $schedConfs);
			$templateMgr->display('user/createAccountConference.tpl');

		} else {
		
			// We have neither conference nor scheduled conference; start by selecting a
			// conference and we'll end up above after a redirect.
			
			$conferencesDao = &DAORegistry::getDAO('ConferenceDAO');
			$conferences = &$conferencesDao->getEnabledConferences();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('source', Request::getUserVar('source'));
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->display('user/createAccountSite.tpl');
		}
	}
	
	/**
	 * Validate user information and create new user.
	 */
	function createAccount() {
		CreateAccountHandler::validate();
		import('user.form.CreateAccountForm');
		
		$regForm = &new CreateAccountForm();
		$regForm->readInputData();
		
		if ($regForm->validate()) {
			$regForm->execute();
			Validation::login($regForm->getData('username'), $regForm->getData('password'), $reason);
			if ($reason !== null) {
				parent::setupTemplate(true);
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'user.login');
				$templateMgr->assign('errorMsg', $reason==''?'user.login.accountDisabled':'user.login.accountDisabledWithReason');
				$templateMgr->assign('errorParams', array('reason' => $reason));
				$templateMgr->assign('backLink', Request::url(null, null, null, 'login'));
				$templateMgr->assign('backLinkLabel', 'user.login');
				$templateMgr->display('common/error.tpl');
			}
			if($source = Request::getUserVar('source'))
				Request::redirectUrl($source);

			else Request::redirect(null, null, 'login');
			
		} else {
			parent::setupTemplate(true);
			$regForm->display();
		}
	}
	
	/**
	 * Show error message if user account creation is not allowed.
	 */
	function createAccountDisabled() {
		parent::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'navigation.account');
		$templateMgr->assign('errorMsg', 'user.account.createAccountDisabled');
		$templateMgr->assign('backLink', Request::url(null, null, null, 'login'));
		$templateMgr->assign('backLinkLabel', 'user.login');
		$templateMgr->display('common/error.tpl');
	}

	/**
	 * Validation check.
	 * Checks if conference allows user account creation.
	 */	
	function validate() {
		list($conference, $schedConf) = parent::validate(false);

		if ($conference != null) {
			$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
			if ($conferenceSettingsDao->getSetting($conference->getConferenceId(), 'disableUserReg')) {
				// Users cannot create accounts for this conference
				CreateAccountHandler::createAccountDisabled();
				exit;
			}
		}
		
		return array($conference, $schedConf);
	}
	
}

?>
