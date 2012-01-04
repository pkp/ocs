<?php

/**
 * @file TrackForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackForm
 * @ingroup manager_form
 *
 * @brief Form for creating and modifying scheduled conference tracks.
 *
 */

// $Id$


import('form.Form');

class TrackForm extends Form {

	/** The ID of the track being edited */
	var $trackId;

	/**
	 * Constructor.
	 * @param $trackId int omit for a new track
	 */
	function TrackForm($trackId = null) {
		parent::Form('manager/tracks/trackForm.tpl');

		$conference =& Request::getConference();
		$this->trackId = $trackId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.tracks.form.titleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'abbrev', 'required', 'manager.tracks.form.abbrevRequired'));
		$this->addCheck(new FormValidatorPost($this));

		$this->addCheck(new FormValidatorCustom($this, 'reviewFormId', 'optional', 'manager.sections.form.reviewFormId', array(DAORegistry::getDAO('ReviewFormDAO'), 'reviewFormExists'), array(ASSOC_TYPE_CONFERENCE, $conference->getId())));
	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$trackDao =& DAORegistry::getDAO('TrackDAO');
		return $trackDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('trackId', $this->trackId);

		if (Request::getUserVar('assignedDirectors') != null) {
			// Reloading edit form -- get directors from form data
			$unassignedDirectorIds = explode(':', Request::getUserVar('unassignedDirectors'));
			$assignedDirectorIds = explode(':', Request::getUserVar('assignedDirectors'));

			$userDao =& DAORegistry::getDAO('UserDAO');

			// Get track directors not assigned to this track
			$unassignedDirectors = array();
			foreach ($unassignedDirectorIds as $edUserId) {
				if (!empty($edUserId)) {
					$unassignedDirectors[] =& $userDao->getUser($edUserId);
				}
			}

			// Get track directors assigned to this track
			$assignedDirectors = array();
			foreach ($assignedDirectorIds as $edUserId) {
				if (!empty($edUserId)) {
					$assignedDirectors[] =& $userDao->getUser($edUserId);
				}
			}

		} else {
			$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');

			// Get track directors not assigned to this track
			$unassignedDirectors =& $trackDirectorsDao->getDirectorsNotInTrack($schedConf->getId(), $this->trackId);

			// Get track directors assigned to this track
			$assignedDirectors =& $trackDirectorsDao->getDirectorsByTrackId($schedConf->getId(), $this->trackId);
		}

		$templateMgr->assign('unassignedDirectors', $unassignedDirectors);
		$templateMgr->assign('assignedDirectors', $assignedDirectors);
		$templateMgr->assign('commentsEnabled', $conference->getSetting('enableComments'));
		$templateMgr->assign('helpTopicId','conference.currentConferences.tracks');

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForms =& $reviewFormDao->getActiveByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getId());
		$reviewFormOptions = array();
		while ($reviewForm =& $reviewForms->next()) {
			$reviewFormOptions[$reviewForm->getId()] = $reviewForm->getLocalizedTitle();
		}
		$templateMgr->assign_by_ref('reviewFormOptions', $reviewFormOptions);

		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$conference =& Request::getConference();
		$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');
		
		if (isset($this->trackId)) {
			$trackDao =& DAORegistry::getDAO('TrackDAO');
			$track =& $trackDao->getTrack($this->trackId);

			if ($track == null) {
				unset($this->trackId);
			} else {
				$this->_data = array(
					'title' => $track->getTitle(null), // Localized
					'abbrev' => $track->getAbbrev(null), // Localized
					'reviewFormId' => $track->getReviewFormId(),
					'metaNotReviewed' => $track->getMetaReviewed()?0:1,
					'identifyType' => $track->getIdentifyType(null), // Localized
					'directorRestriction' => $track->getDirectorRestricted(),
					'policy' => $track->getPolicy(null), // Localized
					'hideAbout' => $track->getHideAbout(),
					'disableComments' => $track->getDisableComments(),
					'wordCount' => $track->getAbstractWordCount()
				);
			}
		} else {
			$this->_data = array(
				'unassignedDirectors' => $trackDirectorsDao->getDirectorsNotInTrack($conference->getId(), null)
			);

		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'abbrev', 'policy', 'reviewFormId', 'metaNotReviewed', 'identifyType', 'directorRestriction', 'hideAbout', 'disableComments', 'wordCount'));
	}

	/**
	 * Save track.
	 */
	function execute() {
		$schedConf =& Request::getSchedConf();

		$trackDao =& DAORegistry::getDAO('TrackDAO');

		if (isset($this->trackId)) {
			$track =& $trackDao->getTrack($this->trackId);
		}

		if (!isset($track)) {
			$track = new Track();
			$track->setSchedConfId($schedConf->getId());
			$track->setSequence(REALLY_BIG_NUMBER);
		}

		$track->setTitle($this->getData('title'), null); // Localized
		$track->setAbbrev($this->getData('abbrev'), null); // Localized
		$reviewFormId = $this->getData('reviewFormId');
		if ($reviewFormId === '') $reviewFormId = null;
		$track->setReviewFormId($reviewFormId);
		$track->setMetaReviewed($this->getData('metaNotReviewed') ? 0 : 1);
		$track->setIdentifyType($this->getData('identifyType'), null); // Localized
		$track->setDirectorRestricted($this->getData('directorRestriction') ? 1 : 0);
		$track->setPolicy($this->getData('policy'), null); // Localized
		$track->setHideAbout($this->getData('hideAbout'));
		$track->setDisableComments($this->getData('disableComments') ? 1 : 0);
		$track->setAbstractWordCount($this->getData('wordCount'));

		if ($track->getId() != null) {
			$trackDao->updateTrack($track);
			$trackId = $track->getId();
		} else {
			$trackId = $trackDao->insertTrack($track);
			$trackDao->resequenceTracks($schedConf->getId());
		}

		// Save assigned directors
		$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');
		$trackDirectorsDao->deleteDirectorsByTrackId($trackId, $schedConf->getId());
		$directors = explode(':', Request::getUserVar('assignedDirectors'));
		foreach ($directors as $edUserId) {
			if (!empty($edUserId)) {
				$trackDirectorsDao->insertDirector($schedConf->getId(), $trackId, $edUserId);
			}
		}
	}
}

?>
