<?php

/**
 * @file plugins/generic/customLocale/CustomLocaleHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleHandler
 * @ingroup plugins_generic_customLocale
 *
 * @brief This handles requests for the customLocale plugin.
 *
 */

// $Id$


require_once('CustomLocalePlugin.inc.php');
require_once('CustomLocaleAction.inc.php');
import('handler.Handler');

class CustomLocaleHandler extends Handler {
	/** plugin associated with this request **/
	var $plugin;
	
	/**
	 * Constructor
	 */
	function CustomLocaleHandler() {
		parent::Handler();
		
		$this->addCheck(new HandlerValidatorConference($this));		
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_CONFERENCE_MANAGER)));

		$plugin =& PluginRegistry::getPlugin('generic', 'CustomLocalePlugin');
		$this->plugin =& $plugin;				
	} 

	function index() {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate($plugin, false);

		$conference = Request::getConference();
		$rangeInfo = Handler::getRangeInfo('locales');

		$templateMgr =& TemplateManager::getManager();
		import('core.ArrayItemIterator');
		$templateMgr->assign('locales', new ArrayItemIterator($conference->getSupportedLocaleNames(), $rangeInfo->getPage(), $rangeInfo->getCount()));
		$templateMgr->assign('masterLocale', MASTER_LOCALE);

		$templateMgr->display($plugin->getTemplatePath() . 'index.tpl');
	}

	function edit($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate($plugin, true);

		$locale = array_shift($args);
		$file = array_shift($args);

		if (!Locale::isLocaleValid($locale)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'index');
			Request::redirect(null, null, null, null, $path);
		}
		$localeFiles = CustomLocaleAction::getLocaleFiles($locale);

		$templateMgr =& TemplateManager::getManager();

		$localeFilesRangeInfo = Handler::getRangeInfo('localeFiles');

		import('core.ArrayItemIterator');
		$templateMgr->assign('localeFiles', new ArrayItemIterator($localeFiles, $localeFilesRangeInfo->getPage(), $localeFilesRangeInfo->getCount()));

		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('masterLocale', MASTER_LOCALE);

		$templateMgr->display($plugin->getTemplatePath() . 'locale.tpl');
	}

	function editLocaleFile($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate($plugin, true);

		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'index');
			Request::redirect(null, null, null, null, $path);
		}

		$filename = urldecode(urldecode(array_shift($args)));
		if (!CustomLocaleAction::isLocaleFile($locale, $filename)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'edit', $locale);
			Request::redirect(null, null, null, null, $path);
		}

		$templateMgr =& TemplateManager::getManager();

		import('file.FileManager');
		import('i18n.EditableLocaleFile');
		$conference = Request::getConference();
		$conferenceId = $conference->getId();
		$publicFilesDir = Config::getVar('files', 'public_files_dir');
		$customLocaleDir = $publicFilesDir . DIRECTORY_SEPARATOR . 'conferences' . DIRECTORY_SEPARATOR . $conferenceId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR;
		$customLocalePath = $customLocaleDir . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $filename;
		if (FileManager::fileExists($customLocalePath)) {
			$localeContents = EditableLocaleFile::load($customLocalePath);
		} else {
			$localeContents = null;
		}

		$referenceLocaleContents = EditableLocaleFile::load($filename);
		$referenceLocaleContentsRangeInfo = Handler::getRangeInfo('referenceLocaleContents');

		// Handle a search, if one was executed.
		$searchKey = Request::getUserVar('searchKey');
		$found = false;
		$index = 0;
		$pageIndex = 0;
		if (!empty($searchKey)) foreach ($referenceLocaleContents as $key => $value) {
			if ($index % $referenceLocaleContentsRangeInfo->getCount() == 0) $pageIndex++;
			if ($key == $searchKey) {
				$found = true;
				break;
			}
			$index++;
		}

		if ($found) {
			$referenceLocaleContentsRangeInfo->setPage($pageIndex);
			$templateMgr->assign('searchKey', $searchKey);
		}

		$templateMgr->assign('filename', $filename);
		$templateMgr->assign('locale', $locale);
		import('core.ArrayItemIterator');
		$templateMgr->assign_by_ref('referenceLocaleContents', new ArrayItemIterator($referenceLocaleContents, $referenceLocaleContentsRangeInfo->getPage(), $referenceLocaleContentsRangeInfo->getCount()));
		$templateMgr->assign('localeContents', $localeContents);

		$templateMgr->display($plugin->getTemplatePath() . 'localeFile.tpl');
	}

	function saveLocaleFile($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate($plugin, true);

		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'index');
			Request::redirect(null, null, null, null, $path);
		}

		$filename = urldecode(urldecode(array_shift($args)));
		if (!CustomLocaleAction::isLocaleFile($locale, $filename)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'edit', $locale);
			Request::redirect(null, null, null, null, $path);
		}

		$conference =& Request::getConference();
		$conferenceId = $conference->getId();
		$changes = Request::getUserVar('changes');
		$customFilesDir = Config::getVar('files', 'public_files_dir') . DIRECTORY_SEPARATOR . 'conferences' . DIRECTORY_SEPARATOR . $conferenceId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR . DIRECTORY_SEPARATOR . $locale;
		$customFilePath = $customFilesDir . DIRECTORY_SEPARATOR . $filename;

		// Create empty custom locale file if it doesn't exist
		import('file.FileManager');
		import('i18n.EditableLocaleFile');
		if (!FileManager::fileExists($customFilePath)) {
			$numParentDirs = substr_count($customFilePath, DIRECTORY_SEPARATOR); 
			$parentDirs = '';
			for ($i=0; $i<$numParentDirs; $i++) {
				$parentDirs .= '..' . DIRECTORY_SEPARATOR;
			}

			$newFileContents = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$newFileContents .= '<!DOCTYPE locale SYSTEM "' . $parentDirs . 'locale' . DIRECTORY_SEPARATOR . 'locale.dtd' . '">' . "\n";
			$newFileContents .= '<locale name="' . $locale . '">' . "\n";
			$newFileContents .= '</locale>';
			FileManager::writeFile($customFilePath, $newFileContents);
		}

		$file = new EditableLocaleFile($locale, $customFilePath);

		while (!empty($changes)) {
			$key = array_shift($changes);
			$value = $this->correctCr(array_shift($changes));
			if (!empty($value)) {
				if (!$file->update($key, $value)) {
					$file->insert($key, $value);
				}
			} else {
				$file->delete($key);
			}
		}
		$file->write();

		Request::redirectUrl(Request::getUserVar('redirectUrl'));
	}

	function correctCr($value) {
		return str_replace("\r\n", "\n", $value);
	}

	function setupTemplate(&$plugin, $subclass = true) {
		parent::setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$plugin, 'smartyPluginUrl'));
		$pageHierarchy = array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'manager'), 'user.role.manager'));
		if ($subclass) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'index');
			$pageHierarchy[] = array(Request::url(null, null, null, null, $path), 'plugins.generic.customLocale.name');
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
		$templateMgr->assign('helpTopicId', 'plugins.generic.CustomLocalePlugin');
	}
}
?>
