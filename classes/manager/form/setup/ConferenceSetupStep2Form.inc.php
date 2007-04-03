<?php

/**
 * ConferenceSetupStep2Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 2 of conference setup.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");
import('schedConf.SchedConf');

class ConferenceSetupStep2Form extends ConferenceSetupForm {
	
	function ConferenceSetupStep2Form() {
		parent::ConferenceSetupForm(
			2,
			array(
				'additionalHomeContent' => 'string',
				'readerInformation' => 'string',
				'presenterInformation' => 'string',
				'enableAnnouncements' => 'bool',
				'enableAnnouncementsHomepage' => 'bool',
				'numAnnouncementsHomepage' => 'int',
				'paperAccess' => 'int',
				'announcementsIntroduction' => 'string'
			)
		);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference = &Request::getConference();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'homepageImage' => $conference->getSetting('homepageImage')
		));
		parent::display();	   
	}
}

?>
