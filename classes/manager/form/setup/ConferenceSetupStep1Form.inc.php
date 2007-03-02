<?php

/**
 * ConferenceSetupStep1Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 1 of conference setup.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep1Form extends ConferenceSetupForm {
	
	function ConferenceSetupStep1Form() {
		parent::ConferenceSetupForm(
			1,
			array(
				'conferenceDescription' => 'string',
				'contactName' => 'string',
				'contactTitle' => 'string',
				'contactAffiliation' => 'string',
				'contactEmail' => 'string',
				'contactPhone' => 'string',
				'contactFax' => 'string',
				'contactMailingAddress' => 'string',
				'archiveAccessPolicy' => 'string',
				'copyrightNotice' => 'string',
				'copyrightNoticeAgree' => 'bool',
				'postCreativeCommons' => 'bool',
				'privacyStatement' => 'string',
				'customAboutItems' => 'object'
			)
		);
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'contactName', 'required', 'manager.setup.form.contactNameRequired'));
		$this->addCheck(new FormValidator($this, 'contactEmail', 'required', 'manager.setup.form.contactEmailRequired'));
	}

	function initData() {
		parent::initData();

		$conference = Request::getConference();
		$this->_data['conferenceTitle'] = $conference->getTitle();
	}

	function readInputData() {
		parent::readInputData();
		$this->_data['conferenceTitle'] = Request::getUserVar('conferenceTitle');
	}
}

?>
