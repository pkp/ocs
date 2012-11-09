<?php

/**
 * @file ManagerProgramHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerProgramHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing scheduled conference program settings. 
 */


import('pages.manager.ManagerHandler');

class ManagerProgramHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function ManagerProgramHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display form to edit program settings.
	 */
	function program($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		if (!$schedConf) $request->redirect (null, null, 'index');

		import('classes.manager.form.ProgramSettingsForm');

		$settingsForm = new ProgramSettingsForm();
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
	function saveProgramSettings($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		if (!$schedConf) $request->redirect (null, null, 'index');

		import('classes.manager.form.ProgramSettingsForm');

		$settingsForm = new ProgramSettingsForm();
		$settingsForm->readInputData();
		$formLocale = $settingsForm->getFormLocale();
		$programTitle = $request->getUserVar('programFileTitle');

		$editData = false;

		if ($request->getUserVar('uploadProgramFile')) {
			if (!$settingsForm->uploadProgram('programFile', $formLocale)) {
				$settingsForm->addError('programFile', __('common.uploadFailed'));
			}
			$editData = true;
		} elseif ($request->getUserVar('deleteProgramFile')) {
			$settingsForm->deleteProgram('programFile', $formLocale);
			$editData = true;
		}

		if (!$editData && $settingsForm->validate()) {
			$settingsForm->execute();

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'currentUrl' => $request->url(null, null, null, 'program'),
				'pageTitle' => 'schedConf.program',
				'message' => 'common.changesSaved',
				'backLink' => $request->url(null, null, $request->getRequestedPage()),
				'backLinkLabel' => 'manager.conferenceSiteManagement'
			));
			$templateMgr->display('common/message.tpl');

		} else {
			$settingsForm->display();
		}
	}
}

?>
