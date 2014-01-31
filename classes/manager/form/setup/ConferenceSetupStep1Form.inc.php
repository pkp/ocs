<?php

/**
 * @file ConferenceSetupStep1Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSetupStep1Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 1 of conference setup.
 */

//$Id$

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep1Form extends ConferenceSetupForm {
	function ConferenceSetupStep1Form() {
		parent::ConferenceSetupForm(
			1,
			array(
				'title' => 'string',
				'description' => 'string',
				'contactName' => 'string',
				'contactTitle' => 'string',
				'contactAffiliation' => 'string',
				'contactEmail' => 'string',
				'contactPhone' => 'string',
				'contactFax' => 'string',
				'contactMailingAddress' => 'string',
				'restrictPaperAccess' => 'bool',
				'enableComments' => 'bool',
				'commentsRequireRegistration' => 'bool',
				'commentsAllowAnonymous' => 'bool',
				'paperAccess' => 'int',
				'archiveAccessPolicy' => 'string',
				'copyrightNotice' => 'string',
				'copyrightNoticeAgree' => 'bool',
				'postCreativeCommons' => 'bool',
				'privacyStatement' => 'string',
				'customAboutItems' => 'object'
			)
		);

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'contactName', 'required', 'manager.schedConfSetup.details.contactNameRequired'));
		$this->addCheck(new FormValidator($this, 'contactEmail', 'required', 'manager.schedConfSetup.details.contactEmailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'description', 'archiveAccessPolicy', 'copyrightNotice', 'privacyStatement', 'customAboutItems', 'contactAffiliation');
	}
}

?>
