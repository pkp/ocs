<?php

/**
 * EventSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form.eventSetup
 *
 * Form for Step 3 of event setup.
 *
 * $Id$
 */

import("director.form.eventSetup.EventSetupForm");

class EventSetupStep3Form extends EventSetupForm {
	
	function EventSetupStep3Form() {
		parent::EventSetupForm(
			3,
			array(
				'numWeeksPerReview' => 'int',
				'remindForInvite' => 'int',
				'remindForSubmit' => 'int',
				'rateReviewerOnQuality' => 'int',
				'restrictReviewerFileAccess' => 'int',
				'reviewerAccessKeysEnabled' => 'int',
				'numDaysBeforeInviteReminder' => 'int',
				'numDaysBeforeSubmitReminder' => 'int'
			)
		);

		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));
	}
	
	function readInputData() {
		parent::readInputData();
	}

	/**
	 * Display the form
	 */
	function display() {
		import('mail.MailTemplate');
		$mail = &new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('submissionAckEnabled', true);
		}

		parent::display();
	}
}

?>
