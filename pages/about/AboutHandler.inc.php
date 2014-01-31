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

// $Id$


import('handler.Handler');

class AboutHandler extends Handler {
	/**
	 * Constructor
	 **/
	function AboutHandler() {
		parent::Handler();
	}

	/**
	 * Display about index page.
	 */
	function index() {
		$this->validate();
		$this->setupTemplate(false);

		$templateMgr =& TemplateManager::getManager();
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$conferencePath = Request::getRequestedConferencePath();

		if ($conferencePath != 'index' && $conferenceDao->conferenceExistsByPath($conferencePath)) {
			$schedConf =& Request::getSchedConf();
			$conference =& Request::getConference();

			if($schedConf) {
				$templateMgr->assign('showAboutSchedConf', true);
				$settings = $schedConf->getSettings();
			} else {
				$templateMgr->assign('showAboutSchedConf', false);
				$settings = $conference->getSettings();
				$templateMgr->assign_by_ref('currentSchedConfs', $schedConfDao->getCurrentSchedConfs($conference->getId()));
			}

			$customAboutItems = $conference->getSetting('customAboutItems');

			foreach (AboutHandler::getPublicStatisticsNames() as $name) {
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
			$site =& Request::getSite();
			$about = $site->getLocalizedAbout();
			$templateMgr->assign('about', $about);

			$conferences =& $conferenceDao->getEnabledConferences(); //Enabled Added
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->display('about/site.tpl');
		}
	}


	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = true) {
		parent::setupTemplate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OCS_MANAGER, LOCALE_COMPONENT_PKP_MANAGER));

		$pageHierarchy = array();
		if ($conference) $pageHierarchy[] = array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true);
		if ($schedConf) $pageHierarchy[] = array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true);
		if ($subclass) $pageHierarchy[] = array(Request::url(null, null, 'about'), 'about.aboutTheConference');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display contact page.
	 */
	function contact() {
		$this->validate(true);
		$this->setupTemplate();

		$schedConf =& Request::getSchedConf();
		$conference =& Request::getConference();

		$settings = ($schedConf? $schedConf->getSettings():$conference->getSettings());
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('conferenceSettings', $settings);
		$templateMgr->display('about/contact.tpl');
	}

	/**
	 * Display organizingTeam page.
	 */
	function organizingTeam() {
		$this->validate(true);
		$this->setupTemplate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$conferenceId = $conference->getId();
		$schedConfId = ($schedConf? $schedConf->getId():-1);

		if($schedConf)
			$settings = $schedConf->getSettings();
		else
			$settings =& $conference->getSettings();

		$templateMgr =& TemplateManager::getManager();

		$countryDao =& DAORegistry::getDAO('CountryDAO');
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

		if (!$schedConf->getSetting('boardEnabled')) {
			// Don't use the Organizing Team feature. Generate
			// Organizing Team information using Role info.
			$roleDao =& DAORegistry::getDAO('RoleDAO');

			$directors =& $roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getId(), $schedConfId);
			$directors =& $directors->toArray();

			$trackDirectors =& $roleDao->getUsersByRoleId(ROLE_ID_TRACK_DIRECTOR, $conference->getId(), $schedConfId);
			$trackDirectors =& $trackDirectors->toArray();

			$templateMgr->assign_by_ref('directors', $directors);
			$templateMgr->assign_by_ref('trackDirectors', $trackDirectors);
			$templateMgr->display('about/organizingTeam.tpl');
		} else {
			// The Organizing Team feature has been enabled.
			// Generate information using Group data.
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups(ASSOC_TYPE_SCHED_CONF, $schedConf->getId());
			$teamInfo = array();
			$groups = array();
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$memberships = array();
				$allMemberships =& $groupMembershipDao->getMemberships($group->getId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$memberships[] =& $membership;
				}
				if (!empty($memberships)) $groups[] =& $group;
				$teamInfo[$group->getId()] = $memberships;
			}

			$templateMgr->assign_by_ref('groups', $groups);
			$templateMgr->assign_by_ref('teamInfo', $teamInfo);
			$templateMgr->display('about/organizingTeamBoard.tpl');
		}
	}

	/**
	 * Display a biography for an organizing team member.
	 */
	function organizingTeamBio($args) {
		$this->addCheck(new HandlerValidatorConference($this));
		$this->validate();
		$this->setupTemplate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$templateMgr =& TemplateManager::getManager();

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
		if (!isset($settings['boardEnabled']) || $settings['boardEnabled'] != true) {
			$directors =& $roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getId());
			while ($potentialUser =& $directors->next()) {
				if ($potentialUser->getId() == $userId)
					$user =& $potentialUser;
			}

			$trackDirectors =& $roleDao->getUsersByRoleId(ROLE_ID_TRACK_DIRECTOR, $conference->getId());
			while ($potentialUser =& $trackDirectors->next()) {
				if ($potentialUser->getId() == $userId)
					$user =& $potentialUser;
			}

		} else {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups(ASSOC_TYPE_SCHED_CONF, $schedConfId);
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$allMemberships =& $groupMembershipDao->getMemberships($group->getId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$potentialUser =& $membership->getUser();
					if ($potentialUser->getId() == $userId)
						$user = $potentialUser;
				}
			}
		}

		if (!$user) Request::redirect(null, null, null, 'about', 'organizingTeam');

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		if ($user && $user->getCountry() != '') {
			$country = $countryDao->getCountry($user->getCountry());
			$templateMgr->assign('country', $country);
		}

		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->display('about/organizingTeamBio.tpl');
	}

	/**
	 * Display editorialPolicies page.
	 */
	function editorialPolicies() {
		parent::validate(true);
		$this->setupTemplate();

		$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');
		$schedConf =& Request::getSchedConf();
		$conference =& Request::getConference();

		$templateMgr =& TemplateManager::getManager();
		$settings = ($schedConf? $schedConf->getSettings(): $conference->getSettings());
		$templateMgr->assign('conferenceSettings', $settings);

		$templateMgr->display('about/editorialPolicies.tpl');
	}

	/**
	 * Display registration page.
	 */
	function registration() {
		parent::validate(true);
		$this->setupTemplate();

		$conferenceDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');

		$schedConf =& Request::getSchedConf();
		$conference =& Request::getConference();

		if (!$schedConf || !$conference) Request::redirect(null, null, 'about');

		$registrationName =& $schedConf->getSetting('registrationName');
		$registrationEmail =& $schedConf->getSetting('registrationEmail');
		$registrationPhone =& $schedConf->getSetting('registrationPhone');
		$registrationFax =& $schedConf->getSetting('registrationFax');
		$registrationMailingAddress =& $schedConf->getSetting('registrationMailingAddress');
		$registrationAdditionalInformation =& $schedConf->getLocalizedSetting('registrationAdditionalInformation');
		$registrationTypes =& $registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getId());

		$templateMgr =& TemplateManager::getManager();
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
	function submissions() {
		parent::validate(true);
		$this->setupTemplate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$settings = ($schedConf? $schedConf->getSettings():$conference->getSettings());

		$templateMgr =& TemplateManager::getManager();
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
	function siteMap() {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$user =& Request::getUser();
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		if ($user) {
			$rolesByConference = array();
			$conferences =& $conferenceDao->getEnabledConferences();
			// Fetch the user's roles for each conference
			foreach ($conferences->toArray() as $conference) {
				$roles =& $roleDao->getRolesByUserId($user->getId(), $conference->getId());
				if (!empty($roles)) {
					$rolesByConference[$conference->getId()] =& $roles;
				}
			}
		}

		$conferences =& $conferenceDao->getEnabledConferences();
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
	function aboutThisPublishingSystem() {
		$this->validate();
		$this->setupTemplate();

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr =& TemplateManager::getManager();
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
	function statistics() {
		$this->validate();
		$this->setupTemplate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId','user.about');

		$trackIds = $schedConf->getSetting('statisticsTrackIds');
		if (!is_array($trackIds)) $trackIds = array();
		$templateMgr->assign('trackIds', $trackIds);

		foreach (AboutHandler::getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $schedConf->getSetting($name));
		}

		$schedConfStatisticsDao =& DAORegistry::getDAO('SchedConfStatisticsDAO');
		$paperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getId(), null);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$limitedPaperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getId(), $trackIds);
		$templateMgr->assign('limitedPaperStatistics', $limitedPaperStatistics);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
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

	function getPublicStatisticsNames() {
		import ('pages.manager.ManagerHandler');
		import ('pages.manager.StatisticsHandler');
		return StatisticsHandler::getPublicStatisticsNames();
	}
}

?>
