<?php

/**
 * @file PhpMyVisitesSettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.phpMyVisites
 * @class PhpMyVisitesSettingsForm
 *
 * Form for conference managers to modify phpMyVisites plugin settings
 *
 * $Id$
 */

import('form.Form');

class PhpMyVisitesSettingsForm extends Form {

	/** @var $conferenceId int */
	var $conferenceId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $conferenceId int
	 */
	function PhpMyVisitesSettingsForm(&$plugin, $conferenceId) {
		$this->conferenceId = $conferenceId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'phpmvUrl', 'required', 'plugins.generic.phpmv.manager.settings.phpmvUrlRequired', create_function('$phpmvUrl', 'return strpos(trim(strtolower($phpmvUrl)), \'http://\') === 0 ? true : false;')));
		$this->addCheck(new FormValidator($this, 'phpmvSiteId', 'required', 'plugins.generic.phpmv.manager.settings.phpmvSiteIdRequired'));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'phpmvUrl' => $plugin->getSetting($conferenceId, 0, 'phpmvUrl'),
			'phpmvSiteId' => $plugin->getSetting($conferenceId, 0, 'phpmvSiteId')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('phpmvUrl', 'phpmvSiteId'));
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$conferenceId = $this->conferenceId;

		$plugin->updateSetting($conferenceId, 0, 'phpmvUrl', rtrim($this->getData('phpmvUrl'), "/"), 'string');
		$plugin->updateSetting($conferenceId, 0, 'phpmvSiteId', $this->getData('phpmvSiteId'), 'int');
	}
}

?>
