<?php

/**
 * DirectorHandler.inc.php
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

class DirectorHandler extends Handler {

	/**
	 * Display conference management index page.
	 */
	function index() {
		list($conference, $event) = Handler::validate(true, false);
		DirectorHandler::setupTemplate();

		$templateMgr = &TemplateManager::getManager();

		$eventDao =& DAORegistry::getDAO('EventDAO');
		$events =& $eventDao->getEventsByConferenceId($conference->getConferenceId());
		$templateMgr->assign_by_ref('events', $events);
		
		$templateMgr->assign('helpTopicId','conference.index');
		$templateMgr->display(ROLE_PATH_CONFERENCE_DIRECTOR . '/index.tpl');
	}


	/**
	 * Send an email to a user or group of users.
	 */
	function email($args) {
		list($conference, $event) = parent::validate();

		DirectorHandler::setupTemplate(true);
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
			$email->displayEditForm(Request::url(null, null, null, 'email'), array(), 'director/people/email.tpl');
		}
	}


	/**
	 * Validate that user has permissions to manage the selected conference.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		list($conference, $event) = Handler::validate(true, false);
	
		if (!$conference || (!Validation::isConferenceDirector() && !Validation::isSiteAdmin())) {
			Validation::redirectLogin();
		}
		
		return array($conference, $event);
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(
					array(Request::url(null, null, 'user'), 'navigation.user'),
					array(Request::url(null, 'index', 'director'), 'user.role.director'))
				: array(array(Request::url(null, null, 'user'), 'navigation.user'))
		);
	}
	
	
	//
	// Setup
	//

	function setup($args) {
		import('pages.director.DirectorSetupHandler');
		DirectorSetupHandler::setup($args);
	}

	function saveSetup($args) {
		import('pages.director.DirectorSetupHandler');
		DirectorSetupHandler::saveSetup($args);
	}

	function downloadLayoutTemplate($args) {
		import('pages.director.DirectorSetupHandler');
		DirectorSetupHandler::downloadLayoutTemplate($args);
	}
	
	//
	// Event Setup
	//

	function eventSetup($args) {
		import('pages.director.EventDirectorSetupHandler');
		EventDirectorSetupHandler::setup($args);
	}

	function saveEventSetup($args) {
		import('pages.director.EventDirectorSetupHandler');
		EventDirectorSetupHandler::saveSetup($args);
	}

	//
	// Event Management
	//

	function events($args) {
		import('pages.director.DirectorEventHandler');
		DirectorEventHandler::events($args);
	}

	function createEvent($args) {
		import('pages.director.DirectorEventHandler');
		DirectorEventHandler::createEvent($args);
	}

	function editEvent($args) {
		import('pages.director.DirectorEventHandler');
		DirectorEventHandler::editEvent($args);
	}

	function updateEvent($args) {
		import('pages.director.DirectorEventHandler');
		DirectorEventHandler::updateEvent($args);
	}

	function deleteEvent($args) {
		import('pages.director.DirectorEventHandler');
		DirectorEventHandler::deleteEvent($args);
	}

	function moveEvent($args) {
		import('pages.director.DirectorEventHandler');
		DirectorEventHandler::moveEvent($args);
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
		import('pages.director.TrackHandler');
		TrackHandler::tracks();
	}
	
	function createTrack() {
		import('pages.director.TrackHandler');
		TrackHandler::createTrack();
	}
	
	function editTrack($args) {
		import('pages.director.TrackHandler');
		TrackHandler::editTrack($args);
	}
	
	function updateTrack() {
		import('pages.director.TrackHandler');
		TrackHandler::updateTrack();
	}
	
	function deleteTrack($args) {
		import('pages.director.TrackHandler');
		TrackHandler::deleteTrack($args);
	}
	
	function moveTrack() {
		import('pages.director.TrackHandler');
		TrackHandler::moveTrack();
	}
	
	
	//
	// E-mail Management
	//
	
	function emails($args) {
		import('pages.director.EmailHandler');
		EmailHandler::emails($args);
	}
	
	function createEmail($args) {
		import('pages.director.EmailHandler');
		EmailHandler::createEmail($args);
	}
	
	function editEmail($args) {
		import('pages.director.EmailHandler');
		EmailHandler::editEmail($args);
	}
	
	function updateEmail() {
		import('pages.director.EmailHandler');
		EmailHandler::updateEmail();
	}
	
	function deleteCustomEmail($args) {
		import('pages.director.EmailHandler');
		EmailHandler::deleteCustomEmail($args);
	}
	
	function resetEmail($args) {
		import('pages.director.EmailHandler');
		EmailHandler::resetEmail($args);
	}
	
	function disableEmail($args) {
		import('pages.director.EmailHandler');
		EmailHandler::disableEmail($args);
	}
	
	function enableEmail($args) {
		import('pages.director.EmailHandler');
		EmailHandler::enableEmail($args);
	}
	
	function resetAllEmails() {
		import('pages.director.EmailHandler');
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
		import('pages.director.RegistrationHandler');
		RegistrationHandler::registrationPolicies();
	}

	function saveRegistrationPolicies($args) {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::saveRegistrationPolicies($args);
	}


	//
	// Registration Types
	//

	function registrationTypes() {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::registrationTypes();
	}

	function deleteRegistrationType($args) {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::deleteRegistrationType($args);
	}

	function createRegistrationType() {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::createRegistrationType();
	}

	function selectSubscriber($args) {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::selectSubscriber($args);
	}

	function editRegistrationType($args) {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::editRegistrationType($args);
	}

	function updateRegistrationType($args) {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::updateRegistrationType($args);
	}

	function moveRegistrationType($args) {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::moveRegistrationType($args);
	}


	//
	// Registrations
	//

	function registration() {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::registrations();
	}

	function deleteRegistration($args) {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::deleteRegistration($args);
	}

	function createRegistration() {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::createRegistration();
	}

	function editRegistration($args) {
		import('pages.director.RegistrationHandler');
		RegistrationHandler::editRegistration($args);
	}

	function updateRegistration($args) {
		import('pages.director.RegistrationHandler');
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
		import('pages.director.GroupHandler');
		GroupHandler::groups($args);
	}

	function createGroup($args) {
		import('pages.director.GroupHandler');
		GroupHandler::createGroup($args);
	}

	function updateGroup($args) {
		import('pages.director.GroupHandler');
		GroupHandler::updateGroup($args);
	}

	function deleteGroup($args) {
		import('pages.director.GroupHandler');
		GroupHandler::deleteGroup($args);
	}

	function editGroup($args) {
		import('pages.director.GroupHandler');
		GroupHandler::editGroup($args);
	}

	function groupMembership($args) {
		import('pages.director.GroupHandler');
		GroupHandler::groupMembership($args);
	}

	function addMembership($args) {
		import('pages.director.GroupHandler');
		GroupHandler::addMembership($args);
	}

	function deleteMembership($args) {
		import('pages.director.GroupHandler');
		GroupHandler::deleteMembership($args);
	}

	function setBoardEnabled($args) {
		import('pages.director.GroupHandler');
		GroupHandler::setBoardEnabled($args);
	}

	function moveGroup($args) {
		import('pages.director.GroupHandler');
		GroupHandler::moveGroup($args);
	}

	function moveMembership($args) {
		import('pages.director.GroupHandler');
		GroupHandler::moveMembership($args);
	}

	//
	// Statistics Functions
	//

	function statistics($args) {
		import('pages.director.StatisticsHandler');
		StatisticsHandler::statistics($args);
	}
	
	function saveStatisticsTracks() {
		import('pages.director.StatisticsHandler');
		StatisticsHandler::saveStatisticsTracks();
	}

	function savePublicStatisticsList() {
		import('pages.director.StatisticsHandler');
		StatisticsHandler::savePublicStatisticsList();
	}

	function reportGenerator($args) {
		import('pages.director.StatisticsHandler');
		StatisticsHandler::reportGenerator($args);
	}


	//
	// Languages
	//
	
	function languages() {
		import('pages.director.ConferenceLanguagesHandler');
		ConferenceLanguagesHandler::languages();
	}
	
	function saveLanguageSettings() {
		import('pages.director.ConferenceLanguagesHandler');
		ConferenceLanguagesHandler::saveLanguageSettings();
	}
	
	
	//
	// Files Browser
	//
	
	function files($args) {
		import('pages.director.FilesHandler');
		FilesHandler::files($args);
	}
	
	function fileUpload($args) {
		import('pages.director.FilesHandler');
		FilesHandler::fileUpload($args);
	}
	
	function fileMakeDir($args) {
		import('pages.director.FilesHandler');
		FilesHandler::fileMakeDir($args);
	}
	
	function fileDelete($args) {
		import('pages.director.FilesHandler');
		FilesHandler::fileDelete($args);
	}


	//
	// Import/Export
	//

	function importexport($args) {
		import('pages.director.ImportExportHandler');
		ImportExportHandler::importExport($args);
	}

	//
	// Plugin Management
	//

	function plugins($args) {
		import('pages.director.PluginHandler');
		PluginHandler::plugins($args);
	}

	function plugin($args) {
		import('pages.director.PluginHandler');
		PluginHandler::plugin($args);
	}
}

?>
