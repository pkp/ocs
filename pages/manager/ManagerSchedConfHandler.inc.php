<?php

/**
 * @file pages/manager/ManagerSchedConfHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerSchedConfHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for scheduled conference management in site administration.
 */


import('pages.manager.ManagerHandler');

class ManagerSchedConfHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function ManagerSchedConfHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the scheduled conferences hosted on the site.
	 */
	function schedConfs($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->display('manager/schedConfs.tpl');
	}
}

?>
