<?php

/**
 * EventDirectorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for conference management functions. 
 *
 * $Id$
 */

class EventDirectorHandler extends Handler {

	/**
	 * Display conference management index page.
	 */
	function index() {
		list($conference, $event) = EventDirectorHandler::validate(false);
		EventDirectorHandler::setupTemplate();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('conference', $conference);

		if(!$event) {
			// The event page has been selected without an event!
			// Show a list of candidates.
			
			$eventDao = &DAORegistry::getDao('EventDAO');
			$events = &$eventDao->getEventsByConferenceId($conference->getConferenceId());
			$templateMgr->assign_by_ref('events', $events);
			$templateMgr->display(ROLE_PATH_EVENT_DIRECTOR . '/events.tpl');
		} else {
			$registrationEnabled = $event->getSetting('enableRegistration', true);
			$announcementsEnabled = $event->getSetting('enableAnnouncements', true);

			$templateMgr->assign('registrationEnabled', $registrationEnabled);
			$templateMgr->assign('announcementsEnabled', $announcementsEnabled);
			$templateMgr->assign('helpTopicId','conference.index');
			$templateMgr->display(ROLE_PATH_EVENT_DIRECTOR . '/index.tpl');
		}
	}
	
	/**
	 * Send an email to a user or group of users.
	 */
	function email($args) {
		list($conference, $event) = parent::validate();

		EventDirectorHandler::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.users.emailUsers');

		$userDao = &DAORegistry::getDAO('UserDAO');

		$site = &Request::getSite();
		$user = &Request::getUser();

		import('mail.MailTemplate');
		$email = &new MailTemplate(Request::getUserVar('template'), Request::getUserVar('locale'));
		
		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();
			Request::redirect(null, null, Request::getRequestedPage());
		} else {
			$email->assignParams(); // FIXME Forces default parameters to be assigned (should do this automatically in MailTemplate?)
			if (!Request::getUserVar('continued')) {
				if (($groupId = Request::getUserVar('toGroup')) != '') {
					// Special case for emailing entire groups:
					// Check for a group ID and add recipients.
					$groupDao =& DAORegistry::getDAO('GroupDAO');
					$group =& $groupDao->getGroup($groupId);
					if ($group && $group->getConferenceId() == $conference->getConferenceId()) {
						$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
						$memberships =& $groupMembershipDao->getMemberships($group->getGroupId());
						$memberships =& $memberships->toArray();
						foreach ($memberships as $membership) {
							$user =& $membership->getUser();
							$email->addRecipient($user->getEmail(), $user->getFullName());
						}
					}
				}
				if (count($email->getRecipients())==0) $email->addRecipient($user->getEmail(), $user->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, null, 'email'), array(), ROLE_PATH_EVENT_DIRECTOR . '/people/email.tpl');
		}
	}

	/**
	 * Validate that user has permissions to manage the selected conference.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate($needsEvent = true) {
		list($conference, $event) = Handler::validate(true, $needsEvent);
		if (!$conference ||
				(!Validation::isEventDirector() &&
				 !Validation::isConferenceDirector() &&
				 !Validation::isSiteAdmin())) {
			Validation::redirectLogin();
		}
		return array($conference, $event);
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$conference = &Request::getConference();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(
					array(Request::url(null, null, 'user'), 'navigation.user'),
					array(Request::url(null, null, ROLE_PATH_EVENT_DIRECTOR), 'director.eventManagement'))
				: array(
					array(Request::url(null, null, 'user'), 'navigation.user'),
			));
	}
	
	
	//
	// Setup
	//

	function setup($args) {
		import('pages.eventDirector.EventDirectorSetupHandler');
		EventDirectorSetupHandler::setup($args);
	}

	function saveSetup($args) {
		import('pages.eventDirector.EventDirectorSetupHandler');
		EventDirectorSetupHandler::saveSetup($args);
	}

	//
	// Timeline Management
	//
	
	function timeline($args) {
		import('pages.eventDirector.TimelineHandler');
		TimelineHandler::timeline($args);
	}

	function updateTimeline($args) {
		import('pages.eventDirector.TimelineHandler');
		TimelineHandler::updateTimeline($args);
	}

	//
	// People Management
	//

	function people($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::people($args);
	}
	
	function enrollSearch($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::enrollSearch($args);
	}
	
	function enroll($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::enroll($args);
	}
	
	function unEnroll($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::unEnroll($args);
	}
	
	function enrollSyncSelect($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::enrollSyncSelect($args);
	}
	
	function enrollSync($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::enrollSync($args);
	}
	
	function createUser() {
		import('pages.director.PeopleHandler');
		PeopleHandler::createUser();
	}

	function mergeUsers($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::mergeUsers($args);
	}
	
	function disableUser($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::disableUser($args);
	}
	
	function enableUser($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::enableUser($args);
	}
	
	function removeUser($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::removeUser($args);
	}
	
	function editUser($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::editUser($args);
	}
	
	function updateUser() {
		import('pages.director.PeopleHandler');
		PeopleHandler::updateUser();
	}
	
	function userProfile($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::userProfile($args);
	}
	
	function signInAsUser($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::signInAsUser($args);
	}
	
	function signOutAsUser() {
		import('pages.director.PeopleHandler');
		PeopleHandler::signOutAsUser();
	}
	
	
	//
	// Track Management
	//
	
	function tracks() {
		import('pages.eventDirector.TrackHandler');
		TrackHandler::tracks();
	}
	
	function createTrack() {
		import('pages.eventDirector.TrackHandler');
		TrackHandler::createTrack();
	}
	
	function editTrack($args) {
		import('pages.eventDirector.TrackHandler');
		TrackHandler::editTrack($args);
	}
	
	function updateTrack() {
		import('pages.eventDirector.TrackHandler');
		TrackHandler::updateTrack();
	}
	
	function deleteTrack($args) {
		import('pages.eventDirector.TrackHandler');
		TrackHandler::deleteTrack($args);
	}
	
	function moveTrack() {
		import('pages.eventDirector.TrackHandler');
		TrackHandler::moveTrack();
	}
	
	
	//
	// E-mail Management
	//
	
	function emails($args) {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::emails($args);
	}
	
	function createEmail($args) {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::createEmail($args);
	}
	
	function editEmail($args) {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::editEmail($args);
	}
	
	function updateEmail() {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::updateEmail();
	}
	
	function deleteCustomEmail($args) {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::deleteCustomEmail($args);
	}
	
	function resetEmail($args) {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::resetEmail($args);
	}
	
	function disableEmail($args) {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::disableEmail($args);
	}
	
	function enableEmail($args) {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::enableEmail($args);
	}
	
	function resetAllEmails() {
		import('pages.eventDirector.EmailHandler');
		EmailHandler::resetAllEmails();
	}
	
	function selectTemplate($args) {
		import('pages.director.PeopleHandler');
		PeopleHandler::selectTemplate($args);
	}
	
	
	//
	// Registration Policies 
	//

	function registrationPolicies() {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::registrationPolicies();
	}

	function saveRegistrationPolicies($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::saveRegistrationPolicies($args);
	}


	//
	// Registration Types
	//

	function registrationTypes() {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::registrationTypes();
	}

	function deleteRegistrationType($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::deleteRegistrationType($args);
	}

	function createRegistrationType() {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::createRegistrationType();
	}

	function selectSubscriber($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::selectSubscriber($args);
	}

	function editRegistrationType($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::editRegistrationType($args);
	}

	function updateRegistrationType($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::updateRegistrationType($args);
	}

	function moveRegistrationType($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::moveRegistrationType($args);
	}


	//
	// Registrations
	//

	function registration() {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::registrations();
	}

	function deleteRegistration($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::deleteRegistration($args);
	}

	function createRegistration() {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::createRegistration();
	}

	function editRegistration($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::editRegistration($args);
	}

	function updateRegistration($args) {
		import('pages.eventDirector.RegistrationHandler');
		RegistrationHandler::updateRegistration($args);
	}


	//
	// Announcement Types 
	//

	function announcementTypes() {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::announcementTypes();
	}

	function deleteAnnouncementType($args) {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::deleteAnnouncementType($args);
	}

	function createAnnouncementType() {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::createAnnouncementType();
	}

	function editAnnouncementType($args) {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::editAnnouncementType($args);
	}

	function updateAnnouncementType($args) {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::updateAnnouncementType($args);
	}


	//
	// Announcements 
	//

	function announcements() {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::announcements();
	}

	function deleteAnnouncement($args) {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::deleteAnnouncement($args);
	}

	function createAnnouncement() {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::createAnnouncement();
	}

	function editAnnouncement($args) {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::editAnnouncement($args);
	}

	function updateAnnouncement($args) {
		import('pages.director.AnnouncementHandler');
		AnnouncementHandler::updateAnnouncement($args);
	}

	//
	// Group Management
	//

	function groups($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::groups($args);
	}

	function createGroup($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::createGroup($args);
	}

	function updateGroup($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::updateGroup($args);
	}

	function deleteGroup($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::deleteGroup($args);
	}

	function editGroup($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::editGroup($args);
	}

	function groupMembership($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::groupMembership($args);
	}

	function addMembership($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::addMembership($args);
	}

	function deleteMembership($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::deleteMembership($args);
	}

	function setBoardEnabled($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::setBoardEnabled($args);
	}

	function moveGroup($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::moveGroup($args);
	}

	function moveMembership($args) {
		import('pages.eventDirector.GroupHandler');
		GroupHandler::moveMembership($args);
	}

	//
	// Statistics Functions
	//

	function statistics($args) {
		import('pages.eventDirector.StatisticsHandler');
		StatisticsHandler::statistics($args);
	}
	
	function saveStatisticsTracks() {
		import('pages.eventDirector.StatisticsHandler');
		StatisticsHandler::saveStatisticsTracks();
	}

	function savePublicStatisticsList() {
		import('pages.eventDirector.StatisticsHandler');
		StatisticsHandler::savePublicStatisticsList();
	}

	function reportGenerator($args) {
		import('pages.eventDirector.StatisticsHandler');
		StatisticsHandler::reportGenerator($args);
	}
}

?>
