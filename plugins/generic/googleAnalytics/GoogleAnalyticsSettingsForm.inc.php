<?php

/**
 * @file GoogleAnalyticsSettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.googleAnalytics
 * @class GoogleAnalyticsSettingsForm
 *
 * Form for conference managers to modify Google Analytics plugin settings
 *
 * $Id$
 */

import('form.Form');

class GoogleAnalyticsSettingsForm extends Form {

	/** @var $conferenceId int */
	var $conferenceId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $conferenceId int
	 */
	function GoogleAnalyticsSettingsForm(&$plugin, $conferenceId) {
		$this->conferenceId = $conferenceId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'googleAnalyticsSiteId', 'required', 'plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteIdRequired'));

		$this->addCheck(new FormValidator($this, 'trackingCode', 'required', 'plugins.generic.googleAnalytics.manager.settings.trackingCodeRequired'));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'googleAnalyticsSiteId' => $plugin->getSetting($conferenceId, 0, 'googleAnalyticsSiteId'),
			'trackingCode' => $plugin->getSetting($conferenceId, 0, 'trackingCode')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('googleAnalyticsSiteId', 'trackingCode'));
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$conferenceId = $this->conferenceId;

		$plugin->updateSetting($conferenceId, 0, 'googleAnalyticsSiteId', trim($this->getData('googleAnalyticsSiteId'), "\"\';"), 'string');

		$trackingCode = $this->getData('trackingCode');
		if (($trackingCode != "urchin") && ($trackingCode != "ga")) {
			$trackingCode = "urchin";
		}	
		$plugin->updateSetting($conferenceId, 0, 'trackingCode', $trackingCode, 'string');
	}
}

?>
