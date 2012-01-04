<?php

/**
 * @file AuthorSubmitStep4Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitStep4Form
 * @ingroup author_form_submit
 *
 * @brief Form for Step 4 of author paper submission.
 */

//$Id$

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep4Form extends AuthorSubmitForm {
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep4Form($paper) {
		parent::AuthorSubmitForm($paper, 4);
		$schedConf =& Request::getSchedConf();
		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) {
			// If supplementary files are not allowed, redirect.
			Request::redirect(null, null, null, null, '5');
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();

		// Get supplementary files for this paper
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$templateMgr->assign_by_ref('suppFiles', $suppFileDao->getSuppFilesByPaper($this->paperId));

		parent::display();
	}

	/**
	 * Save changes to paper.
	 */
	function execute() {
		$paperDao =& DAORegistry::getDAO('PaperDAO');

		// Update paper
		$paper =& $this->paper;
		if ($paper->getSubmissionProgress() <= $this->step) {
			$paper->stampStatusModified();
			$paper->setSubmissionProgress($this->step + 1);
		}
		$paperDao->updatePaper($paper);

		return $this->paperId;
	}
}

?>
