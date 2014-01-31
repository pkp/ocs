<?php

/**
 * @file classes/plugins/Plugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Plugin
 * @ingroup plugins
 *
 * @brief Abstract class for plugins
 */

// $Id$


import('plugins.PKPPlugin');

class Plugin extends PKPPlugin {
	/**
	 * Constructor
	 */
	function Plugin() {
		parent::PKPPlugin();
	}

	function getTemplatePath() {
		$basePath = dirname(dirname(dirname(__FILE__)));
		return "file:$basePath/" . $this->getPluginPath() . '/';
	}

	/**
	 * Called as a plugin is registered to the registry. Subclasses over-
	 * riding this method should call the parent method first.
	 * @param $category String Name of category plugin was registered to
	 * @param $path String The path the plugin was found in
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$returner = parent::register($category, $path);
		if ($this->getNewConferencePluginSettingsFile()) {
			HookRegistry::register ('ConferenceSiteSettingsForm::execute', array(&$this, 'installConferenceSettings'));
		}
		return $returner;
	}

	function getSetting($conferenceId, $schedConfId, $name) {
		if (!Config::getVar('general', 'installed')) return null;
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		return $pluginSettingsDao->getSetting($conferenceId, $schedConfId, $this->getName(), $name);
	}

	/**
	 * Update a plugin setting.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @param $name string The name of the setting
	 * @param $value mixed
	 * @param $type string optional
	 */
	function updateSetting($conferenceId, $schedConfId, $name, $value, $type = null) {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->updateSetting($conferenceId, $schedConfId, $this->getName(), $name, $value, $type);
	}

	/**
	 * Get the filename of the settings data for this plugin to install
	 * when a conference is created (i.e. conference-level plugin settings).
	 * Subclasses using default settings should override this.
	 * @return string
	 */
	function getNewConferencePluginSettingsFile() {
		return null;
	}

	/**
	 * Callback used to install settings on conference creation.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installConferenceSettings($hookName, $args) {
		$conference =& $args[1];

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->installSettings($conference->getId(), 0, $this->getName(), $this->getNewConferencePluginSettingsFile());

		return false;
	}

	/**
	 * Callback used to install settings on system install.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function installSiteSettings($hookName, $args) {
		$installer =& $args[0];
		$result =& $args[1];

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->installSettings(0, 0, $this->getName(), $this->getInstallSitePluginSettingsFile());

		return false;
	}
	
	/**
	 * Get the current version of this plugin
	 * @return object Version
	 */
	function getCurrentVersion() {
		$versionDao =& DAORegistry::getDAO('VersionDAO'); 
		$product = basename($this->getPluginPath());
		$installedPlugin = $versionDao->getCurrentVersion($product);
		
		if ($installedPlugin) {
			return $installedPlugin;
		} else {
			return false;
		}
	}
}

?>
