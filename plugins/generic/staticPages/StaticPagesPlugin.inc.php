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

import('classes.plugins.GenericPlugin');

class StaticPagesPlugin extends GenericPlugin {

	function getName() {
		return 'StaticPagesPlugin';
	}

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
		$tinyMCEPlugin =& PluginRegistry::getPlugin('generic', 'TinyMCEPlugin');

		if ( $tinyMCEPlugin )
			return $tinyMCEPlugin->getEnabled();

		return false;
	}

	/**
	 * Register the plugin, attaching to hooks as necessary.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->getEnabled()) {
				$this->import('StaticPagesDAO');
				if (checkPhpVersion('5.0.0')) { // WARNING: see http://pkp.sfu.ca/wiki/index.php/Information_for_Developers#Use_of_.24this_in_the_constructur
					$staticPagesDAO = new StaticPagesDAO();
				} else {
					$staticPagesDAO =& new StaticPagesDAO();
				}
				$returner =& DAORegistry::registerDAO('StaticPagesDAO', $staticPagesDAO);

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

		if ( $page == 'pages' ) {
			define('HANDLER_CLASS', 'StaticPagesHandler');
			$this->import('StaticPagesHandler');
			return true;
		}
		return false;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$conference =& Request::getConference();
		$conferenceId = $conference?$conference->getId():0;
		return $this->getSetting($conferenceId, 0, 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$conference =& Request::getConference();
		$conferenceId = $conference?$conference->getId():0;
		$this->updateSetting($conferenceId, 0, 'enabled', $enabled);

		return true;
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
			if ( $this->isTinyMCEInstalled() ) {
				$verbs[] = array(
					'settings',
					__('plugins.generic.staticPages.editAddContent')
				);
			}
		} else {
			$verbs[] = array(
				'enable',
				__('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$returner = true;

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$templateMgr->assign('pagesPath', Request::url(null, null, 'pages', 'view', 'REPLACEME'));

		$pageCrumbs = array(
			array(
				Request::url(null, null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, null, 'manager'),
				'user.role.manager'
			)
		);

		switch ($verb) {
			case 'settings':
				$conference =& Request::getConference();

				$this->import('StaticPagesSettingsForm');
				$form = new StaticPagesSettingsForm($this, $conference->getId());

				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$form->initData();
				$form->display();
				break;
			case 'edit':
			case 'add':
				$conference =& Request::getConference();

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $conference->getId(), $staticPageId);

				if ($form->isLocaleResubmit()) {
					$form->readInputData();
					$form->addTinyMCE();
				} else {
					$form->initData();
				}

				$pageCrumbs[] = array(
					Request::url(null, null,  'manager', 'plugin', array('generic', $this->getName(), 'settings')),
					$this->getDisplayName(),
					true
				);
				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$form->display();
				$returner = true;
				break;
			case 'save':
				$conference =& Request::getConference();

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $conference->getId(), $staticPageId);

				if (Request::getUserVar('edit')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->save();
						$templateMgr->assign(array(
							'currentUrl' => Request::url(null, null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'pageTitle' => 'plugins.generic.staticPages.displayName',
							'pageHierarchy' => $pageCrumbs,
							'message' => 'plugins.generic.staticPages.pageSaved',
							'backLink' => Request::url(null, null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'backLinkLabel' => 'common.continue'
						));
						$templateMgr->display('common/message.tpl');
						exit;
					} else {
						$form->addTinyMCE();
						$form->display();
						exit;
					}
				}
				Request::redirect(null, null, null, 'manager', 'plugins');
				$returner = true;
				break;
			case 'delete':
				$conference =& Request::getConference();
				$staticPageId = isset($args[0])?(int) $args[0]:null;
				$staticPagesDAO =& DAORegistry::getDAO('StaticPagesDAO');
				$staticPagesDAO->deleteStaticPageById($staticPageId);

				$templateMgr->assign(array(
					'currentUrl' => Request::url(null, null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'pageTitle' => 'plugins.generic.staticPages.displayName',
					'message' => 'plugins.generic.staticPages.pageDeleted',
					'backLink' => Request::url(null, null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'backLinkLabel' => 'common.continue'
				));

				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$templateMgr->display('common/message.tpl');
				$returner = true;
				break;
			case 'enable':
				$this->setEnabled(true);
				$returner = false;
				break;
			case 'disable':
				$this->setEnabled(false);
				$returner = false;
				break;
		}

		return $returner;
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}
}

?>
