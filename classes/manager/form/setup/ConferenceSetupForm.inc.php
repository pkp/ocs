<?php

/**
 * @defgroup manager_form_setup
 */
 
/**
 * @file ConferenceSetupForm.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSetupForm
 * @ingroup manager_form_setup
 *
 * @brief Base class for conference setup forms.
 */

//$Id$

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
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('setupStep', $this->step);
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.websiteManagement');
		$templateMgr->setCacheability(CACHEABILITY_MUST_REVALIDATE);
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
				$isLocalized = in_array($name, $this->getLocaleFieldNames());
				$settingsDao->updateSetting(
					$conference->getConferenceId(),
					$name,
					$value,
					$this->settings[$name],
					$isLocalized
				);
			}
		}
	}

	/**
	 * Uploads a conference image.
	 * @param $settingName string setting key associated with the file
	 * @param $locale string
	 */
	function uploadImage($settingName, $locale) {
		$conference = &Request::getConference();
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');

		import('file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				return false;
			}
			$uploadName = $settingName . '_' . $locale . $extension;
			if ($fileManager->uploadConferenceFile($conference->getConferenceId(), $settingName, $uploadName)) {
				// Get image dimensions
				$filePath = $fileManager->getConferenceFilesPath($conference->getConferenceId());
				list($width, $height) = getimagesize($filePath . '/' . $uploadName);

				$value = $conference->getSetting($settingName);
				$value[$locale] = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'width' => $width,
					'height' => $height,
					'dateUploaded' => Core::getCurrentDate()
				);

				$settingsDao->updateSetting($conference->getConferenceId(), $settingName, $value, 'object', true);
				return true;
			}
		}

		return false;
	}

	/**
	 * Deletes a conference image.
	 * @param $settingName string setting key associated with the file
	 * @param $locale string
	 */
	function deleteImage($settingName, $locale = null) {
		$conference = &Request::getConference();
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		$setting = $settingsDao->getSetting($conference->getConferenceId(), $settingName);

		import('file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->removeConferenceFile($conference->getConferenceId(), $locale !== null ? $setting[$locale]['uploadName'] : $setting['uploadName'] )) {
			$returner = $settingsDao->deleteSetting($conference->getConferenceId(), $settingName, $locale);
			// Ensure page header is refreshed
			if ($returner) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign(array(
					'displayPageHeaderTitle' => $conference->getPageHeaderTitle(),
					'displayPageHeaderLogo' => $conference->getPageHeaderLogo()
				));
			}
			return $returner;
		} else {
			return false;
		}
	}
}

?>
