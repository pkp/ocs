<?php

/**
 * EventSetupStep2Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form.eventSetup
 *
 * Form for Step 2 of event setup.
 *
 * $Id$
 */

import("director.form.eventSetup.EventSetupForm");

class EventSetupStep2Form extends EventSetupForm {
	
	function EventSetupStep2Form() {
		$settings = array(
			'cfpMessage' => 'string',
			'openRegAuthor' => 'bool',
			'acceptPapers' => 'bool',
			'collectPapersWithAbstracts' => 'bool',
			'reviewPapers' => 'bool',
			'acceptSupplementaryReviewMaterials' => 'bool',
			'acceptSupplementaryPublishedMaterials' => 'bool',
			'copySubmissionAckPrimaryContact' => 'bool',
			'copySubmissionAckSpecified' => 'bool',
			'copySubmissionAckAddress' => 'string'
		);
		
		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));

		parent::EventSetupForm(2, $settings);
	}

	function readInputData() {
		parent::readInputData();

	}

	function display() {
		$event = &Request::getEvent();
		$templateMgr = &TemplateManager::getManager();
		
		import('mail.MailTemplate');
		$mail = &new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr->assign('submissionAckEnabled', true);
		}
		$mail = &new MailTemplate('SUBMISSION_DEADLINE_WARN');
		if ($mail->isEnabled()) {
			$templateMgr->assign('submissionDeadlineWarnEnabled', true);
		}

		parent::display();
	}
}

?>
