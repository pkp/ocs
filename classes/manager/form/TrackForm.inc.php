<?php

/**
 * @file TrackForm.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class TrackForm
 *
 * Form for creating and modifying scheduled conference tracks.
 *
 * $Id$
 */

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

		$this->trackId = $trackId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.tracks.form.titleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'abbrev', 'required', 'manager.tracks.form.abbrevRequired'));
		$this->addCheck(new FormValidatorPost($this));
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
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('trackId', $this->trackId);

		if (Request::getUserVar('assignedDirectors') != null) {
			// Reloading edit form -- get directors from form data
			$unassignedDirectorIds = explode(':', Request::getUserVar('unassignedDirectors'));
			$assignedDirectorIds = explode(':', Request::getUserVar('assignedDirectors'));

			$userDao = &DAORegistry::getDAO('UserDAO');

			// Get track directors not assigned to this track
			$unassignedDirectors = array();
			foreach ($unassignedDirectorIds as $edUserId) {
				if (!empty($edUserId)) {
					$unassignedDirectors[] = &$userDao->getUser($edUserId);
				}
			}

			// Get track directors assigned to this track
			$assignedDirectors = array();
			foreach ($assignedDirectorIds as $edUserId) {
				if (!empty($edUserId)) {
					$assignedDirectors[] = &$userDao->getUser($edUserId);
				}
			}

		} else {
			$schedConf = &Request::getSchedConf();
			$trackDirectorsDao = &DAORegistry::getDAO('TrackDirectorsDAO');

			// Get track directors not assigned to this track
			$unassignedDirectors = &$trackDirectorsDao->getDirectorsNotInTrack($schedConf->getSchedConfId(), $this->trackId);

			// Get track directors assigned to this track
			$assignedDirectors = &$trackDirectorsDao->getDirectorsByTrackId($schedConf->getSchedConfId(), $this->trackId);
		}

		$templateMgr->assign('unassignedDirectors', $unassignedDirectors);
		$templateMgr->assign('assignedDirectors', $assignedDirectors);
		$templateMgr->assign('helpTopicId','conference.managementPages.tracks');

		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->trackId)) {
			$trackDao = &DAORegistry::getDAO('TrackDAO');
			$track = &$trackDao->getTrack($this->trackId);

			if ($track == null) {
				unset($this->trackId);
			} else {
				$this->_data = array(
					'title' => $track->getTitle(null), // Localized
					'abbrev' => $track->getAbbrev(null), // Localized
					'metaNotReviewed' => $track->getMetaReviewed()?0:1,
					'identifyType' => $track->getIdentifyType(null), // Localized
					'directorRestriction' => $track->getDirectorRestricted(),
					'policy' => $track->getPolicy(null), // Localized
					'hideAbout' => $track->getHideAbout()
				);
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'abbrev', 'metaNotReviewed', 'identifyType', 'directorRestriction', 'policy', 'hideAbout'));
	}

	/**
	 * Save track.
	 */
	function execute() {
		$schedConf = &Request::getSchedConf();

		$trackDao = &DAORegistry::getDAO('TrackDAO');

		if (isset($this->trackId)) {
			$track = &$trackDao->getTrack($this->trackId);
		}

		if (!isset($track)) {
			$track = &new Track();
			$track->setSchedConfId($schedConf->getSchedConfId());
			$track->setSequence(REALLY_BIG_NUMBER);
		}

		$track->setTitle($this->getData('title'), null); // Localized
		$track->setAbbrev($this->getData('abbrev'), null); // Localized
		$track->setMetaReviewed($this->getData('metaNotReviewed') ? 0 : 1);
		$track->setIdentifyType($this->getData('identifyType'), null); // Localized
		$track->setDirectorRestricted($this->getData('directorRestriction') ? 1 : 0);
		$track->setPolicy($this->getData('policy'), null); // Localized
		$track->setHideAbout($this->getData('hideAbout'));

		if ($track->getTrackId() != null) {
			$trackDao->updateTrack($track);
			$trackId = $track->getTrackId();
		} else {
			$trackId = $trackDao->insertTrack($track);
			$trackDao->resequenceTracks($schedConf->getSchedConfId());
		}

		// Save assigned directors
		$trackDirectorsDao = &DAORegistry::getDAO('TrackDirectorsDAO');
		$trackDirectorsDao->deleteDirectorsByTrackId($trackId, $schedConf->getSchedConfId());
		$directors = explode(':', Request::getUserVar('assignedDirectors'));
		foreach ($directors as $edUserId) {
			if (!empty($edUserId)) {
				$trackDirectorsDao->insertDirector($schedConf->getSchedConfId(), $trackId, $edUserId);
			}
		}
	}
}

?>
