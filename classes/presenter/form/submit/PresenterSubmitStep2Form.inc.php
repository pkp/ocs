<?php

/**
 * @file PresenterSubmitStep2Form.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PresenterSubmitStep2Form
 * @ingroup presenter_form_submit
 *
 * @brief Form for Step 2 of presenter paper submission.
 */

//$Id$

import("presenter.form.submit.PresenterSubmitForm");

class PresenterSubmitStep2Form extends PresenterSubmitForm {
	/**
	 * Constructor.
	 */
	function PresenterSubmitStep2Form($paper) {
		parent::PresenterSubmitForm($paper, 2);

		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom($this, 'presenters', 'required', 'presenter.submit.form.presenterRequired', create_function('$presenters', 'return count($presenters) > 0;')));
		$this->addCheck(new FormValidatorArray($this, 'presenters', 'required', 'presenter.submit.form.presenterRequiredFields', array('firstName', 'lastName')));
		$this->addCheck(new FormValidatorArrayCustom($this, 'presenters', 'required', 'presenter.submit.form.presenterRequiredFields', create_function('$email, $regExp', 'return String::regexp_match($regExp, $email);'), array(FormValidatorEmail::getRegexp()), false, array('email')));
		$this->addCheck(new FormValidatorArrayCustom($this, 'presenters', 'required', 'user.profile.form.urlInvalid', create_function('$url, $regExp', 'return empty($url) ? true : String::regexp_match($regExp, $url);'), array(FormValidatorUrl::getRegexp()), false, array('url')));
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'presenter.submit.form.titleRequired'));

		$schedConf =& Request::getSchedConf();
		$reviewMode = $paper->getReviewMode();
		if ($reviewMode != REVIEW_MODE_PRESENTATIONS_ALONE) {
			$this->addCheck(new FormValidatorLocale($this, 'abstract', 'required', 'presenter.submit.form.abstractRequired'));
		}
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
				'title' => $paper->getTitle(null), // Localized
				'abstract' => $paper->getAbstract(null), // Localized
				'discipline' => $paper->getDiscipline(null), // Localized
				'subjectClass' => $paper->getSubjectClass(null), // Localized
				'subject' => $paper->getSubject(null), // Localized
				'coverageGeo' => $paper->getCoverageGeo(null), // Localized
				'coverageChron' => $paper->getCoverageChron(null), // Localized
				'coverageSample' => $paper->getCoverageSample(null), // Localized
				'type' => $paper->getType(null), // Localized
				'paperType' => $paper->getTypeConst(),
				'language' => $paper->getLanguage(),
				'sponsor' => $paper->getSponsor(null), // Localized
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
						'biography' => $presenters[$i]->getBiography(null)
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
		$userVars = array(
			'presenters',
			'deletedPresenters',
			'primaryContact',
			'title',
			'discipline',
			'subjectClass',
			'subject',
			'coverageGeo',
			'coverageChron',
			'coverageSample',
			'type',
			'language',
			'sponsor',
			'paperType'
		);

		$schedConf =& Request::getSchedConf();
		$reviewMode = $this->paper->getReviewMode();
		if ($reviewMode != REVIEW_MODE_PRESENTATIONS_ALONE) {
			$userVars[] = 'abstract';
		}
		$this->readUserVars($userVars);

		// Load the track. This is used in the step 2 form to
		// determine whether or not to display indexing options.
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$this->_data['track'] = &$trackDao->getTrack($this->paper->getTrackId());
	}

	/**
	 * Get the names of fields for which data should be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		$returner = array('title', 'subjectClass', 'subject', 'coverageGeo', 'coverageChron', 'coverageSample', 'type', 'sponsor');
		$schedConf =& Request::getSchedConf();
		$reviewMode = $this->paper->getReviewMode();
		if ($reviewMode != REVIEW_MODE_PRESENTATIONS_ALONE) {
			$returner[] = 'abstract';
		}
		return $returner;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		$schedConf =& Request::getSchedConf();
		$reviewMode = $this->paper->getReviewMode();
		$templateMgr->assign('collectAbstracts', $reviewMode != REVIEW_MODE_PRESENTATIONS_ALONE);
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
		$user =& Request::getUser();

		// Update paper
		$paper->setTitle($this->getData('title'), null); // Localized

		$reviewMode = $this->paper->getReviewMode();
		if ($reviewMode != REVIEW_MODE_PRESENTATIONS_ALONE) {
			$paper->setAbstract($this->getData('abstract'), null); // Localized
		}

		$paper->setDiscipline($this->getData('discipline'), null); // Localized
		$paper->setSubjectClass($this->getData('subjectClass'), null); // Localized
		$paper->setSubject($this->getData('subject'), null); // Localized
		$paper->setCoverageGeo($this->getData('coverageGeo'), null); // Localized
		$paper->setCoverageChron($this->getData('coverageChron'), null); // Localized
		$paper->setCoverageSample($this->getData('coverageSample'), null); // Localized
		$paper->setType($this->getData('type'), null); // Localized
		$paper->setLanguage($this->getData('language')); // Localized
		$paper->setSponsor($this->getData('sponsor'), null); // Localized

		$allowIndividualSubmissions = $schedConf->getSetting('allowIndividualSubmissions');
		$allowPanelSubmissions = $schedConf->getSetting('allowPanelSubmissions');

		$paperType = SUBMISSION_TYPE_SINGLE;
		if ($allowIndividualSubmissions && $allowPanelSubmissions) {
			if ($this->getData('paperType') == SUBMISSION_TYPE_PANEL) $paperType = SUBMISSION_TYPE_PANEL;
		} elseif (!$allowIndividualSubmissions) $paperType = SUBMISSION_TYPE_PANEL;
		$paper->setTypeConst($paperType);

		// Update the submission progress if necessary.
		if ($paper->getSubmissionProgress() <= $this->step) {
			$paper->stampStatusModified();

			// If we aren't about to collect the paper, the submission is complete
			// (for now)
			$reviewMode = $this->paper->getReviewMode();
			if($reviewMode == REVIEW_MODE_BOTH_SIMULTANEOUS || $reviewMode == REVIEW_MODE_PRESENTATIONS_ALONE) {
				$paper->setSubmissionProgress($this->step + 1);
				// The line below is necessary to ensure that
				// the paper upload goes in with the correct
				// stage number (i.e. paper).
				$paper->setCurrentStage(REVIEW_STAGE_PRESENTATION);
			} else {
				$paper->setDateSubmitted(Core::getCurrentDate());
				$paper->stampStatusModified();
				$paper->setCurrentStage(REVIEW_STAGE_ABSTRACT);
				$trackDirectors = $this->assignDirectors($paper);
				
				if ($schedConf->getSetting('acceptSupplementaryReviewMaterials')) {
					$paper->setSubmissionProgress($this->step + 2); 
				} else {
					$paper->setSubmissionProgress(0); 
					$this->confirmSubmission($paper, $user, $schedConf, $conference, 'SUBMISSION_ACK', $trackDirectors);
				}
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
				$presenter->setBiography($presenters[$i]['biography'], null); // Localized
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

		// Log the submission, even though it may not be "complete"
		// at this step. This is important because we don't otherwise
		// capture changes in review process.
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($this->paperId, PAPER_LOG_ABSTRACT_SUBMIT, LOG_TYPE_PRESENTER, $user->getUserId(), 'log.presenter.abstractSubmitted', array('submissionId' => $paper->getPaperId(), 'presenterName' => $user->getFullName()));
		return $this->paperId;
	}
}

?>
