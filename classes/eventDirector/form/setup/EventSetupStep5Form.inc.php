<?php

/**
 * EventSetupStep5Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package eventDirector.form.setup
 *
 * Form for Step 5 of event setup.
 *
 * $Id$
 */

import("eventDirector.form.setup.EventSetupForm");

class EventSetupStep5Form extends EventSetupForm {
	
	/**
	 * Constructor.
	 */
	function EventSetupStep5Form() {
		parent::EventSetupForm(
			5,
			array(
				'homeHeaderTitleType' => 'int',
				'homeHeaderTitle' => 'string',
				'homeHeaderTitleTypeAlt1' => 'int',
				'homeHeaderTitleAlt1' => 'string',
				'homeHeaderTitleTypeAlt2' => 'int',
				'homeHeaderTitleAlt2' => 'string',
				'pageHeaderTitleType' => 'int',
				'pageHeaderTitle' => 'string',
				'pageHeaderTitleTypeAlt1' => 'int',
				'pageHeaderTitleAlt1' => 'string',
				'pageHeaderTitleTypeAlt2' => 'int',
				'pageHeaderTitleAlt2' => 'string',
				'readerInformation' => 'string',
				'authorInformation' => 'string',
				'librarianInformation' => 'string',
				'conferencePageHeader' => 'string',
				'conferencePageFooter' => 'string',
				'displayCurrentIssue' => 'bool',
				'additionalHomeContent' => 'string',
				'eventDescription' => 'string',
				'navItems' => 'object',
				'itemsPerPage' => 'int',
				'numPageLinks' => 'int'
			)
		);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$event = &Request::getEvent();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'homeHeaderTitleImage' => $event->getSetting('homeHeaderTitleImage'),
			'homeHeaderLogoImage'=> $event->getSetting('homeHeaderLogoImage'),
			'homeHeaderTitleImageAlt1' => $event->getSetting('homeHeaderTitleImageAlt1'),
			'homeHeaderLogoImageAlt1'=> $event->getSetting('homeHeaderLogoImageAlt1'),
			'homeHeaderTitleImageAlt2' => $event->getSetting('homeHeaderTitleImageAlt2'),
			'homeHeaderLogoImageAlt2'=> $event->getSetting('homeHeaderLogoImageAlt2'),
			'pageHeaderTitleImage' => $event->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $event->getSetting('pageHeaderLogoImage'),
			'pageHeaderTitleImageAlt1' => $event->getSetting('pageHeaderTitleImageAlt1'),
			'pageHeaderLogoImageAlt1' => $event->getSetting('pageHeaderLogoImageAlt1'),
			'pageHeaderTitleImageAlt2' => $event->getSetting('pageHeaderTitleImageAlt2'),
			'pageHeaderLogoImageAlt2' => $event->getSetting('pageHeaderLogoImageAlt2'),
			'homepageImage' => $event->getSetting('homepageImage'),
			'eventStyleSheet' => $event->getSetting('eventStyleSheet'),
			'readerInformation' => $event->getSetting('readerInformation'),
			'authorInformation' => $event->getSetting('authorInformation'),
			'librarianInformation' => $event->getSetting('librarianInformation')
		));
		
		parent::display();	   
	}
	
	/**
	 * Uploads a event image.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadImage($settingName) {
		$event = &Request::getEvent();
		$settingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				return false;
			}
			
			$uploadName = $settingName . $extension;
			if ($fileManager->uploadEventFile($event->getEventId(), $settingName, $uploadName)) {
				// Get image dimensions
				$filePath = $fileManager->getEventFilesPath($event->getEventId());
				list($width, $height) = getimagesize($filePath . '/' . $settingName.$extension);
				
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'width' => $width,
					'height' => $height,
					'dateUploaded' => Core::getCurrentDate()
				);
				
				return $settingsDao->updateSetting($event->getEventId(), $settingName, $value, 'object');
			}
		}
		
		return false;
	}

	/**
	 * Deletes a event image.
	 * @param $settingName string setting key associated with the file
	 */
	function deleteImage($settingName) {
		$event = &Request::getEvent();
		$settingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		$setting = $settingsDao->getSetting($event->getEventId(), $settingName);
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
	 	if ($fileManager->removeEventFile($event->getEventId(), $setting['uploadName'])) {
			return $settingsDao->deleteSetting($event->getEventId(), $settingName);
		} else {
			return false;
		}
	}
	
	/**
	 * Uploads event custom stylesheet.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadStyleSheet($settingName) {
		$event = &Request::getEvent();
		$settingsDao = &DAORegistry::getDAO('EventSettingsDAO');
	
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			if ($type != 'text/plain' && $type != 'text/css') {
				return false;
			}
	
			$uploadName = $settingName . '.css';
			if($fileManager->uploadEventFile($event->getEventId(), $settingName, $uploadName)) {			
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => date("Y-m-d g:i:s")
				);
				
				return $settingsDao->updateSetting($event->getEventId(), $settingName, $value, 'object');
			}
		}
		
		return false;
	}
	
}

?>
