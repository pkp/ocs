<?php

/**
 * @file UserHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 */

//$Id$

class UserHandler extends Handler {

	/**
	 * Display user index page.
	 */
	function index() {
		UserHandler::validate();

		$user =& Request::getUser();
		$userId = $user->getUserId();

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');

		UserHandler::setupTemplate();
		$templateMgr = &TemplateManager::getManager();

		$conference = &Request::getConference();
		$templateMgr->assign('helpTopicId', 'user.userHome');

		$schedConfsToDisplay = array();
		$schedConfRolesToDisplay = array();

		$allConferences = $allSchedConfs = array();

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
			while ($conference =& $conferences->next()) {
				$conferenceId = $conference->getConferenceId();

				// First, the generic roles for this conference
				$roles = &$roleDao->getRolesByUserId($userId, $conferenceId, 0);
				if (!empty($roles)) {
					$conferencesToDisplay[$conferenceId] =& $conference;
					$rolesToDisplay[$conferenceId] = &$roles;
				}

				// Second, scheduled conference-specific roles
				// TODO: don't display scheduled conference roles if granted at conference level too?
				$schedConfs = &$schedConfDao->getSchedConfsByConferenceId($conferenceId);
				while ($schedConf =& $schedConfs->next()) {
					$schedConfId = $schedConf->getSchedConfId();

					$schedConfRoles =& $roleDao->getRolesByUserId($userId, $conferenceId, $schedConfId);
					if(!empty($schedConfRoles)) {
						$schedConfRolesToDisplay[$schedConfId] =& $schedConfRoles;
						$schedConfsToDisplay[$conferenceId][$schedConfId] =& $schedConf;
						if (!isset($conferencesToDisplay[$conferenceId])) {
							$conferencesToDisplay[$conferenceId] =& $conference;
						}
					}
					$allSchedConfs[$conference->getConferenceId()][$schedConf->getSchedConfId()] =& $schedConf;
					unset($schedConf);
					unset($schedConfRoles);
				}
				$allConferences[$conference->getConferenceId()] =& $conference;
				unset($schedConfs);
				unset($conference);
			}

			$templateMgr->assign('showAllConferences', 1);
			$templateMgr->assign_by_ref('userConferences', $conferencesToDisplay);

		} else {
			// Show roles for the currently selected conference
			$conferenceId = $conference->getConferenceId();
			$roles =& $roleDao->getRolesByUserId($userId, $conferenceId, 0);
			if(!empty($roles)) {
				$rolesToDisplay[$conferenceId] =& $roles;
			}

			$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conferenceId);
			while($schedConf =& $schedConfs->next()) {
				$schedConfId = $schedConf->getSchedConfId();
				$schedConfRoles =& $roleDao->getRolesByUserId($userId, $conferenceId, $schedConfId);
				if(!empty($schedConfRoles)) {
					$schedConfRolesToDisplay[$schedConfId] =& $schedConfRoles;
					$schedConfsToDisplay[$conferenceId][$schedConfId] =& $schedConf;
				}
				unset($schedConfRoles);
				unset($schedConf);
			}

			$schedConf =& Request::getSchedConf();
			if ($schedConf) {
				import('schedConf.SchedConfAction');
				$templateMgr->assign('allowRegPresenter', SchedConfAction::allowRegPresenter($schedConf));
				$templateMgr->assign('allowRegReviewer', SchedConfAction::allowRegReviewer($schedConf));
				$templateMgr->assign('submissionsOpen', SchedConfAction::submissionsOpen($schedConf));
			}

			$templateMgr->assign_by_ref('userConference', $conference);
		}

		$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, 0, $userId, ROLE_ID_SITE_ADMIN));
		$templateMgr->assign('allConferences', $allConferences);
		$templateMgr->assign('allSchedConfs', $allSchedConfs);
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
	 * Become a given role.
	 */
	function become($args) {
		list($conference, $schedConf) = parent::validate(true, true);
		import('schedConf.SchedConfAction');
		$user =& Request::getUser();
		if (!$user) Request::redirect(null, null, 'index');

		$schedConfAction =& new SchedConfAction();

		switch (array_shift($args)) {
			case 'presenter':
				$roleId = ROLE_ID_PRESENTER;
				$func = 'allowRegPresenter';
				$deniedKey = 'presenter.submit.authorRegistrationClosed';
				break;
			case 'reviewer':
				$roleId = ROLE_ID_REVIEWER;
				$func = 'allowRegReviewer';
				$deniedKey = 'user.noRoles.regReviewerClosed';
				break;
			default:
				Request::redirect(null, null, 'index');
		}

		if ($schedConfAction->$func($schedConf)) {
			$role =& new Role();
			$role->setSchedConfId($schedConf->getSchedConfId());
			$role->setConferenceId($schedConf->getConferenceId());
			$role->setRoleId($roleId);
			$role->setUserId($user->getUserId());

			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roleDao->insertRole($role);
			Request::redirectUrl(Request::getUserVar('source'));
		} else {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('message', $deniedKey);
			return $templateMgr->display('common/message.tpl');
		}
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
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = array();

		if ($schedConf) {
			$pageHierarchy[] = array(Request::url(null, null, 'index'), $schedConf->getFullTitle(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true);
		}

		if ($subclass) {
			$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
		}

		$templateMgr->assign('pageHierarchy', $pageHierarchy);
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
		CreateAccountHandler::createAccount();
	}

	function activateUser($args) {
		import('pages.user.CreateAccountHandler');
		CreateAccountHandler::activateUser($args);
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
