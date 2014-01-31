<?php

/**
 * @file SchedConfSettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSettingsForm
 * @ingroup manager_form
 *
 * @brief Form for conference manager to edit basic scheduled conference settings.
 */

//$Id$

import('db.DBDataXMLParser');
import('form.Form');

class SchedConfSettingsForm extends Form {

	/** The ID of the scheduled conference being edited */
	var $schedConfId;
	var $conferenceId;

	/**
	 * Constructor.
	 * @param $schedConfId omit for a new scheduled conference
	 */
	function SchedConfSettingsForm($args = array()) {
		parent::Form('manager/schedConfSettings.tpl');

		$this->conferenceId = $args[0];
		$this->schedConfId = $args[1];

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.schedConfs.form.titleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'acronym', 'required', 'manager.schedConfs.form.acronymRequired'));
		$this->addCheck(new FormValidator($this, 'schedConfPath', 'required', 'manager.schedConfs.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'schedConfPath', 'required', 'manager.schedConfs.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom($this, 'schedConfPath', 'required', 'manager.schedConfs.form.pathExists', create_function('$path,$form,$schedConfDao', 'return !$schedConfDao->schedConfExistsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, DAORegistry::getDAO('SchedConfDAO'))));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('schedConfId', $this->schedConfId);
		$templateMgr->assign('conferenceId', $this->conferenceId);
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.scheduledConferences');
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if(isset($this->schedConfId)) {
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getSchedConf($this->schedConfId);

			if($schedConf != null) {
				$this->_data = array(
					'conferenceId' => $schedConf->getConferenceId(),
					'title' => $schedConf->getTitle(null), // Localized
					'schedConfPath' => $schedConf->getPath(),
					'acronym' => $schedConf->getAcronym(null) // Localized
				);
			} else {
				$this->schedConfId = null;
			}
		}

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conference =& $conferenceDao->getConference($this->conferenceId);
		if ($conference == null) {
			// TODO: redirect?
			$this->conferenceId = null;
		}

		if (!isset($this->schedConfId)) {
			$this->_data = array(
				'conferenceId' => $this->conferenceId
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('conferenceId', 'acronym', 'title', 'schedConfPath'));

		if (isset($this->schedConfId)) {
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getSchedConf($this->schedConfId);
			$this->setData('oldPath', $schedConf->getPath());
		}
	}

	/**
	 * Get a list of field names for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'acronym');
	}

	/**
	 * Save scheduled conference settings.
	 */
	function execute() {
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');

		$conference =& $conferenceDao->getConference($this->getData('conferenceId'));

		if (isset($this->schedConfId)) {
			$schedConf =& $schedConfDao->getSchedConf($this->schedConfId);
		}

		if (!isset($schedConf)) {
			$schedConf = new SchedConf();
		}

		$schedConf->setConferenceId($this->getData('conferenceId'));
		$schedConf->setPath($this->getData('schedConfPath'));

		if ($schedConf->getId() != null) {
			$schedConfDao->updateSchedConf($schedConf);
			$track = null; // avoid warning
		} else {
			$schedConfId = $schedConfDao->insertSchedConf($schedConf);
			$schedConfDao->resequenceSchedConfs($this->getData('conferenceId'));

			// Make the file directories for the scheduled conference
			import('file.FileManager');
			$conferenceId = $schedConf->getConferenceId();
			$privateBasePath = Config::getVar('files','files_dir') . '/conferences/' . $conferenceId . '/schedConfs/' . $schedConfId;
			$publicBasePath = Config::getVar('files','public_files_dir') . '/conferences/' . $conferenceId . '/schedConfs/' . $schedConfId;
			FileManager::mkdirtree($privateBasePath);
			FileManager::mkdirtree($privateBasePath . '/papers');
			FileManager::mkdirtree($privateBasePath . '/tracks');
			FileManager::mkdirtree($publicBasePath);

			// Install default scheduled conference settings
			$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');

			$title = $this->getData('title');
			$title = $title[$this->getFormLocale()];

			AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_OCS_DEFAULT));
			$schedConfSettingsDao->installSettings($schedConfId, Config::getVar('general', 'registry_dir') . '/schedConfSettings.xml', array(
				'authorGuidelinesUrl' => Request::url($conference->getPath(), $this->getData('schedConfPath'), 'about', 'submissions', null, null, 'authorGuidelines'),
				'indexUrl' => Request::getIndexUrl(),
				'conferencePath' => $conference->getPath(),
				'conferenceName' => $conference->getConferenceTitle(),
				'schedConfPath' => $this->getData('schedConfPath'),
				'schedConfUrl' => Request::url($conference->getPath(), $this->getData('schedConfPath'), 'index'),
				'schedConfName' => $title
			));

			// Create a default "Papers" track
			$trackDao =& DAORegistry::getDAO('TrackDAO');
			$track = new Track();
			$track->setSchedConfId($schedConfId);
			$track->setMetaReviewed(true);
			$track->setTitle(__('track.default.title'), $conference->getPrimaryLocale());
			$track->setAbbrev(__('track.default.abbrev'), $conference->getPrimaryLocale());
			$track->setPolicy(__('track.default.policy'), $conference->getPrimaryLocale());
			$track->setDirectorRestricted(false);
			$trackDao->insertTrack($track);
		}

		$schedConf->updateSetting('title', $this->getData('title'), 'string', true);
		$schedConf->updateSetting('acronym', $this->getData('acronym'), 'string', true);

		HookRegistry::call('SchedConfSettingsForm::execute', array(&$this, &$conference, &$schedConf, &$track));
	}
}

?>
