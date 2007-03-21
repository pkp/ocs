<?php

/**
 * UserHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 *
 * Handle requests for user functions.
 *
 * $Id$
 */

class UserHandler extends Handler {

	/**
	 * Display user index page.
	 */
	function index() {
		UserHandler::validate();

		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');

		UserHandler::setupTemplate();
		$templateMgr = &TemplateManager::getManager();

		$conference = &Request::getConference();
		$templateMgr->assign('helpTopicId', 'user.userHome');

		$schedConfsToDisplay = array();
		$schedConfRolesToDisplay = array();

		$rolesToDisplay = array();

		if ($conference == null) {
			// Prevent variable clobbering
			unset($conference);

			// Show roles for all conferences
			$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
			$conferences = &$conferenceDao->getConferences();

			$conferencesToDisplay = array();
			$rolesToDisplay = array();
			// Fetch the user's roles for each conference
			foreach ($conferences->toArray() as $conference) {
				// First, the generic roles for this conference
				$roles = &$roleDao->getRolesByUserId($session->getUserId(), $conference->getConferenceId(), 0);
				if (!empty($roles)) {
					$conferencesToDisplay[] = $conference;
					$rolesToDisplay[$conference->getConferenceId()] = &$roles;
				}

				// Second, scheduled conference-specific roles
				// TODO: don't display scheduled conference roles if granted at conference level too?
				$schedConfs = &$schedConfDao->getSchedConfsByConferenceId($conference->getConferenceId());
				$schedConfsArray = &$schedConfs->toArray();
				foreach($schedConfsArray as $schedConf) {
					$schedConfRoles = &$roleDao->getRolesByUserId($session->getUserId(), $conference->getConferenceId(), $schedConf->getSchedConfId());
					if(!empty($schedConfRoles)) {
						$schedConfRolesToDisplay[$schedConf->getSchedConfId()] = &$schedConfRoles;
						$schedConfsToDisplay[$conference->getConferenceId()] = &$schedConfsArray;
					}
				}
			}

			$templateMgr->assign('showAllConferences', 1);
			$templateMgr->assign_by_ref('userConferences', $conferencesToDisplay);

		} else {
			// Show roles for the currently selected conference
			$roles = &$roleDao->getRolesByUserId($session->getUserId(), $conference->getConferenceId(), 0);
			if(!empty($roles)) {
				$rolesToDisplay[$conference->getConferenceId()] = &$roles;
			}

			$schedConfs = &$schedConfDao->getSchedConfsByConferenceId($conference->getConferenceId());
			$schedConfsArray = &$schedConfs->toArray();
			foreach($schedConfsArray as $schedConf) {
				$schedConfRoles = &$roleDao->getRolesByUserId($session->getUserId(), $conference->getConferenceId(), $schedConf->getSchedConfId());
				if(!empty($schedConfRoles)) {
					$schedConfRolesToDisplay[$schedConf->getSchedConfId()] = &$schedConfRoles;
					$schedConfsToDisplay[$conference->getConferenceId()] = &$schedConfsArray;
				}
			}

			if (empty($roles) && empty($schedConfsToDisplay)) {
				Request::redirect('index', 'index', 'user');
			}

			$templateMgr->assign_by_ref('userConference', $conference);
		}

		$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, 0, $session->getUserId(), ROLE_ID_SITE_ADMIN));
		$templateMgr->assign('userRoles', $rolesToDisplay);
		$templateMgr->assign('userSchedConfs', $schedConfsToDisplay);
		$templateMgr->assign('userSchedConfRoles', $schedConfRolesToDisplay);
		$templateMgr->display('user/index.tpl');
	}

	/**
	 * Change the locale for the current user.
	 * @param $args array first parameter is the new locale
	 */
	function setLocale($args) {
		$setLocale = isset($args[0]) ? $args[0] : null;

		$site = &Request::getSite();
		$conference = &Request::getConference();
		if ($conference != null) {
			$conferenceSupportedLocales = $conference->getSetting('supportedLocales');
			if (!is_array($conferenceSupportedLocales)) {
				$conferenceSupportedLocales = array();
			}
		}

		if (Locale::isLocaleValid($setLocale) && (!isset($conferenceSupportedLocales) || in_array($setLocale, $conferenceSupportedLocales)) && in_array($setLocale, $site->getSupportedLocales())) {
			$session = &Request::getSession();
			$session->setSessionVar('currentLocale', $setLocale);
		}

		if(isset($_SERVER['HTTP_REFERER'])) {
			Request::redirectUrl($_SERVER['HTTP_REFERER']);
		}

		$source = Request::getUserVar('source');
		if (isset($source) && !empty($source)) {
			Request::redirectUrl(Request::getProtocol() . '://' . Request::getServerHost() . $source, false);
		}

		Request::redirect(null, null, 'index');
	}

	/**
	 * Validate that user is logged in.
	 * Redirects to login form if not logged in.
	 * @param $loginCheck boolean check if user is logged in
	 */
	function validate($loginCheck = true) {
		list($conference, $schedConf) = parent::validate();
		
		if ($loginCheck && !Validation::isLoggedIn()) {
			Validation::redirectLogin();
		}
		
		return array($conference, $schedConf);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		if ($subclass) {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'user'), 'navigation.user')));
		}
	}


	//
	// Profiles
	//

	function profile() {
		import('pages.user.ProfileHandler');
		ProfileHandler::profile();
	}

	function saveProfile() {
		import('pages.user.ProfileHandler');
		ProfileHandler::saveProfile();
	}

	function changePassword() {
		import('pages.user.ProfileHandler');
		ProfileHandler::changePassword();
	}

	function savePassword() {
		import('pages.user.ProfileHandler');
		ProfileHandler::savePassword();
	}


	//
	// Create Account
	//

	function account() {
		import('pages.user.CreateAccountHandler');
		CreateAccountHandler::account();
	}

	function createAccount() {
		import('pages.user.CreateAccountHandler');
		CreateAccountHandler::createAccountUser();
	}

	//
	// Email
	//

	function email($args) {
		import('pages.user.EmailHandler');
		EmailHandler::email($args);
	}

	//
	// Captcha
	//
	
	function viewCaptcha($args) {
		$captchaId = (int) array_shift($args);
		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		if ($captchaManager->isEnabled()) {
			$captchaDao =& DAORegistry::getDAO('CaptchaDAO');
			$captcha =& $captchaDao->getCaptcha($captchaId);
			if ($captcha) {
				$captchaManager->generateImage($captcha);
				exit();
			}
		}
		Request::redirect(null, null, 'user');
	}
}

?>
