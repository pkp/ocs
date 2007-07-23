<?php

/**
 * @file SchedConfSetupStep2Form.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.schedConfSetup
 * @class SchedConfSetupStep2Form
 *
 * Form for Step 2 of scheduled conference setup.
 *
 * $Id$
 */

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep2Form extends SchedConfSetupForm {
	
	function SchedConfSetupStep2Form() {
		$settings = array(
			'reviewMode' => 'int',
			'allowIndividualSubmissions' => 'bool',
			'allowPanelSubmissions' => 'bool',
			'acceptSupplementaryReviewMaterials' => 'bool',
			'copySubmissionAckPrimaryContact' => 'bool',
			'copySubmissionAckSpecified' => 'bool',
			'copySubmissionAckAddress' => 'string',
			'cfpMessage' => 'string',
			'presenterGuidelines' => 'string',
			'submissionChecklist' => 'object',
			'metaDiscipline' => 'bool',
			'metaDisciplineExamples' => 'string',
			'metaSubjectClass' => 'bool',
			'metaSubjectClassTitle' => 'string',
			'metaSubjectClassUrl' => 'string',
			'metaSubject' => 'bool',
			'metaSubjectExamples' => 'string',
			'metaCoverage' => 'bool',
			'metaCoverageGeoExamples' => 'string',
			'metaCoverageChronExamples' => 'string',
			'metaCoverageResearchSampleExamples' => 'string',
			'metaType' => 'bool',
			'metaTypeExamples' => 'string',
			'enablePublicPaperId' => 'bool',
			'enablePublicSuppFileId' => 'bool'
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
