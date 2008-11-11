<?php

/**
 * @file SchedConfSetupStep2Form.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSetupStep2Form
 * @ingroup manager_form_schedConfSetup
 *
 * @brief Form for Step 2 of scheduled conference setup.
 */

//$Id$

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep2Form extends SchedConfSetupForm {

	function SchedConfSetupStep2Form() {
		$settings = array(
			'reviewMode' => 'int',
			'previewAbstracts' => 'bool',
			'allowIndividualSubmissions' => 'bool',
			'allowPanelSubmissions' => 'bool',
			'acceptSupplementaryReviewMaterials' => 'bool',
			'copySubmissionAckPrimaryContact' => 'bool',
			'copySubmissionAckSpecified' => 'bool',
			'copySubmissionAckAddress' => 'string',
			'cfpMessage' => 'string',
			'authorGuidelines' => 'string',
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

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('cfpMessage', 'authorGuidelines', 'submissionChecklist', 'metaDisciplineExamples', 'metaSubjectClassTitle', 'metaSubjectExamples', 'metaCoverageGeoExamples', 'metaCoverageChronExamples', 'metaCoverageResearchSampleExamples', 'metaTypeExamples');
	}

	function display() {
		$schedConf = &Request::getSchedConf();
		$templateMgr = &TemplateManager::getManager();

		import('mail.MailTemplate');
		$mail = new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr->assign('submissionAckEnabled', true);
		}

		parent::display();
	}
}

?>
