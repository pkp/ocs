<?php

/**
 * AuthorSubmitStep5Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 5 of author paper submission.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep5Form extends AuthorSubmitForm {
	
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep5Form($paper) {
		parent::AuthorSubmitForm($paper, 5);
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
		$event = Request::getEvent();

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
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($paper->getPaperId());
		AuthorAction::designateReviewVersion($authorSubmission, true);
		unset($authorSubmission);

		// Update any review assignments so they may access the file
		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($paper->getPaperId());
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByPaperId($paper->getPaperId(), REVIEW_PROGRESS_PAPER, 1);
		foreach($reviewAssignments as $reviewAssignment) {
			$reviewAssignment->setReviewFileId($authorSubmission->getReviewFileId());
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		}
		
		$user = &Request::getUser();
		
		// Update search index
		import('search.PaperSearchIndex');
		PaperSearchIndex::indexPaperMetadata($paper);
		PaperSearchIndex::indexPaperFiles($paper);

		// Send author notification email
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
				'authorName' => $user->getFullName(),
				'authorUsername' => $user->getUsername(),
				'editorialContactSignature' => $conference->getSetting('contactName') . "\n" . $conference->getTitle(),
				'submissionUrl' => Request::url(null, null, 'author', 'submission', $paper->getPaperId())
			));
			$mail->send();
		}

		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($this->paperId, PAPER_LOG_PAPER_SUBMIT, LOG_TYPE_AUTHOR, $user->getUserId(), 'log.author.submitted', array('submissionId' => $paper->getPaperId(), 'authorName' => $user->getFullName()));
		
		return $this->paperId;
	}
	
}

?>
