<?php

/**
 * PresenterSubmitForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package presenter.form.submit
 *
 * Base class for conference presenter submit forms.
 *
 * $Id$
 */

import('form.Form');

class PresenterSubmitForm extends Form {

	/** @var int the ID of the paper */
	var $paperId;
	
	/** @var Paper current paper */
	var $paper;

	/** @var int the current step */
	var $step;
	
	/**
	 * Constructor.
	 * @param $paper object
	 * @param $step int
	 */
	function PresenterSubmitForm($paper, $step) {
		parent::Form(sprintf('presenter/submit/step%d.tpl', $step));
		$this->step = $step;
		$this->paper = $paper;
		$this->paperId = $paper ? $paper->getPaperId() : null;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('sidebarTemplate', 'presenter/submit/submitSidebar.tpl');
		$templateMgr->assign('paperId', $this->paperId);
		$templateMgr->assign('submitStep', $this->step);
		
		switch($this->step) {
			case '2':
				$helpTopicId = 'submission.indexingAndMetadata';
				break;
			case '4':
				$helpTopicId = 'submission.supplementaryFiles';
				break;
			default:
				$helpTopicId = 'submission.index';
		}
		$templateMgr->assign('helpTopicId', $helpTopicId);

		$schedConf = &Request::getSchedConf();
		$settingsDao = &DAORegistry::getDAO('SchedConfSettingsDAO');
		$templateMgr->assign_by_ref('schedConfSettings', $settingsDao->getSchedConfSettings($schedConf->getSchedConfId(), true));

		// Determine which submission steps should be shown
		
		$progress = isset($this->paper) ? $this->paper->getCurrentStage() : REVIEW_PROGRESS_ABSTRACT;

		$showAbstractSteps = $progress == REVIEW_PROGRESS_ABSTRACT;
		$showPaperSteps = $progress == REVIEW_PROGRESS_PAPER || $schedConf->getCollectPapersWithAbstracts();
		
		$templateMgr->assign('showAbstractSteps', $showAbstractSteps);
		$templateMgr->assign('showPaperSteps', $showPaperSteps);
		
		if (isset($this->paper)) {
			$templateMgr->assign('submissionProgress', $this->paper->getSubmissionProgress());
		}
		
		parent::display();
	}
	
}

?>
