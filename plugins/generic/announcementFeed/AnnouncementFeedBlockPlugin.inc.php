<?php

/**
 * @file AnnouncementFeedBlockPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.announcementFeed
 * @class AnnouncementFeedBlockPlugin
 *
 * Class for block component of announcement feed plugin
 *
 * $Id$
 */

import('plugins.BlockPlugin');

class AnnouncementFeedBlockPlugin extends BlockPlugin {
	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'AnnouncementFeedBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Install default settings on system install.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the announcement feed plugin
	 * @return object
	 */
	function &getAnnouncementFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'AnnouncementFeedPlugin');
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getAnnouncementFeedPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getAnnouncementFeedPlugin();
		return $plugin->getTemplatePath() . 'templates/';
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @return $string
	 */
	function getContents(&$templateMgr) {
		$conference =& Request::getConference();

		if (!$conference) return '';

		if (!$conference->getSetting('enableAnnouncements')) return ''; 

		$plugin =& $this->getAnnouncementFeedPlugin();
		$displayPage = $plugin->getSetting($conference->getId(), 0, 'displayPage');
		$requestedPage = Request::getRequestedPage();

		if (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'announcement')) || ($displayPage == $requestedPage)) { 
			return parent::getContents($templateMgr);
		} else {
			return '';
		}
	}
}

?>
