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

// $Id$


import('admin.form.PKPSiteSettingsForm');

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
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conferences =& $conferenceDao->getConferenceTitles();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('redirectOptions', $conferences);
		return parent::display();
	}
}

?>
