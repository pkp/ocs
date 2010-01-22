<?php

/**
 * @file ConferenceSiteSettingsForm.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSiteSettingsForm
 * @ingroup admin_form
 *
 * @brief Form for site administrator to edit basic conference settings.
 */

//$Id$

import('db.DBDataXMLParser');
import('form.Form');

class ConferenceSiteSettingsForm extends Form {

	/** The ID of the conference being edited */
	var $conferenceId;

	/**
	 * Constructor.
	 * @param $conferenceId omit for a new conference
	 */
	function ConferenceSiteSettingsForm($conferenceId = null) {
		parent::Form('admin/conferenceSettings.tpl');

		$this->conferenceId = isset($conferenceId) ? (int) $conferenceId : null;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'admin.conferences.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'path', 'required', 'admin.conferences.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'path', 'required', 'admin.conferences.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'admin.conferences.form.pathExists', create_function('$path,$form,$conferenceDao', 'return !$conferenceDao->conferenceExistsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, DAORegistry::getDAO('ConferenceDAO'))));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('conferenceId', $this->conferenceId);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->conferenceId)) {
			$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
			$conference = &$conferenceDao->getConference($this->conferenceId);

			if ($conference != null) {
				$this->_data = array(
					'title' => $conference->getTitle(null), // Localized
					'description' => $conference->getDescription(null), // Localized
					'path' => $conference->getPath(),
					'enabled' => $conference->getEnabled()
				);

			} else {
				$this->conferenceId = null;
			}
		}
		if (!isset($this->conferenceId)) {
			$this->_data = array(
				'enabled' => 1
			);
		}
	}

	/**
	 * Get a list of field names for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'description');
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'description', 'path', 'enabled'));
		$this->setData('enabled', (int)$this->getData('enabled'));

		if (isset($this->conferenceId)) {
			$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
			$conference = &$conferenceDao->getConference($this->conferenceId);
			$this->setData('oldPath', $conference->getPath());
		}
	}

	/**
	 * Save conference settings.
	 */
	function execute() {
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');

		if (isset($this->conferenceId)) {
			$conference = &$conferenceDao->getConference($this->conferenceId);
		}

		if (!isset($conference)) {
			$conference = &new Conference();
		}

		$conference->setPath($this->getData('path'));
		$conference->setEnabled($this->getData('enabled'));

		if ($conference->getConferenceId() != null) {
			$conferenceDao->updateConference($conference);
		} else {
			$site =& Request::getSite();

			// Give it a default primary locale.
			$conference->setPrimaryLocale($site->getPrimaryLocale());

			$conferenceId = $conferenceDao->insertConference($conference);
			$conferenceDao->resequenceConferences();

			// Make the site administrator the conference manager
			$sessionManager = &SessionManager::getManager();
			$userSession = &$sessionManager->getUserSession();
			if ($userSession->getUserId() != null && $userSession->getUserId() != 0 && !empty($conferenceId)) {
				$roleDao = &DAORegistry::getDAO('RoleDAO');

				$role = &new Role();
				$role->setConferenceId($conferenceId);
				$role->setSchedConfId(0);
				$role->setUserId($userSession->getUserId());
				$role->setRoleId(ROLE_ID_CONFERENCE_MANAGER);
				$roleDao->insertRole($role);
			}

			// Make the file directories for the conference
			import('file.FileManager');
			FileManager::mkdir(Config::getVar('files', 'files_dir') . '/conferences/' . $conferenceId);
			FileManager::mkdir(Config::getVar('files', 'files_dir') . '/conferences/' . $conferenceId . '/schedConfs');
			FileManager::mkdir(Config::getVar('files', 'public_files_dir') . '/conferences/' . $conferenceId);
			FileManager::mkdir(Config::getVar('files', 'public_files_dir') . '/conferences/' . $conferenceId . '/schedConfs');

			// Install default conference settings
			$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
			$titles = $this->getData('title');
			$conferenceSettingsDao->installSettings($conferenceId, 'registry/conferenceSettings.xml', array(
				'privacyStatementUrl' => Request::url($this->getData('path'), 'index', 'about', 'submissions', null, null, 'privacyStatement'),
				'loginUrl' => Request::url('index', 'index', 'login'),
				'conferenceUrl' => Request::url($this->getData('path'), null),
				'conferencePath' => $this->getData('path'),
				'primaryLocale' => $site->getPrimaryLocale(),
				'aboutUrl' => Request::url($this->getData('path'), 'index', 'about', null),
				'accountUrl' => Request::url($this->getData('path'), 'index', 'user', 'register'),
				'conferenceName' => $titles[$site->getPrimaryLocale()]
			));

			// Install the default RT versions.
			import('rt.ocs.ConferenceRTAdmin');
			$conferenceRtAdmin = &new ConferenceRTAdmin($conferenceId);
			$conferenceRtAdmin->restoreVersions(false);
		}

		$conference->updateSetting('title', $this->getData('title'), 'string', true);
		$conference->updateSetting('description', $this->getData('description'), 'string', true);

		HookRegistry::call('ConferenceSiteSettingsForm::execute', array(&$this, &$conference));
	}
}

?>
