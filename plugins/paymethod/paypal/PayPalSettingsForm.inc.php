<?php

/**
 * @file PayPalSettingsForm.inc.php
 *
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PayPalSettingsForm
 * @ingroup plugins_paymethod_paypal
 * @see PayPalPlugin
 *
 * @brief Form for conference managers to edit the PayPal Settings
 * 
 */
 
//$Id$

import('form.Form');

class PayPalSettingsForm extends Form {
	/** @var $schedConfId int */
	var $schedConfId;

	/** @var $plugin object */
	var $plugin;

	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $schedConfId int
	 */
	function PayPalSettingsForm(&$plugin, $conferenceId, $schedConfId) {
		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorPost($this));

		$this->conferenceId = $conferenceId;
		$this->schedConfId = $schedConfId;
		$this->plugin =& $plugin;

	}



	/**
	 * Initialize form data from current group group.
	 */
	function initData( ) {
		$schedConfId = $this->schedConfId;
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;

		/* FIXME: put these defaults somewhere else */
		/*
		$paypalSettings['enabled'] = true;
		$paypalSettings['paypalurl'] = "http://www.sandbox.paypal.com";
		$paypalSettings['selleraccount'] = "seller@ojs.org";
		;
		*/

		$this->_data = array(
			'enabled' => $plugin->getSetting($conferenceId, $schedConfId, 'enabled'),
			'paypalurl' => $plugin->getSetting($conferenceId, $schedConfId, 'paypalurl'),
			'selleraccount' => $plugin->getSetting($conferenceId, $schedConfId, 'selleraccount'),
		);

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('enabled',
								'paypalurl', 
								'selleraccount'
								));
	}

	/**
	 * Save page - write to content file. 
	 */	 
	function save() {
		$plugin =& $this->plugin;
		$conferenceId = $this->conferenceId;
		$schedConfId = $this->schedConfId;

		$paypalSettings = array();
		$plugin->updateSetting($conferenceId, $schedConfId, 'enabled', $this->getData('enabled'));
		$plugin->updateSetting($conferenceId, $schedConfId, 'paypalurl', $this->getData('paypalurl'));
		$plugin->updateSetting($conferenceId, $schedConfId, 'selleraccount',$this->getData('selleraccount'));
	}
}

?>
