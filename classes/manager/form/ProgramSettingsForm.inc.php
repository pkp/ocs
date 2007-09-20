<?php

/**
 * @file ProgramSettingsForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class ProgramSettingsForm
 *
 * Form for modifying scheduled conference program settings.
 *
 * $Id$
 */

import('form.Form');

class ProgramSettingsForm extends Form {

	/** @var array the setting names */
	var $settings;

	/**
	 * Constructor.
	 */
	function ProgramSettingsForm() {
		parent::Form('manager/programSettings.tpl');

		$this->addCheck(new FormValidatorPost($this));

		$this->settings = array(
			'program' => 'string',
			'programFileTitle' => 'string'
		);
	}

	/**
	 * Display the form.
	 */
	function display() {
		import('file.PublicFileManager');
		$schedConf =& Request::getSchedConf();

		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('helpTopicId','conference.managementPages.program');
		$templateMgr->assign('publicSchedConfFilesDir', Request::getBaseUrl() . '/' . PublicFileManager::getSchedConfFilesPath($schedConf->getSchedConfId()));
		$templateMgr->assign('programFile', $schedConf->getSetting('programFile'));
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$schedConf = &Request::getSchedConf();
		foreach (array_keys($this->settings) as $settingName) {
			$this->_data[$settingName] = $schedConf->getSetting($settingName);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->settings));
	}

	/**
	 * Save modified settings.
	 */
	function execute() {
		$schedConf = &Request::getSchedConf();
		$settingsDao = &DAORegistry::getDAO('SchedConfSettingsDAO');

		foreach ($this->_data as $name => $value) {
			$settingsDao->updateSetting(
				$schedConf->getSchedConfId(),
				$name,
				$value,
				$this->settings[$name]
			);
		}
	}
}

?>
