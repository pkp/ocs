<?php

/**
 * MetadataForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * Form to change metadata information for a submission.
 *
 * $Id$
 */

import('form.Form');

class MetadataForm extends Form {
	/** @var Paper current paper */
	var $paper;
	
	/** @var boolean can edit metadata */
	var $canEdit;
	
	/** @var boolean can view authors */
	var $canViewAuthors;
	
	/**
	 * Constructor.
	 */
	function MetadataForm($paper) {
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$event = &Request::getEvent();
		$user = &Request::getUser();
		$roleId = $roleDao->getRoleIdFromPath(Request::getRequestedPage());
		
		// If the user is an editor of this paper, make the form editable.
		$this->canEdit = false;
		if ($roleId != null && ($roleId == ROLE_ID_EDITOR || $roleId == ROLE_ID_TRACK_EDITOR)) {
			$this->canEdit = true;
		}

		// If the abstract hasn't yet been accepted, allow the author to modify it.
		if ($roleId == ROLE_ID_AUTHOR) {
			if($paper->getReviewProgress() == REVIEW_PROGRESS_ABSTRACT) {
				$this->canEdit = true;
			}
		}

		if ($this->canEdit) {
			parent::Form('submission/metadata/metadataEdit.tpl');
			$this->addCheck(new FormValidator($this, 'title', 'required', 'author.submit.form.titleRequired'));
		} else {
			parent::Form('submission/metadata/metadataView.tpl');
		}
		
		// If the user is a reviewer of this paper, do not show authors.
		$this->canViewAuthors = true;
		if ($roleId != null && $roleId == ROLE_ID_REVIEWER) {
			$this->canViewAuthors = false;
		}
		
		$this->paper = $paper;
	}
	
	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
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
				'sponsor' => $paper->getSponsor()
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
						'countryLocalized' => $authors[$i]->getCountryLocalized(),
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
	 * Display the form.
	 */
	function display() {
		$event = &Request::getEvent();
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$countryDao =& DAORegistry::getDAO('CountryDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', isset($this->paper)?$this->paper->getPaperId():null);
		$templateMgr->assign('eventSettings', $event->getSettings(true));
		$templateMgr->assign('rolePath', Request::getRequestedPage());
		$templateMgr->assign('canViewAuthors', $this->canViewAuthors);
		$templateMgr->assign('countries', $countryDao->getCountries());
		$templateMgr->assign('helpTopicId','submission.indexingAndMetadata');
		if ($this->paper) {
			$templateMgr->assign_by_ref('track', $trackDao->getTrack($this->paper->getTrackId()));
		}

		parent::display();
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
	}

	/**
	 * Save changes to paper.
	 * @return int the paper ID
	 */
	function execute() {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$authorDao = &DAORegistry::getDAO('AuthorDAO');
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		
		// Update paper
	
		$paper = &$this->paper;
		$paper->setTitle($this->getData('title'));
		$paper->setTitleAlt1($this->getData('titleAlt1'));
		$paper->setTitleAlt2($this->getData('titleAlt2'));

		$track = &$trackDao->getTrack($paper->getTrackId());
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
		
		// Update search index
		import('search.PaperSearchIndex');
		PaperSearchIndex::indexPaperMetadata($paper);
		
		return $paper->getPaperId();
	}
	
}

?>
