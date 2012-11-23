<?php

/**
 * @file ConferenceLanguagesHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceLanguagesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing conference language settings.
 */


import('pages.manager.ManagerHandler');

class ConferenceLanguagesHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function ConferenceLanguagesHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display form to edit language settings.
	 */
	function languages($args, &$request) {
		$this->validate($request);
		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->display('manager/languageSettings.tpl');
	}
}

?>
