<?php

/**
 * @file PresenterSubmitStep3Form.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PresenterSubmitStep3Form
 * @ingroup presenter_form_submit
 *
 * @brief Form for Step 3 of presenter paper submission.
 */

//$Id$

import("presenter.form.submit.PresenterSubmitForm");

class PresenterSubmitStep3Form extends PresenterSubmitForm {
	/**
	 * Constructor.
	 */
	function PresenterSubmitStep3Form($paper) {
		parent::PresenterSubmitForm($paper, 3);

		// Validation checks for this form
	}

	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
		if (isset($this->paper)) {
			$paper = &$this->paper;
			$this->_data = array(
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
			)
		);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();

		// Get supplementary files for this paper
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		if ($this->paper->getSubmissionFileId() != null) {
			$templateMgr->assign_by_ref('submissionFile', $paperFileDao->getPaperFile($this->paper->getSubmissionFileId()));
		}
		parent::display();
	}

	/**
	 * Upload the submission file.
	 * @param $fileName string
	 * @return boolean
	 */
	function uploadSubmissionFile($fileName) {
		import("file.PaperFileManager");

		$paperFileManager = &new PaperFileManager($this->paperId);
		$paperDao = &DAORegistry::getDAO('PaperDAO');

		if ($paperFileManager->uploadedFileExists($fileName)) {
			// upload new submission file, overwriting previous if necessary
			$submissionFileId = $paperFileManager->uploadSubmissionFile($fileName, $this->paper->getSubmissionFileId(), true);
		}

		if (isset($submissionFileId)) {
			$this->paper->setSubmissionFileId($submissionFileId);
			return $paperDao->updatePaper($this->paper);

		} else {
			return false;
		}
	}

	/**
	 * Save changes to paper.
	 * @return int the paper ID
	 */
	function execute() {
		// Update paper
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paper = &$this->paper;

		if ($paper->getSubmissionProgress() <= $this->step) {
			$schedConf =& Request::getSchedConf();

			$paper->stampStatusModified();
			if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) $paper->setSubmissionProgress($this->step + 2); // Skip supp files
			else $paper->setSubmissionProgress($this->step + 1);

			$paperDao->updatePaper($paper);
		}

		return $this->paperId;
	}
}

?>
