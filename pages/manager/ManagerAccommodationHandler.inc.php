<?php

/**
 * @file ManagerAccommodationHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerAccommodationHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing scheduled conference accommodation settings. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class ManagerAccommodationHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function ManagerAccommodationHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display form to edit accommodation settings.
	 */
	function accommodation() {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect (null, null, 'index');

		import('manager.form.AccommodationSettingsForm');

		$settingsForm = new AccommodationSettingsForm();
		if ($settingsForm->isLocaleResubmit()) {
			$settingsForm->readInputData();
		} else {
			$settingsForm->initData();
		}
		$settingsForm->display();
	}

	/**
	 * Save changes to accommodation settings.
	 */
	function saveAccommodationSettings() {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect (null, null, 'index');

		import('manager.form.AccommodationSettingsForm');

		$settingsForm = new AccommodationSettingsForm();
		$settingsForm->readInputData();

		$editData = false;

		$accommodationFiles =& $schedConf->getSetting('accommodationFiles');
		if (Request::getUserVar('uploadAccommodationFile')) {
			// Get a numeric key for this file.
			$thisFileKey = 0;
			if (isset($accommodationFiles[$settingsForm->getFormLocale()])) foreach ($accommodationFiles[$settingsForm->getFormLocale()] as $key => $junk) {
				$thisFileKey = $key + 1;
			}

			import('file.PublicFileManager');
			$fileManager = new PublicFileManager();
			$success = !$fileManager->uploadError('accommodationFile');
			if ($success && $success = $fileManager->uploadedFileExists('accommodationFile')) {
				$oldName = $fileManager->getUploadedFileName('accommodationFile');
				$extension = $fileManager->getExtension($oldName);
				if (!$extension) break;
				$uploadName = 'accommodation-' . $thisFileKey . '.' . $extension;
				if ($success && $success = $fileManager->uploadSchedConfFile($schedConf->getId(), 'accommodationFile', $uploadName)) {
					$value = array(
						'name' => $oldName,
						'uploadName' => $uploadName,
						'dateUploaded' => Core::getCurrentDate(),
						'title' => Request::getUserVar('accommodationFileTitle')
					);
					$accommodationFiles[$settingsForm->getFormLocale()][$thisFileKey] =& $value;
					$settingsForm->setData('accommodationFiles', $accommodationFiles);
					$settingsForm->setData('accommodationFileTitle', '');
					$schedConf->updateSetting('accommodationFiles', $accommodationFiles, 'object', true);
				}
			}
			if (!$success) {
				$settingsForm->addError('accommodationFiles', __('common.uploadFailed'));
			}
			$editData = true;
		} else {
			$formLocale = $settingsForm->getFormLocale();
			$deleteKey = null;
			if (isset($accommodationFiles[$formLocale])) {
				foreach ($accommodationFiles[$formLocale] as $key => $junk) {
					if (Request::getUserVar("deleteAccommodationFile-$formLocale-$key")) $deleteKey = $key;
				}
			}
			if ($deleteKey !== null) {
				import('file.PublicFileManager');
				$fileManager = new PublicFileManager();
				if ($fileManager->removeSchedConfFile($schedConf->getId(), $accommodationFiles[$formLocale][$deleteKey]['uploadName'])) {
					unset($accommodationFiles[$formLocale][$deleteKey]);
					$schedConf->updateSetting('accommodationFiles', $accommodationFiles, 'object', true);
				}
				$editData = true;
			}
		}

		if (!$editData && $settingsForm->validate()) {
			$settingsForm->execute();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, null, 'accommodation'),
				'pageTitle' => 'schedConf.accommodation',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, Request::getRequestedPage()),
				'backLinkLabel' => 'manager.conferenceSiteManagement'
			));
			$templateMgr->display('common/message.tpl');

		} else {
			$settingsForm->display();
		}
	}

}
?>
