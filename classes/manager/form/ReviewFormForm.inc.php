<?php

/**
 * @file ReviewFormForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class ReviewFormForm
 *
 * Form for creating and modifying review forms.
 *
 */

import('form.Form');

class ReviewFormForm extends Form {

	/** @var $reviewFormId int The ID of the review form being edited */
	var $reviewFormId;

	/**
	 * Constructor.
	 * @param $reviewFormId int
	 */
	function ReviewFormForm($reviewFormId = null) {
		parent::Form('manager/reviewForms/reviewFormForm.tpl');

		$this->reviewFormId = $reviewFormId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.reviewForms.form.titleRequired'));
		$this->addCheck(new FormValidatorPost($this));

	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		return $reviewFormDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('reviewFormId', $this->reviewFormId);
		$templateMgr->assign('helpTopicId','conference.managementPages.reviewForms');
		parent::display();
	}

	/**
	 * Initialize form data from current review form.
	 */
	function initData() {
		if ($this->reviewFormId != null) {
			$conference =& Request::getConference();
			$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm =& $reviewFormDao->getReviewForm($this->reviewFormId, ASSOC_TYPE_CONFERENCE, $conference->getId());

			if ($reviewForm == null) {
				$this->reviewFormId = null;
			} else {
				$this->_data = array(
					'title' => $reviewForm->getTitle(null), // Localized
					'description' => $reviewForm->getDescription(null) // Localized
				);
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'description'));
	}

	/**
	 * Save review form.
	 */
	function execute() {
		$conference =& Request::getConference();
		$conferenceId = $conference->getId();

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

		if ($this->reviewFormId != null) {
			$reviewForm =& $reviewFormDao->getReviewForm($this->reviewFormId, ASSOC_TYPE_CONFERENCE, $conferenceId);
		}

		if (!isset($reviewForm)) {
			$reviewForm = $reviewFormDao->newDataObject();

			$reviewForm->setAssocType(ASSOC_TYPE_CONFERENCE);
			$reviewForm->setAssocId($conferenceId);
			$reviewForm->setActive(0);
			$reviewForm->setSequence(REALLY_BIG_NUMBER);
		}

		$reviewForm->setTitle($this->getData('title'), null); // Localized
		$reviewForm->setDescription($this->getData('description'), null); // Localized

		if ($reviewForm->getId() != null) {
			$reviewFormDao->updateObject($reviewForm);
			$reviewFormId = $reviewForm->getId();
		} else {
			$reviewFormId = $reviewFormDao->insertObject($reviewForm);
			$reviewFormDao->resequenceReviewForms(ASSOC_TYPE_CONFERENCE, $conferenceId);
		}
	}
}

?>
