<?php

/**
 * @defgroup manager_form
 */

/**
 * @file AccommodationSettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AccommodationForm
 * @ingroup manager_form
 *
 * @brief Form for modifying scheduled conference accommodation settings.
 *
 */

// $Id$


import('form.Form');

class AccommodationSettingsForm extends Form {

	/** @var array the setting names */
	var $settings;

	/**
	 * Constructor.
	 */
	function AccommodationSettingsForm() {
		parent::Form('manager/accommodationSettings.tpl');

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		import('file.PublicFileManager');
		$schedConf =& Request::getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$site =& Request::getSite();
		$templateMgr->assign('helpTopicId','conference.currentConferences.accommodation');
		$templateMgr->assign('publicSchedConfFilesDir', Request::getBaseUrl() . '/' . PublicFileManager::getSchedConfFilesPath($schedConf->getId()));
		$templateMgr->assign('accommodationFiles', $schedConf->getSetting('accommodationFiles'));
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();
		$this->_data = array();
		$this->_data['accommodationDescription'] = $schedConf->getSetting('accommodationDescription');
	}
	
	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('accommodationDescription', 'accommodationFileTitle');
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('accommodationDescription', 'accommodationFileTitle'));
	}

	/**
	 * Save modified settings.
	 */
	function execute() {
		$schedConf =& Request::getSchedConf();
		$settingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');

		foreach ($this->_data as $name => $value) {
			$settingsDao->updateSetting(
				$schedConf->getId(),
				$name,
				$value,
				$this->settings[$name],
				true // Localized
			);
		}
	}
}

?>
