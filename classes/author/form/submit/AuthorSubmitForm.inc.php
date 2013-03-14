<?php

/**
 * @defgroup author_form_submit
 */

/**
 * @file AuthorSubmitForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitForm
 * @ingroup author_form_submit
 *
 * @brief Base class for conference author submit forms.
 *
 */



import('lib.pkp.classes.form.Form');

class AuthorSubmitForm extends Form {

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
	function AuthorSubmitForm($paper, $step) {
		parent::Form(sprintf('author/submit/step%d.tpl', $step));

		$this->addCheck(new FormValidatorPost($this));

		$this->step = $step;
		$this->paper = $paper;
		$this->paperId = $paper ? $paper->getId() : null;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $this->paperId);
		$templateMgr->assign('submitStep', $this->step);

		switch($this->step) {
			case 3:
				$helpTopicId = 'submission.indexingMetadata';
				break;
			case 4:
				$helpTopicId = 'submission.supplementaryFiles';
				break;
			default:
				$helpTopicId = 'submission.index';
		}
		$templateMgr->assign('helpTopicId', $helpTopicId);

		$schedConf =& Request::getSchedConf();
		$settingsDao = DAORegistry::getDAO('SchedConfSettingsDAO');

		// Determine which submission steps should be shown

		$progress = isset($this->paper) ? $this->paper->getCurrentRound() : REVIEW_ROUND_ABSTRACT;
		$reviewMode = isset($this->paper)?$this->paper->getReviewMode():$schedConf->getSetting('reviewMode');

		$showAbstractSteps = $progress == REVIEW_ROUND_ABSTRACT || $reviewMode != REVIEW_MODE_BOTH_SEQUENTIAL;
		$showPaperSteps = $progress == REVIEW_ROUND_PRESENTATION || $reviewMode == REVIEW_MODE_BOTH_SIMULTANEOUS || $reviewMode == REVIEW_MODE_PRESENTATIONS_ALONE;

		$templateMgr->assign('showAbstractSteps', $showAbstractSteps);
		$templateMgr->assign('showPaperSteps', $showPaperSteps);

		if (isset($this->paper)) {
			$templateMgr->assign('submissionProgress', $this->paper->getSubmissionProgress());
		}

		parent::display();
	}

	function confirmSubmission(&$paper, &$user, &$schedConf, &$conference, $mailTemplate = 'SUBMISSION_ACK') {
		// Update search index
		import('classes.search.PaperSearchIndex');
		PaperSearchIndex::indexPaperMetadata($paper);
		PaperSearchIndex::indexPaperFiles($paper);

		// Send author notification email
		import('classes.mail.PaperMailTemplate');
		$mail = new PaperMailTemplate($paper, $mailTemplate, null, null, null, null, false, true);
		$mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
		if ($mail->isEnabled()) {
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			// If necessary, BCC the acknowledgement to someone.
			if($schedConf->getSetting('copySubmissionAckPrimaryContact')) {
				$mail->addBcc(
					$schedConf->getSetting('contactEmail'),
					$schedConf->getSetting('contactName')
				);
			}
			if($schedConf->getSetting('copySubmissionAckSpecified')) {
				$copyAddress = $schedConf->getSetting('copySubmissionAckAddress');
				if (!empty($copyAddress)) $mail->addBcc($copyAddress);
			}

			$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($paper->getId());
			while ($editAssignment =& $editAssignments->next()) {
				$mail->addBcc($editAssignment->getDirectorEmail(), $editAssignment->getDirectorFullName());
				unset($editAssignment);
			}

			$mail->assignParams(array(
				'authorName' => $user->getFullName(),
				'authorUsername' => $user->getUsername(),
				'editorialContactSignature' => $schedConf->getSetting('contactName') . "\n" . $conference->getLocalizedName(),
				'submissionUrl' => Request::url(null, null, 'author', 'submission', $paper->getId())
			));
			$mail->send();
		}
	}

	/**
	 * Automatically assign Track Directors to new submissions.
	 * @param $paper object
	 * @return array of track directors
	 */

	function assignDirectors(&$paper) {
		$trackId = $paper->getTrackId();
		$schedConf =& Request::getSchedConf();

		$trackDirectorsDao = DAORegistry::getDAO('TrackDirectorsDAO');
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');

		$trackDirectors =& $trackDirectorsDao->getDirectorsByTrackId($schedConf->getId(), $trackId);

		foreach ($trackDirectors as $trackDirector) {
			$editAssignment = new EditAssignment();
			$editAssignment->setPaperId($paper->getId());
			$editAssignment->setDirectorId($trackDirector->getId());
			$editAssignmentDao->insertEditAssignment($editAssignment);
			unset($editAssignment);
		}

		return $trackDirectors;
	}
}

?>
