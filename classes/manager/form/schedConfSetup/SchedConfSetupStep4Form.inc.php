<?php

/**
 * SchedConfSetupStep4Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.schedConfSetup
 *
 * Form for Step 4 of scheduled conference setup.
 *
 * $Id$
 */

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep4Form extends SchedConfSetupForm {
	
	function SchedConfSetupStep4Form() {
		parent::SchedConfSetupForm(
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
	 * Uploads a scheduled conference file.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadDocument($settingName) {
		$schedConf = &Request::getSchedConf();
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			$extension = $fileManager->getDocumentExtension($type);
			if (!$extension) {
				return false;
			}
			
			$uploadName = $settingName . $extension;
			if ($fileManager->uploadSchedConfFile($schedConf->getSchedConfId(), $settingName, $uploadName)) {
				
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => Core::getCurrentDate()
				);
				
				return $schedConf->updateSetting($settingName, $value, 'object');
			}
		}
		
		return false;
	}

	/**
	 * Deletes a scheduled conference document.
	 * @param $settingName string setting key associated with the file
	 */
	function deleteDocument($settingName) {
		$schedConf = &Request::getSchedConf();
		$settingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$setting = $settingsDao->deleteSetting($schedConf->getSchedConfId(), $settingName);
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
	 	if ($fileManager->removeSchedConfFile($schedConf->getSchedConfId(), $setting['uploadName'])) {
			return $schedConf->deleteSetting($settingName);
		} else {
			return false;
		}
	}
	
	function initData() {
		parent::initData();

		$schedConf = &Request::getSchedConf();

		$this->_data['requireRegReader'] = !$schedConf->getSetting('openAccessVisitor', false);
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
		$schedConf = &Request::getSchedConf();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'programFile' => $schedConf->getSetting('programFile')
		));
		
		parent::display();	   
	}
	
	function execute() {
		$schedConf = Request::getSchedConf();

		$schedConf->updateSetting('openAccessReader', $this->_data['openAccessReader'], 'bool');
		$schedConf->updateSetting('openAccessVisitor', $this->_data['openAccessVisitor'], 'bool');

		parent::execute();
	}
}

?>
