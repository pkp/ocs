<?php

/**
 * @file SchedConfSetupForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSetupForm
 * @ingroup manager_form_setup
 *
 * @brief Base class for scheduled conference setup forms.
 */

//$Id$

import("manager.form.schedConfSetup.SchedConfSetupForm");
import('form.Form');

class SchedConfSetupForm extends Form {
	var $step;
	var $settings;

	/**
	 * Constructor.
	 * @param $step the step number
	 * @param $settings an associative array with the setting names as keys and associated types as values
	 */
	function SchedConfSetupForm($step, $settings) {
		parent::Form(sprintf('manager/schedConfSetup/step%d.tpl', $step));
		$this->addCheck(new FormValidatorPost($this));
		$this->step = $step;
		$this->settings = $settings;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('setupStep', $this->step);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.setup');
		$templateMgr->setCacheability(CACHEABILITY_MUST_REVALIDATE);
		$templateMgr->assign('yearOffsetFuture', SCHED_CONF_DATE_YEAR_OFFSET_FUTURE);
		parent::display();
	}

	/**
	 * Initialize data from current settings.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();
		$this->_data = $schedConf->getSettings();
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
		$schedConf =& Request::getSchedConf();
		$settingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');

		foreach ($this->_data as $name => $value) {
			if (isset($this->settings[$name])) {
				$isLocalized = in_array($name, $this->getLocaleFieldNames());
				$settingsDao->updateSetting(
					$schedConf->getId(),
					$name,
					$value,
					$this->settings[$name],
					$isLocalized
				);
			}
		}
	}
}

?>
