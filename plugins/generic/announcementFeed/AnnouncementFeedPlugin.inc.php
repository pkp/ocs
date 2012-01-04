<?php

/**
 * @file AnnouncementFeedPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.announcementFeed
 * @class AnnouncementFeedPlugin
 *
 * Annoucement Feed plugin class
 *
 * $Id$
 */

import('classes.plugins.GenericPlugin');

class AnnouncementFeedPlugin extends GenericPlugin {
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('TemplateManager::display',array(&$this, 'callbackAddLinks'));
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			}
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'AnnouncementFeedPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}   

	/**
	 * Check whether or not this plugin is enabled
	 * @return boolean
	 */
	function getEnabled() {
		$conference =& Request::getConference();
		$conferenceId = $conference?$conference->getId():0;
		return $this->getSetting($conferenceId, 0, 'enabled');
	}

	/**
	 * Register as a block and gateway plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block and gateway plugin
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks':
				$this->import('AnnouncementFeedBlockPlugin');
				$blockPlugin = new AnnouncementFeedBlockPlugin();
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] =& $blockPlugin;
				break;
			case 'gateways':
				$this->import('AnnouncementFeedGatewayPlugin');
				$gatewayPlugin = new AnnouncementFeedGatewayPlugin();
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] =& $gatewayPlugin;
				break;
		}
		return false;
	}

	function callbackAddLinks($hookName, $args) {
		if ($this->getEnabled()) {
			$templateManager =& $args[0];
			$currentConference =& $templateManager->get_template_vars('currentConference');
			$announcementsEnabled = $currentConference ? $currentConference->getSetting('enableAnnouncements') : false;
			$displayPage = $currentConference ? $this->getSetting($currentConference->getId(), 0, 'displayPage') : null;
			$requestedPage = Request::getRequestedPage();

			if ( $announcementsEnabled && (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'announcement')) || ($displayPage == $requestedPage)) ) { 

				// if we have a conference selected, append feed meta-links into the header
				$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');

				$feedUrl1 = '<link rel="alternate" type="application/atom+xml" href="' . Request::url(null, null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'atom')) . '" />';
				$feedUrl2 = '<link rel="alternate" type="application/rdf+xml" href="'. Request::url(null, null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'rss')) . '" />';
				$feedUrl3 = '<link rel="alternate" type="application/rss+xml" href="' . Request::url(null, null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'rss2')) . '" />';

				$templateManager->assign('additionalHeadData', $additionalHeadData."\n\t".$feedUrl1."\n\t".$feedUrl2."\n\t".$feedUrl3);
			}
		}

		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				__('manager.plugins.disable')
			);
			$verbs[] = array(
				'settings',
				__('plugins.generic.announcementfeed.settings')
			);
		} else {
			$verbs[] = array(
				'enable',
				__('manager.plugins.enable')
			);
		}
		return $verbs;
	}

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message) {
		$returner = true;
		$conference =& Request::getConference();

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $conference->getId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$returner = false;
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				break;
			case 'enable':
				$this->updateSetting($conference->getId(), 0, 'enabled', true);
				$message = __('plugins.generic.announcementfeed.enabled');
				$returner = false;
				break;
			case 'disable':
				$this->updateSetting($conference->getId(), 0, 'enabled', false);
				$message = __('plugins.generic.announcementfeed.enabled');
				$returner = false;
				break;	
		}
		return $returner;		
	}
}

?>
