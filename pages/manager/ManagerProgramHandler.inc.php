<?php

/**
 * @file ManagerProgramHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerProgramHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing scheduled conference program settings. 
 */

//$Id$

class ManagerProgramHandler extends ManagerHandler {

	/**
	 * Display form to edit program settings.
	 */
	function program() {
		parent::validate();
		parent::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect (null, null, 'index');

		import('manager.form.ProgramSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new ProgramSettingsForm();
		if ($settingsForm->isLocaleResubmit()) {
			$settingsForm->readInputData();
		} else {
			$settingsForm->initData();
		};
		$settingsForm->display();
	}

	/**
	 * Save changes to program settings.
	 */
	function saveProgramSettings() {
		parent::validate();
		parent::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect (null, null, 'index');

		import('manager.form.ProgramSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new ProgramSettingsForm();
		$settingsForm->readInputData();
		$formLocale = $settingsForm->getFormLocale();
		$programTitle = Request::getUserVar('programFileTitle');

		$editData = false;

		if (Request::getUserVar('uploadProgramFile')) {
			$settingsForm->uploadProgram('programFile', $formLocale);
			$editData = true;
		} elseif (Request::getUserVar('deleteProgramFile')) {
			$settingsForm->deleteProgram('programFile', $formLocale);
			$editData = true;
		}

		if (!$editData && $settingsForm->validate()) {
			$settingsForm->execute();

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, null, 'program'),
				'pageTitle' => 'schedConf.program',
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
