<?php

/**
 * ImportExportHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for import/export functions. 
 *
 * $Id$
 */

define('IMPORTEXPORT_PLUGIN_CATEGORY', 'importexport');

class ImportExportHandler extends DirectorHandler {
	function importexport($args) {
		parent::validate();
		parent::setupTemplate(true);

		PluginRegistry::loadCategory(IMPORTEXPORT_PLUGIN_CATEGORY);
		$templateMgr = &TemplateManager::getManager();

		if (array_shift($args) === 'plugin') {
			$pluginName = array_shift($args);
			$plugin = &PluginRegistry::getPlugin(IMPORTEXPORT_PLUGIN_CATEGORY, $pluginName); 
			if ($plugin) return $plugin->display($args);
		}
		$templateMgr->assign_by_ref('plugins', PluginRegistry::getPlugins(IMPORTEXPORT_PLUGIN_CATEGORY));
		$templateMgr->assign('helpTopicId', 'conference.managementPages.importExport');
		$templateMgr->display('director/importexport/plugins.tpl');
	}
}
?>
