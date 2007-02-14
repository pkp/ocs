<?php

/**
 * SchedConfSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.schedConfSetup
 *
 * Form for Step 3 of scheduled conference setup.
 *
 * $Id$
 */

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep3Form extends SchedConfSetupForm {
	
	function SchedConfSetupStep3Form() {
		parent::SchedConfSetupForm(
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
