<?php

/**
 * @file SiteSettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteSettingsForm
 * @ingroup admin_form
 * @see PKPSiteSettingsForm
 *
 * @brief Form to edit site settings.
 */



import('lib.pkp.classes.admin.form.PKPSiteSettingsForm');

class SiteSettingsForm extends PKPSiteSettingsForm {

	/**
	 * Constructor.
	 */
	function SiteSettingsForm() {
		parent::PKPSiteSettingsForm();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
		$conferences =& $conferenceDao->getNames();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('redirectOptions', $conferences);

		$allThemes =& PluginRegistry::loadCategory('themes');
		$themes = array();
		foreach ($allThemes as $key => $junk) {
			$plugin =& $allThemes[$key]; // by ref
			$themes[basename($plugin->getPluginPath())] =& $plugin;
			unset($plugin);
		}
		$templateMgr->assign('themes', $themes);

		return parent::display();
	}
}

?>
