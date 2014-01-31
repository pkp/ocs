<?php

/**
 * @file SettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 *
 * @brief Form for conference managers to add or delete sidebar blocks
 *
 */

import('form.Form');

class SettingsForm extends Form {
	/** @var $conferenceId int */
	var $conferenceId;

	/** @var $plugin object */
	var $plugin;

	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $conferenceId int
	 */
	function SettingsForm(&$plugin, $conferenceId) {

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->conferenceId = $conferenceId;
		$this->plugin =& $plugin;

	}

	/**
	 * Initialize form data from  the plugin settings to the form
	 */
	function initData() {
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;

		$templateMgr =& TemplateManager::getManager();

		$blocks = $plugin->getSetting($conferenceId, 0, 'blocks');

		if ( !is_array($blocks) ) {
			$this->setData('blocks', array());
		} else {
			$this->setData('blocks', $blocks);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'blocks',
				'deletedBlocks'
			)
		);
	}

	/**
	 * Update the plugin settings
	 */
	function execute() {
		$plugin =& $this->plugin;
		$conferenceId = $this->conferenceId;

		$pluginSettingsDAO =& DAORegistry::getDAO('PluginSettingsDAO');

		$deletedBlocks = explode(':',$this->getData('deletedBlocks'));
		foreach ($deletedBlocks as $deletedBlock) {
			$pluginSettingsDAO->deleteSetting($conferenceId, 0, $deletedBlock.'CustomBlockPlugin', 'enabled');
			$pluginSettingsDAO->deleteSetting($conferenceId, 0, $deletedBlock.'CustomBlockPlugin', 'seq');
			$pluginSettingsDAO->deleteSetting($conferenceId, 0, $deletedBlock.'CustomBlockPlugin', 'context');
			$pluginSettingsDAO->deleteSetting($conferenceId, 0, $deletedBlock.'CustomBlockPlugin', 'blockContent');
		}

		//sort the blocks in alphabetical order
		$blocks = $this->getData('blocks');
		ksort($blocks);

		//remove any blank entries that made it into the array
		foreach ($blocks as $key => $value) {
			if (is_null($value) || trim($value)=="") {
				unset($blocks[$key]);
			}
		}

		// Update blocks
		$plugin->updateSetting($conferenceId, 0, 'blocks', $blocks);
		$this->setData('blocks',$blocks);
	}
}

?>
