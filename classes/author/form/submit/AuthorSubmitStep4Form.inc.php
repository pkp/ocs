<?php

/**
 * AuthorSubmitStep4Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 4 of author paper submission.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep4Form extends AuthorSubmitForm {
	
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep4Form($paper) {
		parent::AuthorSubmitForm($paper, 4);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		
		// Get supplementary files for this paper
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$templateMgr->assign_by_ref('suppFiles', $suppFileDao->getSuppFilesByPaper($this->paperId));

		parent::display();
	}
	
	/**
	 * Save changes to paper.
	 */
	function execute() {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		
		// Update paper
		$paper = &$this->paper;
		if ($paper->getSubmissionProgress() <= $this->step) {
			$paper->stampStatusModified();
			$paper->setSubmissionProgress($this->step + 1);
		}
		$paperDao->updatePaper($paper);
		
		return $this->paperId;
	}
	
}

?>
