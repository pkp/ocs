<?php

/**
 * @file AboutHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AboutHandler
 * @ingroup pages_director
 *
 * @brief Handle requests for director functions.
 *
 */



import('classes.handler.Handler');

class AboutHandler extends Handler {
	/**
	 * Constructor
	 */
	function AboutHandler() {
		parent::Handler();
	}

	/**
	 * Display about index page.
	 */
	function index($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, false);

		$templateMgr =& TemplateManager::getManager($request);
		$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$conferencePath = $request->getRequestedConferencePath();

		if ($conferencePath != 'index' && $conferenceDao->existsByPath($conferencePath)) {
			$schedConf =& $request->getSchedConf();
			$conference =& $request->getConference();

			if($schedConf) {
				$templateMgr->assign('showAboutSchedConf', true);
				$settings = $schedConf->getSettings();
			} else {
				$templateMgr->assign('showAboutSchedConf', false);
				$settings = $conference->getSettings();
				$templateMgr->assign_by_ref('currentSchedConfs', $schedConfDao->getCurrentSchedConfs($conference->getId()));
			}

			$customAboutItems = $conference->getSetting('customAboutItems');

			foreach (AboutHandler::_getPublicStatisticsNames() as $name) {
				if (isset($settings[$name])) {
					$templateMgr->assign('publicStatisticsEnabled', true);
					break;
				}
			}

			if (isset($customAboutItems[AppLocale::getLocale()])) $templateMgr->assign('customAboutItems', $customAboutItems[AppLocale::getLocale()]);
			elseif (isset($customAboutItems[AppLocale::getPrimaryLocale()])) $templateMgr->assign('customAboutItems', $customAboutItems[AppLocale::getPrimaryLocale()]);

			$templateMgr->assign('helpTopicId', 'user.about');
			$templateMgr->assign_by_ref('conferenceSettings', $settings);
			$templateMgr->display('about/index.tpl');
		} else {
			$site =& $request->getSite();
			$about = $site->getLocalizedAbout();
			$templateMgr->assign('about', $about);

			$conferences =& $conferenceDao->getConferences(true);
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->display('about/site.tpl');
		}
	}


	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($request, $subclass = true) {
		parent::setupTemplate($request);

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER);

		$pageHierarchy = array();
		if ($conference) $pageHierarchy[] = array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true);
		if ($schedConf) $pageHierarchy[] = array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true);
		if ($subclass) $pageHierarchy[] = array($request->url(null, null, 'about'), 'about.aboutTheConference');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display contact page.
	 */
	function contact($args, &$request) {
		$this->validate(true);
		$this->setupTemplate($request);

		$schedConf =& $request->getSchedConf();
		$conference =& $request->getConference();

		$settings = ($schedConf? $schedConf->getSettings():$conference->getSettings());
		$templateMgr =& TemplateManager::getManager($request);

		$templateMgr->assign_by_ref('conferenceSettings', $settings);
		$templateMgr->display('about/contact.tpl');
	}

	/**
	 * Display organizingTeam page.
	 */
	function organizingTeam($args, &$request) {
		$this->validate(true);
		$this->setupTemplate($request);

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$conferenceId = $conference->getId();
		$schedConfId = ($schedConf? $schedConf->getId():-1);

		if($schedConf)
			$settings = $schedConf->getSettings();
		else
			$settings =& $conference->getSettings();

		$templateMgr =& TemplateManager::getManager($request);

		$countryDao = DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		$contributors = array();
		$sponsors = array();

		if($conference) {
			$contributorNote = $conference->getLocalizedSetting('contributorNote');
			$contributors = $conference->getSetting('contributors');
			if (!is_array($contributors)) $contributors = array();

			$sponsorNote = $conference->getLocalizedSetting('sponsorNote');
			$sponsors = $conference->getSetting('sponsors');
			if (!is_array($sponsors)) $sponsors = array();
		}

		if($schedConf) {
			$contributorNote = $schedConf->getLocalizedSetting('contributorNote');
			$eventContributors = $schedConf->getSetting('contributors');
			if (is_array($eventContributors)) $contributors = array_merge($contributors, $eventContributors);

			$sponsorNote = $schedConf->getLocalizedSetting('sponsorNote');
			$eventSponsors = $schedConf->getSetting('sponsors');
			if (is_array($eventSponsors)) $sponsors = array_merge($sponsors, $eventSponsors);
		}

		$templateMgr->assign_by_ref('contributorNote', $contributorNote);
		$templateMgr->assign_by_ref('contributors', $contributors);
		$templateMgr->assign('sponsorNote', $sponsorNote);
		$templateMgr->assign_by_ref('sponsors', $sponsors);

		// FIXME: This is pretty inefficient; should probably be cached.

		// Don't use the Organizing Team feature. Generate
		// Organizing Team information using Role info.
		$roleDao = DAORegistry::getDAO('RoleDAO');

		$directors =& $roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getId(), $schedConfId);
		$directors =& $directors->toArray();

		$trackDirectors =& $roleDao->getUsersByRoleId(ROLE_ID_TRACK_DIRECTOR, $conference->getId(), $schedConfId);
		$trackDirectors =& $trackDirectors->toArray();

		$templateMgr->assign_by_ref('directors', $directors);
		$templateMgr->assign_by_ref('trackDirectors', $trackDirectors);
		$templateMgr->display('about/organizingTeam.tpl');
	}

	/**
	 * Display a biography for an organizing team member.
	 */
	function organizingTeamBio($args, &$request) {
		$this->addCheck(new HandlerValidatorConference($this));
		$this->validate();
		$this->setupTemplate($request);

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$roleDao = DAORegistry::getDAO('RoleDAO');

		$templateMgr =& TemplateManager::getManager($request);

		$userId = isset($args[0])?(int)$args[0]:0;

		// Make sure we're fetching a biography for
		// a user who should appear on the listing;
		// otherwise we'll be exposing user information
		// that might not necessarily be public.

		// FIXME: This is pretty inefficient. Should be cached.

		if($schedConf) {
			$settings = $schedConf->getSettings();
			$schedConfId = $schedConf->getId();
		} else {
			$settings = $conference->getSettings();
			$schedConfId = 0;
		}

		$user = null;
		$roles =& $roleDao->getRolesByUserId($userId, $conference->getId());
		$acceptableRoles = array(
			ROLE_ID_DIRECTOR,
			ROLE_ID_TRACK_DIRECTOR
		);
		foreach ($roles as $role) {
			$roleId = $role->getRoleId();
			if (in_array($roleId, $acceptableRoles)) {
				$userDao = DAORegistry::getDAO('UserDAO');
				$user =& $userDao->getById($userId);
				break;
			}
		}

		// Currently we always publish emails in this mode.
		$publishEmail = true;

		if (!$user) $request->redirect(null, null, null, 'about', 'organizingTeam');

		$countryDao = DAORegistry::getDAO('CountryDAO');
		if ($user && $user->getCountry() != '') {
			$country = $countryDao->getCountry($user->getCountry());
			$templateMgr->assign('country', $country);
		}

		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->assign_by_ref('publishEmail', $publishEmail);
		$templateMgr->display('about/organizingTeamBio.tpl');
	}

	/**
	 * Display editorialPolicies page.
	 */
	function editorialPolicies($args, &$request) {
		parent::validate(true);
		$this->setupTemplate($request);

		$trackDirectorsDao = DAORegistry::getDAO('TrackDirectorsDAO');
		$schedConf =& $request->getSchedConf();
		$conference =& $request->getConference();

		$templateMgr =& TemplateManager::getManager($request);
		$settings = ($schedConf? $schedConf->getSettings(): $conference->getSettings());
		$templateMgr->assign('conferenceSettings', $settings);

		$templateMgr->display('about/editorialPolicies.tpl');
	}

	/**
	 * Display registration page.
	 */
	function registration($args, &$request) {
		parent::validate(true);
		$this->setupTemplate($request);

		$conferenceDao = DAORegistry::getDAO('ConferenceSettingsDAO');
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');

		$schedConf =& $request->getSchedConf();
		$conference =& $request->getConference();

		if (!$schedConf || !$conference) $request->redirect(null, null, 'about');

		$registrationName =& $schedConf->getSetting('registrationName');
		$registrationEmail =& $schedConf->getSetting('registrationEmail');
		$registrationPhone =& $schedConf->getSetting('registrationPhone');
		$registrationFax =& $schedConf->getSetting('registrationFax');
		$registrationMailingAddress =& $schedConf->getSetting('registrationMailingAddress');
		$registrationAdditionalInformation =& $schedConf->getLocalizedSetting('registrationAdditionalInformation');
		$registrationTypes =& $registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getId());

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('registrationName', $registrationName);
		$templateMgr->assign('registrationEmail', $registrationEmail);
		$templateMgr->assign('registrationPhone', $registrationPhone);
		$templateMgr->assign('registrationFax', $registrationFax);
		$templateMgr->assign('registrationMailingAddress', $registrationMailingAddress);
		$templateMgr->assign('registrationAdditionalInformation', $registrationAdditionalInformation);
		$templateMgr->assign('registrationTypes', $registrationTypes);
		$templateMgr->display('about/registration.tpl');
	}

	/**
	 * Display submissions page.
	 */
	function submissions($args, &$request) {
		parent::validate(true);
		$this->setupTemplate($request);

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$settings = ($schedConf? $schedConf->getSettings():$conference->getSettings());

		$templateMgr =& TemplateManager::getManager($request);
		$submissionChecklist = $schedConf?$schedConf->getLocalizedSetting('submissionChecklist'):null;
		if (!empty($submissionChecklist)) {
			ksort($submissionChecklist);
			reset($submissionChecklist);
		}
		$templateMgr->assign('submissionChecklist', $submissionChecklist);
		if ($schedConf) {
			$templateMgr->assign('authorGuidelines', $schedConf->getLocalizedSetting('authorGuidelines'));
		}
		$templateMgr->assign('copyrightNotice', $conference->getLocalizedSetting('copyrightNotice'));
		$templateMgr->assign('privacyStatement', $conference->getLocalizedSetting('privacyStatement'));

		$templateMgr->assign('helpTopicId','submission.authorGuidelines');
		$templateMgr->display('about/submissions.tpl');
	}

	/**
	 * Display siteMap page.
	 */
	function siteMap($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$templateMgr =& TemplateManager::getManager($request);
		$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
		$user =& $request->getUser();
		$roleDao = DAORegistry::getDAO('RoleDAO');

		if ($user) {
			$rolesByConference = array();
			$conferences =& $conferenceDao->getConferences(true);
			// Fetch the user's roles for each conference
			foreach ($conferences->toArray() as $conference) {
				$roles =& $roleDao->getRolesByUserId($user->getId(), $conference->getId());
				if (!empty($roles)) {
					$rolesByConference[$conference->getId()] =& $roles;
				}
			}
		}

		$conferences =& $conferenceDao->getConferences(true);
		$templateMgr->assign_by_ref('conferences', $conferences->toArray());
		if (isset($rolesByConference)) {
			$templateMgr->assign_by_ref('rolesByConference', $rolesByConference);
		}
		if ($user) {
			$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, 0, $user->getId(), ROLE_ID_SITE_ADMIN));
		}

		$templateMgr->display('about/siteMap.tpl');
	}

	/**
	 * Display aboutThisPublishingSystem page.
	 */
	function aboutThisPublishingSystem($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$versionDao = DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('ocsVersion', $version->getVersionString());

		foreach (array(AppLocale::getLocale(), $primaryLocale = AppLocale::getPrimaryLocale(), 'en_US') as $locale) {
			$edProcessFile = "locale/$locale/edprocesslarge.png";
			if (file_exists($edProcessFile)) break;
		}
		$templateMgr->assign('edProcessFile', $edProcessFile);

		$templateMgr->display('about/aboutThisPublishingSystem.tpl');
	}

	/**
	 * Display a list of public stats for the current conference.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the reader's statistics view in the About pages.
	 */
	function statistics($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('helpTopicId','user.about');

		$trackIds = $schedConf->getSetting('statisticsTrackIds');
		if (!is_array($trackIds)) $trackIds = array();
		$templateMgr->assign('trackIds', $trackIds);

		foreach (AboutHandler::_getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $schedConf->getSetting($name));
		}

		$schedConfStatisticsDao = DAORegistry::getDAO('SchedConfStatisticsDAO');
		$paperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getId(), null);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$limitedPaperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getId(), $trackIds);
		$templateMgr->assign('limitedPaperStatistics', $limitedPaperStatistics);

		$trackDao = DAORegistry::getDAO('TrackDAO');
		$tracks =& $trackDao->getSchedConfTracks($schedConf->getId());
		$templateMgr->assign('tracks', $tracks->toArray());

		$reviewerStatistics = $schedConfStatisticsDao->getReviewerStatistics($schedConf->getId(), $trackIds);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$userStatistics = $schedConfStatisticsDao->getUserStatistics($schedConf->getId());
		$templateMgr->assign('userStatistics', $userStatistics);

		$registrationStatistics = $schedConfStatisticsDao->getRegistrationStatistics($schedConf->getId());
		$templateMgr->assign('registrationStatistics', $registrationStatistics);

		$templateMgr->display('about/statistics.tpl');
	}

	function _getPublicStatisticsNames() {
		import ('pages.manager.ManagerHandler');
		import ('pages.manager.StatisticsHandler');
		return StatisticsHandler::_getPublicStatisticsNames();
	}
}

?>
