<?php

/**
 * PresenterSubmitStep4Form.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package presenter.form.submit
 *
 * Form for Step 4 of presenter paper submission.
 *
 * $Id$
 */

import("presenter.form.submit.PresenterSubmitForm");

class PresenterSubmitStep4Form extends PresenterSubmitForm {
	
	/**
	 * Constructor.
	 */
	function PresenterSubmitStep4Form($paper) {
		parent::PresenterSubmitForm($paper, 4);
		$schedConf =& Request::getSchedConf();
		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) {
			// If supplementary files are not allowed, redirect.
			Request::redirect(null, null, null, null, '3');
		}
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
