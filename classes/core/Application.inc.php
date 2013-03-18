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


import('lib.pkp.classes.core.PKPApplication');

define('PHP_REQUIRED_VERSION', '4.2.0');

define('ASSOC_TYPE_PAPER',			ASSOC_TYPE_SUBMISSION);
define('ASSOC_TYPE_PUBLISHED_PAPER',		ASSOC_TYPE_PUBLISHED_SUBMISSION);

define('ASSOC_TYPE_CONFERENCE',		0x0000100);
define('ASSOC_TYPE_SCHED_CONF',		0x0000101);

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
			'AnnouncementDAO' => 'classes.announcement.AnnouncementDAO',
			'AnnouncementTypeDAO' => 'classes.announcement.AnnouncementTypeDAO',
			'BuildingDAO' => 'classes.scheduler.BuildingDAO',
			'CommentDAO' => 'lib.pkp.classes.comment.CommentDAO',
			'ConferenceDAO' => 'classes.conference.ConferenceDAO',
			'ConferenceEventLogDAO' => 'classes.conference.log.ConferenceEventLogDAO',
			'ConferenceSettingsDAO' => 'classes.conference.ConferenceSettingsDAO',
			'DirectorSubmissionDAO' => 'classes.submission.director.DirectorSubmissionDAO',
			'EditAssignmentDAO' => 'classes.submission.editAssignment.EditAssignmentDAO',
			'EmailTemplateDAO' => 'classes.mail.EmailTemplateDAO',
			'NoteDAO' => 'classes.note.NoteDAO',
			'OAIDAO' => 'classes.oai.ocs.OAIDAO',
			'PaperCommentDAO' => 'classes.paper.PaperCommentDAO',
			'PaperDAO' => 'classes.paper.PaperDAO',
			'PaperEmailLogDAO' => 'classes.paper.log.PaperEmailLogDAO',
			'PaperEventLogDAO' => 'classes.paper.log.PaperEventLogDAO',
			'PaperFileDAO' => 'classes.paper.PaperFileDAO',
			'PaperGalleyDAO' => 'classes.paper.PaperGalleyDAO',
			'PaperSearchDAO' => 'classes.search.PaperSearchDAO',
			'PaperTypeDAO' => 'classes.paper.PaperTypeDAO',
			'PaperTypeEntryDAO' => 'classes.paper.PaperTypeEntryDAO',
			'PluginSettingsDAO' => 'classes.plugins.PluginSettingsDAO',
			'AuthorDAO' => 'classes.paper.AuthorDAO',
			'AuthorSubmissionDAO' => 'classes.submission.author.AuthorSubmissionDAO',
			'PublishedPaperDAO' => 'classes.paper.PublishedPaperDAO',
			'QueuedPaymentDAO' => 'lib.pkp.classes.payment.QueuedPaymentDAO',
			'RoleDAO' => 'classes.security.RoleDAO',
			'RegistrationDAO' => 'classes.registration.RegistrationDAO',
			'RegistrationTypeDAO' => 'classes.registration.RegistrationTypeDAO',
			'RegistrationOptionDAO' => 'classes.registration.RegistrationOptionDAO',
			'ReviewAssignmentDAO' => 'classes.submission.reviewAssignment.ReviewAssignmentDAO',
			'ReviewerSubmissionDAO' => 'classes.submission.reviewer.ReviewerSubmissionDAO',
			'ReviewFormDAO' => 'lib.pkp.classes.reviewForm.ReviewFormDAO',
			'ReviewFormElementDAO' => 'lib.pkp.classes.reviewForm.ReviewFormElementDAO',
			'ReviewFormResponseDAO' => 'lib.pkp.classes.reviewForm.ReviewFormResponseDAO',
			'RoomDAO' => 'classes.scheduler.RoomDAO',
			'RTDAO' => 'classes.rt.ocs.RTDAO',
			'ScheduledTaskDAO' => 'lib.pkp.classes.scheduledTask.ScheduledTaskDAO',
			'SchedConfDAO' => 'classes.schedConf.SchedConfDAO',
			'SchedConfSettingsDAO' => 'classes.schedConf.SchedConfSettingsDAO',
			'SchedConfStatisticsDAO' => 'classes.schedConf.SchedConfStatisticsDAO',
			'SignoffDAO' => 'classes.signoff.SignoffDAO',
			'SpecialEventDAO' => 'classes.scheduler.SpecialEventDAO',
			'SuppFileDAO' => 'classes.paper.SuppFileDAO',
			'TimeBlockDAO' => 'classes.scheduler.TimeBlockDAO',
			'TrackDAO' => 'classes.conference.TrackDAO',
			'TrackDirectorsDAO' => 'classes.conference.TrackDirectorsDAO',
			'TrackDirectorSubmissionDAO' => 'classes.submission.trackDirector.TrackDirectorSubmissionDAO',
			'UserDAO' => 'classes.user.UserDAO',
			'UserSettingsDAO' => 'classes.user.UserSettingsDAO'
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
			'oaiMetadataFormats',
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
		import('classes.help.Help');
		$help = new Help();
		return $help;
	}
}

?>
