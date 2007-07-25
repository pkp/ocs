<?php

/**
 * @file Upgrade.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package install
 * @class Upgrade
 *
 * Perform system upgrade.
 *
 * $Id$
 */

import('install.Installer');

class Upgrade extends Installer {

	/**
	 * Constructor.
	 * @param $params array upgrade parameters
	 */
	function Upgrade($params) {
		parent::Installer('upgrade.xml', $params);
	}
	

	/**
	 * Returns true iff this is an upgrade process.
	 */
	function isUpgrade() {
		return true;
	}

	//
	// Upgrade actions
	//
	
	/**
	 * Rebuild the search index.
	 * @return boolean
	 */
	function rebuildSearchIndex() {
		import('search.PaperSearchIndex');
		PaperSearchIndex::rebuildIndex();
		return true;
	}

	/**
	 * For upgrade to 2.0.1: Install default settings for block plugins.
	 */
	function installBlockPlugins() {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conferences =& $conferenceDao->getConferences();

		// Get conference IDs for insertion, including 0 for site-level
		$conferenceIds = array(0);
		while ($conference =& $conferences->next()) {
			$conferenceIds[] = $conference->getConferenceId();
			unset($conference);
		}

		$pluginNames = array(
			'DevelopedByBlockPlugin',
			'HelpBlockPlugin',
			'UserBlockPlugin',
			'LanguageToggleBlockPlugin',
			'NavigationBlockPlugin',
			'FontSizeBlockPlugin',
			'InformationBlockPlugin'
		);
		foreach ($conferenceIds as $conferenceId) {
			$i = 0;
			foreach ($pluginNames as $pluginName) {
				$pluginSettingsDao->updateSetting($conferenceId, 0, $pluginName, 'enabled', 'true', 'bool');
				$pluginSettingsDao->updateSetting($conferenceId, 0, $pluginName, 'seq', $i++, 'int');
				$pluginSettingsDao->updateSetting($conferenceId, 0, $pluginName, 'context', BLOCK_CONTEXT_RIGHT_SIDEBAR, 'int');
			}
		}

		return true;
	}
}

?>
