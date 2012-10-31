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


import('lib.pkp.classes.plugins.BlockPlugin');

class NavigationBlockPlugin extends BlockPlugin {
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
	function getContextSpecificPluginSettingsFile() {
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
	function getContents(&$templateMgr, $request = null) {
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
