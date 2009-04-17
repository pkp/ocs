<?php

/**
 * @file ConferenceLanguagesHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceLanguagesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing conference language settings. 
 */

//$Id$

class ConferenceLanguagesHandler extends ManagerHandler {

	/**
	 * Display form to edit language settings.
	 */
	function languages() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new LanguageSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}

	/**
	 * Save changes to language settings.
	 */
	function saveLanguageSettings() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new LanguageSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, null, 'languages'),
				'pageTitle' => 'common.languages',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, Request::getRequestedPage()),
				'backLinkLabel' => 'manager.conferenceSiteManagement'
			));
			$templateMgr->display('common/message.tpl');

		} else {
			$settingsForm->display();
		}
	}

}
?>
