<?php

/**
 * AuthorSubmitStep1Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 1 of author paper submission.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep1Form extends AuthorSubmitForm {
	
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep1Form($paper = null) {
		parent::AuthorSubmitForm($paper, 1);
		
		$event = &Request::getEvent();
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'trackId', 'required', 'author.submit.form.trackRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'trackId', 'required', 'author.submit.form.trackRequired', array(DAORegistry::getDAO('TrackDAO'), 'trackExists'), array($event->getEventId())));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$conference = &Request::getConference();
		$event = &Request::getEvent();
		
		$user = &Request::getUser();

		$templateMgr = &TemplateManager::getManager();
		
		// Get tracks for this conference
		$trackDao = &DAORegistry::getDAO('TrackDAO');

		// If this user is a track editor or an editor, they are allowed
		// to submit to tracks flagged as "editor-only" for submissions.
		// Otherwise, display only tracks they are allowed to submit to.
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->roleExists($conference->getConferenceId(), $event->getEventId(), $user->getUserId(), ROLE_ID_EDITOR) ||
			$roleDao->roleExists($conference->getConferenceId(), $event->getEventId(), $user->getUserId(), ROLE_ID_TRACK_EDITOR) ||
			$roleDao->roleExists($conference->getConferenceId(), 0, $user->getUserId(), ROLE_ID_EDITOR) ||
			$roleDao->roleExists($conference->getConferenceId(), 0, $user->getUserId(), ROLE_ID_TRACK_EDITOR);

		$templateMgr->assign('trackOptions', array('0' => Locale::translate('author.submit.selectTrack')) + $trackDao->getTrackTitles($event->getEventId(), !$isEditor));

		$templateMgr->assign('secondaryTrackOptions',
			array(
				'0' => Locale::translate('common.none')) +
				$trackDao->getTrackTitles($event->getEventId(), !$isEditor));
		parent::display();
	}
	
	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
		if (isset($this->paper)) {
			$this->_data = array(
				'trackId' => $this->paper->getTrackId(),
				'secondaryTrackId' => $this->paper->getSecondaryTrackId(),
				'commentsToEditor' => $this->paper->getCommentsToEditor()
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('submissionChecklist', 'copyrightNoticeAgree', 'trackId', 'commentsToEditor', 'secondaryTrackId'));

		if($this->getData('secondaryTrackId') == 0) {
			$this->setData('secondaryTrackId', null);
		}
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
			$this->paper->setSecondaryTrackId($this->getData('secondaryTrackId'));
			$this->paper->setCommentsToEditor($this->getData('commentsToEditor'));
			if ($this->paper->getSubmissionProgress() <= $this->step) {
				$this->paper->stampStatusModified();
				$this->paper->setSubmissionProgress($this->step + 1);
			}
			$paperDao->updatePaper($this->paper);
			
		} else {
			// Insert new paper
			$event = &Request::getEvent();
			$user = &Request::getUser();
		
			$this->paper = &new Paper();
			$this->paper->setUserId($user->getUserId());
			$this->paper->setEventId($event->getEventId());
			$this->paper->setTrackId($this->getData('trackId'));
			$this->paper->setSecondaryTrackId($this->getData('secondaryTrackId'));
			$this->paper->stampStatusModified();
			$this->paper->setSubmissionProgress($this->step + 1);
			$this->paper->setLanguage('');
			$this->paper->setCommentsToEditor($this->getData('commentsToEditor'));
		
			// Set user to initial author
			$user = &Request::getUser();
			$author = &new Author();
			$author->setFirstName($user->getFirstName());
			$author->setMiddleName($user->getMiddleName());
			$author->setLastName($user->getLastName());
			$author->setAffiliation($user->getAffiliation());
			$author->setCountry($user->getCountry());
			$author->setEmail($user->getEmail());
			$author->setUrl($user->getUrl());
			$author->setBiography($user->getBiography());
			$author->setPrimaryContact(1);
			$this->paper->addAuthor($author);
			
			$paperDao->insertPaper($this->paper);
			$this->paperId = $this->paper->getPaperId();
		}
		
		return $this->paperId;
	}
	
}

?>
