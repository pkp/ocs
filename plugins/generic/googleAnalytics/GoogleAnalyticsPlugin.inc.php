<?php

/**
 * @file GoogleAnalyticsPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.googleAnalytics
 * @class GoogleAnalyticsPlugin
 *
 * Google Analytics plugin class
 *
 * $Id$
 */

import('classes.plugins.GenericPlugin');

class GoogleAnalyticsPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;
		$this->addLocaleData();
		if ($success) {
			// Insert Google Analytics page tag to common footer  
			HookRegistry::register('Templates::Common::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert Google Analytics page tag to paper footer
			HookRegistry::register('Templates::Paper::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert Google Analytics page tag to paper interstitial footer
			HookRegistry::register('Templates::Paper::Interstitial::PageFooter', array($this, 'insertFooter'));

			// Insert Google Analytics page tag to paper pdf interstitial footer
			HookRegistry::register('Templates::Paper::PdfInterstitial::PageFooter', array($this, 'insertFooter'));

			// Insert Google Analytics page tag to reading tools footer
			HookRegistry::register('Templates::Rt::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert Google Analytics page tag to help footer
			HookRegistry::register('Templates::Help::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'GoogleAnalyticsPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.googleAnalytics.displayName');
	}

	function getDescription() {
		return __('plugins.generic.googleAnalytics.description');
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, null,  'user'),
				'navigation.user'
			),
			array(
				Request::url(null, null,  'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
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
				__('plugins.generic.googleAnalytics.manager.settings')
			);
		} else {
			$verbs[] = array(
				'enable',
				__('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$conference =& Request::getConference();
		if (!$conference) return false;
		return $this->getSetting($conference->getId(), 0, 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$conference =& Request::getConference();
		if ($conference) {
			$this->updateSetting($conference->getId(), 0, 'enabled', $enabled ? true : false);
			return true;
		}
		return false;
	}

	/**
	 * Insert Google Analytics page tag to footer
	 */  
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr =& TemplateManager::getManager();
			$currentConference = $templateMgr->get_template_vars('currentConference');

			if (!empty($currentConference)) {
				$conference =& Request::getConference();
				$conferenceId = $conference->getId();
				$googleAnalyticsSiteId = $this->getSetting($conferenceId, 0, 'googleAnalyticsSiteId');

				if (!empty($googleAnalyticsSiteId)) {
					$templateMgr->assign('googleAnalyticsSiteId', $googleAnalyticsSiteId);
					$trackingCode = $this->getSetting($conferenceId, 0, 'trackingCode');
					if ($trackingCode == "ga") {
						$output .= $templateMgr->fetch($this->getTemplatePath() . 'pageTagGa.tpl'); 
					} else {
						$output .= $templateMgr->fetch($this->getTemplatePath() . 'pageTagUrchin.tpl'); 
					}
				}
			}
		}
		return false;
	}

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$conference =& Request::getConference();
		$returner = true;

		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				$returner = false;
				$message = __('plugins.generic.googleAnalytics.enabled');
				break;
			case 'disable':
				$this->setEnabled(false);
				$returner = false;
				$message = __('plugins.generic.sgoogleAnalyticsehl.disabled'); // Typo is intentional to match locale files (#5350)
				break;
			case 'settings':
				if ($this->getEnabled()) {
					$this->import('GoogleAnalyticsSettingsForm');
					$form = new GoogleAnalyticsSettingsForm($this, $conference->getId());
					if (Request::getUserVar('save')) {
						$form->readInputData();
						if ($form->validate()) {
							$form->execute();
							Request::redirect(null, null, 'manager', 'plugin');
						} else {
							$this->setBreadCrumbs(true);
							$form->display();
						}
					} else {
						$this->setBreadCrumbs(true);
						$form->initData();
						$form->display();
					}
				} else {
					Request::redirect(null, null, 'manager');
				}
				break;
			default:
				Request::redirect(null, null, 'manager');
		}
		return $returner;
	}
}
?>
