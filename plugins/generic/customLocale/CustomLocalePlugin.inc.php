<?php

/**
 * @file CustomLocalePlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customLocale
 * @class CustomLocalePlugin
 *
 * This plugin enables customization of locale strings.
 *
 * $Id$
 */

define('CUSTOM_LOCALE_DIR', 'customLocale');
import('classes.plugins.GenericPlugin');

class CustomLocalePlugin extends GenericPlugin {

	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();

			if ($this->getEnabled()) {
				// Add custom locale data for already registered locale files.
				$locale = AppLocale::getLocale();
				$localeFiles = AppLocale::getLocaleFiles($locale);
				$conference = Request::getConference();
				$conferenceId = $conference->getId();
				$publicFilesDir = Config::getVar('files', 'public_files_dir');
				$customLocalePathBase = $publicFilesDir . DIRECTORY_SEPARATOR . 'conferences' . DIRECTORY_SEPARATOR . $conferenceId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR;

				import('file.FileManager');
				foreach ($localeFiles as $localeFile) {
					$customLocalePath = $customLocalePathBase . $localeFile->getFilename();
					if (FileManager::fileExists($customLocalePath)) {
						AppLocale::registerLocaleFile($locale, $customLocalePath, true);
					}
				}

				// Add custom locale data for all locale files registered after this plugin
				HookRegistry::register('PKPLocale::registerLocaleFile', array(&$this, 'addCustomLocale'));
			}

			return true;
		}
		return false;
	}

	function addCustomLocale($hookName, $args) {
		$locale =& $args[0];		
		$localeFilename =& $args[1];

		$conference = Request::getConference();
		$conferenceId = $conference->getId();
		$publicFilesDir = Config::getVar('files', 'public_files_dir');
		$customLocalePath = $publicFilesDir . DIRECTORY_SEPARATOR . 'conferences' . DIRECTORY_SEPARATOR . $conferenceId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $localeFilename;

		import('file.FileManager');
		if (FileManager::fileExists($customLocalePath)) {
			AppLocale::registerLocaleFile($locale, $customLocalePath, true);
		}

		return true;

	}

	function getName() {
		return 'CustomLocalePlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.customLocale.name');
	}

	function getDescription() {
		return __('plugins.generic.customLocale.description');
	}

	function getEnabled() {
		$conference =& Request::getConference();
		if (!$conference) return false;
		return $this->getSetting($conference->getId(), 0, 'enabled');
	}

	function setEnabled($enabled) {
		$conference =& Request::getConference();
		if ($conference) {
			$this->updateSetting($conference->getId(), 0, 'enabled', $enabled ? true : false);
			return true;
		}
		return false;
	}

	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['key'])) {
			$params['path'] = array_merge($params['path'], array($params['key']));
			unset($params['key']);
		}

		if (!empty($params['file'])) {
			$params['path'] = array_merge($params['path'], array($params['file']));
			unset($params['file']);
		}

		return $smarty->smartyUrl($params, $smarty);
	}

	function getManagementVerbs() {
		$isEnabled = $this->getEnabled();

		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			__($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);

		if ($isEnabled) $verbs[] = array(
			'index',
			__('plugins.generic.customLocale.customize')
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
		$this->import('CustomLocaleHandler');
		$returner = true;

		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				$returner = false;
				$message = __('plugins.generic.customLocale.enabled');
				break;
			case 'disable':
				$this->setEnabled(false);
				$returner = false;
				$message = __('plugins.generic.customLocale.disabled');
				break;
			case 'index':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->index();
				}
				break;
			case 'edit':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->edit($args);
				}
				break;
			case 'saveLocaleChanges':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->saveLocaleChanges($args);
				}
				break;
			case 'editLocaleFile':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->editLocaleFile($args);
				}
				break;
			case 'saveLocaleFile':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->saveLocaleFile($args);
				}
				break;
			default:
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->index();
				}
				
		}
		return $returner;
	}
}

?>
