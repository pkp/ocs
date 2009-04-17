<?php

/**
 * @file AdminHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminHandler
 * @ingroup pages_admin
 * @see AdminLanguagesHandler, AdminSettingsHandler
 *
 * @brief Handle requests for site administration functions. 
 */

//$Id$


import('handler.Handler');

class AdminHandler extends Handler {

	/**
	 * Display site admin index page.
	 */
	function index() {
		$this->validate();
		$this->setupTemplate();

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
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_ADMIN, LOCALE_COMPONENT_OCS_ADMIN));
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, ROLE_PATH_SITE_ADMIN), 'admin.siteAdmin'))
				: array(array(Request::url(null, null, 'user'), 'navigation.user'))
		);
	}

}

?>
