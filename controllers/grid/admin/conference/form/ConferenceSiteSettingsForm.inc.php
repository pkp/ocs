<?php

/**
 * @file controllers/grid/admin/conference/form/ConferenceSiteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSiteSettingsForm
 * @ingroup controllers_grid_admin_conference_form
 *
 * @brief Form for site administrator to edit basic conference settings.
 */

import('lib.pkp.controllers.grid.admin.context.form.ContextSiteSettingsForm');

class ConferenceSiteSettingsForm extends ContextSiteSettingsForm {
	/**
	 * Constructor.
	 * @param $contextId omit for a new conference
	 */
	function ConferenceSiteSettingsForm($contextId = null) {
		parent::ContextSiteSettingsForm('admin/conferenceSettings.tpl', $contextId);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'admin.conferences.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'path', 'required', 'admin.conferences.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'path', 'required', 'admin.conferences.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'admin.conferences.form.pathExists', create_function('$path,$form,$conferenceDao', 'return !$conferenceDao->existsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, DAORegistry::getDAO('ConferenceDAO'))));
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->contextId)) {
			$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
			$conference =& $conferenceDao->getById($this->contextId);

			parent::initData($conference);
		} else {
			parent::initData();
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();

		if ($this->contextId) {
			$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
			$conference =& $conferenceDao->getById($this->contextId);
			if ($conference) $this->setData('oldPath', $conference->getPath());
		}
	}

	/**
	 * Save conference settings.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$conferenceDao = DAORegistry::getDAO('ConferenceDAO');

		if (isset($this->contextId)) {
			$conference =& $conferenceDao->getById($this->contextId);
		}

		if (!isset($conference)) {
			$conference = $conferenceDao->newDataObject();
		}

		$conference->setPath($this->getData('path'));
		$conference->setEnabled($this->getData('enabled'));

		if ($conference->getId() != null) {
			$isNewConference = false;
			$conferenceDao->updateObject($conference);
			$section = null;
		} else {
			$isNewConference = true;
			$site = $request->getSite();

			// Give it a default primary locale
			$conference->setPrimaryLocale ($site->getPrimaryLocale());

			$conferenceId = $conferenceDao->insertObject($conference);
			$conferenceDao->resequence();

			// Make the site administrator the conference manager of newly created conferences
			$sessionManager =& SessionManager::getManager();
			$userSession =& $sessionManager->getUserSession();
			if ($userSession->getUserId() != null && $userSession->getUserId() != 0 && !empty($conferenceId)) {
				$role = new Role();
				$role->setConferenceId($conferenceId);
				$role->setUserId($userSession->getUserId());
				$role->setRoleId(ROLE_ID_MANAGER);

				$roleDao = DAORegistry::getDAO('RoleDAO');
				$roleDao->insertRole($role);
			}

			// Make the file directories for the conference
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$fileManager->mkdir(Config::getVar('files', 'files_dir') . '/conferences/' . $conferenceId);
			$fileManager->mkdir(Config::getVar('files', 'files_dir') . '/conferences/' . $conferenceId . '/schedConfs');
			$fileManager->mkdir(Config::getVar('files', 'public_files_dir') . '/conferences/' . $conferenceId);
			$fileManager->mkdir(Config::getVar('files', 'public_files_dir') . '/conferences/' . $conferenceId . '/schedConfs');

			// Install default conference settings
			$conferenceSettingsDao = DAORegistry::getDAO('ConferenceSettingsDAO');
			$names = $this->getData('name');
			AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_APP_COMMON);
			$dispatcher = $request->getDispatcher();
			$conferenceSettingsDao->installSettings($conferenceId, 'registry/conferenceSettings.xml', array(
				'privacyStatementUrl' => $dispatcher->url($request, ROUTE_PAGE, array($this->getData('path'), 'index'), 'about', 'submissions', null, null, 'privacyStatement'),
				'loginUrl' => $dispatcher->url($request, ROUTE_PAGE, array('index', 'index'), 'login'),
				'conferenceUrl' => $dispatcher->url($request, ROUTE_PAGE, array($this->getData('path'), 'index')),
				'conferencePath' => $this->getData('path'),
				'primaryLocale' => $site->getPrimaryLocale(),
				'aboutUrl' => $dispatcher->url($request, ROUTE_PAGE, array($this->getData('path'), 'index'), 'about'),
				'accountUrl' => $dispatcher->url($request, ROUTE_PAGE, array($this->getData('path'), 'index'), 'user', 'register'),
				'conferenceName' => $names[$site->getPrimaryLocale()]
			));

			// Install the default RT versions.
			import('classes.rt.ocs.ConferenceRTAdmin');
			$conferenceRtAdmin = new ConferenceRTAdmin($conferenceId);
			$conferenceRtAdmin->restoreVersions(false);
		}
		$conference->updateSetting('name', $this->getData('name'), 'string', true);
		$conference->updateSetting('description', $this->getData('description'), 'string', true);

		// Make sure all plugins are loaded for settings preload
		PluginRegistry::loadAllPlugins();

		HookRegistry::call('ConferenceSiteSettingsForm::execute', array(&$this, &$conference));
	}
}

?>
