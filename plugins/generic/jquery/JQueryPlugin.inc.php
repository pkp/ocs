<?php

/**
 * @file plugins/generic/jquery/JQueryPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JQueryPlugin
 * @ingroup plugins_generic_jquery
 *
 * @brief Plugin to allow jQuery scripts to be added to OCS
 */

// $Id$


import('classes.plugins.GenericPlugin');

define('JQUERY_INSTALL_PATH', 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'jquery');
define('JQUERY_JS_PATH', JQUERY_INSTALL_PATH . DIRECTORY_SEPARATOR . 'jquery.min.js');

class JQueryPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * runs under both Conference and Site contexts.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->isJQueryInstalled()) {
				HookRegistry::register('TemplateManager::display', array(&$this, 'displayCallback'));
				$user =& Request::getUser();
				if ($user) HookRegistry::register('Templates::Common::Footer::PageFooter', array(&$this, 'footerCallback'));
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->addStyleSheet(Request::getBaseUrl() . '/lib/pkp/styles/jqueryUi.css');
				$templateMgr->addStyleSheet(Request::getBaseUrl() . '/lib/pkp/styles/jquery.pnotify.default.css');
				$templateMgr->addStyleSheet(Request::getBaseUrl() . '/lib/pkp/styles/themes/default/pnotify.css');
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the URL for the jQuery script
	 * @return string
	 */
	function getScriptPath() {
		return Request::getBaseUrl() . DIRECTORY_SEPARATOR . JQUERY_JS_PATH;
	}

	/**
	 * Given a $page and $op, return a list of scripts that should be loaded
	 * @param $page string The requested page
	 * @param $op string The requested operation
	 * @return array
	 */
	function getEnabledScripts($page, $op) {
		$scripts = array();
		switch ("$page/$op") {
			case 'admin/conferences':
			case 'manager/schedConfs':
			case 'manager/groupMembership':
			case 'manager/groups':
			case 'manager/reviewFormElements':
			case 'manager/reviewForms':
			case 'manager/sections':
			case 'manager/subscriptionTypes':
			case 'rtadmin/contexts':
			case 'rtadmin/searches':
			case 'registrationManager/registrationTypes':
				$scripts[] = 'plugins/generic/jquery/scripts/jquery.tablednd_0_5.js';
				$scripts[] = 'plugins/generic/jquery/scripts/tablednd.js';
				break;
		}
		$user =& Request::getUser();
		if ($user) $scripts[] = 'lib/pkp/js/jquery.pnotify.js';
		return $scripts;
	}

	/**
	 * Hook callback function for TemplateManager::display
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function displayCallback($hookName, $args) {
		$page = Request::getRequestedPage();
		$op = Request::getRequestedOp();
		$scripts = JQueryPlugin::getEnabledScripts($page, $op);
		if(empty($scripts)) return null;

		$templateManager =& $args[0];
		$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');
		$baseUrl = $templateManager->get_template_vars('baseUrl');

		$jQueryScript = JQueryPlugin::addScripts($baseUrl, $scripts);

		$templateManager->assign('additionalHeadData', $additionalHeadData."\n".$jQueryScript);
	}

	function jsEscape($string) {
		return strtr($string, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
	}

	/**
	 * Footer callback to load and display notifications
	 */
	function footerCallback($hookName, $args) {
		$output =& $args[2];
		$notificationDao =& DAORegistry::getDAO('NotificationDAO');
		$user =& Request::getUser();
		$notificationsMarkup = '';

		$notifications =& $notificationDao->getByUserId($user->getId(), NOTIFICATION_LEVEL_TRIVIAL);
		while ($notification =& $notifications->next()) {
			$notificationTitle = $notification->getTitle();
			if ($notification->getIsLocalized() && !empty($notificationTitle)) $notificationTitle = __($notificationTitle);
			if (empty($notificationTitle)) $notificationTitle = __('notification.notification');
			$notificationsMarkup .= '$.pnotify({pnotify_title: \'' . $this->jsEscape($notificationTitle) . '\', pnotify_text: \'';
			if ($notification->getIsLocalized()) $notificationsMarkup .= $this->jsEscape(__($notification->getContents(), array('param' => $notification->getParam())));
			else $notificationsMarkup .= $this->jsEscape($notification->getContents());
			$notificationsMarkup .= '\', pnotify_addclass: \'' . $this->jsEscape($notification->getStyleClass());
			$notificationsMarkup .= '\', pnotify_notice_icon: \'notifyIcon ' . $this->jsEscape($notification->getIconClass());
			$notificationsMarkup .= '\'});';
			$notificationDao->deleteNotificationById($notification->getId());
			unset($notification);
		}
		if (!empty($notificationsMarkup)) $notificationsMarkup = "<script type=\"text/javascript\">$notificationsMarkup</script>\n";

		$output .= $notificationsMarkup;
		return false;
	}

	/**
	 * Add scripts contained in scripts/ subdirectory to a string to be returned to callback func.
	 * @param baseUrl string
	 * @param scripts array All enabled scripts for this page
	 * @return string
	 */
	function addScripts($baseUrl, $scripts) {
		$scriptOpen = '	<script language="javascript" type="text/javascript" src="';
		$scriptClose = '"></script>';
		$returner = '';

		foreach ($scripts as $script) {
			if(file_exists(Core::getBaseDir() . DIRECTORY_SEPARATOR . $script)) {
				$returner .= $scriptOpen . $baseUrl . '/' . $script . $scriptClose . "\n";
			}
		}
		return $returner;
	}

	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'JQueryPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.jquery.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		if ($this->isJQueryInstalled()) return __('plugins.generic.jquery.description');
		return __('plugins.generic.jquery.descriptionDisabled', array('jQueryPath' => JQUERY_INSTALL_PATH));
	}

	/**
	 * Check whether or not the JQuery library is installed
	 * @return boolean
	 */
	function isJQueryInstalled() {
		// We may not register jQuery when the application is not yet installed
		// as access to the template manager (see register method) requires db access.
		return Config::getVar('general', 'installed') && file_exists(JQUERY_JS_PATH);
	}
}

?>
