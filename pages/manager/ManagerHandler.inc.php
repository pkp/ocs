<?php

/**
 * @file ManagerHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for conference management functions. 
 */

//$Id$


import('core.PKPHandler');

class ManagerHandler extends PKPHandler {

	/**
	 * Display conference management index page.
	 */
	function index() {
		// Manager requests should come to the Conference context, not Sched Conf
		if (Request::getRequestedSchedConfPath() != 'index') Request::redirect(null, 'index', 'manager');

		list($conference, $schedConf) = ManagerHandler::validate(true, false);
		ManagerHandler::setupTemplate();

		$templateMgr =& TemplateManager::getManager();

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conference->getConferenceId());
		$templateMgr->assign_by_ref('schedConfs', $schedConfs);

		$announcementsEnabled = $conference->getSetting('enableAnnouncements');
		$templateMgr->assign('announcementsEnabled', $announcementsEnabled);

		$templateMgr->assign('helpTopicId','conference.index');
		$templateMgr->display(ROLE_PATH_CONFERENCE_MANAGER . '/index.tpl');
	}


	/**
	 * Send an email to a user or group of users.
	 */
	function email($args) {
		list($conference, $schedConf) = parent::validate();
		ManagerHandler::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.users.emailUsers');

		$userDao = &DAORegistry::getDAO('UserDAO');

		$site = &Request::getSite();
		$user = &Request::getUser();

		import('mail.MailTemplate');
		$email = new MailTemplate(Request::getUserVar('template'), Request::getUserVar('locale'));

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
			$email->displayEditForm(Request::url(null, null, null, 'email'), array(), 'manager/people/email.tpl');
		}
	}


	/**
	 * Validate that user has permissions to manage the selected conference.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		list($conference, $schedConf) = PKPHandler::validate(true, false);

		if (!$conference || (!Validation::isConferenceManager() && !Validation::isSiteAdmin())) {
			Validation::redirectLogin();
		}

		return array($conference, $schedConf);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OCS_MANAGER));
		$templateMgr = &TemplateManager::getManager();
		$pageHierarchy = array();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if ($schedConf) {
			$pageHierarchy[] = array(Request::url(null, null, 'index'), $schedConf->getFullTitle(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true);
		}

		if ($subclass) {
			$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
			$pageHierarchy[] = array(Request::url(null, 'index', 'manager'), 'manager.conferenceSiteManagement');
		} else {
			$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
		}

		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}


	//
	// Setup
	//

	function setup($args) {
		import('pages.manager.ManagerSetupHandler');
		ManagerSetupHandler::setup($args);
	}

	function saveSetup($args) {
		import('pages.manager.ManagerSetupHandler');
		ManagerSetupHandler::saveSetup($args);
	}

	function setupSaved($args) {
		import('pages.manager.ManagerSetupHandler');
		ManagerSetupHandler::setupSaved($args);
	}

	//
	// Scheduled Conference Setup
	//

	function schedConfSetup($args) {
		import('pages.manager.SchedConfSetupHandler');
		SchedConfSetupHandler::setup($args);
	}

	function saveSchedConfSetup($args) {
		import('pages.manager.SchedConfSetupHandler');
		SchedConfSetupHandler::saveSetup($args);
	}

	function schedConfSetupSaved($args) {
		import('pages.manager.SchedConfSetupHandler');
		SchedConfSetupHandler::schedConfSetupSaved($args);
	}

	//
	// Scheduled Conference Management
	//

	function schedConfs($args) {
		import('pages.manager.ManagerSchedConfHandler');
		ManagerSchedConfHandler::schedConfs($args);
	}

	function createSchedConf($args) {
		import('pages.manager.ManagerSchedConfHandler');
		ManagerSchedConfHandler::createSchedConf($args);
	}

	function editSchedConf($args) {
		import('pages.manager.ManagerSchedConfHandler');
		ManagerSchedConfHandler::editSchedConf($args);
	}

	function updateSchedConf($args) {
		import('pages.manager.ManagerSchedConfHandler');
		ManagerSchedConfHandler::updateSchedConf($args);
	}

	function deleteSchedConf($args) {
		import('pages.manager.ManagerSchedConfHandler');
		ManagerSchedConfHandler::deleteSchedConf($args);
	}

	function moveSchedConf($args) {
		import('pages.manager.ManagerSchedConfHandler');
		ManagerSchedConfHandler::moveSchedConf($args);
	}

	//
	// People Management
	//

	function people($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::people($args);
	}

	function enrollSearch($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enrollSearch($args);
	}

	function enroll($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enroll($args);
	}

	function unEnroll($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::unEnroll($args);
	}

	function enrollSyncSelect($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enrollSyncSelect($args);
	}

	function enrollSync($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enrollSync($args);
	}

	function createUser() {
		import('pages.manager.PeopleHandler');
		PeopleHandler::createUser();
	}

	function suggestUsername() {
		import('pages.manager.PeopleHandler');
		PeopleHandler::suggestUsername();
	}

	function mergeUsers($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::mergeUsers($args);
	}

	function disableUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::disableUser($args);
	}

	function enableUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::enableUser($args);
	}

	function removeUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::removeUser($args);
	}

	function editUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::editUser($args);
	}

	function updateUser() {
		import('pages.manager.PeopleHandler');
		PeopleHandler::updateUser();
	}

	function userProfile($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::userProfile($args);
	}

	function signInAsUser($args) {
		import('pages.manager.PeopleHandler');
		PeopleHandler::signInAsUser($args);
	}

	function signOutAsUser() {
		import('pages.manager.PeopleHandler');
		PeopleHandler::signOutAsUser();
	}


	//
	// Track Management
	//

	function tracks() {
		import('pages.manager.TrackHandler');
		TrackHandler::tracks();
	}

	function createTrack() {
		import('pages.manager.TrackHandler');
		TrackHandler::createTrack();
	}

	function editTrack($args) {
		import('pages.manager.TrackHandler');
		TrackHandler::editTrack($args);
	}

	function updateTrack() {
		import('pages.manager.TrackHandler');
		TrackHandler::updateTrack();
	}

	function deleteTrack($args) {
		import('pages.manager.TrackHandler');
		TrackHandler::deleteTrack($args);
	}

	function moveTrack() {
		import('pages.manager.TrackHandler');
		TrackHandler::moveTrack();
	}
	
	//
	// Review Form Management
	//

	function reviewForms() {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::reviewForms();
	}

	function createReviewForm() {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::createReviewForm();
	}

	function editReviewForm($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::editReviewForm($args);
	}

	function updateReviewForm() {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::updateReviewForm();
	}

	function previewReviewForm($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::previewReviewForm($args);
	}

	function deleteReviewForm($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::deleteReviewForm($args);
	}

	function activateReviewForm($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::activateReviewForm($args);
	}

	function deactivateReviewForm($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::deactivateReviewForm($args);
	}

	function copyReviewForm($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::copyReviewForm($args);
	}

	function moveReviewForm() {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::moveReviewForm();
	}

	function reviewFormElements($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::reviewFormElements($args);
	}

	function createReviewFormElement($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::createReviewFormElement($args);
	}

	function editReviewFormElement($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::editReviewFormElement($args);
	}

	function deleteReviewFormElement($args) {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::deleteReviewFormElement($args);
	}

	function updateReviewFormElement() {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::updateReviewFormElement();
	}

	function moveReviewFormElement() {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::moveReviewFormElement();
	}
	
	function copyReviewFormElement() {
		import('pages.manager.ReviewFormHandler');
		ReviewFormHandler::copyReviewFormElement();
	}

	//
	// E-mail Management
	//

	function emails($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::emails($args);
	}

	function createEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::createEmail($args);
	}

	function editEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::editEmail($args);
	}

	function updateEmail() {
		import('pages.manager.EmailHandler');
		EmailHandler::updateEmail();
	}

	function deleteCustomEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::deleteCustomEmail($args);
	}

	function resetEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::resetEmail($args);
	}

	function disableEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::disableEmail($args);
	}

	function enableEmail($args) {
		import('pages.manager.EmailHandler');
		EmailHandler::enableEmail($args);
	}

	function resetAllEmails() {
		import('pages.manager.EmailHandler');
		EmailHandler::resetAllEmails();
	}


	//
	// Registration Policies 
	//

	function registrationPolicies() {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::registrationPolicies();
	}

	function saveRegistrationPolicies($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::saveRegistrationPolicies($args);
	}


	//
	// Registration Types
	//

	function registrationTypes() {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::registrationTypes();
	}

	function deleteRegistrationType($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::deleteRegistrationType($args);
	}

	function createRegistrationType() {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::createRegistrationType();
	}

	function selectRegistrant($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::selectRegistrant($args);
	}

	function editRegistrationType($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::editRegistrationType($args);
	}

	function updateRegistrationType($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::updateRegistrationType($args);
	}

	function moveRegistrationType($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::moveRegistrationType($args);
	}


	//
	// Registration Options
	//

	function registrationOptions() {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::registrationOptions();
	}

	function deleteRegistrationOption($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::deleteRegistrationOption($args);
	}

	function createRegistrationOption() {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::createRegistrationOption();
	}

	function editRegistrationOption($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::editRegistrationOption($args);
	}

	function updateRegistrationOption($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::updateRegistrationOption($args);
	}

	function moveRegistrationOption($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::moveRegistrationOption($args);
	}
	
		
	//
	// Registration
	//

	function registration() {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::registration();
	}

	function deleteRegistration($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::deleteRegistration($args);
	}

	function createRegistration() {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::createRegistration();
	}

	function editRegistration($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::editRegistration($args);
	}

	function updateRegistration($args) {
		import('pages.manager.RegistrationHandler');
		RegistrationHandler::updateRegistration($args);
	}


	//
	// Scheduler
	//

	function scheduler() {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::scheduler();
	}

	function saveSchedule() {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::saveSchedule();
	}

	// Buildings

	function buildings() {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::buildings();
	}

	function deleteBuilding($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::deleteBuilding($args);
	}

	function editBuilding($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::editBuilding($args);
	}

	function createBuilding() {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::createBuilding();
	}

	function updateBuilding($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::updateBuilding($args);
	}

	// Rooms

	function rooms($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::rooms($args);
	}

	function deleteRoom($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::deleteRoom($args);
	}

	function editRoom($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::editRoom($args);
	}

	function createRoom($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::createRoom($args);
	}

	function updateRoom($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::updateRoom($args);
	}

	// Special Events

	function specialEvents() {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::specialEvents();
	}

	function deleteSpecialEvent($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::deleteSpecialEvent($args);
	}

	function editSpecialEvent($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::editSpecialEvent($args);
	}

	function createSpecialEvent() {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::createSpecialEvent();
	}

	function updateSpecialEvent($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::updateSpecialEvent($args);
	}

	// Scheduler

	function schedule($args) {
		import('pages.manager.SchedulerHandler');
		SchedulerHandler::schedule($args);
	}

	//
	// Announcement Types 
	//

	function announcementTypes() {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::announcementTypes();
	}

	function deleteAnnouncementType($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::deleteAnnouncementType($args);
	}

	function createAnnouncementType() {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::createAnnouncementType();
	}

	function editAnnouncementType($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::editAnnouncementType($args);
	}

	function updateAnnouncementType($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::updateAnnouncementType($args);
	}


	//
	// Announcements 
	//

	function announcements() {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::announcements();
	}

	function deleteAnnouncement($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::deleteAnnouncement($args);
	}

	function createAnnouncement() {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::createAnnouncement();
	}

	function editAnnouncement($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::editAnnouncement($args);
	}

	function updateAnnouncement($args) {
		import('pages.manager.AnnouncementHandler');
		AnnouncementHandler::updateAnnouncement($args);
	}

	//
	// Group Management
	//

	function groups($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::groups($args);
	}

	function createGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::createGroup($args);
	}

	function updateGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::updateGroup($args);
	}

	function deleteGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::deleteGroup($args);
	}

	function editGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::editGroup($args);
	}

	function groupMembership($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::groupMembership($args);
	}

	function addMembership($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::addMembership($args);
	}

	function deleteMembership($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::deleteMembership($args);
	}

	function setBoardEnabled($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::setBoardEnabled($args);
	}

	function moveGroup($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::moveGroup($args);
	}

	function moveMembership($args) {
		import('pages.manager.GroupHandler');
		GroupHandler::moveMembership($args);
	}

	//
	// Statistics Functions
	//

	function statistics($args) {
		import('pages.manager.StatisticsHandler');
		StatisticsHandler::statistics($args);
	}

	function saveStatisticsTracks() {
		import('pages.manager.StatisticsHandler');
		StatisticsHandler::saveStatisticsTracks();
	}

	function savePublicStatisticsList() {
		import('pages.manager.StatisticsHandler');
		StatisticsHandler::savePublicStatisticsList();
	}

	function report($args) {
		import('pages.manager.StatisticsHandler');
		StatisticsHandler::report($args);
	}


	//
	// Languages
	//

	function languages() {
		import('pages.manager.ConferenceLanguagesHandler');
		ConferenceLanguagesHandler::languages();
	}

	function saveLanguageSettings() {
		import('pages.manager.ConferenceLanguagesHandler');
		ConferenceLanguagesHandler::saveLanguageSettings();
	}

	//
	// Program
	//

	function program() {
		import('pages.manager.ManagerProgramHandler');
		ManagerProgramHandler::program();
	}

	function saveProgramSettings() {
		import('pages.manager.ManagerProgramHandler');
		ManagerProgramHandler::saveProgramSettings();
	}


	//
	// Accommodation
	//

	function accommodation() {
		import('pages.manager.ManagerAccommodationHandler');
		ManagerAccommodationHandler::accommodation();
	}

	function saveAccommodationSettings() {
		import('pages.manager.ManagerAccommodationHandler');
		ManagerAccommodationHandler::saveAccommodationSettings();
	}


	//
	// Payment
	//

	function paymentSettings() {
		import('pages.manager.ManagerPaymentHandler');
		ManagerPaymentHandler::paymentSettings();
	}

	function savePaymentSettings() {
		import('pages.manager.ManagerPaymentHandler');
		ManagerPaymentHandler::savePaymentSettings();
	}


	//
	// Files Browser
	//

	function files($args) {
		import('pages.manager.FilesHandler');
		FilesHandler::files($args);
	}

	function fileUpload($args) {
		import('pages.manager.FilesHandler');
		FilesHandler::fileUpload($args);
	}

	function fileMakeDir($args) {
		import('pages.manager.FilesHandler');
		FilesHandler::fileMakeDir($args);
	}

	function fileDelete($args) {
		import('pages.manager.FilesHandler');
		FilesHandler::fileDelete($args);
	}


	//
	// Import/Export
	//

	function importexport($args) {
		import('pages.manager.ImportExportHandler');
		ImportExportHandler::importExport($args);
	}

	//
	// Plugin Management
	//

	function plugins($args) {
		import('pages.manager.PluginHandler');
		PluginHandler::plugins($args);
	}

	function plugin($args) {
		import('pages.manager.PluginHandler');
		PluginHandler::plugin($args);
	}
	
	function pluginManagement($args) {
		import('pages.manager.PluginManagementHandler');
		PluginManagementHandler::managePlugins($args);
	}

	//
	// Timeline Management
	//

	function timeline($args) {
		import('pages.manager.TimelineHandler');
		TimelineHandler::timeline($args);
	}

	function updateTimeline($args) {
		import('pages.manager.TimelineHandler');
		TimelineHandler::updateTimeline($args);
	}

	//
	// Conference History
	//

	function conferenceEventLog($args) {
		import('pages.manager.ConferenceHistoryHandler');
		ConferenceHistoryHandler::conferenceEventLog($args);
	}		

	function conferenceEventLogType($args) {
		import('pages.manager.ConferenceHistoryHandler');
		ConferenceHistoryHandler::conferenceEventLogType($args);
	}

	function clearConferenceEventLog($args) {
		import('pages.manager.ConferenceHistoryHandler');
		ConferenceHistoryHandler::clearConferenceEventLog($args);
	}
}

?>
