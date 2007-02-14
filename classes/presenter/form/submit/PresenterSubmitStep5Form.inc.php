<?php

/**
 * PresenterSubmitStep5Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package presenter.form.submit
 *
 * Form for Step 5 of presenter paper submission.
 *
 * $Id$
 */

import("presenter.form.submit.PresenterSubmitForm");

class PresenterSubmitStep5Form extends PresenterSubmitForm {
	
	/**
	 * Constructor.
	 */
	function PresenterSubmitStep5Form($paper) {
		parent::PresenterSubmitForm($paper, 5);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		
		// Get paper file for this paper
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$paperFiles =& $paperFileDao->getPaperFilesByPaper($this->paperId);

		$templateMgr->assign_by_ref('files', $paperFiles);
		$templateMgr->assign_by_ref('conference', Request::getConference());

		parent::display();
	}
	
	/**
	 * Save changes to paper.
	 */
	function execute() {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');

		$conference = Request::getConference();
		$schedConf = Request::getSchedConf();

		// Update paper
		$paper = &$this->paper;
		$paper->setDateSubmitted(Core::getCurrentDate());
		$paper->setSubmissionProgress(0);
		$paper->stampStatusModified();

		// We've collected the paper now -- bump the review progress.		
		$paper->setReviewProgress(REVIEW_PROGRESS_PAPER);
			
		$paperDao->updatePaper($paper);

		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		if(!$layoutDao->getLayoutAssignmentByPaperId($paper->getPaperId())) {
			$layoutAssignment = &new LayoutAssignment();
			$layoutAssignment->setPaperId($paper->getPaperId());
			$layoutAssignment->setEditorId(0);
			$layoutDao->insertLayoutAssignment($layoutAssignment);
		}

		// Designate this as the review version by default.
		$presenterSubmissionDao =& DAORegistry::getDAO('PresenterSubmissionDAO');
		$presenterSubmission =& $presenterSubmissionDao->getPresenterSubmission($paper->getPaperId());
		PresenterAction::designateReviewVersion($presenterSubmission, true);
		unset($presenterSubmission);

		// Update any review assignments so they may access the file
		$presenterSubmission =& $presenterSubmissionDao->getPresenterSubmission($paper->getPaperId());
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByPaperId($paper->getPaperId(), REVIEW_PROGRESS_PAPER, 1);
		foreach($reviewAssignments as $reviewAssignment) {
			$reviewAssignment->setReviewFileId($presenterSubmission->getReviewFileId());
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		}
		
		$user = &Request::getUser();
		
		// Update search index
		import('search.PaperSearchIndex');
		PaperSearchIndex::indexPaperMetadata($paper);
		PaperSearchIndex::indexPaperFiles($paper);

		// Send presenter notification email
		import('mail.PaperMailTemplate');
		$mail = &new PaperMailTemplate($paper, 'SUBMISSION_ACK');
		$mail->setFrom($conference->getSetting('contactEmail'), $conference->getSetting('contactName'));
		if ($mail->isEnabled()) {
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			// If necessary, BCC the acknowledgement to someone.
			if($conference->getSetting('copySubmissionAckPrimaryContact')) {
				$mail->addBcc(
					$conference->getSetting('contactEmail'),
					$conference->getSetting('contactName')
				);
			}
			if($conference->getSetting('copySubmissionAckSpecified')) {
				$copyAddress = $conference->getSetting('copySubmissionAckAddress');
				if (!empty($copyAddress)) $mail->addBcc($copyAddress);
			}

			$mail->assignParams(array(
				'presenterName' => $user->getFullName(),
				'presenterUsername' => $user->getUsername(),
				'editorialContactSignature' => $conference->getSetting('contactName') . "\n" . $conference->getTitle(),
				'submissionUrl' => Request::url(null, null, 'presenter', 'submission', $paper->getPaperId())
			));
			$mail->send();
		}

		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($this->paperId, PAPER_LOG_PAPER_SUBMIT, LOG_TYPE_PRESENTER, $user->getUserId(), 'log.presenter.submitted', array('submissionId' => $paper->getPaperId(), 'presenterName' => $user->getFullName()));
		
		return $this->paperId;
	}
	
}

?>
