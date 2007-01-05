<?php

/**
 * TrackForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package eventDirector.form
 *
 * Form for creating and modifying event tracks.
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
		parent::Form('eventDirector/tracks/trackForm.tpl');
		
		$this->trackId = $trackId;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'title', 'required', 'director.tracks.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'abbrev', 'required', 'director.tracks.form.abbrevRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('trackId', $this->trackId);
		
		if (Request::getUserVar('assignedEditors') != null) {
			// Reloading edit form -- get editors from form data
			$unassignedEditorIds = explode(':', Request::getUserVar('unassignedEditors'));
			$assignedEditorIds = explode(':', Request::getUserVar('assignedEditors'));
			
			$userDao = &DAORegistry::getDAO('UserDAO');
			
			// Get track editors not assigned to this track
			$unassignedEditors = array();
			foreach ($unassignedEditorIds as $edUserId) {
				if (!empty($edUserId)) {
					$unassignedEditors[] = &$userDao->getUser($edUserId);
				}
			}
			
			// Get track editors assigned to this track
			$assignedEditors = array();
			foreach ($assignedEditorIds as $edUserId) {
				if (!empty($edUserId)) {
					$assignedEditors[] = &$userDao->getUser($edUserId);
				}
			}
			
		} else {
			$event = &Request::getEvent();
			$trackEditorsDao = &DAORegistry::getDAO('TrackEditorsDAO');
			
			// Get track editors not assigned to this track
			$unassignedEditors = &$trackEditorsDao->getEditorsNotInTrack($event->getEventId(), $this->trackId);
			
			// Get track editors assigned to this track
			$assignedEditors = &$trackEditorsDao->getEditorsByTrackId($event->getEventId(), $this->trackId);
		}
		
		$templateMgr->assign('unassignedEditors', $unassignedEditors);
		$templateMgr->assign('assignedEditors', $assignedEditors);
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
					'identifyType' => $track->getIdentifyType(),
					'editorRestriction' => $track->getEditorRestricted(),
					'hideTitle' => $track->getHideTitle(),
					'policy' => $track->getPolicy()
				);
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'titleAlt1', 'titleAlt2', 'abbrev', 'abbrevAlt1', 'abbrevAlt2', 'metaIndexed', 'identifyType', 'editorRestriction', 'hideTitle', 'policy'));
	}
	
	/**
	 * Save track.
	 */
	function execute() {
		$event = &Request::getEvent();
			
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		
		if (isset($this->trackId)) {
			$track = &$trackDao->getTrack($this->trackId);
		}
		
		if (!isset($track)) {
			$track = &new Track();
			$track->setEventId($event->getEventId());
			// Kludge: Move this track to the end of the list
			$track->setSequence(10000);
		}
		
		$track->setTitle($this->getData('title'));
		$track->setTitleAlt1($this->getData('titleAlt1'));
		$track->setTitleAlt2($this->getData('titleAlt2'));
		$track->setAbbrev($this->getData('abbrev'));
		$track->setAbbrevAlt1($this->getData('abbrevAlt1'));
		$track->setAbbrevAlt2($this->getData('abbrevAlt2'));
		$track->setMetaIndexed($this->getData('metaIndexed') ? 1 : 0);
		$track->setIdentifyType($this->getData('identifyType'));
		$track->setEditorRestricted($this->getData('editorRestriction') ? 1 : 0);
		$track->setHideTitle($this->getData('hideTitle') ? 1 : 0);
		$track->setPolicy($this->getData('policy'));
		
		if ($track->getTrackId() != null) {
			$trackDao->updateTrack($track);
			$trackId = $track->getTrackId();
		} else {
			$trackId = $trackDao->insertTrack($track);
			$trackDao->resequenceTracks($event->getEventId());
		}
		
		// Save assigned editors
		$trackEditorsDao = &DAORegistry::getDAO('TrackEditorsDAO');
		$trackEditorsDao->deleteEditorsByTrackId($trackId, $event->getEventId());
		$editors = explode(':', Request::getUserVar('assignedEditors'));
		foreach ($editors as $edUserId) {
			if (!empty($edUserId)) {
				$trackEditorsDao->insertEditor($event->getEventId(), $trackId, $edUserId);
			}
		}
	}
	
}

?>
