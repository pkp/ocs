<?php

/**
 * AuthorSubmitStep2Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 2 of author paper submission.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep2Form extends AuthorSubmitForm {
	
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep2Form($paper) {
		parent::AuthorSubmitForm($paper, 2);
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom($this, 'authors', 'required', 'author.submit.form.authorRequired', create_function('$authors', 'return count($authors) > 0;')));
		$this->addCheck(new FormValidatorArray($this, 'authors', 'required', 'author.submit.form.authorRequiredFields', array('firstName', 'lastName', 'email')));
		$this->addCheck(new FormValidator($this, 'title', 'required', 'author.submit.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'abstract', 'required', 'author.submit.form.abstractRequired'));
	}
	
	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
		$trackDao = &DAORegistry::getDAO('TrackDAO');

		if (isset($this->paper)) {
			$paper = &$this->paper;
			$this->_data = array(
				'authors' => array(),
				'title' => $paper->getTitle(),
				'titleAlt1' => $paper->getTitleAlt1(),
				'titleAlt2' => $paper->getTitleAlt2(),
				'abstract' => $paper->getAbstract(),
				'abstractAlt1' => $paper->getAbstractAlt1(),
				'abstractAlt2' => $paper->getAbstractAlt2(),
				'discipline' => $paper->getDiscipline(),
				'subjectClass' => $paper->getSubjectClass(),
				'subject' => $paper->getSubject(),
				'coverageGeo' => $paper->getCoverageGeo(),
				'coverageChron' => $paper->getCoverageChron(),
				'coverageSample' => $paper->getCoverageSample(),
				'type' => $paper->getType(),
				'language' => $paper->getLanguage(),
				'sponsor' => $paper->getSponsor(),
				'track' => $trackDao->getTrack($paper->getTrackId())
			);
			
			$authors = &$paper->getAuthors();
			for ($i=0, $count=count($authors); $i < $count; $i++) {
				array_push(
					$this->_data['authors'],
					array(
						'authorId' => $authors[$i]->getAuthorId(),
						'firstName' => $authors[$i]->getFirstName(),
						'middleName' => $authors[$i]->getMiddleName(),
						'lastName' => $authors[$i]->getLastName(),
						'affiliation' => $authors[$i]->getAffiliation(),
						'country' => $authors[$i]->getCountry(),
						'email' => $authors[$i]->getEmail(),
						'url' => $authors[$i]->getUrl(),
						'biography' => $authors[$i]->getBiography()
					)
				);
				if ($authors[$i]->getPrimaryContact()) {
					$this->setData('primaryContact', $i);
				}
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'authors',
				'deletedAuthors',
				'primaryContact',
				'title',
				'titleAlt1',
				'titleAlt2',
				'abstract',
				'abstractAlt1',
				'abstractAlt2',
				'discipline',
				'subjectClass',
				'subject',
				'coverageGeo',
				'coverageChron',
				'coverageSample',
				'type',
				'language',
				'sponsor'
			)
		);

		// Load the track. This is used in the step 2 form to
		// determine whether or not to display indexing options.
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$this->_data['track'] = &$trackDao->getTrack($this->paper->getTrackId());
	}

	/**
	 * Display the form.
	 */
	function display() {
		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('countries', $countries);

		parent::display();
	}

	/**
	 * Save changes to paper.
	 * @return int the paper ID
	 */
	function execute() {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$authorDao = &DAORegistry::getDAO('AuthorDAO');
		$paper = &$this->paper;
		$conference = &Request::getConference();
		$event = &Request::getEvent();

		// Update paper
		$paper->setTitle($this->getData('title'));
		$paper->setTitleAlt1($this->getData('titleAlt1'));
		$paper->setTitleAlt2($this->getData('titleAlt2'));
		$paper->setAbstract($this->getData('abstract'));
		$paper->setAbstractAlt1($this->getData('abstractAlt1'));
		$paper->setAbstractAlt2($this->getData('abstractAlt2'));
		$paper->setDiscipline($this->getData('discipline'));
		$paper->setSubjectClass($this->getData('subjectClass'));
		$paper->setSubject($this->getData('subject'));
		$paper->setCoverageGeo($this->getData('coverageGeo'));
		$paper->setCoverageChron($this->getData('coverageChron'));
		$paper->setCoverageSample($this->getData('coverageSample'));
		$paper->setType($this->getData('type'));
		$paper->setLanguage($this->getData('language'));
		$paper->setSponsor($this->getData('sponsor'));

		// Update the submission progress if necessary.
		if ($paper->getSubmissionProgress() <= $this->step) {
			$paper->stampStatusModified();
			
			// If we aren't about to collect the paper, the submission is complete
			// (for now)
			if(!$event->getCollectPapersWithAbstracts()) {
				$paper->setDateSubmitted(Core::getCurrentDate());
				$paper->stampStatusModified();
				$paper->setSubmissionProgress(0);
				$paper->setReviewProgress(REVIEW_PROGRESS_ABSTRACT);

				$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
				if(!$layoutDao->getLayoutAssignmentByPaperId($paper->getPaperId())) {
					$layoutAssignment = &new LayoutAssignment();
					$layoutAssignment->setPaperId($paper->getPaperId());
					$layoutAssignment->setEditorId(0);
					$layoutDao->insertLayoutAssignment($layoutAssignment);
				}
			} else {
				$paper->setSubmissionProgress($this->step + 1);
			}
		}
		
		// Update authors
		$authors = $this->getData('authors');
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]['authorId'] > 0) {
				// Update an existing author
				$author = &$paper->getAuthor($authors[$i]['authorId']);
				$isExistingAuthor = true;
				
			} else {
				// Create a new author
				$author = &new Author();
				$isExistingAuthor = false;
			}
			
			if ($author != null) {
				$author->setFirstName($authors[$i]['firstName']);
				$author->setMiddleName($authors[$i]['middleName']);
				$author->setLastName($authors[$i]['lastName']);
				$author->setAffiliation($authors[$i]['affiliation']);
				$author->setCountry($authors[$i]['country']);
				$author->setEmail($authors[$i]['email']);
				$author->setUrl($authors[$i]['url']);
				$author->setBiography($authors[$i]['biography']);
				$author->setPrimaryContact($this->getData('primaryContact') == $i ? 1 : 0);
				$author->setSequence($authors[$i]['seq']);
				
				if ($isExistingAuthor == false) {
					$paper->addAuthor($author);
				}
			}
		}
		
		// Remove deleted authors
		$deletedAuthors = explode(':', $this->getData('deletedAuthors'));
		for ($i=0, $count=count($deletedAuthors); $i < $count; $i++) {
			$paper->removeAuthor($deletedAuthors[$i]);
		}
		
		// Save the paper
		$paperDao->updatePaper($paper);
		
		return $this->paperId;
	}
	
}

?>
