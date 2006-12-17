<?php

/**
 * DAORegistry.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Class for retrieving DAO objects.
 * Maintains a static list of DAO objects so each DAO is instantiated only once. 
 *
 * $Id$
 */

class DAORegistry {

	/**
	 * Get the current list of registered DAOs.
	 * This returns a reference to the static hash used to
	 * store all DAOs currently instantiated by the system.
	 * @return array
	 */
	function &getDAOs() {
		static $daos = array();
		return $daos;
	}

	/**
	 * Register a new DAO with the system.
	 * @param $name string The name of the DAO to register
	 * @param $dao object A reference to the DAO to be registered
	 * @return object A reference to previously-registered DAO of the same
	 *    name, if one was already registered; null otherwise
	 */
	function &registerDAO($name, &$dao) {
		if (isset($daos[$name])) {
			$returner = &$daos[$name];
		} else {
			$returner = null;
		}
		$daos = &DAORegistry::getDAOs();
		$daos[$name] = &$dao;
		return $returner;
	}

	/**
	 * Retrieve a reference to the specified DAO.
	 * @param $name string the class name of the requested DAO
	 * @param $dbconn ADONewConnection optional
	 * @return DAO
	 */
	function &getDAO($name, $dbconn = null) {
		$daos = &DAORegistry::getDAOs();

		if (!isset($daos[$name])) {
			// Import the required DAO class.
			import(DAORegistry::getQualifiedDAOName($name));

			// Only instantiate each class of DAO a single time
			$daos[$name] = &new $name();
			if ($dbconn != null) {
				// FIXME Needed by installer but shouldn't access member variable directly
				$daos[$name]->_dataSource = $dbconn;
			}
		}
		
		return $daos[$name];
	}

	/**
	 * Return the fully-qualified (e.g. page.name.ClassNameDAO) name of the
	 * given DAO.
	 * @param $name string
	 * @return string
	 */
	function getQualifiedDAOName($name) {
		// FIXME This function should be removed (require fully-qualified name to be passed to getDAO?)
		switch ($name) {
			case 'ConferenceEventLogDAO': return 'conference.log.ConferenceEventLogDAO';
			case 'PaperEmailLogDAO': return 'paper.log.PaperEmailLogDAO';
			case 'PaperEventLogDAO': return 'paper.log.PaperEventLogDAO';
			case 'PaperCommentDAO': return 'paper.PaperCommentDAO';
			case 'PaperDAO': return 'paper.PaperDAO';
			case 'PaperFileDAO': return 'paper.PaperFileDAO';
			case 'PaperGalleyDAO': return 'paper.PaperGalleyDAO';
			case 'PaperNoteDAO': return 'paper.PaperNoteDAO';
			case 'AuthorDAO': return 'paper.AuthorDAO';
			case 'PublishedPaperDAO': return 'paper.PublishedPaperDAO';
			case 'SuppFileDAO': return 'paper.SuppFileDAO';
			case 'DAO': return 'db.DAO';
			case 'XMLDAO': return 'db.XMLDAO';
			case 'HelpTocDAO': return 'help.HelpTocDAO';
			case 'HelpTopicDAO': return 'help.HelpTopicDAO';
			case 'EventDAO': return 'event.EventDAO';
			case 'ConferenceDAO': return 'conference.ConferenceDAO';
			case 'CountryDAO': return 'user.CountryDAO';
			case 'EventStatisticsDAO': return 'event.EventStatisticsDAO';
			case 'EventSettingsDAO': return 'event.EventSettingsDAO';
			case 'ConferenceSettingsDAO': return 'conference.ConferenceSettingsDAO';
			case 'TrackDAO': return 'conference.TrackDAO';
			case 'TrackEditorsDAO': return 'conference.TrackEditorsDAO';
			case 'NotificationStatusDAO': return 'conference.NotificationStatusDAO';
			case 'EmailTemplateDAO': return 'mail.EmailTemplateDAO';
			//case 'OAIDAO': return 'oai.ocs.OAIDAO';
			case 'ScheduledTaskDAO': return 'scheduledTask.ScheduledTaskDAO';
			case 'PaperSearchDAO': return 'search.PaperSearchDAO';
			case 'RoleDAO': return 'security.RoleDAO';
			case 'SessionDAO': return 'session.SessionDAO';
			case 'SiteDAO': return 'site.SiteDAO';
			case 'VersionDAO': return 'site.VersionDAO';
			case 'AuthorSubmissionDAO': return 'submission.author.AuthorSubmissionDAO';
			//case 'CopyAssignmentDAO': return 'submission.copyAssignment.CopyAssignmentDAO';
			//case 'CopyeditorSubmissionDAO': return 'submission.copyeditor.CopyeditorSubmissionDAO';
			case 'EditAssignmentDAO': return 'submission.editAssignment.EditAssignmentDAO';
			case 'EditorSubmissionDAO': return 'submission.editor.EditorSubmissionDAO';
			//case 'LayoutAssignmentDAO': return 'submission.layoutAssignment.LayoutAssignmentDAO';
			//case 'LayoutEditorSubmissionDAO': return 'submission.layoutEditor.LayoutEditorSubmissionDAO';
			//case 'ProofAssignmentDAO': return 'submission.proofAssignment.ProofAssignmentDAO';
			//case 'ProofreaderSubmissionDAO': return 'submission.proofreader.ProofreaderSubmissionDAO';
			case 'ReviewAssignmentDAO': return 'submission.reviewAssignment.ReviewAssignmentDAO';
			case 'ReviewerSubmissionDAO': return 'submission.reviewer.ReviewerSubmissionDAO';
			case 'TrackEditorSubmissionDAO': return 'submission.trackEditor.TrackEditorSubmissionDAO';
			case 'UserDAO': return 'user.UserDAO';
			case 'UserSettingsDAO': return 'user.UserSettingsDAO';
			case 'RTDAO': return 'rt.ocs.RTDAO';
			case 'CurrencyDAO': return 'registration.CurrencyDAO';
			case 'RegistrationDAO': return 'registration.RegistrationDAO';
			case 'RegistrationTypeDAO': return 'registration.RegistrationTypeDAO';
			case 'AnnouncementDAO': return 'announcement.AnnouncementDAO';
			case 'AnnouncementTypeDAO': return 'announcement.AnnouncementTypeDAO';
			case 'TemporaryFileDAO': return 'file.TemporaryFileDAO';
			case 'CommentDAO': return 'comment.CommentDAO';
			case 'AuthSourceDAO': return 'security.AuthSourceDAO';
			case 'AccessKeyDAO': return 'security.AccessKeyDAO';
			case 'PluginSettingsDAO': return 'plugins.PluginSettingsDAO';
			case 'GroupDAO': return 'group.GroupDAO';
			case 'GroupMembershipDAO': return 'group.GroupMembershipDAO';
			default: fatalError('Unrecognized DAO ' . $name);
		}
		return null;
	}
}
?>
