<?php

/**
 * ConferenceSetupForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Base class for conference setup forms.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");
import('form.Form');

class ConferenceSetupForm extends Form {
	var $step;
	var $settings;
	
	/**
	 * Constructor.
	 * @param $step the step number
	 * @param $settings an associative array with the setting names as keys and associated types as values
	 */
	function ConferenceSetupForm($step, $settings) {
		parent::Form(sprintf('manager/setup/step%d.tpl', $step));
		$this->step = $step;
		$this->settings = $settings;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('setupStep', $this->step);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.setup');
		parent::display();
	}
	
	/**
	 * Initialize data from current settings.
	 */
	function initData() {
		$conference = &Request::getConference();
		$this->_data = $conference->getSettings();
	}
	
	/**
	 * Read user input.
	 */
	function readInputData() {		
		$this->readUserVars(array_keys($this->settings));
	}
	
	/**
	 * Save modified settings.
	 */
	function execute() {
		$conference = &Request::getConference();
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		
		foreach ($this->_data as $name => $value) {
			if (isset($this->settings[$name])) {
				$settingsDao->updateSetting(
					$conference->getConferenceId(),
					$name,
					$value,
					$this->settings[$name]
				);
			}
		}
	}

	/**
	 * Uploads a conference image.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadImage($settingName) {
		$conference = &Request::getConference();
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				return false;
			}
			
			$uploadName = $settingName . $extension;
			if ($fileManager->uploadConferenceFile($conference->getConferenceId(), $settingName, $uploadName)) {
				// Get image dimensions
				$filePath = $fileManager->getConferenceFilesPath($conference->getConferenceId());
				list($width, $height) = getimagesize($filePath . '/' . $settingName.$extension);
				
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'width' => $width,
					'height' => $height,
					'dateUploaded' => Core::getCurrentDate()
				);
				
				return $settingsDao->updateSetting($conference->getConferenceId(), $settingName, $value, 'object');
			}
		}
		
		return false;
	}

	/**
	 * Deletes a conference image.
	 * @param $settingName string setting key associated with the file
	 */
	function deleteImage($settingName) {
		$conference = &Request::getConference();
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		$setting = $settingsDao->getSetting($conference->getConferenceId(), $settingName);
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
	 	if ($fileManager->removeConferenceFile($conference->getConferenceId(), $setting['uploadName'])) {
			return $settingsDao->deleteSetting($conference->getConferenceId(), $settingName);
		} else {
			return false;
		}
	}
}

?>
