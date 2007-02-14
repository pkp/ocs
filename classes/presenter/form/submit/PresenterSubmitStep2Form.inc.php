<?php

/**
 * PresenterSubmitStep2Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package presenter.form.submit
 *
 * Form for Step 2 of presenter paper submission.
 *
 * $Id$
 */

import("presenter.form.submit.PresenterSubmitForm");

class PresenterSubmitStep2Form extends PresenterSubmitForm {
	
	/**
	 * Constructor.
	 */
	function PresenterSubmitStep2Form($paper) {
		parent::PresenterSubmitForm($paper, 2);
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom($this, 'presenters', 'required', 'presenter.submit.form.presenterRequired', create_function('$presenters', 'return count($presenters) > 0;')));
		$this->addCheck(new FormValidatorArray($this, 'presenters', 'required', 'presenter.submit.form.presenterRequiredFields', array('firstName', 'lastName', 'email')));
		$this->addCheck(new FormValidator($this, 'title', 'required', 'presenter.submit.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'abstract', 'required', 'presenter.submit.form.abstractRequired'));
	}
	
	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
		$trackDao = &DAORegistry::getDAO('TrackDAO');

		if (isset($this->paper)) {
			$paper = &$this->paper;
			$this->_data = array(
				'presenters' => array(),
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
			
			$presenters = &$paper->getPresenters();
			for ($i=0, $count=count($presenters); $i < $count; $i++) {
				array_push(
					$this->_data['presenters'],
					array(
						'presenterId' => $presenters[$i]->getPresenterId(),
						'firstName' => $presenters[$i]->getFirstName(),
						'middleName' => $presenters[$i]->getMiddleName(),
						'lastName' => $presenters[$i]->getLastName(),
						'affiliation' => $presenters[$i]->getAffiliation(),
						'country' => $presenters[$i]->getCountry(),
						'email' => $presenters[$i]->getEmail(),
						'url' => $presenters[$i]->getUrl(),
						'biography' => $presenters[$i]->getBiography()
					)
				);
				if ($presenters[$i]->getPrimaryContact()) {
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
				'presenters',
				'deletedPresenters',
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
		$presenterDao = &DAORegistry::getDAO('PresenterDAO');
		$paper = &$this->paper;
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

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
			if(!$schedConf->getCollectPapersWithAbstracts()) {
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
		
		// Update presenters
		$presenters = $this->getData('presenters');
		for ($i=0, $count=count($presenters); $i < $count; $i++) {
			if ($presenters[$i]['presenterId'] > 0) {
				// Update an existing presenter
				$presenter = &$paper->getPresenter($presenters[$i]['presenterId']);
				$isExistingPresenter = true;
				
			} else {
				// Create a new presenter
				$presenter = &new Presenter();
				$isExistingPresenter = false;
			}
			
			if ($presenter != null) {
				$presenter->setFirstName($presenters[$i]['firstName']);
				$presenter->setMiddleName($presenters[$i]['middleName']);
				$presenter->setLastName($presenters[$i]['lastName']);
				$presenter->setAffiliation($presenters[$i]['affiliation']);
				$presenter->setCountry($presenters[$i]['country']);
				$presenter->setEmail($presenters[$i]['email']);
				$presenter->setUrl($presenters[$i]['url']);
				$presenter->setBiography($presenters[$i]['biography']);
				$presenter->setPrimaryContact($this->getData('primaryContact') == $i ? 1 : 0);
				$presenter->setSequence($presenters[$i]['seq']);
				
				if ($isExistingPresenter == false) {
					$paper->addPresenter($presenter);
				}
			}
		}
		
		// Remove deleted presenters
		$deletedPresenters = explode(':', $this->getData('deletedPresenters'));
		for ($i=0, $count=count($deletedPresenters); $i < $count; $i++) {
			$paper->removePresenter($deletedPresenters[$i]);
		}
		
		// Save the paper
		$paperDao->updatePaper($paper);
		
		return $this->paperId;
	}
	
}

?>
