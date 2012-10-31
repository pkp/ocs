<?php

/**
 * @file PhpMyVisitesPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.phpMyVisites
 * @class PhpMyVisitesPlugin
 *
 * phpMyVisites plugin class
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class PhpMyVisitesPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;
		if ($success) {
			// Insert phpmv page tag to common footer
			HookRegistry::register('Templates::Common::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to paper footer
			HookRegistry::register('Templates::Paper::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to paper interstitial footer
			HookRegistry::register('Templates::Paper::Interstitial::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to paper pdf interstitial footer
			HookRegistry::register('Templates::Paper::PdfInterstitial::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to reading tools pages footer
			HookRegistry::register('Templates::Rt::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to help footer
			HookRegistry::register('Templates::Help::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.phpmv.displayName');
	}

	function getDescription() {
		return __('plugins.generic.phpmv.description');
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
		$request =& $this->getRequest();
		$pageCrumbs = array(
			array(
				$request->url(null, null, 'user'),
				'navigation.user'
			),
			array(
				$request->url(null, null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			$request->url(null, null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.phpmv.manager.settings'));
		}
		return $verbs;
	}

	/**
	 * Insert phpmv page tag to footer
	 */
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$templateMgr =& TemplateManager::getManager();
			$currentConference = $templateMgr->get_template_vars('currentConference');

			if (!empty($currentConference)) {
				$request =& $this->getRequest();
				$conference =& $request->getConference();
				$conferenceId = $conference->getId();
				$phpmvSiteId = $this->getSetting($conferenceId, 0, 'phpmvSiteId');
				$phpmvUrl = $this->getSetting($conferenceId, 0, 'phpmvUrl');

				if (!empty($phpmvSiteId) && !empty($phpmvUrl)) {
					$templateMgr->assign('phpmvSiteId', $phpmvSiteId);
					$templateMgr->assign('phpmvUrl', $phpmvUrl);
					$output .= $templateMgr->fetch($this->getTemplatePath() . 'pageTag.tpl');
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
	 * @param $messageParams array Parameters for the message key
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request =& $this->getRequest();

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$conference =& $request->getConference();

				$this->import('PhpMyVisitesSettingsForm');
				$form = new PhpMyVisitesSettingsForm($this, $conference->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, null, 'manager', 'plugin');
						return false;
					} else {
						$this->setBreadCrumbs(true);
						$form->display();
					}
				} else {
					$this->setBreadCrumbs(true);
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}
}
?>
