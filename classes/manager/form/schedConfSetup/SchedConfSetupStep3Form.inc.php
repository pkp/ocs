<?php

/**
 * @file SchedConfSetupStep3Form.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSetupStep3Form
 * @ingroup manager_form_schedConfSetup
 *
 * @brief Form for Step 3 of scheduled conference setup.
 */

define('REVIEW_YEAR_OFFSET_PAST',		'-10');
define('REVIEW_YEAR_OFFSET_FUTURE',		'+10');

import('classes.manager.form.schedConfSetup.SchedConfSetupForm');

class SchedConfSetupStep3Form extends SchedConfSetupForm {

	function SchedConfSetupStep3Form() {
		parent::SchedConfSetupForm(
			3,
			array(
				'reviewPolicy' => 'string',
				'reviewGuidelines' => 'string',
				'remindForInvite' => 'int',
				'remindForSubmit' => 'int',
				'rateReviewerOnQuality' => 'int',
				'restrictReviewerFileAccess' => 'int',
				'reviewerAccessKeysEnabled' => 'int',
				'reviewDeadlineType' => 'int',
				'numDaysBeforeInviteReminder' => 'int',
				'numDaysBeforeSubmitReminder' => 'int',
				'numWeeksPerReviewRelative'	=> 'int',
				'numWeeksPerReviewAbsolute'	=> 'date',
				'notifyAllAuthorsOnDecision' => 'bool'
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
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$settingNames = array_keys($this->settings);
		$this->readUserVars($settingNames);
		$this->readUserDateVars(array('numWeeksPerReviewAbsolute'));
	}

	/**
	 * Display the form
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();

		import('classes.mail.MailTemplate');
		$mail = new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr->assign('submissionAckEnabled', true);
		}

		if ($this->_data['reviewDeadlineType'] == REVIEW_DEADLINE_TYPE_ABSOLUTE) {
			$templateMgr->assign('absoluteReviewDate', $this->_data['numWeeksPerReviewAbsolute']);
		}

		if (Config::getVar('general', 'scheduled_tasks'))
			$templateMgr->assign('scheduledTasksEnabled', true);

		$templateMgr->assign('reviewYearOffsetPast', REVIEW_YEAR_OFFSET_PAST);
		$templateMgr->assign('reviewYearOffsetFuture', REVIEW_YEAR_OFFSET_FUTURE);

		parent::display();
	}
	

}

?>
