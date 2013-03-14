<?php

/**
 * @file controllers/grid/settings/schedConf/form/SchedConfSettingsForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSettingsForm
 * @ingroup controllers_grid_settings_schedConf_form
 *
 * @brief Form for conference manager to edit basic schedConf settings.
 */

import('lib.pkp.controllers.grid.admin.context.form.ContextSiteSettingsForm');

class SchedConfSettingsForm extends ContextSiteSettingsForm {
	/**
	 * Constructor.
	 * @param $contextId omit for a new schedConf
	 */
	function SchedConfSettingsForm($contextId = null) {
		parent::ContextSiteSettingsForm('manager/schedConfSettings.tpl', $contextId);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'admin.schedconfs.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'path', 'required', 'admin.schedconfs.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'path', 'required', 'admin.schedconfs.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'admin.schedconfs.form.pathExists', create_function('$path,$form,$schedConfDao', 'return !$schedConfDao->existsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, DAORegistry::getDAO('SchedConfDAO'))));
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->contextId)) {
			$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getById($this->contextId);

			parent::initData($schedConf);
			$this->setData('acronym', $schedConf->getAcronym(null));
		} else {
			parent::initData();
		}
	}

	/**
	 * Get a list of field names for which localized settings are used
         * @return array
	 */
	function getLocaleFieldNames() {
		return array('name', 'acronym');
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'acronym', 'path'));

		if ($this->contextId) {
			$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getById($this->contextId);
			if ($schedConf) $this->setData('oldPath', $schedConf->getPath());
		}
	}

	/**
	 * Save schedConf settings.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$conference = $request->getConference();
		if (isset($this->contextId)) {
			$schedConf = $schedConfDao->getById($this->contextId);
		}

		if (!isset($schedConf)) {
			$schedConf = $schedConfDao->newDataObject();
			$schedConf->setConferenceId($conference->getId());
		}

		$schedConf->setPath($this->getData('path'));
		$schedConf->setEnabled($this->getData('enabled'));

		if ($schedConf->getId() != null) {
			$isNewSchedConf = false;
			$schedConfDao->updateObject($schedConf);
			$section = null;
		} else {
			$isNewSchedConf = true;
			$site = $request->getSite();

			// Give it a default primary locale
			$schedConfId = $schedConfDao->insertObject($schedConf);
			$schedConfDao->resequence();

			// Make the file directories for the scheduled conference
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$conferenceId = $schedConf->getConferenceId();
			$privateBasePath = Config::getVar('files','files_dir') . '/conferences/' . $conferenceId . '/schedConfs/' . $schedConfId;
			$publicBasePath = Config::getVar('files','public_files_dir') . '/conferences/' . $conferenceId . '/schedConfs/' . $schedConfId;
			$fileManager->mkdirtree($privateBasePath);
			$fileManager->mkdirtree($privateBasePath . '/papers');
			$fileManager->mkdirtree($privateBasePath . '/tracks');
			$fileManager->mkdirtree($publicBasePath);

			// Install default scheduled conference settings
			$schedConfSettingsDao = DAORegistry::getDAO('SchedConfSettingsDAO');

			$name = $this->getData('name');
			$name = $name[$this->getFormLocale()];

			AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_APP_DEFAULT);
			$dispatcher = $request->getDispatcher();
			$schedConfSettingsDao->installSettings($schedConfId, Config::getVar('general', 'registry_dir') . '/schedConfSettings.xml', array(
				'authorGuidelinesUrl' => $dispatcher->url($request, ROUTE_PAGE, array($conference->getPath(), $this->getData('schedConfPath')), 'about', 'submissions', null, null, 'authorGuidelines'),
				'indexUrl' => $request->getIndexUrl(),
				'conferencePath' => $conference->getPath(),
				'conferenceName' => $conference->getLocalizedName(),
				'schedConfPath' => $this->getData('path'),
				'schedConfUrl' => $dispatcher->url($request, ROUTE_PAGE, array($conference->getPath(), $this->getData('schedConfPath')), 'index'),
				'schedConfName' => $name
			));

			// Create a default "Papers" track
			$trackDao = DAORegistry::getDAO('TrackDAO');
			$track = new Track();
			$track->setSchedConfId($schedConfId);
			$track->setMetaReviewed(true);
			$track->setTitle(__('track.default.title'), $conference->getPrimaryLocale());
			$track->setAbbrev(__('track.default.abbrev'), $conference->getPrimaryLocale());
			$track->setPolicy(__('track.default.policy'), $conference->getPrimaryLocale());
			$track->setDirectorRestricted(false);
			$trackDao->insertTrack($track);
		}
		$schedConf->updateSetting('name', $this->getData('name'), 'string', true);
		$schedConf->updateSetting('acronym', $this->getData('acronym'), 'string', true);

		// Make sure all plugins are loaded for settings preload
		PluginRegistry::loadAllPlugins();

		HookRegistry::call('SchedConfSettingsForm::execute', array(&$this, &$schedConf));
	}
}

?>
