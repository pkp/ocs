<?php

/**
 * @file classes/core/Application.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Application
 * @ingroup core
 * @see PKPApplication
 *
 * @brief Class describing this application.
 *
 */

// $Id$


import('core.PKPApplication');

define('PHP_REQUIRED_VERSION', '4.2.0');

define('ASSOC_TYPE_CONFERENCE',	0x0000100);
define('ASSOC_TYPE_SCHED_CONF',	0x0000101);

define('CONTEXT_CONFERENCE', 1);
define('CONTEXT_SCHED_CONF', 2);

class Application extends PKPApplication {
	function Application() {
		parent::PKPApplication();
	}

	/**
	 * Get the "context depth" of this application, i.e. the number of
	 * parts of the URL after index.php that represent the context of
	 * the current request (e.g. Journal [1], or Conference and
	 * Scheduled Conference [2]).
	 * @return int
	 */
	function getContextDepth() {
		return 2;
	}

	function getContextList() {
		return array('conference', 'schedConf');
	}

	/**
	 * Get the symbolic name of this application
	 * @return string
	 */
	function getName() {
		return 'ocs2';
	}

	/**
	 * Get the locale key for the name of this application.
	 * @return string
	 */
	function getNameKey() {
		return('common.openConferenceSystems');
	}

	/**
	 * Get the URL to the XML descriptor for the current version of this
	 * application.
	 * @return string
	 */
	function getVersionDescriptorUrl() {
		return('http://pkp.sfu.ca/ocs/xml/ocs-version.xml');
	}

	/**
	 * Get the map of DAOName => full.class.Path for this application.
	 * @return array
	 */
	function getDAOMap() {
		return array_merge(parent::getDAOMap(), array(
			'AnnouncementDAO' => 'announcement.AnnouncementDAO',
			'AnnouncementTypeDAO' => 'announcement.AnnouncementTypeDAO',
			'BuildingDAO' => 'scheduler.BuildingDAO',
			'CommentDAO' => 'comment.CommentDAO',
			'ConferenceDAO' => 'conference.ConferenceDAO',
			'ConferenceEventLogDAO' => 'conference.log.ConferenceEventLogDAO',
			'ConferenceSettingsDAO' => 'conference.ConferenceSettingsDAO',
			'DirectorSubmissionDAO' => 'submission.director.DirectorSubmissionDAO',
			'EditAssignmentDAO' => 'submission.editAssignment.EditAssignmentDAO',
			'EmailTemplateDAO' => 'mail.EmailTemplateDAO',
			'GroupDAO' => 'group.GroupDAO',
			'GroupMembershipDAO' => 'group.GroupMembershipDAO',
			'OAIDAO' => 'oai.ocs.OAIDAO',
			'PaperCommentDAO' => 'paper.PaperCommentDAO',
			'PaperDAO' => 'paper.PaperDAO',
			'PaperEmailLogDAO' => 'paper.log.PaperEmailLogDAO',
			'PaperEventLogDAO' => 'paper.log.PaperEventLogDAO',
			'PaperFileDAO' => 'paper.PaperFileDAO',
			'PaperGalleyDAO' => 'paper.PaperGalleyDAO',
			'PaperNoteDAO' => 'paper.PaperNoteDAO',
			'PaperSearchDAO' => 'search.PaperSearchDAO',
			'PaperTypeDAO' => 'paper.PaperTypeDAO',
			'PaperTypeEntryDAO' => 'paper.PaperTypeEntryDAO',
			'PluginSettingsDAO' => 'plugins.PluginSettingsDAO',
			'AuthorDAO' => 'paper.AuthorDAO',
			'AuthorSubmissionDAO' => 'submission.author.AuthorSubmissionDAO',
			'PublishedPaperDAO' => 'paper.PublishedPaperDAO',
			'QueuedPaymentDAO' => 'payment.QueuedPaymentDAO',
			'RoleDAO' => 'security.RoleDAO',
			'RegistrationDAO' => 'registration.RegistrationDAO',
			'RegistrationTypeDAO' => 'registration.RegistrationTypeDAO',
			'RegistrationOptionDAO' => 'registration.RegistrationOptionDAO',
			'ReviewAssignmentDAO' => 'submission.reviewAssignment.ReviewAssignmentDAO',
			'ReviewerSubmissionDAO' => 'submission.reviewer.ReviewerSubmissionDAO',
			'ReviewFormDAO' => 'reviewForm.ReviewFormDAO',
			'ReviewFormElementDAO' => 'reviewForm.ReviewFormElementDAO',
			'ReviewFormResponseDAO' => 'reviewForm.ReviewFormResponseDAO',
			'RoomDAO' => 'scheduler.RoomDAO',
			'RTDAO' => 'rt.ocs.RTDAO',
			'ScheduledTaskDAO' => 'scheduledTask.ScheduledTaskDAO',
			'SchedConfDAO' => 'schedConf.SchedConfDAO',
			'SchedConfSettingsDAO' => 'schedConf.SchedConfSettingsDAO',
			'SchedConfStatisticsDAO' => 'schedConf.SchedConfStatisticsDAO',
			'SpecialEventDAO' => 'scheduler.SpecialEventDAO',
			'SuppFileDAO' => 'paper.SuppFileDAO',
			'TimeBlockDAO' => 'scheduler.TimeBlockDAO',
			'TrackDAO' => 'conference.TrackDAO',
			'TrackDirectorsDAO' => 'conference.TrackDirectorsDAO',
			'TrackDirectorSubmissionDAO' => 'submission.trackDirector.TrackDirectorSubmissionDAO',
			'UserDAO' => 'user.UserDAO',
			'UserSettingsDAO' => 'user.UserSettingsDAO'
		));
	}

	/**
	 * Get the list of plugin categories for this application.
	 */
	function getPluginCategories() {
		return array(
			'auth',
			'blocks',
			'citationFormats',
			'gateways',
			'generic',
			'implicitAuth',
			'importexport',
			'paymethod',
			'reports',
			'themes'
		);
	}

	/**
	 * Instantiate the help object for this application.
	 * @return object
	 */
	function &instantiateHelp() {
		import('help.Help');
		$help = new Help();
		return $help;
	}
}

?>
