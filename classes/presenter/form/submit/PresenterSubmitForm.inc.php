<?php

/**
 * @file PresenterSubmitForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package presenter.form.submit
 * @class PresenterSubmitForm
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

		$progress = isset($this->paper) ? $this->paper->getCurrentStage() : REVIEW_STAGE_ABSTRACT;

		$reviewMode = $schedConf->getSetting('reviewMode');
		$showAbstractSteps = $progress == REVIEW_STAGE_ABSTRACT || $reviewMode != REVIEW_MODE_BOTH_SEQUENTIAL;
		$showPaperSteps = $progress == REVIEW_STAGE_PRESENTATION || $reviewMode == REVIEW_MODE_BOTH_SIMULTANEOUS;

		$templateMgr->assign('showAbstractSteps', $showAbstractSteps);
		$templateMgr->assign('showPaperSteps', $showPaperSteps);

		if (isset($this->paper)) {
			$templateMgr->assign('submissionProgress', $this->paper->getSubmissionProgress());
		}

		parent::display();
	}

	function confirmSubmission(&$paper, &$user, &$schedConf, $conference, $mailTemplate = 'SUBMISSION_ACK', $trackDirectors = array()) {
		// Update search index
		import('search.PaperSearchIndex');
		PaperSearchIndex::indexPaperMetadata($paper);
		PaperSearchIndex::indexPaperFiles($paper);

		// Send presenter notification email
		import('mail.PaperMailTemplate');
		$mail = &new PaperMailTemplate($paper, $mailTemplate);
		$mail->setFrom($schedConf->getSetting('contactEmail', true), $schedConf->getSetting('contactName', true));
		if ($mail->isEnabled()) {
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			// If necessary, BCC the acknowledgement to someone.
			if($schedConf->getSetting('copySubmissionAckPrimaryContact')) {
				$mail->addBcc(
					$schedConf->getSetting('contactEmail', true),
					$schedConf->getSetting('contactName', true)
				);
			}
			if($schedConf->getSetting('copySubmissionAckSpecified')) {
				$copyAddress = $schedConf->getSetting('copySubmissionAckAddress');
				if (!empty($copyAddress)) $mail->addBcc($copyAddress);
			}

			foreach ($trackDirectors as $trackDirector) {
				$mail->addBcc($trackDirector->getEmail(), $trackDirector->getFullName());
			}

			$mail->assignParams(array(
				'presenterName' => $user->getFullName(),
				'presenterUsername' => $user->getUsername(),
				'editorialContactSignature' => $schedConf->getSetting('contactName', true) . "\n" . $conference->getConferenceTitle(),
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
