<?php

/**
 * ConferenceSetupStep4Form.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 4 of conference setup.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep4Form extends ConferenceSetupForm {
	
	function ConferenceSetupStep4Form() {
		parent::ConferenceSetupForm(4, array());
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference = &Request::getConference();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'conferenceStyleSheet' => $conference->getSetting('conferenceStyleSheet')
		));
		
		parent::display();	   
	}
	
	
	/**
	 * Uploads conference custom stylesheet.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadStyleSheet($settingName) {
		$conference = &Request::getConference();
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
	
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			if ($type != 'text/plain' && $type != 'text/css') {
				return false;
			}
			$uploadName = $settingName . '.css';
			if($fileManager->uploadConferenceFile($conference->getConferenceId(), $settingName, $uploadName)) {			
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => date("Y-m-d g:i:s")
				);
				
				return $settingsDao->updateSetting($conference->getConferenceId(), $settingName, $value, 'object');
			}
		}
		return false;
	}
}

?>
