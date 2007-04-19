<?php

/**
 * PresenterSubmitStep1Form.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package presenter.form.submit
 *
 * Form for Step 1 of presenter paper submission.
 *
 * $Id$
 */

import("presenter.form.submit.PresenterSubmitForm");

class PresenterSubmitStep1Form extends PresenterSubmitForm {
	
	/**
	 * Constructor.
	 */
	function PresenterSubmitStep1Form($paper = null) {
		parent::PresenterSubmitForm($paper, 1);
		
		$schedConf = &Request::getSchedConf();
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'trackId', 'required', 'presenter.submit.form.trackRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'trackId', 'required', 'presenter.submit.form.trackRequired', array(DAORegistry::getDAO('TrackDAO'), 'trackExists'), array($schedConf->getSchedConfId())));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();
		
		$user = &Request::getUser();

		$templateMgr = &TemplateManager::getManager();
		
		// Get tracks for this conference
		$trackDao = &DAORegistry::getDAO('TrackDAO');

		// If this user is a track director or a director, they are allowed
		// to submit to tracks flagged as "director-only" for submissions.
		// Otherwise, display only tracks they are allowed to submit to.
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isDirector = $roleDao->roleExists($conference->getConferenceId(), $schedConf->getSchedConfId(), $user->getUserId(), ROLE_ID_DIRECTOR) ||
			$roleDao->roleExists($conference->getConferenceId(), $schedConf->getSchedConfId(), $user->getUserId(), ROLE_ID_TRACK_DIRECTOR) ||
			$roleDao->roleExists($conference->getConferenceId(), 0, $user->getUserId(), ROLE_ID_DIRECTOR) ||
			$roleDao->roleExists($conference->getConferenceId(), 0, $user->getUserId(), ROLE_ID_TRACK_DIRECTOR);

		$templateMgr->assign('trackOptions', array('0' => Locale::translate('presenter.submit.selectTrack')) + $trackDao->getTrackTitles($schedConf->getSchedConfId(), !$isDirector));

		parent::display();
	}
	
	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
		if (isset($this->paper)) {
			$this->_data = array(
				'trackId' => $this->paper->getTrackId(),
				'commentsToDirector' => $this->paper->getCommentsToDirector()
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('submissionChecklist', 'copyrightNoticeAgree', 'trackId', 'commentsToDirector'));
	}
	
	/**
	 * Save changes to paper.
	 * @return int the paper ID
	 */
	function execute() {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		
		if (isset($this->paper)) {
			// Update existing paper
			$this->paper->setTrackId($this->getData('trackId'));
			$this->paper->setCommentsToDirector($this->getData('commentsToDirector'));
			if ($this->paper->getSubmissionProgress() <= $this->step) {
				$this->paper->stampStatusModified();
				$this->paper->setSubmissionProgress($this->step + 1);
			}
			$paperDao->updatePaper($this->paper);
			
		} else {
			// Insert new paper
			$schedConf = &Request::getSchedConf();
			$user = &Request::getUser();
		
			$this->paper = &new Paper();
			$this->paper->setUserId($user->getUserId());
			$this->paper->setSchedConfId($schedConf->getSchedConfId());
			$this->paper->setTrackId($this->getData('trackId'));
			$this->paper->stampStatusModified();
			$this->paper->setSubmissionProgress($this->step + 1);
			$this->paper->setLanguage('');
			$this->paper->setCommentsToDirector($this->getData('commentsToDirector'));

			switch($schedConf->getSetting('reviewMode')) {
				case REVIEW_MODE_ABSTRACTS_ALONE:
				case REVIEW_MODE_BOTH_SEQUENTIAL:
					$this->paper->setCurrentStage(REVIEW_STAGE_ABSTRACT);
					break;
				case REVIEW_MODE_PRESENTATIONS_ALONE:
				case REVIEW_MODE_BOTH_SIMULTANEOUS:
					$this->paper->setCurrentStage(REVIEW_STAGE_PRESENTATION);
					break;
			}
		
			// Set user to initial presenter
			$user = &Request::getUser();
			$presenter = &new Presenter();
			$presenter->setFirstName($user->getFirstName());
			$presenter->setMiddleName($user->getMiddleName());
			$presenter->setLastName($user->getLastName());
			$presenter->setAffiliation($user->getAffiliation());
			$presenter->setCountry($user->getCountry());
			$presenter->setEmail($user->getEmail());
			$presenter->setUrl($user->getUrl());
			$presenter->setBiography($user->getBiography());
			$presenter->setPrimaryContact(1);
			$this->paper->addPresenter($presenter);
			
			$paperDao->insertPaper($this->paper);
			$this->paperId = $this->paper->getPaperId();
		}
		
		return $this->paperId;
	}
	
}

?>
