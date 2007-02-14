<?php

/**
 * SchedConfSetupStep2Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.schedConfSetup
 *
 * Form for Step 2 of scheduled conference setup.
 *
 * $Id$
 */

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep2Form extends SchedConfSetupForm {
	
	function SchedConfSetupStep2Form() {
		$settings = array(
			'cfpMessage' => 'string',
			'openRegPresenter' => 'bool',
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

		parent::SchedConfSetupForm(2, $settings);
	}

	function readInputData() {
		parent::readInputData();

	}

	function display() {
		$schedConf = &Request::getSchedConf();
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
