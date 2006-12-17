<?php

/**
 * EventSetupForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package eventDirector.form.setup
 *
 * Base class for event setup forms.
 *
 * $Id$
 */

import("eventDirector.form.setup.EventSetupForm");
import('form.Form');

class EventSetupForm extends Form {
	var $step;
	var $settings;
	
	/**
	 * Constructor.
	 * @param $step the step number
	 * @param $settings an associative array with the setting names as keys and associated types as values
	 */
	function EventSetupForm($step, $settings) {
		parent::Form(sprintf('eventDirector/setup/step%d.tpl', $step));
		$this->step = $step;
		$this->settings = $settings;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('setupStep', $this->step);
		$templateMgr->assign('helpTopicId', 'event.managementPages.setup');
		$templateMgr->assign('yearOffsetFuture', EVENT_DATE_YEAR_OFFSET_FUTURE);
		parent::display();
	}
	
	/**
	 * Initialize data from current settings.
	 */
	function initData() {
		$event = &Request::getEvent();
		$this->_data = $event->getSettings();
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
		$event = &Request::getEvent();
		$settingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		
		foreach ($this->_data as $name => $value) {
			if (isset($this->settings[$name])) {
				$settingsDao->updateSetting(
					$event->getEventId(),
					$name,
					$value,
					$this->settings[$name]
				);
			}
		}
	}
}

?>
