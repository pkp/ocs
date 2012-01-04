<?php

/**
 * NavigationBlockPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NavigationBlockPlugin
 * @ingroup plugins
 *
 * @brief Class for navigation block plugin
 */

//$Id$

import('plugins.BlockPlugin');

class NavigationBlockPlugin extends BlockPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->addLocaleData();
		}
		return $success;
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'NavigationBlockPlugin';
	}

	/**
	 * Install default settings on system install.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Install default settings on conference creation.
	 * @return string
	 */
	function getNewConferencePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.navigation.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.navigation.description');
	}

	/**
	 * Get the contents for this block.
	 * @param $templateMgr object
	 * @return string
	 */
	function getContents(&$templateMgr) {
		$templateMgr->assign('paperSearchByOptions', array(
			'' => 'search.allFields',
			PAPER_SEARCH_AUTHOR => 'search.author',
			PAPER_SEARCH_TITLE => 'paper.title',
			PAPER_SEARCH_ABSTRACT => 'search.abstract',
			PAPER_SEARCH_INDEX_TERMS => 'search.indexTerms',
			PAPER_SEARCH_GALLEY_FILE => 'search.fullText'
		));
		return parent::getContents($templateMgr);
	}
}

?>
