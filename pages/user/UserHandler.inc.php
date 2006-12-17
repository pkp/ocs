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
		$eventDao = &DAORegistry::getDAO('EventDAO');

		UserHandler::setupTemplate();
		$templateMgr = &TemplateManager::getManager();

		$conference = &Request::getConference();
		$templateMgr->assign('helpTopicId', 'user.userHome');

		$eventsToDisplay = array();
		$eventRolesToDisplay = array();

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

				// Second, event-specific roles
				// TODO: don't display event roles if granted at conference level too?
				$events = &$eventDao->getEventsByConferenceId($conference->getConferenceId());
				$eventsArray = &$events->toArray();
				foreach($eventsArray as $event) {
					$eventRoles = &$roleDao->getRolesByUserId($session->getUserId(), $conference->getConferenceId(), $event->getEventId());
					if(!empty($eventRoles)) {
						$eventRolesToDisplay[$event->getEventId()] = &$eventRoles;
						$eventsToDisplay[$conference->getConferenceId()] = &$eventsArray;
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

			$events = &$eventDao->getEventsByConferenceId($conference->getConferenceId());
			$eventsArray = &$events->toArray();
			foreach($eventsArray as $event) {
				$eventRoles = &$roleDao->getRolesByUserId($session->getUserId(), $conference->getConferenceId(), $event->getEventId());
				if(!empty($eventRoles)) {
					$eventRolesToDisplay[$event->getEventId()] = &$eventRoles;
					$eventsToDisplay[$conference->getConferenceId()] = &$eventsArray;
				}
			}

			if (empty($roles) && empty($eventsToDisplay)) {
				Request::redirect('index', 'index', 'user');
			}

			$templateMgr->assign_by_ref('userConference', $conference);
		}

		$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, 0, $session->getUserId(), ROLE_ID_SITE_ADMIN));
		$templateMgr->assign('userRoles', $rolesToDisplay);
		$templateMgr->assign('userEvents', $eventsToDisplay);
		$templateMgr->assign('userEventRoles', $eventRolesToDisplay);
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
		list($conference, $event) = parent::validate();
		
		if ($loginCheck && !Validation::isLoggedIn()) {
			Validation::redirectLogin();
		}
		
		return array($conference, $event);
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
	// Registration
	//

	function register() {
		import('pages.user.RegistrationHandler');
		RegistrationHandler::register();
	}

	function registerUser() {
		import('pages.user.RegistrationHandler');
		RegistrationHandler::registerUser();
	}

	function email($args) {
		import('pages.user.EmailHandler');
		EmailHandler::email($args);
	}
}

?>
