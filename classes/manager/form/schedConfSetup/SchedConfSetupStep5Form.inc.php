<?php

/**
 * SchedConfSetupStep5Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.schedConfSetup
 *
 * Form for Step 5 of scheduled conference setup.
 *
 * $Id$
 */

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep5Form extends SchedConfSetupForm {
	
	/**
	 * Constructor.
	 */
	function SchedConfSetupStep5Form() {
		parent::SchedConfSetupForm(
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
				'presenterInformation' => 'string',
				'librarianInformation' => 'string',
				'conferencePageHeader' => 'string',
				'conferencePageFooter' => 'string',
				'displayCurrentIssue' => 'bool',
				'additionalHomeContent' => 'string',
				'schedConfDescription' => 'string',
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
		$schedConf = &Request::getSchedConf();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'homeHeaderTitleImage' => $schedConf->getSetting('homeHeaderTitleImage'),
			'homeHeaderLogoImage'=> $schedConf->getSetting('homeHeaderLogoImage'),
			'homeHeaderTitleImageAlt1' => $schedConf->getSetting('homeHeaderTitleImageAlt1'),
			'homeHeaderLogoImageAlt1'=> $schedConf->getSetting('homeHeaderLogoImageAlt1'),
			'homeHeaderTitleImageAlt2' => $schedConf->getSetting('homeHeaderTitleImageAlt2'),
			'homeHeaderLogoImageAlt2'=> $schedConf->getSetting('homeHeaderLogoImageAlt2'),
			'pageHeaderTitleImage' => $schedConf->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $schedConf->getSetting('pageHeaderLogoImage'),
			'pageHeaderTitleImageAlt1' => $schedConf->getSetting('pageHeaderTitleImageAlt1'),
			'pageHeaderLogoImageAlt1' => $schedConf->getSetting('pageHeaderLogoImageAlt1'),
			'pageHeaderTitleImageAlt2' => $schedConf->getSetting('pageHeaderTitleImageAlt2'),
			'pageHeaderLogoImageAlt2' => $schedConf->getSetting('pageHeaderLogoImageAlt2'),
			'homepageImage' => $schedConf->getSetting('homepageImage'),
			'schedConfStyleSheet' => $schedConf->getSetting('schedConfStyleSheet'),
			'readerInformation' => $schedConf->getSetting('readerInformation'),
			'presenterInformation' => $schedConf->getSetting('presenterInformation'),
			'librarianInformation' => $schedConf->getSetting('librarianInformation')
		));
		
		parent::display();	   
	}
	
	/**
	 * Uploads a scheduled conference image.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadImage($settingName) {
		$schedConf = &Request::getSchedConf();
		$settingsDao = &DAORegistry::getDAO('SchedConfSettingsDAO');
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				return false;
			}
			
			$uploadName = $settingName . $extension;
			if ($fileManager->uploadSchedConfFile($schedConf->getSchedConfId(), $settingName, $uploadName)) {
				// Get image dimensions
				$filePath = $fileManager->getSchedConfFilesPath($schedConf->getSchedConfId());
				list($width, $height) = getimagesize($filePath . '/' . $settingName.$extension);
				
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'width' => $width,
					'height' => $height,
					'dateUploaded' => Core::getCurrentDate()
				);
				
				return $settingsDao->updateSetting($schedConf->getSchedConfId(), $settingName, $value, 'object');
			}
		}
		
		return false;
	}

	/**
	 * Deletes a scheduled conference image.
	 * @param $settingName string setting key associated with the file
	 */
	function deleteImage($settingName) {
		$schedConf = &Request::getSchedConf();
		$settingsDao = &DAORegistry::getDAO('SchedConfSettingsDAO');
		$setting = $settingsDao->getSetting($schedConf->getSchedConfId(), $settingName);
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
	 	if ($fileManager->removeSchedConfFile($schedConf->getSchedConfId(), $setting['uploadName'])) {
			return $settingsDao->deleteSetting($schedConf->getSchedConfId(), $settingName);
		} else {
			return false;
		}
	}
	
	/**
	 * Uploads scheduled conference custom stylesheet.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadStyleSheet($settingName) {
		$schedConf = &Request::getSchedConf();
		$settingsDao = &DAORegistry::getDAO('SchedConfSettingsDAO');
	
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			if ($type != 'text/plain' && $type != 'text/css') {
				return false;
			}
	
			$uploadName = $settingName . '.css';
			if($fileManager->uploadSchedConfFile($schedConf->getSchedConfId(), $settingName, $uploadName)) {			
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => date("Y-m-d g:i:s")
				);
				
				return $settingsDao->updateSetting($schedConf->getSchedConfId(), $settingName, $value, 'object');
			}
		}
		
		return false;
	}
	
}

?>
