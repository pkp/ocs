<?php

/**
 * @defgroup pages_manager
 */
 
/**
 * @file pages/manager/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle requests for conference management functions.
 *
 * @ingroup pages_manager
 */

//$Id$


switch ($op) {
	//
	//	announcements
	//
	case 'announcements':
	case 'deleteAnnouncement':
	case 'createAnnouncement':
	case 'editAnnouncement':
	case 'updateAnnouncement':
	//
	//	announcement Types
	//
	case 'announcementTypes':
	case 'deleteAnnouncementType':
	case 'createAnnouncementType':
	case 'editAnnouncementType':
	case 'updateAnnouncementType':
		define('HANDLER_CLASS', 'AnnouncementHandler');
		import('pages.manager.AnnouncementHandler');
		break;
	//
	// Setup
	//
	case 'setup':
	case 'saveSetup':
	case 'setupSaved':
		define('HANDLER_CLASS', 'ManagerSetupHandler');
		import('pages.manager.ManagerSetupHandler');
		break;
	//
	// Scheduled Conference Setup
	//
	case 'schedConfSetup':
		define('HANDLER_CLASS', 'SchedConfSetupHandler');
		import('pages.manager.SchedConfSetupHandler');
		break;
	case 'saveSchedConfSetup':
		define('HANDLER_CLASS', 'SchedConfSetupHandler');
		import('pages.manager.SchedConfSetupHandler');
		break;	
	case 'schedConfSetupSaved':
		define('HANDLER_CLASS', 'SchedConfSetupHandler');
		import('pages.manager.SchedConfSetupHandler');
		break;
	//
	// Scheduled Conference Management
	//
	case 'schedConfs':
	case 'createSchedConf':
	case 'editSchedConf':
	case 'updateSchedConf':
	case 'deleteSchedConf':
	case 'moveSchedConf':
		define('HANDLER_CLASS', 'ManagerSchedConfHandler');
		import('pages.manager.ManagerSchedConfHandler');
		break;
	//
	// People Management
	//
	case 'people':
	case 'enrollSearch':
	case 'enroll':
	case 'unEnroll':
	case 'enrollSyncSelect':
	case 'enrollSync':
	case 'createUser':
	case 'suggestUsername':
	case 'mergeUsers':
	case 'disableUser':
	case 'enableUser':
	case 'removeUser':
	case 'editUser':
	case 'updateUser':
	case 'userProfile':
		define('HANDLER_CLASS', 'PeopleHandler');
		import('pages.manager.PeopleHandler');
		break;
	//
	// Track Management
	//
	case 'tracks':
	case 'createTrack':
	case 'editTrack':
	case 'updateTrack':
	case 'deleteTrack':
	case 'moveTrack':
		define('HANDLER_CLASS', 'TrackHandler');
		import('pages.manager.TrackHandler');
		break;
	//
	// Review Form Management
	//
	case 'reviewForms':
	case 'createReviewForm':
	case 'editReviewForm':
	case 'updateReviewForm':
	case 'previewReviewForm':
	case 'deleteReviewForm':
	case 'activateReviewForm':
	case 'deactivateReviewForm':
	case 'copyReviewForm':
	case 'moveReviewForm':
	case 'reviewFormElements':
	case 'createReviewFormElement':
	case 'editReviewFormElement':
	case 'deleteReviewFormElement':
	case 'updateReviewFormElement':
	case 'moveReviewFormElement':
	case 'copyReviewFormElement':
		define('HANDLER_CLASS', 'ReviewFormHandler');
		import('pages.manager.ReviewFormHandler');
		break;
	//
	// E-mail Management
	//
	case 'emails':
	case 'createEmail':
	case 'editEmail':
	case 'updateEmail':
	case 'deleteCustomEmail':
	case 'resetEmail':
	case 'disableEmail':
	case 'enableEmail':
	case 'resetAllEmails':
		define('HANDLER_CLASS', 'EmailHandler');
		import('pages.manager.EmailHandler');
		break;
	//
	// Registration Policies 
	//
	case 'registrationPolicies':
	case 'saveRegistrationPolicies':
	//
	// Registration Types
	//
	case 'registrationTypes':
	case 'deleteRegistrationType':
	case 'createRegistrationType':
	case 'selectRegistrant':
	case 'editRegistrationType':
	case 'updateRegistrationType':
	case 'moveRegistrationType':
	//
	// Registration Options
	//
	case 'registrationOptions':
	case 'deleteRegistrationOption':
	case 'createRegistrationOption':
	case 'editRegistrationOption':
	case 'updateRegistrationOption':
	case 'moveRegistrationOption':
	//
	// Registration
	//
	case 'registration':
	case 'deleteRegistration':
	case 'createRegistration':
	case 'editRegistration':
	case 'updateRegistration':
		define('HANDLER_CLASS', 'RegistrationHandler');
		import('pages.manager.RegistrationHandler');
		break;
	//
	// Scheduler
	//
	case 'scheduler':
	case 'saveSchedulerSettings':
	case 'saveSchedule':
	case 'scheduleLayout':
	case 'saveScheduleLayout':
	// Time Blocks
	case 'timeBlocks':
	case 'deleteTimeBlock':
	case 'editTimeBlock':
	case 'createTimeBlock':
	case 'updateTimeBlock':
	// Buildings
	case 'buildings':
	case 'deleteBuilding':
	case 'editBuilding':
	case 'createBuilding':
	case 'updateBuilding':
	// Rooms
	case 'rooms':
	case 'deleteRoom':
	case 'editRoom':
	case 'createRoom':
	case 'updateRoom':
	// Special Events
	case 'specialEvents':
	case 'deleteSpecialEvent':
	case 'editSpecialEvent':
	case 'createSpecialEvent':
	case 'updateSpecialEvent':
	// Scheduler
	case 'schedule':
		define('HANDLER_CLASS', 'SchedulerHandler');
		import('pages.manager.SchedulerHandler');
		break;
	//
	// Group Management
	//
	case 'groups':
	case 'createGroup':
	case 'updateGroup':
	case 'deleteGroup':
	case 'editGroup':
	case 'groupMembership':
	case 'addMembership':
	case 'deleteMembership':
	case 'setBoardEnabled':
	case 'moveGroup':
	case 'moveMembership':
		define('HANDLER_CLASS', 'GroupHandler');
		import('pages.manager.GroupHandler');
		break;
	//
	// Statistics Functions
	//
	case 'statistics':
	case 'saveStatisticsTracks':
	case 'savePublicStatisticsList':
	case 'report':
		define('HANDLER_CLASS', 'StatisticsHandler');
		import('pages.manager.StatisticsHandler');
		break;
	//
	// Languages
	//
	case 'languages':
	case 'saveLanguageSettings':
	case 'reloadLocalizedDefaultSettings':
		define('HANDLER_CLASS', 'ConferenceLanguagesHandler');
		import('pages.manager.ConferenceLanguagesHandler');
		break;
	//
	// Program
	//
	case 'program':
	case 'saveProgramSettings':
		define('HANDLER_CLASS', 'ManagerProgramHandler');
		import('pages.manager.ManagerProgramHandler');
		break;
	//
	// Accommodation
	//
	case 'accommodation':
	case 'saveAccommodationSettings':
		define('HANDLER_CLASS', 'ManagerAccommodationHandler');
		import('pages.manager.ManagerAccommodationHandler');
		break;
	//
	// Payment
	//
	case 'paymentSettings':
	case 'savePaymentSettings':
		define('HANDLER_CLASS', 'ManagerPaymentHandler');
		import('pages.manager.ManagerPaymentHandler');
		break;
	//
	// Files Browser
	//
	case 'files':
	case 'fileUpload':
	case 'fileMakeDir':
	case 'fileDelete':
		define('HANDLER_CLASS', 'FilesHandler');
		import('pages.manager.FilesHandler');
		break;
	//
	// Import/Export
	//
	case 'importexport':
		define('HANDLER_CLASS', 'ImportExportHandler');
		import('pages.manager.ImportExportHandler');
		break;
	//
	// Plugin Management
	//
	case 'plugins':
	case 'plugin':
		define('HANDLER_CLASS', 'PluginHandler');
		import('pages.manager.PluginHandler');
		break;
	case 'managePlugins':
		define('HANDLER_CLASS', 'PluginManagementHandler');
		import('pages.manager.PluginManagementHandler');
		break;
	//
	// Timeline Management
	//
	case 'timeline':
	case 'updateTimeline':
		define('HANDLER_CLASS', 'TimelineHandler');
		import('pages.manager.TimelineHandler');
		break;
	//
	// Conference History
	//
	case 'conferenceEventLog':
	case 'conferenceEventLogType':
	case 'clearConferenceEventLog':
		define('HANDLER_CLASS', 'ConferenceHistoryHandler');
		import('pages.manager.ConferenceHistoryHandler');
		break;
	case 'index':
	case 'email':
		define('HANDLER_CLASS', 'ManagerHandler');
		import('pages.manager.ManagerHandler');
		break;
}

?>
