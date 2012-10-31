<?php

/**
 * InformationBlockPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InformationBlockPlugin
 * @ingroup plugins
 *
 * @brief Class for information block plugin
 */


import('lib.pkp.classes.plugins.BlockPlugin');

class InformationBlockPlugin extends BlockPlugin {
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
		return __('plugins.block.information.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.information.description');
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @param $request PKPRequest
	 * @return $string
	 */
	function getContents(&$templateMgr, $request = null) {
		$conference =& $request->getConference();
		if (!$conference) return '';

		$templateMgr->assign('forReaders', $conference->getLocalizedSetting('readerInformation'));
		$templateMgr->assign('forAuthors', $conference->getLocalizedSetting('authorInformation'));
		return parent::getContents($templateMgr, $request);
	}
}

?>
