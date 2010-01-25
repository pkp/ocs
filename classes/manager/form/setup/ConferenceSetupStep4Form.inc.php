<?php

/**
 * @file ConferenceSetupStep4Form.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSetupStep4Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 4 of conference setup.
 */

//$Id$

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep4Form extends ConferenceSetupForm {
	/**
	 * Constructor.
	 */
	function ConferenceSetupStep4Form() {
		parent::ConferenceSetupForm(4, array(
			'conferenceTheme' => 'string'
		));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference =& Request::getConference();

		$allThemes =& PluginRegistry::loadCategory('themes', true);
		$conferenceThemes = array();
		foreach ($allThemes as $key => $junk) {
			$plugin =& $allThemes[$key]; // by ref
			$conferenceThemes[basename($plugin->getPluginPath())] =& $plugin;
			unset($plugin);
		}

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array(
			'conferenceStyleSheet' => $conference->getSetting('conferenceStyleSheet'),
			'conferenceThemes' => $conferenceThemes
		));

		// Make lists of the sidebar blocks available.
		$templateMgr->initialize();
		$leftBlockPlugins = $disabledBlockPlugins = $rightBlockPlugins = array();
		$plugins =& PluginRegistry::getPlugins('blocks');
		foreach ($plugins as $key => $junk) {
			if (!$plugins[$key]->getEnabled() || $plugins[$key]->getBlockContext() == '') {
				if (count(array_intersect($plugins[$key]->getSupportedContexts(), array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR))) > 0) $disabledBlockPlugins[] =& $plugins[$key];
			} else switch ($plugins[$key]->getBlockContext()) {
				case BLOCK_CONTEXT_LEFT_SIDEBAR:
					$leftBlockPlugins[] =& $plugins[$key];
					break;
				case BLOCK_CONTEXT_RIGHT_SIDEBAR:
					$rightBlockPlugins[] =& $plugins[$key];
					break;
			}
		}
		$templateMgr->assign(array(
			'disabledBlockPlugins' => &$disabledBlockPlugins,
			'leftBlockPlugins' => &$leftBlockPlugins,
			'rightBlockPlugins' => &$rightBlockPlugins
		));

		parent::display();
	}

	/**
	 * Uploads conference custom stylesheet.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadStyleSheet($settingName) {
		$conference =& Request::getConference();
		$settingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');

		import('file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->uploadError($settingName)) return false;
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			if ($type != 'text/plain' && $type != 'text/css') {
				return false;
			}
			$uploadName = $settingName . '.css';
			if($fileManager->uploadConferenceFile($conference->getId(), $settingName, $uploadName)) {
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => date("Y-m-d g:i:s")
				);

				$settingsDao->updateSetting($conference->getId(), $settingName, $value, 'object');
				return true;
			}
		}
		return false;
	}

	/**
	 * Save the page of settings.
	 */
	function execute() {
		// Save the block plugin layout settings.
		$blockVars = array('blockSelectLeft', 'blockUnselected', 'blockSelectRight');
		foreach ($blockVars as $varName) {
			$$varName = split(' ', Request::getUserVar($varName));
		}

		$plugins =& PluginRegistry::loadCategory('blocks');
		foreach ($plugins as $key => $junk) {
			$plugin =& $plugins[$key]; // Ref hack
			$plugin->setEnabled(!in_array($plugin->getName(), $blockUnselected));
			if (in_array($plugin->getName(), $blockSelectLeft)) {
				$plugin->setBlockContext(BLOCK_CONTEXT_LEFT_SIDEBAR);
				$plugin->setSeq(array_search($key, $blockSelectLeft));
			}
			else if (in_array($plugin->getName(), $blockSelectRight)) {
				$plugin->setBlockContext(BLOCK_CONTEXT_RIGHT_SIDEBAR);
				$plugin->setSeq(array_search($key, $blockSelectRight));
			}
			unset($plugin);
		}

		return parent::execute();
	}
}

?>
