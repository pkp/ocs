<?php

/**
 * ConferenceSetupStep5Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 5 of conference setup.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep5Form extends ConferenceSetupForm {
	
	/**
	 * Constructor.
	 */
	function ConferenceSetupStep5Form() {
		parent::ConferenceSetupForm(
			5,
			array(
			)
		);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$conference = &Request::getConference();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'homeHeaderTitleImage' => $conference->getSetting('homeHeaderTitleImage'),
			'homeHeaderLogoImage'=> $conference->getSetting('homeHeaderLogoImage'),
			'homeHeaderTitleImageAlt1' => $conference->getSetting('homeHeaderTitleImageAlt1'),
			'homeHeaderLogoImageAlt1'=> $conference->getSetting('homeHeaderLogoImageAlt1'),
			'homeHeaderTitleImageAlt2' => $conference->getSetting('homeHeaderTitleImageAlt2'),
			'homeHeaderLogoImageAlt2'=> $conference->getSetting('homeHeaderLogoImageAlt2'),
			'pageHeaderTitleImage' => $conference->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $conference->getSetting('pageHeaderLogoImage'),
			'pageHeaderTitleImageAlt1' => $conference->getSetting('pageHeaderTitleImageAlt1'),
			'pageHeaderLogoImageAlt1' => $conference->getSetting('pageHeaderLogoImageAlt1'),
			'pageHeaderTitleImageAlt2' => $conference->getSetting('pageHeaderTitleImageAlt2'),
			'pageHeaderLogoImageAlt2' => $conference->getSetting('pageHeaderLogoImageAlt2'),
			'homepageImage' => $conference->getSetting('homepageImage'),
			'conferenceStyleSheet' => $conference->getSetting('conferenceStyleSheet'),
			'readerInformation' => $conference->getSetting('readerInformation'),
			'presenterInformation' => $conference->getSetting('presenterInformation')
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
