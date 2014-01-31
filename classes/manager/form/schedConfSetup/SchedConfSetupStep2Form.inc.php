<?php

/**
 * @file SchedConfSetupStep2Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
			'metaCitations' => 'bool',
			'enablePublicPaperId' => 'bool',
			'enablePublicSuppFileId' => 'bool'
		);

		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));

		parent::SchedConfSetupForm(2, $settings);
	}

	/**
	 * Initialize data from current settings.
	 */
	function initData() {
		parent::initData();

		$schedConf =& Request::getSchedConf();
		$paperTypeDao =& DAORegistry::getDAO('PaperTypeDAO');
		$paperTypeEntryIterator = $paperTypeDao->getPaperTypes($schedConf->getId());
		$paperTypes = array();
		$i=0;
		while ($paperTypeEntry =& $paperTypeEntryIterator->next()) {
			$paperTypes[$paperTypeEntry->getId()] = array(
				'name' => $paperTypeEntry->getName(null), // Localized
				'description' => $paperTypeEntry->getDescription(null), // Localized
				'abstractLength' => $paperTypeEntry->getAbstractLength(),
				'length' => $paperTypeEntry->getLength(),
				'seq' => $i++
			);
			unset($paperTypeEntry);
		}
		$this->_data['paperTypes'] = $paperTypes;
	}

	/**
	 * Read user input.
	 */
	function readInputData() {
		$settingNames = array_keys($this->settings);
		$settingNames[] = 'paperTypes';
		$this->readUserVars($settingNames);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('cfpMessage', 'authorGuidelines', 'submissionChecklist', 'metaDisciplineExamples', 'metaSubjectClassTitle', 'metaSubjectExamples', 'metaCoverageGeoExamples', 'metaCoverageChronExamples', 'metaCoverageResearchSampleExamples', 'metaTypeExamples', 'paperTypes');
	}

	function display() {
		$schedConf =& Request::getSchedConf();
		$templateMgr =& TemplateManager::getManager();

		import('mail.MailTemplate');
		$mail = new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr->assign('submissionAckEnabled', true);
		}

		parent::display();
	}

	/**
	 * Check to make sure form conditions are met
	 */
	function validate() {
		// Ensure all submission types have names in the primary locale
		// as well as numeric word limits (optional)
		$primaryLocale = AppLocale::getPrimaryLocale();
		if (isset($this->_data['paperTypes'])) {
			$paperTypes =& $this->_data['paperTypes'];
			if (!is_array($paperTypes)) return false;

			foreach ($paperTypes as $paperTypeId => $paperType) {
				if (!isset($paperType['name'][$primaryLocale]) || empty($paperType['name'][$primaryLocale])) {
					$fieldName = 'paperTypeName-' . $paperTypeId;
					$this->addError($fieldName, __('manager.schedConfSetup.submissions.typeOfSubmission.nameMissing', array('primaryLocale' => $primaryLocale)));
					$this->addErrorField($fieldName);
				}
				if (isset($paperType['abstractLength']) && !empty($paperType['abstractLength']) && (!is_numeric($paperType['abstractLength']) || $paperType['abstractLength'] <= 0)) {
					$fieldName = 'paperTypeAbstractLength-' . $paperTypeId;
					$this->addError($fieldName, __('manager.schedConfSetup.submissions.typeOfSubmission.abstractLengthInvalid'));
					$this->addErrorField($fieldName);
				}
			}
		}

		return parent::validate();
	}

	/**
	 * Save modified settings.
	 */
	function execute() {
		$schedConf =& Request::getSchedConf();
		$paperTypeIds = array();

		$paperTypeDao =& DAORegistry::getDAO('PaperTypeDAO');
		$paperTypeEntryDao =& DAORegistry::getDAO('PaperTypeEntryDAO');

		if (isset($this->_data['paperTypes'])) {
			$paperTypes =& $this->_data['paperTypes'];
			$paperTypesEntry =& $paperTypeDao->build($schedConf->getId());
			$i = 0;
			foreach ($paperTypes as $paperTypeId => $paperType) {
				if (is_numeric($paperTypeId)) {
					// Entry should already exist; update
					$paperTypeEntry = $paperTypeEntryDao->getById($paperTypeId, $paperTypesEntry->getId());
					if (!$paperTypeEntry) continue;
				} else {
					// Entry is new; create
					$paperTypeEntry = $paperTypeEntryDao->newDataObject();
					$paperTypeEntry->setControlledVocabId($paperTypesEntry->getId());
				}
				$paperTypeEntry->setName($paperType['name'], null); // Localized
				$paperTypeEntry->setDescription($paperType['description'], null); // Localized
				$paperTypeEntry->setAbstractLength($paperType['abstractLength']);
				$paperTypeEntry->setLength($paperType['length']);
				$paperTypeEntry->setSequence($i++);
				if (is_numeric($paperTypeId)) {
					$paperTypeEntryDao->updateObject($paperTypeEntry);
				} else {
					$paperTypeEntryDao->insertObject($paperTypeEntry);

				}
				$paperTypeIds[] = $paperTypeEntry->getId();
			}
		}

		// Find and handle deletions
		$paperTypeEntryIterator = $paperTypeDao->getPaperTypes($schedConf->getId());
		while ($paperTypeEntry =& $paperTypeEntryIterator->next()) {
			if (!in_array($paperTypeEntry->getId(), $paperTypeIds))
				$paperTypeEntryDao->deleteObject($paperTypeEntry);
			unset($paperTypeEntry);
		}

		return parent::execute();
	}
}

?>
