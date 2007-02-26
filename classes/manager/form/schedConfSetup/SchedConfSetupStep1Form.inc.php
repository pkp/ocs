<?php

/**
 * SchedConfSetupStep1Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.schedConfSetup
 *
 * Form for Step 1 of scheduled conference setup.
 *
 * $Id$
 */

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep1Form extends SchedConfSetupForm {
	
	function SchedConfSetupStep1Form() {
		parent::SchedConfSetupForm(
			1,
			array(
				'schedConfDescription' => 'string',
				'schedConfOverview' => 'string',
				'location' => 'string',
				'contactName' => 'string',
				'contactTitle' => 'string',
				'contactAffiliation' => 'string',
				'contactEmail' => 'string',
				'contactPhone' => 'string',
				'contactFax' => 'string',
				'contactMailingAddress' => 'string',
				'supportName' => 'string',
				'supportEmail' => 'string',
				'supportPhone' => 'string',
				'emailSignature' => 'string',
				'envelopeSender' => 'string',
				'sponsorNote' => 'string',
				'sponsors' => 'object',
				'contributorNote' => 'string',
				'contributors' => 'object'
			)
		);

		$this->addCheck(new FormValidator($this, 'contactName', 'required', 'manager.schedConfSetup.details.contactNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'contactEmail', 'required', 'manager.schedConfSetup.details.contactEmailRequired'));
		$this->addCheck(new FormValidator($this, 'supportName', 'required', 'manager.schedConfSetup.details.supportNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'supportEmail', 'required', 'manager.schedConfSetup.details.supportEmailRequired'));
	}

	function display() {
		$templateMgr = &TemplateManager::getManager();
		if (Config::getVar('email', 'allow_envelope_sender'))
			$templateMgr->assign('envelopeSenderEnabled', true);
		parent::display();
	}
}

?>
