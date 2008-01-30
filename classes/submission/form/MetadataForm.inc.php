<?php

/**
 * @file MetadataForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 * @class MetadataForm
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

	/** @var boolean can view presenters */
	var $canViewPresenters;

	/**
	 * Constructor.
	 */
	function MetadataForm($paper) {
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();
		$roleId = $roleDao->getRoleIdFromPath(Request::getRequestedPage());

		// If the user is a director of this paper, make the form editable.
		$this->canEdit = false;
		if ($roleId != null && ($roleId == ROLE_ID_DIRECTOR || $roleId == ROLE_ID_TRACK_DIRECTOR)) {
			$this->canEdit = true;
		}

		// Check if the presenter can modify metadata.
		if ($roleId == ROLE_ID_PRESENTER) {
			if(PresenterAction::mayEditPaper($paper)) {
				$this->canEdit = true;
			}
		}

		if ($this->canEdit) {
			parent::Form('submission/metadata/metadataEdit.tpl');
			$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'presenter.submit.form.titleRequired'));
		} else {
			parent::Form('submission/metadata/metadataView.tpl');
		}

		// If the user is a reviewer of this paper, do not show presenters.
		$this->canViewPresenters = true;
		if ($roleId != null && $roleId == ROLE_ID_REVIEWER) {
			$this->canViewPresenters = false;
		}

		$this->paper = $paper;

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current paper.
	 */
	function initData() {
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
				'language' => $paper->getLanguage(),
				'sponsor' => $paper->getSponsor(null) // Localized
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
						'countryLocalized' => $presenters[$i]->getCountryLocalized(),
						'email' => $presenters[$i]->getEmail(),
						'url' => $presenters[$i]->getUrl(),
						'biography' => $presenters[$i]->getBiography(null) // Localized
					)
				);
				if ($presenters[$i]->getPrimaryContact()) {
					$this->setData('primaryContact', $i);
				}
			}
		}
	}

	/**
	 * Get the field names for which data can be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'abstract', 'subjectClass', 'subject', 'coverageGeo', 'coverageChron', 'coverageSample', 'type', 'sponsor');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$schedConf = &Request::getSchedConf();
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$trackDao = &DAORegistry::getDAO('TrackDAO');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', isset($this->paper)?$this->paper->getPaperId():null);
		$templateMgr->assign('schedConfSettings', $schedConf->getSettings(true));
		$templateMgr->assign('rolePath', Request::getRequestedPage());
		$templateMgr->assign('canViewPresenters', $this->canViewPresenters);

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$templateMgr->assign('countries', $countryDao->getCountries());

		$disciplineDao =& DAORegistry::getDAO('DisciplineDAO');
		$templateMgr->assign('disciplines', $disciplineDao->getDisciplines());

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
				'presenters',
				'deletedPresenters',
				'primaryContact',
				'title',
				'abstract',
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
		$presenterDao = &DAORegistry::getDAO('PresenterDAO');
		$trackDao = &DAORegistry::getDAO('TrackDAO');

		// Update paper

		$paper = &$this->paper;
		$paper->setTitle($this->getData('title'), null); // Localized

		$track = &$trackDao->getTrack($paper->getTrackId());
		$paper->setAbstract($this->getData('abstract'), null); // Localized

		$paper->setDiscipline($this->getData('discipline'), null); // Localized
		$paper->setSubjectClass($this->getData('subjectClass'), null); // Localized
		$paper->setSubject($this->getData('subject'), null); // Localized
		$paper->setCoverageGeo($this->getData('coverageGeo'), null); // Localized
		$paper->setCoverageChron($this->getData('coverageChron'), null); // Localized
		$paper->setCoverageSample($this->getData('coverageSample'), null); // Localized
		$paper->setType($this->getData('type'), null); // Localized
		$paper->setLanguage($this->getData('language'));
		$paper->setSponsor($this->getData('sponsor'), null); // Localized

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

		// Update search index
		import('search.PaperSearchIndex');
		PaperSearchIndex::indexPaperMetadata($paper);

		return $paper->getPaperId();
	}

	/**
	 * Determine whether or not the current user is allowed to edit metadata.
	 * @return boolean
	 */
	function getCanEdit() {
		return $this->canEdit;
	}
}

?>
