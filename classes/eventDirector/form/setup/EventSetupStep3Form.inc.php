<?php

/**
 * EventSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package eventDirector.form.setup
 *
 * Form for Step 3 of event setup.
 *
 * $Id$
 */

import("eventDirector.form.setup.EventSetupForm");

class EventSetupStep3Form extends EventSetupForm {
	
	function EventSetupStep3Form() {
		parent::EventSetupForm(
			3,
			array(
				'openRegReviewer' => 'bool',
				'openRegReviewerDate' => 'date',
				'closeRegReviewer' => 'bool',
				'closeRegReviewerDate' => 'date',
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

		$this->_data['openRegReviewerDate'] = Request::getUserDateVar('openRegReviewerDate');
		$this->_data['closeRegReviewerDate'] = Request::getUserDateVar('closeRegReviewerDate');
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
