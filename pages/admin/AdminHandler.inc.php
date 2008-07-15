<?php

/**
 * @file AdminHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminHandler
 * @ingroup pages_admin
 * @see AdminLanguagesHandler, AdminSettingsHandler
 *
 * @brief Handle requests for site administration functions. 
 */

//$Id$


import('core.Handler');

class AdminHandler extends Handler {

	/**
	 * Display site admin index page.
	 */
	function index() {
		AdminHandler::validate();
		AdminHandler::setupTemplate();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'site.index');
		$templateMgr->display('admin/index.tpl');
	}

	/**
	 * Validate that user has admin privileges and is not trying to access the admin module with a conference selected.
	 * Redirects to the user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		if (!Validation::isSiteAdmin() || Request::getRequestedConferencePath() != 'index') {
			Validation::redirectLogin();
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, ROLE_PATH_SITE_ADMIN), 'admin.siteAdmin'))
				: array(array(Request::url(null, null, 'user'), 'navigation.user'))
		);
	}


	//
	// Settings
	//

	function settings() {
		import('pages.admin.AdminSettingsHandler');
		AdminSettingsHandler::settings();
	}

	function saveSettings() {
		import('pages.admin.AdminSettingsHandler');
		AdminSettingsHandler::saveSettings();
	}


	//
	// Conference Management
	//

	function conferences() {
		import('pages.admin.AdminConferenceHandler');
		AdminConferenceHandler::conferences();
	}

	function createConference() {
		import('pages.admin.AdminConferenceHandler');
		AdminConferenceHandler::createConference();
	}

	function editConference($args = array()) {
		import('pages.admin.AdminConferenceHandler');
		AdminConferenceHandler::editConference($args);
	}

	function updateConference() {
		import('pages.admin.AdminConferenceHandler');
		AdminConferenceHandler::updateConference();
	}

	function deleteConference($args) {
		import('pages.admin.AdminConferenceHandler');
		AdminConferenceHandler::deleteConference($args);
	}

	function moveConference() {
		import('pages.admin.AdminConferenceHandler');
		AdminConferenceHandler::moveConference();
	}

	function importOCS1() {
		import('pages.admin.AdminConferenceHandler');
		AdminConferenceHandler::importOCS1();
	}

	function doImportOCS1() {
		import('pages.admin.AdminConferenceHandler');
		AdminConferenceHandler::doImportOCS1();
	}


	//
	// Languages
	//

	function languages() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::languages();
	}

	function saveLanguageSettings() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::saveLanguageSettings();
	}

	function installLocale() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::installLocale();
	}

	function uninstallLocale() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::uninstallLocale();
	}

	function reloadLocale() {
		import('pages.admin.AdminLanguagesHandler');
		AdminLanguagesHandler::reloadLocale();
	}


	//
	// Authentication sources
	//

	function auth() {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::auth();
	}

	function updateAuthSources() {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::updateAuthSources();
	}

	function createAuthSource() {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::createAuthSource();
	}

	function editAuthSource($args) {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::editAuthSource($args);
	}

	function updateAuthSource($args) {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::updateAuthSource($args);
	}

	function deleteAuthSource($args) {
		import('pages.admin.AuthSourcesHandler');
		AuthSourcesHandler::deleteAuthSource($args);
	}


	//
	// Administrative functions
	//

	function systemInfo() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::systemInfo();
	}

	function editSystemConfig() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::editSystemConfig();
	}

	function saveSystemConfig() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::saveSystemConfig();
	}

	function phpinfo() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::phpInfo();
	}

	function expireSessions() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::expireSessions();
	}

	function clearTemplateCache() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::clearTemplateCache();
	}

	function clearDataCache() {
		import('pages.admin.AdminFunctionsHandler');
		AdminFunctionsHandler::clearDataCache();
	}
}

?>
