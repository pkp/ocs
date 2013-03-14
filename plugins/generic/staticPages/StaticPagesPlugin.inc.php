<?php

/**
 * @file StaticPagesPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesPlugin
 *
 * StaticPagesPlugin class
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class StaticPagesPlugin extends GenericPlugin {
	function getDisplayName() {
		return __('plugins.generic.staticPages.displayName');
	}

	function getDescription() {
		$description = __('plugins.generic.staticPages.description');
		if ( !$this->isTinyMCEInstalled() )
			$description .= "<br />".__('plugins.generic.staticPages.requirement.tinymce');
		return $description;
	}

	function isTinyMCEInstalled() {
		// If the thesis plugin isn't enabled, don't do anything.
		$application =& PKPApplication::getApplication();
		$products =& $application->getEnabledProducts('plugins.generic');
		return (isset($products['tinymce']));
	}

	/**
	 * Register the plugin, attaching to hooks as necessary.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				$this->import('StaticPagesDAO');
				$staticPagesDao = new StaticPagesDAO($this->getName());
				$returner =& DAORegistry::registerDAO('StaticPagesDAO', $staticPagesDao);

				HookRegistry::register('LoadHandler', array(&$this, 'callbackHandleContent'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Declare the handler function to process the actual page PATH
	 */
	function callbackHandleContent($hookName, $args) {
		$templateMgr =& TemplateManager::getManager();

		$page =& $args[0];
		$op =& $args[1];

		if ($page == 'pages' && in_array($op, array('index', 'view'))) {
			define('STATIC_PAGES_PLUGIN_NAME', $this->getName()); // Kludge
			define('HANDLER_CLASS', 'StaticPagesHandler');
			$this->import('StaticPagesHandler');
			return true;
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled() && $this->isTinyMCEInstalled()) {
			$verbs[] = array('settings', __('plugins.generic.staticPages.editAddContent'));
		}
		return $verbs;
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args, &$message, &$messageparams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request =& $this->getRequest();
		$conference =& $request->getConference();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$templateMgr->assign('pagesPath', $request->url(null, null, 'pages', 'view', 'REPLACEME'));

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

		switch ($verb) {
			case 'settings':

				$this->import('StaticPagesSettingsForm');
				$form = new StaticPagesSettingsForm($this, $conference->getId());

				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$form->initData($request);
				$form->display();
				return true;
			case 'edit':
			case 'add':

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $conference->getId(), $staticPageId);

				if ($form->isLocaleResubmit()) {
					$form->readInputData();
					$form->addTinyMCE($request);
				} else {
					$form->initData();
				}

				$pageCrumbs[] = array(
					$request->url(null, null,  'manager', 'plugin', array('generic', $this->getName(), 'settings')),
					$this->getDisplayName(),
					true
				);
				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$form->display();
				return true;
			case 'save':

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $conference->getId(), $staticPageId);

				if ($request->getUserVar('edit')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->save();
						$templateMgr->assign(array(
							'currentUrl' => $request->url(null, null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'pageTitle' => 'plugins.generic.staticPages.displayName',
							'pageHierarchy' => $pageCrumbs,
							'message' => 'plugins.generic.staticPages.pageSaved',
							'backLink' => $request->url(null, null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'backLinkLabel' => 'common.continue'
						));
						$templateMgr->display('common/message.tpl');
						exit;
					} else {
						$form->addTinyMCE($request);
						$form->display();
						exit;
					}
				}
				$request->redirect(null, null, null, 'manager', 'plugins');
				return false;
			case 'delete':
				$staticPageId = isset($args[0])?(int) $args[0]:null;
				$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
				$staticPagesDao->deleteStaticPageById($staticPageId);

				$templateMgr->assign(array(
					'currentUrl' => $request->url(null, null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'pageTitle' => 'plugins.generic.staticPages.displayName',
					'message' => 'plugins.generic.staticPages.pageDeleted',
					'backLink' => $request->url(null, null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'backLinkLabel' => 'common.continue'
				));

				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$templateMgr->display('common/message.tpl');
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}
}

?>
