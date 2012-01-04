<?php

/**
 * @file ProgramSettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProgramSettingsForm
 * @ingroup manager_form
 *
 * @brief Form for modifying scheduled conference program settings.
 */

//$Id$

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
		$site =& Request::getSite();
		$schedConf =& Request::getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId','conference.currentConferences.program');
		$templateMgr->assign('publicSchedConfFilesDir', Request::getBaseUrl() . '/' . PublicFileManager::getSchedConfFilesPath($schedConf->getId()));
		$templateMgr->assign('programFile', $schedConf->getSetting('programFile'));

		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();
		
		$this->data = array();
		foreach (array_keys($this->settings) as $settingName) {
			$this->_data[$settingName] = $schedConf->getSetting($settingName);
		}
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array_keys($this->settings);
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
		$schedConf =& Request::getSchedConf();
		
		foreach ($this->_data as $name => $value) {
			$schedConf->updateSetting(
				$name,
				$value,
				$this->settings[$name],
				true
			);
		}
	}
	
	/**
	 * Uploads a program file.
	 * @param $settingName string setting key associated with the file
	 * @param $locale string
	 */
	function uploadProgram($settingName, $locale) {
		$schedConf =& Request::getSchedConf();

		import('file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->uploadError($settingName)) return false;
		if ($fileManager->uploadedFileExists($settingName)) {
			$oldName = $fileManager->getUploadedFileName('programFile');
			$extension = $fileManager->getExtension($oldName);
			if (!$extension) {
				return false;
			}
			$uploadName = 'program-' . $locale . '.' . $extension;
			if ($fileManager->uploadSchedConfFile($schedConf->getId(), $settingName, $uploadName)) {
				$value = $schedConf->getSetting($settingName);
				$value[$locale] = array(
					'name' => $oldName,
					'uploadName' => $uploadName,
					'dateUploaded' => Core::getCurrentDate(),
				);

				$schedConf->updateSetting($settingName, $value, 'object', true);
				return true;
			}
		}

		return false;
	}

	/**
	 * Deletes a program file.
	 * @param $settingName string setting key associated with the file
	 * @param $locale string
	 */
	function deleteProgram($settingName, $locale = null) {
		$schedConf =& Request::getSchedConf();
		$settingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$setting = $schedConf->getSetting($settingName);

		import('file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->removeSchedConfFile($schedConf->getId(), $locale !== null ? $setting[$locale]['uploadName'] : $setting['uploadName'] )) {
			return $settingsDao->deleteSetting($schedConf->getId(), $settingName, $locale);
		} else {
			return false;
		}
		
	}
}

?>
