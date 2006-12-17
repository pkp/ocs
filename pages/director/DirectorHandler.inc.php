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

import('eventDirector/EventDirectorHandler');

class DirectorHandler extends EventDirectorHandler {

	/**
	 * Display conference management index page.
	 */
	function index() {
		Handler::validate(true, false);
		EventDirectorHandler::setupTemplate();

		// UI consistency niggle: if we enter Conference Director pages with a
		// specified event, get rid of it.
		if(Request::getEvent()) {
			Request::redirect(null, 'index');
		}
		
		$conference = &Request::getConference();
		$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		$announcementsEnabled = $conferenceSettingsDao->getSetting($conference->getConferenceId(), 'enableAnnouncements'); 

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('announcementsEnabled', $announcementsEnabled);
		$templateMgr->assign('helpTopicId','conference.index');
		$templateMgr->display(ROLE_PATH_CONFERENCE_DIRECTOR . '/index.tpl');
	}
	

	/**
	 * Validate that user has permissions to manage the selected conference.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		Handler::validate(true, false);
		$conference =& Request::getConference();

		if (!$conference || (!Validation::isConferenceDirector() && !Validation::isSiteAdmin())) {
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {

		// PeopleHandler calls this from both director and eventDirector contexts;
		// it must play nicely with both.
		$roleDao = DAORegistry::getDAO("RoleDAO");
		
		$rolePath = Request::getRequestedPage();
		$roleId = $roleDao->getRoleIdFromPath($rolePath);
		$roleName = $roleDao->getRoleName($roleId);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(
					array(Request::url(null, null, 'user'), 'navigation.user'),
					array(Request::url(null, null, $rolePath), $roleName))
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
