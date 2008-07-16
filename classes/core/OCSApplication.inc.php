<?php

/**
 * @file classes/core/OCSApplication.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OCSApplication
 * @ingroup core
 * @see PKPApplication
 *
 * @brief Class describing this application.
 *
 */

// $Id$


import('core.PKPApplication');

class OCSApplication extends PKPApplication {
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
		return('http://pkp.sfu.ca/ojs/xml/ocs-version.xml');
	}

	/**
	 * Determine whether or not the request is cacheable.
	 * @return boolean
	 */
	function isCacheable() {
		if (defined('SESSION_DISABLE_INIT')) return false;
		if (!Config::getVar('general', 'installed')) return false;
		if (!empty($_POST) || Validation::isLoggedIn()) return false;
		if (!Config::getVar('cache', 'web_cache')) return false;

		return false; // FIXME: Not implemented yet.
	}

	/**
	 * Get the map of DAOName => full.class.Path for this application.
	 * @return array
	 */
	function getDAOMap() {
		return array(
			'ConferenceEventLogDAO' => 'conference.log.ConferenceEventLogDAO',
			'PaperEmailLogDAO' => 'paper.log.PaperEmailLogDAO',
			'PaperEventLogDAO' => 'paper.log.PaperEventLogDAO',
			'PaperCommentDAO' => 'paper.PaperCommentDAO',
			'PaperDAO' => 'paper.PaperDAO',
			'PaperFileDAO' => 'paper.PaperFileDAO',
			'PaperGalleyDAO' => 'paper.PaperGalleyDAO',
			'PaperNoteDAO' => 'paper.PaperNoteDAO',
			'PresenterDAO' => 'paper.PresenterDAO',
			'PublishedPaperDAO' => 'paper.PublishedPaperDAO',
			'SuppFileDAO' => 'paper.SuppFileDAO',
			'DAO' => 'db.DAO',
			'XMLDAO' => 'db.XMLDAO',
			'OAIDAO' => 'oai.ocs.OAIDAO',
			'HelpTocDAO' => 'help.HelpTocDAO',
			'HelpTopicDAO' => 'help.HelpTopicDAO',
			'SchedConfDAO' => 'schedConf.SchedConfDAO',
			'ConferenceDAO' => 'conference.ConferenceDAO',
			'CountryDAO' => 'i18n.CountryDAO',
			'SchedConfStatisticsDAO' => 'schedConf.SchedConfStatisticsDAO',
			'SchedConfSettingsDAO' => 'schedConf.SchedConfSettingsDAO',
			'ConferenceSettingsDAO' => 'conference.ConferenceSettingsDAO',
			'TrackDAO' => 'conference.TrackDAO',
			'TrackDirectorsDAO' => 'conference.TrackDirectorsDAO',
			'NotificationStatusDAO' => 'conference.NotificationStatusDAO',
			'EmailTemplateDAO' => 'mail.EmailTemplateDAO',
			'QueuedPaymentDAO' => 'payment.QueuedPaymentDAO',
			'ScheduledTaskDAO' => 'scheduledTask.ScheduledTaskDAO',
			'PaperSearchDAO' => 'search.PaperSearchDAO',
			'RoleDAO' => 'security.RoleDAO',
			'SessionDAO' => 'session.SessionDAO',
			'SiteDAO' => 'site.SiteDAO',
			'SiteSettingsDAO' => 'site.SiteSettingsDAO',
			'VersionDAO' => 'site.VersionDAO',
			'PresenterSubmissionDAO' => 'submission.presenter.PresenterSubmissionDAO',
			'EditAssignmentDAO' => 'submission.editAssignment.EditAssignmentDAO',
			'DirectorSubmissionDAO' => 'submission.director.DirectorSubmissionDAO',
			'ReviewAssignmentDAO' => 'submission.reviewAssignment.ReviewAssignmentDAO',
			'ReviewerSubmissionDAO' => 'submission.reviewer.ReviewerSubmissionDAO',
			'TrackDirectorSubmissionDAO' => 'submission.trackDirector.TrackDirectorSubmissionDAO',
			'UserDAO' => 'user.UserDAO',
			'UserSettingsDAO' => 'user.UserSettingsDAO',
			'RTDAO' => 'rt.ocs.RTDAO',
			'CurrencyDAO' => 'registration.CurrencyDAO',
			'RegistrationDAO' => 'registration.RegistrationDAO',
			'RegistrationTypeDAO' => 'registration.RegistrationTypeDAO',
			'AnnouncementDAO' => 'announcement.AnnouncementDAO',
			'AnnouncementTypeDAO' => 'announcement.AnnouncementTypeDAO',
			'BuildingDAO' => 'scheduler.BuildingDAO',
			'RoomDAO' => 'scheduler.RoomDAO',
			'SpecialEventDAO' => 'scheduler.SpecialEventDAO',
			'TemporaryFileDAO' => 'file.TemporaryFileDAO',
			'CommentDAO' => 'comment.CommentDAO',
			'AuthSourceDAO' => 'security.AuthSourceDAO',
			'AccessKeyDAO' => 'security.AccessKeyDAO',
			'PluginSettingsDAO' => 'plugins.PluginSettingsDAO',
			'GroupDAO' => 'group.GroupDAO',
			'GroupMembershipDAO' => 'group.GroupMembershipDAO',
			'CaptchaDAO' => 'captcha.CaptchaDAO'
		);
	}
}

?>
