<?php

/**
 * @defgroup presenter_form_submit
 */

/**
 * @file PresenterSubmitForm.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PresenterSubmitForm
 * @ingroup presenter_form_submit
 *
 * @brief Base class for conference presenter submit forms.
 *
 */

// $Id$


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

		$this->addCheck(new FormValidatorPost($this));

		$this->step = $step;
		$this->paper = $paper;
		$this->paperId = $paper ? $paper->getPaperId() : null;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', $this->paperId);
		$templateMgr->assign('submitStep', $this->step);

		switch($this->step) {
			case '2':
				$helpTopicId = 'submission.indexingMetadata';
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

		// Determine which submission steps should be shown

		$progress = isset($this->paper) ? $this->paper->getCurrentStage() : REVIEW_STAGE_ABSTRACT;
		$reviewMode = isset($this->paper)?$this->paper->getReviewMode():$schedConf->getSetting('reviewMode');

		$showAbstractSteps = $progress == REVIEW_STAGE_ABSTRACT || $reviewMode != REVIEW_MODE_BOTH_SEQUENTIAL;
		$showPaperSteps = $progress == REVIEW_STAGE_PRESENTATION || $reviewMode == REVIEW_MODE_BOTH_SIMULTANEOUS;

		$templateMgr->assign('showAbstractSteps', $showAbstractSteps);
		$templateMgr->assign('showPaperSteps', $showPaperSteps);

		if (isset($this->paper)) {
			$templateMgr->assign('submissionProgress', $this->paper->getSubmissionProgress());
		}

		parent::display();
	}

	function confirmSubmission(&$paper, &$user, &$schedConf, &$conference, $mailTemplate = 'SUBMISSION_ACK') {
		// Update search index
		import('search.PaperSearchIndex');
		PaperSearchIndex::indexPaperMetadata($paper);
		PaperSearchIndex::indexPaperFiles($paper);

		// Send presenter notification email
		import('mail.PaperMailTemplate');
		$mail = &new PaperMailTemplate($paper, $mailTemplate, null, null, null, null, false);
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

			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($paper->getPaperId());
			while ($editAssignment =& $editAssignments->next()) {
				$mail->addBcc($editAssignment->getDirectorEmail(), $editAssignment->getDirectorFullName());
				unset($editAssignment);
			}

			$mail->assignParams(array(
				'presenterName' => $user->getFullName(),
				'presenterUsername' => $user->getUsername(),
				'editorialContactSignature' => $schedConf->getSetting('contactName') . "\n" . $conference->getConferenceTitle(),
				'submissionUrl' => Request::url(null, null, 'presenter', 'submission', $paper->getPaperId())
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

		$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');

		$trackDirectors =& $trackDirectorsDao->getDirectorsByTrackId($schedConf->getSchedConfId(), $trackId);

		foreach ($trackDirectors as $trackDirector) {
			$editAssignment =& new EditAssignment();
			$editAssignment->setPaperId($paper->getPaperId());
			$editAssignment->setDirectorId($trackDirector->getUserId());
			$editAssignmentDao->insertEditAssignment($editAssignment);
			unset($editAssignment);
		}

		return $trackDirectors;
	}
}

?>
