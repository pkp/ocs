<?php

/**
 * TrackForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
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
		$this->addCheck(new FormValidator($this, 'title', 'required', 'manager.tracks.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'abbrev', 'required', 'manager.tracks.form.abbrevRequired'));
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
					'title' => $track->getTitle(),
					'titleAlt1' => $track->getTitleAlt1(),
					'titleAlt2' => $track->getTitleAlt2(),
					'abbrev' => $track->getAbbrev(),
					'abbrevAlt1' => $track->getAbbrevAlt1(),
					'abbrevAlt2' => $track->getAbbrevAlt2(),
					'metaIndexed' => $track->getMetaIndexed(),
					'metaNotReviewed' => $track->getMetaReviewed()?0:1,
					'identifyType' => $track->getIdentifyType(),
					'directorRestriction' => $track->getDirectorRestricted(),
					'policy' => $track->getPolicy()
				);
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'titleAlt1', 'titleAlt2', 'abbrev', 'abbrevAlt1', 'abbrevAlt2', 'metaNotReviewed', 'metaIndexed', 'identifyType', 'directorRestriction', 'policy'));
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
			// Kludge: Move this track to the end of the list
			$track->setSequence(10000);
		}
		
		$track->setTitle($this->getData('title'));
		$track->setTitleAlt1($this->getData('titleAlt1'));
		$track->setTitleAlt2($this->getData('titleAlt2'));
		$track->setAbbrev($this->getData('abbrev'));
		$track->setAbbrevAlt1($this->getData('abbrevAlt1'));
		$track->setAbbrevAlt2($this->getData('abbrevAlt2'));
		$track->setMetaReviewed($this->getData('metaNotReviewed') ? 0 : 1);
		$track->setMetaIndexed($this->getData('metaIndexed') ? 1 : 0);
		$track->setIdentifyType($this->getData('identifyType'));
		$track->setDirectorRestricted($this->getData('directorRestriction') ? 1 : 0);
		$track->setPolicy($this->getData('policy'));
		
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
