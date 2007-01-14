<?php

/**
 * RegistrationHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 *
 * Handle requests for user registration. 
 *
 * $Id$
 */

class RegistrationHandler extends UserHandler {

	/**
	 * Display registration form for new users.
	 */
	function register() {
		list($conference, $event) = RegistrationHandler::validate();
		parent::setupTemplate(true);
		
		if ($conference != null && $event != null && $event->getEnabled()) {

			// We're trying to register for a specific event
			import('user.form.RegistrationForm');
		
			$regForm = &new RegistrationForm();
			$regForm->initData();
			$regForm->display();

		} elseif ($conference != null) {

			// We have the conference, but need to select an event
			$eventsDao = &DAORegistry::getDAO('EventDAO');
			$events = &$eventsDao->getEnabledEvents($conference->getConferenceId());

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('pageHierarchy', array(
				array(Request::url(null, 'index', 'index'), $conference->getTitle(), true)));
			$templateMgr->assign('source', Request::getUserVar('source'));
			$templateMgr->assign_by_ref('events', $events);
			$templateMgr->display('user/registerConference.tpl');

		} else {
		
			// We have neither conference nor event; start by selecting a
			// conference and we'll end up above after a redirect.
			
			$conferencesDao = &DAORegistry::getDAO('ConferenceDAO');
			$conferences = &$conferencesDao->getEnabledConferences();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('source', Request::getUserVar('source'));
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->display('user/registerSite.tpl');
		}
	}
	
	/**
	 * Validate user registration information and register new user.
	 */
	function registerUser() {
		RegistrationHandler::validate();
		import('user.form.RegistrationForm');
		
		$regForm = &new RegistrationForm();
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
	 * Show error message if user registration is not allowed.
	 */
	function registrationDisabled() {
		parent::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'user.register');
		$templateMgr->assign('errorMsg', 'user.register.registrationDisabled');
		$templateMgr->assign('backLink', Request::url(null, null, null, 'login'));
		$templateMgr->assign('backLinkLabel', 'user.login');
		$templateMgr->display('common/error.tpl');
	}

	/**
	 * Validation check.
	 * Checks if conference allows user registration.
	 */	
	function validate() {
		list($conference, $event) = parent::validate(false);

		if ($conference != null) {
			$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
			if ($conferenceSettingsDao->getSetting($conference->getConferenceId(), 'disableUserReg')) {
				// Users cannot register themselves for this conference
				RegistrationHandler::registrationDisabled();
				exit;
			}
		}
		
		return array($conference, $event);
	}
	
}

?>
