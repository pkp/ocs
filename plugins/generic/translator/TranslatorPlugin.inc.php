<?php

/**
 * @file TranslatorPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TranslatorPlugin
 * @ingroup plugins_generic_translator
 *
 * @brief This plugin helps with translation maintenance.
 */

//$Id$

import('classes.plugins.GenericPlugin');

class TranslatorPlugin extends GenericPlugin {
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->getSetting(0, 0, 'enabled')) {
				$this->addHelpData();
				HookRegistry::register ('LoadHandler', array(&$this, 'handleRequest'));
			}
			return true;
		}
		return false;
	}

	function handleRequest($hookName, $args) {
		$page =& $args[0];
		$op =& $args[1];
		$sourceFile =& $args[2];

		if ($page === 'translate') {
			$this->import('TranslatorHandler');
			Registry::set('plugin', $this);
			define('HANDLER_CLASS', 'TranslatorHandler');
			return true;
		}

		return false;
	}

	function getName() {
		return 'TranslatorPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.translator.name');
	}

	function getDescription() {
		return __('plugins.generic.translator.description');
	}

	function getManagementVerbs() {
		$isEnabled = $this->getSetting(0, 0, 'enabled');

		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			__($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);

		if ($isEnabled) $verbs[] = array(
			'translate',
			__('plugins.generic.translator.translate')
		);

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
		if (!Validation::isSiteAdmin()) return false;

		switch ($verb) {
			case 'enable':
				$this->updateSetting(0, 0, 'enabled', true);
				$message = __('plugins.generic.translator.enabled');
				break;
			case 'disable':
				$this->updateSetting(0, 0, 'enabled', false);
				$message = __('plugins.generic.translator.disabled');
				break;
			case 'translate':
				Request::redirect('index', 'index', 'translate');
				break;
		}
		return false;
	}

	function isSitePlugin() {
		return true;
	}
}

?>
