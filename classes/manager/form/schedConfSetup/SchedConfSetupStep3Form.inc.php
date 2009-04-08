<?php

/**
 * @file SchedConfSetupStep3Form.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSetupStep3Form
 * @ingroup manager_form_schedConfSetup
 *
 * @brief Form for Step 3 of scheduled conference setup.
 */

//$Id$

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep3Form extends SchedConfSetupForm {

	function SchedConfSetupStep3Form() {
		parent::SchedConfSetupForm(
			3,
			array(
				'reviewPolicy' => 'string',
				'reviewGuidelines' => 'string',
				'numWeeksPerReview' => 'int',
				'remindForInvite' => 'int',
				'remindForSubmit' => 'int',
				'rateReviewerOnQuality' => 'int',
				'restrictReviewerFileAccess' => 'int',
				'reviewerAccessKeysEnabled' => 'int',
				'numDaysBeforeInviteReminder' => 'int',
				'numDaysBeforeSubmitReminder' => 'int',
				'notifyAllPresentersOnDecision' => 'bool'
			)
		);

		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('reviewPolicy', 'reviewGuidelines');
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

		if (Config::getVar('general', 'scheduled_tasks'))
			$templateMgr->assign('scheduledTasksEnabled', true);

		parent::display();
	}
}

?>
