<?php

/**
 * @file AboutHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 * @class AboutHandler
 *
 * Handle requests for director functions. 
 *
 * $Id$
 */

class AboutHandler extends Handler {

	/**
	 * Display about index page.
	 */
	function index() {
		parent::validate();

		$templateMgr = &TemplateManager::getManager();
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$conferencePath = Request::getRequestedConferencePath();

		AboutHandler::setupTemplate(false);

		if ($conferencePath != 'index' && $conferenceDao->conferenceExistsByPath($conferencePath)) {
			$schedConf =& Request::getSchedConf();
			$conference =& Request::getConference();

			if($schedConf) {
				$templateMgr->assign('showAboutSchedConf', true);
				$settings = $schedConf->getSettings(true);
			} else {
				$templateMgr->assign('showAboutSchedConf', false);
				$settings = $conference->getSettings();
				$templateMgr->assign_by_ref('currentSchedConfs', $schedConfDao->getCurrentSchedConfs($conference->getConferenceId()));
			}

			$customAboutItems = $conference->getSetting('customAboutItems');

			foreach (AboutHandler::getPublicStatisticsNames() as $name) {
				if (isset($settings[$name])) {
					$templateMgr->assign('publicStatisticsEnabled', true);
					break;
				} 
			}

			if (isset($customAboutItems[Locale::getLocale()])) $templateMgr->assign('customAboutItems', $customAboutItems[Locale::getLocale()]);
			elseif (isset($customAboutItems[Locale::getPrimaryLocale()])) $templateMgr->assign('customAboutItems', $customAboutItems[Locale::getPrimaryLocale()]);

			$templateMgr->assign('helpTopicId', 'user.about');
			$templateMgr->assign_by_ref('conferenceSettings', $settings);
			$templateMgr->display('about/index.tpl');
		} else {
			$site = &Request::getSite();
			$about = $site->getSiteAbout();
			$templateMgr->assign('about', $about);

			$conferences = &$conferenceDao->getEnabledConferences(); //Enabled Added
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->display('about/site.tpl');
		}
	}


	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = true) {
		parent::validate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);

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
		parent::validate(true);

		AboutHandler::setupTemplate();

		$schedConf = &Request::getSchedConf();
		$conference =& Request::getConference();

		$settings = ($schedConf? $schedConf->getSettings(true):$conference->getSettings());
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('conferenceSettings', $settings);
		$templateMgr->display('about/contact.tpl');
	}

	/**
	 * Display organizingTeam page.
	 */
	function organizingTeam() {
		parent::validate(true);
		AboutHandler::setupTemplate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$conferenceId = $conference->getConferenceId();
		$schedConfId = ($schedConf? $schedConf->getSchedConfId():-1);

		if($schedConf)
			$settings = $schedConf->getSettings(true);
		else
			$settings =& $conference->getSettings();

		$templateMgr = &TemplateManager::getManager();

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

			$sponsorNote = $schedConf->getLocalizedSetting('sponsorNote', true);
			$eventSponsors = $schedConf->getSetting('sponsors', false);
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
			$roleDao = &DAORegistry::getDAO('RoleDAO');

			$directors = &$roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getConferenceId(), $schedConfId);
			$directors = &$directors->toArray();

			$trackDirectors = &$roleDao->getUsersByRoleId(ROLE_ID_TRACK_DIRECTOR, $conference->getConferenceId(), $schedConfId);
			$trackDirectors = &$trackDirectors->toArray();

			$templateMgr->assign_by_ref('directors', $directors);
			$templateMgr->assign_by_ref('trackDirectors', $trackDirectors);
			$templateMgr->display('about/organizingTeam.tpl');
		} else {
			// The Organizing Team feature has been enabled.
			// Generate information using Group data.
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups($conference->getConferenceId(), $schedConf->getSchedConfId());
			$teamInfo = array();
			$groups = array();
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$memberships = array();
				$allMemberships =& $groupMembershipDao->getMemberships($group->getGroupId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$memberships[] =& $membership;
				}
				if (!empty($memberships)) $groups[] =& $group;
				$teamInfo[$group->getGroupId()] = $memberships;
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
		list($conference, $schedConf) = parent::validate(true);

		AboutHandler::setupTemplate();

		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$templateMgr = &TemplateManager::getManager();

		$userId = isset($args[0])?(int)$args[0]:0;

		// Make sure we're fetching a biography for
		// a user who should appear on the listing;
		// otherwise we'll be exposing user information
		// that might not necessarily be public.

		// FIXME: This is pretty inefficient. Should be cached.

		if($schedConf) {
			$settings = $schedConf->getSettings(true);
			$schedConfId = $schedConf->getSchedConfId();
		} else {
			$settings = $conference->getSettings();
			$schedConfId = 0;
		}

		$user = null;
		if (!isset($settings['boardEnabled']) || $settings['boardEnabled'] != true) {
			$directors = &$roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getConferenceId());
			while ($potentialUser =& $directors->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user =& $potentialUser;
			}

			$trackDirectors = &$roleDao->getUsersByRoleId(ROLE_ID_TRACK_DIRECTOR, $conference->getConferenceId());
			while ($potentialUser =& $trackDirectors->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user =& $potentialUser;
			}

		} else {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups($conference->getConferenceId(), $schedConfId);
			while ($group =& $allGroups->next()) {
				if (!$group->getAboutDisplayed()) continue;
				$allMemberships =& $groupMembershipDao->getMemberships($group->getGroupId());
				while ($membership =& $allMemberships->next()) {
					if (!$membership->getAboutDisplayed()) continue;
					$potentialUser =& $membership->getUser();
					if ($potentialUser->getUserId() == $userId)
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

		AboutHandler::setupTemplate();

		$trackDirectorsDao = &DAORegistry::getDAO('TrackDirectorsDAO');
		$schedConf = &Request::getSchedConf();
		$conference =& Request::getConference();

		$templateMgr = &TemplateManager::getManager();
		$settings = ($schedConf? $schedConf->getSettings(true): $conference->getSettings());
		$templateMgr->assign('conferenceSettings', $settings);

		$templateMgr->display('about/editorialPolicies.tpl');
	}

	/**
	 * Display registration page.
	 */
	function registration() {
		parent::validate(true);

		AboutHandler::setupTemplate();

		$conferenceDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

		$schedConf = &Request::getSchedConf();
		$conference = &Request::getConference();

		if (!$schedConf || !$conference) Request::redirect(null, null, 'about');

		$registrationName = &$schedConf->getSetting('registrationName', true);
		$registrationEmail = &$schedConf->getSetting('registrationEmail', true);
		$registrationPhone = &$schedConf->getSetting('registrationPhone', true);
		$registrationFax = &$schedConf->getSetting('registrationFax', true);
		$registrationMailingAddress = &$schedConf->getSetting('registrationMailingAddress', true);
		$registrationAdditionalInformation = &$schedConf->getLocalizedSetting('registrationAdditionalInformation');
		$registrationTypes = &$registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getSchedConfId());

		$templateMgr = &TemplateManager::getManager();
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

		AboutHandler::setupTemplate();

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		$settings = ($schedConf? $schedConf->getSettings(true):$conference->getSettings());

		$templateMgr = &TemplateManager::getManager();
		$submissionChecklist = $schedConf?$schedConf->getLocalizedSetting('submissionChecklist'):null;
		if (!empty($submissionChecklist)) {
			ksort($submissionChecklist);
			reset($submissionChecklist);
		}
		$templateMgr->assign('submissionChecklist', $submissionChecklist);
		$templateMgr->assign_by_ref('conferenceSettings', $settings);
		$templateMgr->assign('helpTopicId','submission.presenterGuidelines');
		$templateMgr->display('about/submissions.tpl');
	}

	/**
	 * Display siteMap page.
	 */
	function siteMap() {
		parent::validate();

		AboutHandler::setupTemplate();
		$templateMgr = &TemplateManager::getManager();

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');

		$user = &Request::getUser();
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		if ($user) {
			$rolesByConference = array();
			$conferences = &$conferenceDao->getEnabledConferences();
			// Fetch the user's roles for each conference
			foreach ($conferences->toArray() as $conference) {
				$roles = &$roleDao->getRolesByUserId($user->getUserId(), $conference->getConferenceId());
				if (!empty($roles)) {
					$rolesByConference[$conference->getConferenceId()] = &$roles;
				}
			}
		}

		$conferences = &$conferenceDao->getEnabledConferences();
		$templateMgr->assign_by_ref('conferences', $conferences->toArray());
		if (isset($rolesByConference)) {
			$templateMgr->assign_by_ref('rolesByConference', $rolesByConference);
		}
		if ($user) {
			$templateMgr->assign('isSiteAdmin', $roleDao->getRole(0, 0, $user->getUserId(), ROLE_ID_SITE_ADMIN));
		}

		$templateMgr->display('about/siteMap.tpl');
	}

	/**
	 * Display aboutThisPublishingSystem page.
	 */
	function aboutThisPublishingSystem() {
		parent::validate();

		AboutHandler::setupTemplate();

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('ocsVersion', $version->getVersionString());

		foreach (array(Locale::getLocale(), $primaryLocale = Locale::getPrimaryLocale(), 'en_US') as $locale) {
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
		parent::validate();
		AboutHandler::setupTemplate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId','user.about');

		$statisticsYear = Request::getUserVar('statisticsYear');
		if (empty($statisticsYear)) $statisticsYear = date('Y');
		$templateMgr->assign('statisticsYear', $statisticsYear);

		$trackIds = $schedConf->getSetting('statisticsTrackIds');
		if (!is_array($trackIds)) $trackIds = array();
		$templateMgr->assign('trackIds', $trackIds);

		foreach (AboutHandler::getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $schedConf->getSetting($name));
		}
		$fromDate = mktime(0, 0, 0, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$schedConfStatisticsDao =& DAORegistry::getDAO('SchedConfStatisticsDAO');
		$paperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getSchedConfId(), null, $fromDate, $toDate);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$limitedPaperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getSchedConfId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('limitedPaperStatistics', $limitedPaperStatistics);

		$limitedPaperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getSchedConfId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$tracks =& $trackDao->getSchedConfTracks($schedConf->getSchedConfId());
		$templateMgr->assign('tracks', $tracks->toArray());

		$reviewerStatistics = $schedConfStatisticsDao->getReviewerStatistics($schedConf->getSchedConfId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$allUserStatistics = $schedConfStatisticsDao->getUserStatistics($schedConf->getSchedConfId(), null, $toDate);
		$templateMgr->assign('allUserStatistics', $allUserStatistics);

		$userStatistics = $schedConfStatisticsDao->getUserStatistics($schedConf->getSchedConfId(), $fromDate, $toDate);
		$templateMgr->assign('userStatistics', $userStatistics);

		$allRegistrationStatistics = $schedConfStatisticsDao->getRegistrationStatistics($schedConf->getSchedConfId(), null, $toDate);
		$templateMgr->assign('allRegistrationStatistics', $allRegistrationStatistics);

		$registrationStatistics = $schedConfStatisticsDao->getRegistrationStatistics($schedConf->getSchedConfId(), $fromDate, $toDate);
		$templateMgr->assign('registrationStatistics', $registrationStatistics);

		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notifiableUsers = $notificationStatusDao->getNotifiableUsersCount($schedConf->getSchedConfId());
		$templateMgr->assign('notifiableUsers', $notifiableUsers);

		$templateMgr->display('about/statistics.tpl');
	}

	function getPublicStatisticsNames() {
		import ('pages.manager.ManagerHandler');
		import ('pages.manager.StatisticsHandler');
		return StatisticsHandler::getPublicStatisticsNames();
	}

}

?>
