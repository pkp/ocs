<?php

/**
 * @file SettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.annoucementFeed
 * @class SettingsForm
 *
 * Form for conference managers to modify announcement feed plugin settings
 *
 * $Id$
 */

import('form.Form');

class SettingsForm extends Form {

	/** @var $conferenceId int */
	var $conferenceId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $conferenceId int
	 */
	function SettingsForm(&$plugin, $conferenceId) {
		$this->conferenceId = $conferenceId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;

		$this->setData('displayPage', $plugin->getSetting($conferenceId, 0, 'displayPage'));
		$this->setData('limitRecentItems', $plugin->getSetting($conferenceId, 0, 'limitRecentItems'));
		$this->setData('recentItems', $plugin->getSetting($conferenceId, 0, 'recentItems'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('displayPage','limitRecentItems','recentItems'));

		// check that recent items value is a positive integer
		if ((int) $this->getData('recentItems') <= 0) $this->setData('recentItems', '');
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$conferenceId = $this->conferenceId;

		$plugin->updateSetting($conferenceId, 0, 'displayPage', $this->getData('displayPage'));
		$plugin->updateSetting($conferenceId, 0, 'limitRecentItems', $this->getData('limitRecentItems') ? $this->getData('limitRecentItems') : 0);
		$plugin->updateSetting($conferenceId, 0, 'recentItems', $this->getData('recentItems'));
	}

}

?>
