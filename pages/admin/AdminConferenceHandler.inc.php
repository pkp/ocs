<?php

/**
 * @file AdminConferenceHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminConferenceHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for conference management in site administration.
 */


import('pages.admin.AdminHandler');

class AdminConferenceHandler extends AdminHandler {
	/**
	 * Constructor
	 **/
	function AdminConferenceHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display a list of the conferences hosted on the site.
	 */
	function conferences($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('admin/conferences.tpl');
	}
}

?>
