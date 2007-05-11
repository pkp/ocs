<?php

/**
 * SchedConfSettingsForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for conference manager to edit basic scheduled conference settings.
 *
 * $Id$
 */

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
		$this->addCheck(new FormValidator($this, 'title', 'required', 'manager.schedConfs.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'acronym', 'required', 'manager.schedConfs.form.acronymRequired'));
		$this->addCheck(new FormValidator($this, 'path', 'required', 'manager.schedConfs.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'path', 'required', 'manager.schedConfs.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'manager.schedConfs.form.pathExists', create_function('$path,$form,$schedConfDao', 'return !$schedConfDao->schedConfExistsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, DAORegistry::getDAO('SchedConfDAO'))));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('schedConfId', $this->schedConfId);
		$templateMgr->assign('conferenceId', $this->conferenceId);
		$templateMgr->assign('helpTopicId', 'manager.schedConfManagement');
		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if(isset($this->schedConfId)) {
			$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
			$schedConf = &$schedConfDao->getSchedConf($this->schedConfId);
		
			if($schedConf != null) {
				$this->_data = array(
					'conferenceId' => $schedConf->getConferenceId(),
					'title' => $schedConf->getTitle(),
					'path' => $schedConf->getPath(),
					'acronym' => $schedConf->getSetting('acronym')
				);
			} else {
				$this->schedConfId = null;
			}
		}

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$conference = &$conferenceDao->getConference($this->conferenceId);
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
		$this->readUserVars(array('conferenceId', 'acronym', 'title', 'path'));

		if (isset($this->schedConfId)) {
			$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
			$schedConf = &$schedConfDao->getSchedConf($this->schedConfId);
			$this->setData('oldPath', $schedConf->getPath());
		}
	}
	
	/**
	 * Save scheduled conference settings.
	 */
	function execute() {
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		
		$conference =& $conferenceDao->getConference($this->getData('conferenceId'));
		
		if (isset($this->schedConfId)) {
			$schedConf = &$schedConfDao->getSchedConf($this->schedConfId);
		}
		
		if (!isset($schedConf)) {
			$schedConf = &new SchedConf();
		}
		
		$schedConf->setConferenceId($this->getData('conferenceId'));
		$schedConf->setPath($this->getData('path'));
		$schedConf->setTitle($this->getData('title'));

		if ($schedConf->getSchedConfId() != null) {
			$schedConfDao->updateSchedConf($schedConf);
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
			$schedConfSettingsDao = &DAORegistry::getDAO('SchedConfSettingsDAO');
			$schedConfSettingsDao->installSettings($schedConfId, 'registry/schedConfSettings.xml', array(
				'presenterGuidelinesUrl' => Request::url($conference->getPath(), $this->getData('path'), 'about', 'submissions', null, null, 'presenterGuidelines'),
				'indexUrl' => Request::getIndexUrl(),
				'conferencePath' => $conference->getPath(),
				'conferenceName' => $conference->getTitle(),
				'schedConfPath' => $this->getData('path'),
				'schedConfUrl' => Request::url($conference->getPath(), $this->getData('path'), 'index'),
				'schedConfName' => $this->getData('title')
			));
			
			// Create a default "Papers" track
			$trackDao = &DAORegistry::getDAO('TrackDAO');
			$track = &new Track();
			$track->setSchedConfId($schedConfId);
			$track->setTitle(Locale::translate('track.default.title'));
			$track->setAbbrev(Locale::translate('track.default.abbrev'));
			$track->setPolicy(Locale::translate('track.default.policy'));
			$track->setDirectorRestricted(false);
			$trackDao->insertTrack($track);
		}

		$schedConf->updateSetting('acronym', $this->getData('acronym'));
	}
}

?>
