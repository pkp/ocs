<?php

/**
 * @file classes/plugins/Plugin.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
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

	function getSetting($conferenceId, $schedConfId, $name) {
		return parent::getSetting(array($conferenceId, $schedConfId), $name);
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
		parent::updateSetting(array($conferenceId, $schedConfId), $name, $value, $type);
	}

	/**
	 * Get the filename of the settings data for this plugin to install
	 * when a conference is created (i.e. conference-level plugin settings).
	 * Subclasses using default settings should override this.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		// The default implementation delegates to the old
		// method for backwards compatibility.
		return $this->getNewConferencePluginSettingsFile();
	}

	/**
	 * For backwards compatibility only.
	 *
	 * New plug-ins should override getContextSpecificPluginSettingsFile
	 */
	function getNewConferencePluginSettingsFile() {
		return null;
	}
}

?>
