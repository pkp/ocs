<?php

/**
 * @file UserHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 *
 */

// $Id$


import('handler.Handler');

class UserHandler extends Handler {
	/**
	 * Constructor
	 **/
	function UserHandler() {
		parent::Handler();
	}

	/**
	 * Display user index page.
	 */
	function index() {
		$this->validate();

		$user =& Request::getUser();
		$userId = $user->getId();
		
		$setupIncomplete = array();
		$submissionsCount = array();
		$isValid = array();
		$schedConfsToDisplay = array();
		$conferencesToDisplay = array();

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		$this->setupTemplate();
		$templateMgr =& TemplateManager::getManager();

		$conference =& Request::getConference();
		$templateMgr->assign('helpTopicId', 'user.userHome');
		
		$allConferences = $allSchedConfs = array();

		if ($conference == null) { // Curently at site level
			unset($conference);

			// Show roles for all conferences
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
			$conferences =& $conferenceDao->getConferences();

			// Fetch the user's roles for each conference
			while ($conference =& $conferences->next()) {
				$conferenceId = $conference->getId();
				$schedConfId = 0;
				
				// First, the generic roles for this conference
				$roles =& $roleDao->getRolesByUserId($userId, $conferenceId, 0);
				if (!empty($roles)) {
					$conferencesToDisplay[$conferenceId] =& $conference;
					$rolesToDisplay[$conferenceId] =& $roles;
				}

				// Determine if conference setup is incomplete, to provide a message for JM
				$setupIncomplete[$conferenceId] = $this->checkIncompleteSetup($conference);
				
				$this->getRoleDataForConference($userId, $conferenceId, $schedConfId, $submissionsCount, $isValid);

				// Second, scheduled conference-specific roles
				// TODO: don't display scheduled conference roles if granted at conference level too?
				$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conferenceId);
				while ($schedConf =& $schedConfs->next()) {
					$schedConfId = $schedConf->getId();

					$schedConfRoles =& $roleDao->getRolesByUserId($userId, $conferenceId, $schedConfId);
					if(!empty($schedConfRoles)) {
						$schedConfsToDisplay[$conferenceId][$schedConfId] =& $schedConf;
						$this->getRoleDataForConference($userId, $conferenceId, $schedConfId, $submissionsCount, $isValid);
					}
					$allSchedConfs[$conference->getId()][$schedConf->getId()] =& $schedConf;
					unset($schedConf);
				}
				
				// If the user has Sched. Conf. roles and no Conf. roles, push the conf. object
				// into the conference array so it gets shown
				if(empty($roles) && !empty($schedConfsToDisplay[$conferenceId])) {
					$conferencesToDisplay[$conferenceId] =& $conference;
				}
				
				$allConferences[$conference->getId()] =& $conference;
				unset($schedConfs);
				unset($conference);
			}

			$templateMgr->assign('showAllConferences', 1);
			$templateMgr->assign_by_ref('userConferences', $conferencesToDisplay);
		} else {  // Currently within a conference's context
			$conferenceId = $conference->getId();
			$userConferences = array($conference);
			
			$this->getRoleDataForConference($userId, $conferenceId, 0, $submissionsCount, $isValid);

			$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conferenceId);
			while($schedConf =& $schedConfs->next()) {
				$schedConfId = $schedConf->getId();
				$schedConfRoles =& $roleDao->getRolesByUserId($userId, $conferenceId, $schedConfId);
				if(!empty($schedConfRoles)) {
					$this->getRoleDataForConference($userId, $conferenceId, $schedConfId, $submissionsCount, $isValid);
					$schedConfsToDisplay[$conferenceId][$schedConfId] =& $schedConf;
				}

				unset($schedConf);
			}

			$schedConf =& Request::getSchedConf();
			if ($schedConf) {
				import('schedConf.SchedConfAction');
				$templateMgr->assign('allowRegAuthor', SchedConfAction::allowRegAuthor($schedConf));
				$templateMgr->assign('allowRegReviewer', SchedConfAction::allowRegReviewer($schedConf));
				$templateMgr->assign('submissionsOpen', SchedConfAction::submissionsOpen($schedConf));
			}

			$templateMgr->assign_by_ref('userConferences', $userConferences);
		}

		$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, 0, $userId, ROLE_ID_SITE_ADMIN));
		$templateMgr->assign('allConferences', $allConferences);
		$templateMgr->assign('allSchedConfs', $allSchedConfs);
		$templateMgr->assign('userSchedConfs', $schedConfsToDisplay);
		$templateMgr->assign('isValid', $isValid);
		$templateMgr->assign('submissionsCount', $submissionsCount);
		$templateMgr->assign('setupIncomplete', $setupIncomplete); 
		$templateMgr->display('user/index.tpl');
	}

	/**
	 * Gather information about a user's role within a conference.
	 * @param $userId int
	 * @param $conferenceId int 
	 * @param $submissionsCount array reference
	 * @param $isValid array reference
	
	 */
	function getRoleDataForConference($userId, $conferenceId, $schedConfId, &$submissionsCount, &$isValid) {
		if (Validation::isConferenceManager($conferenceId)) {
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
			$isValid["ConferenceManager"][$conferenceId][$schedConfId] = true;
		}
		if (Validation::isDirector($conferenceId, $schedConfId)) {
			$isValid["Director"][$conferenceId][$schedConfId] = true;
			$directorSubmissionDao =& DAORegistry::getDAO('DirectorSubmissionDAO');
			$submissionsCount["Director"][$conferenceId][$schedConfId] = $directorSubmissionDao->getDirectorSubmissionsCount($schedConfId);
		}
		if (Validation::isTrackDirector($conferenceId, $schedConfId)) {
			$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
			$submissionsCount["TrackDirector"][$conferenceId][$schedConfId] = $trackDirectorSubmissionDao->getTrackDirectorSubmissionsCount($userId, $schedConfId);
			$isValid["TrackDirector"][$conferenceId][$schedConfId] = true;
		}
		if (Validation::isReviewer($conferenceId, $schedConfId)) {
			$reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');
			$submissionsCount["Reviewer"][$conferenceId][$schedConfId] = $reviewerSubmissionDao->getSubmissionsCount($userId, $schedConfId);
			$isValid["Reviewer"][$conferenceId][$schedConfId] = true;
		} 
		if (Validation::isAuthor($conferenceId, $schedConfId)) {
			$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
			$submissionsCount["Author"][$conferenceId][$schedConfId] = $authorSubmissionDao->getSubmissionsCount($userId, $schedConfId);
			$isValid["Author"][$conferenceId][$schedConfId] = true;
		}
	}
	
	/**
	 * Determine if the conference's setup has been sufficiently completed.
	 * @param $conference Object 
	 * @return boolean True iff setup is incomplete
	 */
	function checkIncompleteSetup($conference) {
		if (
			$conference->getSetting('contactEmail') == '' ||  
			$conference->getSetting('contactName') == ''
		) return true;
		return false;
	}

	/**
	 * Change the locale for the current user.
	 * @param $args array first parameter is the new locale
	 */
	function setLocale($args) {
		$setLocale = isset($args[0]) ? $args[0] : null;

		$site =& Request::getSite();
		$conference =& Request::getConference();
		if ($conference != null) {
			$conferenceSupportedLocales = $conference->getSetting('supportedLocales');
			if (!is_array($conferenceSupportedLocales)) {
				$conferenceSupportedLocales = array();
			}
		}

		if (AppLocale::isLocaleValid($setLocale) && (!isset($conferenceSupportedLocales) || in_array($setLocale, $conferenceSupportedLocales)) && in_array($setLocale, $site->getSupportedLocales())) {
			$session =& Request::getSession();
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
		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();
		$schedConf =& Request::getSchedConf();
		
		import('schedConf.SchedConfAction');
		$user =& Request::getUser();
		if (!$user) Request::redirect(null, null, 'index');

		$schedConfAction = new SchedConfAction();

		switch (array_shift($args)) {
			case 'author':
				$roleId = ROLE_ID_AUTHOR;
				$func = 'allowRegAuthor';
				$deniedKey = 'author.submit.authorRegistrationClosed';
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
			$role = new Role();
			$role->setSchedConfId($schedConf->getId());
			$role->setConferenceId($schedConf->getConferenceId());
			$role->setRoleId($roleId);
			$role->setUserId($user->getId());

			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roleDao->insertRole($role);
			Request::redirectUrl(Request::getUserVar('source'));
		} else {
			$this->setupTemplate();
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
		parent::validate();

		if ($loginCheck && !Validation::isLoggedIn()) {
			Validation::redirectLogin();
		}

		return true;
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OCS_AUTHOR, LOCALE_COMPONENT_OCS_DIRECTOR, LOCALE_COMPONENT_OCS_MANAGER));

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$templateMgr =& TemplateManager::getManager();

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
	// Captcha
	//

	function viewCaptcha($args) {
		$captchaId = (int) array_shift($args);
		import('captcha.CaptchaManager');
		$captchaManager = new CaptchaManager();
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
