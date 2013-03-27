<?php

/**
 * @file AnnouncementFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.announcementFeed
 * @class AnnouncementFeedGatewayPlugin
 *
 * Gateway component of announcement feed plugin
 *
 * $Id$
 */

import('classes.plugins.GatewayPlugin');

class AnnouncementFeedGatewayPlugin extends GatewayPlugin {
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
		return 'AnnouncementFeedGatewayPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}

	/**
	 * Get the web feed plugin
	 * @return object
	 */
	function &getAnnouncementFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', 'AnnouncementFeedPlugin');
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
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
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args) {
		// Make sure we're within a Conference context
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		if (!$conference) return false;

		// Make sure announcements and plugin are enabled
		$announcementsEnabled = $conference->getSetting('enableAnnouncements');
		$announcementFeedPlugin =& $this->getAnnouncementFeedPlugin();
		if (!$announcementsEnabled || !$announcementFeedPlugin->getEnabled()) return false;

		// Make sure the feed type is specified and valid
		$type = array_shift($args);
		$typeMap = array(
			'rss' => 'rss.tpl',
			'rss2' => 'rss2.tpl',
			'atom' => 'atom.tpl'
		);
		$mimeTypeMap = array(
			'rss' => 'application/rdf+xml',
			'rss2' => 'application/rss+xml',
			'atom' => 'application/atom+xml'
		);
		if (!isset($typeMap[$type])) return false;

		// Get limit setting, if any 
		$limitRecentItems = $announcementFeedPlugin->getSetting($conference->getId(), 0, 'limitRecentItems');
		$recentItems = (int) $announcementFeedPlugin->getSetting($conference->getId(), 0, 'recentItems');

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$conferenceId = $conference->getId();
		if ($schedConf) {
			$schedConfId = $schedConf->getId();
		} else {
			$schedConfId = 0;
		}
		if ($limitRecentItems && $recentItems > 0) {
			import('db.DBResultRange');
			$rangeInfo = new DBResultRange($recentItems, 1);
			$announcements =& $announcementDao->getAnnouncementsNotExpiredByConferenceId($conferenceId, $schedConfId, $rangeInfo);
		} else {
			$announcements =& $announcementDao->getAnnouncementsNotExpiredByConferenceId($conferenceId, $schedConfId);
		}

		// Get date of most recent announcement
		$lastDateUpdated = $announcementFeedPlugin->getSetting($conference->getId(), $schedConfId, 'dateUpdated');
		if ($announcements->wasEmpty()) {
			if (empty($lastDateUpdated)) { 
				$dateUpdated = Core::getCurrentDate(); 
				$announcementFeedPlugin->updateSetting($conference->getId(), $schedConfId, 'dateUpdated', $dateUpdated, 'string');			
			} else {
				$dateUpdated = $lastDateUpdated;
			}
		} else {
			$mostRecentAnnouncement =& $announcementDao->getMostRecentAnnouncementByAssocId(ASSOC_TYPE_SCHED_CONF, $schedConfId);
			$dateUpdated = $mostRecentAnnouncement->getDatetimePosted();
			if (empty($lastDateUpdated) || (strtotime($dateUpdated) > strtotime($lastDateUpdated))) { 
				$announcementFeedPlugin->updateSetting($conference->getId(), $schedConfId, 'dateUpdated', $dateUpdated, 'string');			
			}
		}

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('selfUrl', Request::getCompleteUrl()); 
		$templateMgr->assign('dateUpdated', $dateUpdated);
		$templateMgr->assign('ocsVersion', $version->getVersionString());
		$templateMgr->assign_by_ref('announcements', $announcements->toArray());
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign_by_ref('schedConf', $schedConf);

		$templateMgr->display($this->getTemplatePath() . $typeMap[$type], $mimeTypeMap[$type]);

		return true;
	}
}

?>
