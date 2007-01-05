<?php

/**
 * AboutHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
 *
 * Handle requests for editor functions. 
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
		$conferencePath = Request::getRequestedConferencePath();
				
		if ($conferencePath != 'index' && $conferenceDao->conferenceExistsByPath($conferencePath)) {
			$event =& Request::getEvent();
			$conference =& Request::getConference();
			
			$settings = ($event? $event->getSettings(true):$conference->getSettings());
			
			$customAboutItems = &$settings['customAboutItems'];

			foreach (AboutHandler::getPublicStatisticsNames() as $name) {
				if (isset($settings[$name])) {
					$templateMgr->assign('publicStatisticsEnabled', true);
					break;
				} 
			}

			$templateMgr->assign('customAboutItems', $customAboutItems);
			$templateMgr->assign('helpTopicId', 'user.about');
			$templateMgr->assign_by_ref('conferenceSettings', $settings);
			$templateMgr->display('about/index.tpl');
		} else {
			$site = &Request::getSite();
			$about = $site->getAbout();
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
	function setupTemplate($subclass = false) {
		parent::validate();
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'about'), 'about.aboutTheConference')));
	}
	
	/**
	 * Display contact page.
	 */
	function contact() {
		parent::validate(true);
		
		AboutHandler::setupTemplate(true);
		
		$event = &Request::getEvent();
		$conference =& Request::getConference();
		
		$settings = ($event? $event->getSettings(true):$conference->getSettings());	
		$templateMgr = &TemplateManager::getManager();
		
		$templateMgr->assign_by_ref('conferenceSettings', $settings);
		$templateMgr->display('about/contact.tpl');
	}
	
	/**
	 * Display editorialTeam page.
	 */
	function editorialTeam() {
		parent::validate(true);
		AboutHandler::setupTemplate(true);

		$conference =& Request::getConference();
		$event =& Request::getEvent();
		
		$conferenceId = $conference->getConferenceId();
		$eventId = ($event? $event->getEventId():0);

		if($event)
			$settings = $event->getSettings(true);
		else
			$settings =& $conference->getSettings();

		$templateMgr = &TemplateManager::getManager();

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		// FIXME: This is pretty inefficient; should probably be cached.

		if ($settings['boardEnabled'] != true) {
			// Don't use the Editorial Team feature. Generate
			// Editorial Team information using Role info.
			$roleDao = &DAORegistry::getDAO('RoleDAO');

			$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $conference->getConferenceId());
			$editors = &$editors->toArray();

			$trackEditors = &$roleDao->getUsersByRoleId(ROLE_ID_TRACK_EDITOR, $conference->getConferenceId());
			$trackEditors = &$trackEditors->toArray();
		
			$templateMgr->assign_by_ref('editors', $editors);
			$templateMgr->assign_by_ref('trackEditors', $trackEditors);
			$templateMgr->display('about/editorialTeam.tpl');
		} else {
			// The Editorial Team feature has been enabled.
			// Generate information using Group data.
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups($conference->getConferenceId(), $event->getEventId());
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
			$templateMgr->display('about/editorialTeamBoard.tpl');
		}
	}

	/**
	 * Display a biography for an editorial team member.
	 */
	function editorialTeamBio($args) {
		parent::validate(true);
		
		AboutHandler::setupTemplate(true);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$conference = &Request::getConference();

		$templateMgr = &TemplateManager::getManager();

		$userId = isset($args[0])?(int)$args[0]:0;

		// Make sure we're fetching a biography for
		// a user who should appear on the listing;
		// otherwise we'll be exposing user information
		// that might not necessarily be public.

		// FIXME: This is pretty inefficient. Should be cached.

		$user = null;
		if ($settings['boardEnabled'] != true) {
			$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $conference->getConferenceId());
			while ($potentialUser =& $editors->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user =& $potentialUser;
			}

			$trackEditors = &$roleDao->getUsersByRoleId(ROLE_ID_TRACK_EDITOR, $conference->getConferenceId());
			while ($potentialUser =& $trackEditors->next()) {
				if ($potentialUser->getUserId() == $userId)
					$user =& $potentialUser;
			}

		} else {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

			$allGroups =& $groupDao->getGroups($conference->getConferenceId());
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

		if (!$user) Request::redirect(null, null, null, 'about', 'editorialTeam');

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		if ($user && $user->getCountry() != '') {
			$country = $countryDao->getCountry($user->getCountry());
			$templateMgr->assign('country', $country);
		}

		$templateMgr->assign_by_ref('user', $user);
		$templateMgr->display('about/editorialTeamBio.tpl');
	}

	/**
	 * Display editorialPolicies page.
	 */
	function editorialPolicies() {
		parent::validate(true);
		
		AboutHandler::setupTemplate(true);
		
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$trackEditorsDao = &DAORegistry::getDAO('TrackEditorsDAO');
		$conference = &Request::getConference();
		$event = &Request::getEvent();
		$conference =& Request::getConference();
				
		$templateMgr = &TemplateManager::getManager();
		$settings = ($event? $event->getSettings(true): $conference->getSettings());
		
		$templateMgr->assign_by_ref('conferenceSettings', $settings);
		if($event) {
			$tracks = &$trackDao->getEventTracks($event->getConferenceId());
			$tracks = &$tracks->toArray();
		} else {
			$tracks = array();
		}
		$templateMgr->assign_by_ref('tracks', $tracks);
		
		$trackEditors = array();
		foreach ($tracks as $track) {
			$trackEditors[$track->getTrackId()] = &$trackEditorsDao->getEditorsByTrackId($conference->getConferenceId(), $track->getTrackId());
		}
		$templateMgr->assign_by_ref('trackEditors', $trackEditors);

		$templateMgr->display('about/editorialPolicies.tpl');
	}

	/**
	 * Display registration page.
	 */
	function registration() {
		parent::validate(true);

		AboutHandler::setupTemplate(true);

		$conferenceDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

		$event = &Request::getEvent();
		$conference = &Request::getConference();

		$registrationName = &$event->getSetting('registrationName', true);
		$registrationEmail = &$event->getSetting('registrationEmail', true);
		$registrationPhone = &$event->getSetting('registrationPhone', true);
		$registrationFax = &$event->getSetting('registrationFax', true);
		$registrationMailingAddress = &$event->getSetting('registrationMailingAddress', true);
		$registrationAdditionalInformation = &$event->getSetting('registrationAdditionalInformation', true);
		$registrationTypes = &$registrationTypeDao->getRegistrationTypesByEventId($event->getEventId());

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
		
		AboutHandler::setupTemplate(true);

		$conference = &Request::getConference();
		$event = &Request::getEvent();
		
		$settings = ($event? $event->getSettings(true):$conference->getSettings());
				
		$templateMgr = &TemplateManager::getManager();
		if (isset($settings['submissionChecklist']) && count($settings['submissionChecklist']) > 0) {
			ksort($settings['submissionChecklist']);
			reset($settings['submissionChecklist']);
		}
		$templateMgr->assign_by_ref('conferenceSettings', $settings);
		$templateMgr->assign('helpTopicId','submission.authorGuidelines');
		$templateMgr->display('about/submissions.tpl');
	}

	/**
	 * Display Conference Sponsorship page.
	 */
	function conferenceSponsorship() {
		parent::validate();

		AboutHandler::setupTemplate(true);

		$conferenceOrEvent = &Request::getEvent();
		if(!$conferenceOrEvent)
			$conferenceOrEvent = &Request::getConference();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('contributorNote', $conferenceOrEvent->getSetting('contributorNote', true));
		$templateMgr->assign_by_ref('contributors', $conferenceOrEvent->getSetting('contributors', true));
		$templateMgr->assign('sponsorNote', $conferenceOrEvent->getSetting('sponsorNote', true));
		$templateMgr->assign_by_ref('sponsors', $conferenceOrEvent->getSetting('sponsors', true));
		$templateMgr->display('about/conferenceSponsorship.tpl');
	}
	
	/**
	 * Display siteMap page.
	 */
	function siteMap() {
		parent::validate();
		
		AboutHandler::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');

		$user = &Request::getUser();
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		if ($user) {
			$rolesByConference = array();
			$conferences = &$conferenceDao->getConferences();
			// Fetch the user's roles for each conference
			foreach ($conferences->toArray() as $conference) {
				$roles = &$roleDao->getRolesByUserId($user->getUserId(), $conference->getConferenceId());
				if (!empty($roles)) {
					$rolesByConference[$conference->getConferenceId()] = &$roles;
				}
			}
		}

		$conferences = &$conferenceDao->getConferences();
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
		
		AboutHandler::setupTemplate(true);
		
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('ocsVersion', $version->getVersionString());
		$templateMgr->display('about/aboutThisPublishingSystem.tpl');
	}
	
	/**
	 * Display a list of public stats for the current conference.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the reader's statistics view in the About pages.
	 */
	function statistics() {
		parent::validate();
		AboutHandler::setupTemplate(true);

		$conference = &Request::getConference();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId','user.about');

		$statisticsYear = Request::getUserVar('statisticsYear');
		if (empty($statisticsYear)) $statisticsYear = date('Y');
		$templateMgr->assign('statisticsYear', $statisticsYear);

		$trackIds = $conference->getSetting('statisticsTrackIds');
		if (!is_array($trackIds)) $trackIds = array();
		$templateMgr->assign('trackIds', $trackIds);

		foreach (AboutHandler::getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $conference->getSetting($name));
		}
		$fromDate = mktime(0, 0, 1, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$conferenceStatisticsDao =& DAORegistry::getDAO('ConferenceStatisticsDAO');
		$articleStatistics = $conferenceStatisticsDao->getArticleStatistics($conference->getConferenceId(), null, $fromDate, $toDate);
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$limitedArticleStatistics = $conferenceStatisticsDao->getArticleStatistics($conference->getConferenceId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('limitedArticleStatistics', $limitedArticleStatistics);

		$limitedArticleStatistics = $conferenceStatisticsDao->getArticleStatistics($conference->getConferenceId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('articleStatistics', $articleStatistics);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$tracks =& $trackDao->getConferenceTracks($conference->getConferenceId());
		$templateMgr->assign('tracks', $tracks->toArray());
		
		$issueStatistics = $conferenceStatisticsDao->getIssueStatistics($conference->getConferenceId(), $fromDate, $toDate);
		$templateMgr->assign('issueStatistics', $issueStatistics);

		$reviewerStatistics = $conferenceStatisticsDao->getReviewerStatistics($event->getEventId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$allUserStatistics = $conferenceStatisticsDao->getUserStatistics($conference->getConferenceId(), null, $toDate);
		$templateMgr->assign('allUserStatistics', $allUserStatistics);

		$userStatistics = $conferenceStatisticsDao->getUserStatistics($conference->getConferenceId(), $fromDate, $toDate);
		$templateMgr->assign('userStatistics', $userStatistics);

		$enableRegistration = $conference->getSetting('enableRegistration');
		if ($enableRegistration) {
			$templateMgr->assign('enableRegistration', true);
			$allRegistrationStatistics = $conferenceStatisticsDao->getRegistrationStatistics($conference->getConferenceId(), null, $toDate);
			$templateMgr->assign('allRegistrationStatistics', $allRegistrationStatistics);

			$registrationStatistics = $conferenceStatisticsDao->getRegistrationStatistics($conference->getConferenceId(), $fromDate, $toDate);
			$templateMgr->assign('registrationStatistics', $registrationStatistics);
		}

		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notifiableUsers = $notificationStatusDao->getNotifiableUsersCount($conference->getConferenceId());
		$templateMgr->assign('notifiableUsers', $notifiableUsers);

		$templateMgr->display('about/statistics.tpl');
	}

	function getPublicStatisticsNames() {
		import ('pages.eventDirector.EventDirectorHandler');
		import ('pages.eventDirector.StatisticsHandler');
		return StatisticsHandler::getPublicStatisticsNames();
	}

}

?>
