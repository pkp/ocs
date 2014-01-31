<?php

/**
 * @file AuthorSubmitStep1Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitStep1Form
 * @ingroup author_form_submit
 *
 * @brief Form for Step 1 of author paper submission.
 */

//$Id$

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep1Form extends AuthorSubmitForm {

	/**
	 * Constructor.
	 */
	function AuthorSubmitStep1Form($paper = null) {
		parent::AuthorSubmitForm($paper, 1);

		$schedConf =& Request::getSchedConf();

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'trackId', 'required', 'author.submit.form.trackRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'trackId', 'required', 'author.submit.form.trackRequired', array(DAORegistry::getDAO('TrackDAO'), 'trackExists'), array($schedConf->getId())));
		$this->addCheck(new FormValidatorControlledVocab($this, 'sessionType', 'optional', 'author.submit.form.sessionTypeRequired', 'paperType', ASSOC_TYPE_SCHED_CONF, $schedConf->getId()));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$user =& Request::getUser();

		$templateMgr =& TemplateManager::getManager();

		// Get tracks for this conference
		$trackDao =& DAORegistry::getDAO('TrackDAO');

		// If this user is a track director or a director, they are
		// allowed to submit to tracks flagged as "director-only" for
		// submissions. Otherwise, display only tracks they are allowed
		// to submit to.
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$isDirector = $roleDao->roleExists($conference->getId(), $schedConf->getId(), $user->getId(), ROLE_ID_DIRECTOR) ||
			$roleDao->roleExists($conference->getId(), $schedConf->getId(), $user->getId(), ROLE_ID_TRACK_DIRECTOR) ||
			$roleDao->roleExists($conference->getId(), 0, $user->getId(), ROLE_ID_DIRECTOR) ||
			$roleDao->roleExists($conference->getId(), 0, $user->getId(), ROLE_ID_TRACK_DIRECTOR);

		$templateMgr->assign('trackOptions', array('0' => __('author.submit.selectTrack')) + $trackDao->getTrackTitles($schedConf->getId(), !$isDirector));

		$paperTypeDao =& DAORegistry::getDAO('PaperTypeDAO');
		$sessionTypes = $paperTypeDao->getPaperTypes($schedConf->getId());
		$templateMgr->assign('sessionTypes', $sessionTypes->toArray());

		parent::display();
	}

	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
		if (isset($this->paper)) {
			$this->_data = array(
				'trackId' => $this->paper->getTrackId(),
				'sessionType' => $this->paper->getData('sessionType'),
				'commentsToDirector' => $this->paper->getCommentsToDirector()
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('submissionChecklist', 'copyrightNoticeAgree', 'trackId', 'commentsToDirector', 'sessionType'));
	}

	/**
	 * Save changes to paper.
	 * @return int the paper ID
	 */
	function execute() {
		$paperDao =& DAORegistry::getDAO('PaperDAO');

		if (isset($this->paper)) {
			$reviewMode = $this->paper->getReviewMode();
			// Update existing paper
			$this->paper->setTrackId($this->getData('trackId'));
			$this->paper->setCommentsToDirector($this->getData('commentsToDirector'));
			$this->paper->setData('sessionType', $this->getData('sessionType'));
			if ($this->paper->getSubmissionProgress() <= $this->step) {
				$this->paper->stampStatusModified();
				if($reviewMode == REVIEW_MODE_ABSTRACTS_ALONE) {
					$this->paper->setSubmissionProgress($this->step + 2);
				}
				else {
					$this->paper->setSubmissionProgress($this->step + 1);
				}
			}
			$paperDao->updatePaper($this->paper);

		} else {
			// Insert new paper
			$conference =& Request::getConference();
			$schedConf =& Request::getSchedConf();
			$user =& Request::getUser();

			$this->paper = new Paper();
			$this->paper->setUserId($user->getId());
			$this->paper->setSchedConfId($schedConf->getId());
			$this->paper->setTrackId($this->getData('trackId'));
			$this->paper->stampStatusModified();
			$reviewMode = $schedConf->getSetting('reviewMode');
			$this->paper->setReviewMode($reviewMode);
			$this->paper->setLanguage(String::substr($conference->getPrimaryLocale(), 0, 2));
			$this->paper->setCommentsToDirector($this->getData('commentsToDirector'));

			switch($reviewMode) {
				case REVIEW_MODE_ABSTRACTS_ALONE:
				case REVIEW_MODE_BOTH_SEQUENTIAL:
					$this->paper->setSubmissionProgress($this->step + 2);
					$this->paper->setCurrentStage(REVIEW_STAGE_ABSTRACT);
					break;
				case REVIEW_MODE_PRESENTATIONS_ALONE:
				case REVIEW_MODE_BOTH_SIMULTANEOUS:
					$this->paper->setSubmissionProgress($this->step + 1);
					$this->paper->setCurrentStage(REVIEW_STAGE_PRESENTATION);
					break;
			}

			// Set user to initial author
			$user =& Request::getUser();
			$author = new Author();
			$author->setFirstName($user->getFirstName());
			$author->setMiddleName($user->getMiddleName());
			$author->setLastName($user->getLastName());
			$author->setAffiliation($user->getAffiliation());
			$author->setCountry($user->getCountry());
			$author->setEmail($user->getEmail());
			$author->setUrl($user->getUrl());
			$author->setBiography($user->getBiography(null), null);
			$author->setPrimaryContact(1);
			$this->paper->addAuthor($author);

			$this->paper->setData('sessionType', $this->getData('sessionType'));

			$paperDao->insertPaper($this->paper);
			$this->paperId = $this->paper->getPaperId();
		}

		return $this->paperId;
	}

}

?>
