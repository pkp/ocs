<?php

/**
 * ConferenceSetupForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form.setup
 *
 * Base class for conference setup forms.
 *
 * $Id$
 */

import("director.form.setup.ConferenceSetupForm");
import('form.Form');

class ConferenceSetupForm extends Form {
	var $step;
	var $settings;
	
	/**
	 * Constructor.
	 * @param $step the step number
	 * @param $settings an associative array with the setting names as keys and associated types as values
	 */
	function ConferenceSetupForm($step, $settings) {
		parent::Form(sprintf('director/setup/step%d.tpl', $step));
		$this->step = $step;
		$this->settings = $settings;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('setupStep', $this->step);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.setup');
		parent::display();
	}
	
	/**
	 * Initialize data from current settings.
	 */
	function initData() {
		$conference = &Request::getConference();
		$this->_data = $conference->getSettings();
	}
	
	/**
	 * Read user input.
	 */
	function readInputData() {		
		$this->readUserVars(array_keys($this->settings));
	}
	
	/**
	 * Save modified settings.
	 */
	function execute() {
		$conference = &Request::getConference();
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		
		foreach ($this->_data as $name => $value) {
			if (isset($this->settings[$name])) {
				$settingsDao->updateSetting(
					$conference->getConferenceId(),
					$name,
					$value,
					$this->settings[$name]
				);
			}
		}
	}
}

?>
