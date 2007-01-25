<?php

/**
 * EventSetupStep4Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form.eventSetup
 *
 * Form for Step 4 of event setup.
 *
 * $Id$
 */

import("director.form.eventSetup.EventSetupForm");

class EventSetupStep4Form extends EventSetupForm {
	
	function EventSetupStep4Form() {
		parent::EventSetupForm(
			4,
			array(
				'enableRegistration' => 'bool',
				'openAccessPolicy' => 'string',
				'registrationName' => 'string',
				'registrationEmail' => 'string',
				'registrationPhone' => 'string',
				'registrationFax' => 'string',
				'registrationMailingAddress' => 'string',
				'program' => 'string'
			)
		);
	}

	/**
	 * Uploads a event file.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadDocument($settingName) {
		$event = &Request::getEvent();
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			$extension = $fileManager->getDocumentExtension($type);
			if (!$extension) {
				return false;
			}
			
			$uploadName = $settingName . $extension;
			if ($fileManager->uploadEventFile($event->getEventId(), $settingName, $uploadName)) {
				
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => Core::getCurrentDate()
				);
				
				return $event->updateSetting($settingName, $value, 'object');
			}
		}
		
		return false;
	}

	/**
	 * Deletes a event document.
	 * @param $settingName string setting key associated with the file
	 */
	function deleteDocument($settingName) {
		$event = &Request::getEvent();
		$settingsDao =& DAORegistry::getDAO('EventSettingsDAO');
		$setting = $settingsDao->deleteSetting($event->getEventId(), $settingName);
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
	 	if ($fileManager->removeEventFile($event->getEventId(), $setting['uploadName'])) {
			return $event->deleteSetting($settingName);
		} else {
			return false;
		}
	}
	
	function initData() {
		parent::initData();

		$event = &Request::getEvent();

		$this->_data['requireRegReader'] = !$event->getSetting('openAccessVisitor', false);
	}

	function readInputData() {
		parent::readInputData();
		
		if($this->_data['enableRegistration']) {
			$this->_data['openAccessReader'] = false;
			$this->_data['openAccessVisitor'] = false;
		} else {
			$this->_data['openAccessReader'] = true;
			$this->_data['openAccessVisitor'] = !Request::getUserVar('requireRegReader');
		}
	}

	function display() {
		$event = &Request::getEvent();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'programFile' => $event->getSetting('programFile')
		));
		
		parent::display();	   
	}
	
	function execute() {
		$event = Request::getEvent();

		$event->updateSetting('openAccessReader', $this->_data['openAccessReader'], 'bool');
		$event->updateSetting('openAccessVisitor', $this->_data['openAccessVisitor'], 'bool');

		parent::execute();
	}
}

?>
