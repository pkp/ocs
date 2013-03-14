<?php

/**
 * @file AuthorSubmitStep2Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitStep2Form
 * @ingroup author_form_submit
 *
 * @brief Form for Step 2 of author paper submission.
 */


import('classes.author.form.submit.AuthorSubmitForm');

class AuthorSubmitStep2Form extends AuthorSubmitForm {
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep2Form($paper) {
		parent::AuthorSubmitForm($paper, 2);

		// Validation checks for this form
	}

	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
		if (isset($this->paper)) {
			$paper =& $this->paper;
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
		$templateMgr =& TemplateManager::getManager();

		// Get supplementary files for this paper
		$paperFileDao = DAORegistry::getDAO('PaperFileDAO');
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
		import('classes.file.PaperFileManager');

		$paperFileManager = new PaperFileManager($this->paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');

		if ($paperFileManager->uploadError($fileName)) return false;

		if ($paperFileManager->uploadedFileExists($fileName)) {
			// upload new submission file, overwriting previous if necessary
			$submissionFileId = $paperFileManager->uploadSubmissionFile($fileName, $this->paper->getSubmissionFileId(), true);
		}

		if (isset($submissionFileId)) {
			$this->paper->setSubmissionFileId($submissionFileId);
			$paperDao->updatePaper($this->paper);
			return true;
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
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$paper =& $this->paper;

		if ($paper->getSubmissionProgress() <= $this->step) {
			$schedConf =& Request::getSchedConf();

			$paper->stampStatusModified();
			if ($paper->getReviewMode() == REVIEW_MODE_BOTH_SEQUENTIAL) {
				$nextStep = $schedConf->getSetting('acceptSupplementaryReviewMaterials') ? 4:5;
				$paper->setSubmissionProgress($nextStep);
			} else {
				$paper->setSubmissionProgress($this->step + 1);
			}

			$paperDao->updatePaper($paper);
		}

		return $this->paperId;
	}
}

?>
