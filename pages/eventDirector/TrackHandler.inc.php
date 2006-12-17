<?php

/**
 * TrackHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for track management functions. 
 *
 * $Id$
 */

class TrackHandler extends EventDirectorHandler {

	/**
	 * Display a list of the tracks within the current conference.
	 */
	function tracks() {
		list($conference, $event) = parent::validate();
		parent::setupTemplate(true);

		$rangeInfo = &Handler::getRangeInfo('tracks');
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$tracks = &$trackDao->getEventTracks($event->getEventId(), $rangeInfo);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'eventDirector'), 'director.eventManagement')));
		$templateMgr->assign_by_ref('tracks', $tracks);
		$templateMgr->assign('helpTopicId','conference.managementPages.tracks');
		$templateMgr->display('eventDirector/tracks/tracks.tpl');
	}
	
	/**
	 * Display form to create a new track.
	 */
	function createTrack() {
		TrackHandler::editTrack();
	}
	
	/**
	 * Display form to create/edit a track.
	 * @param $args array optional, if set the first parameter is the ID of the track to edit
	 */
	function editTrack($args = array()) {
		parent::validate();
		parent::setupTemplate(true);
		
		import('eventDirector.form.TrackForm');
		
		$trackForm = &new TrackForm(!isset($args) || empty($args) ? null : $args[0]);
		$trackForm->initData();
		$trackForm->display();
	}
	
	/**
	 * Save changes to a track.
	 */
	function updateTrack() {
		parent::validate();
		
		import('eventDirector.form.TrackForm');
		
		$trackForm = &new TrackForm(Request::getUserVar('trackId'));
		$trackForm->readInputData();
		
		if ($trackForm->validate()) {
			$trackForm->execute();
			Request::redirect(null, null, null, 'tracks');
			
		} else {
			parent::setupTemplate(true);
			$trackForm->display();
		}
	}
	
	/**
	 * Delete a track.
	 * @param $args array first parameter is the ID of the track to delete
	 */
	function deleteTrack($args) {
		list($conference, $event) = parent::validate();
		
		if (isset($args) && !empty($args)) {
			$trackDao = &DAORegistry::getDAO('TrackDAO');
			$trackDao->deleteTrackById($args[0], $event->getEventId());
		}
		
		Request::redirect(null, null, null, 'tracks');
	}
	
	/**
	 * Change the sequence of a track.
	 */
	function moveTrack() {
		list($conference, $event) = parent::validate();
		
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack(Request::getUserVar('trackId'), $event->getEventId());
		
		if ($track != null) {
			$track->setSequence($track->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$trackDao->updateTrack($track);
			$trackDao->resequenceTracks($event->getEventId());
		}
		
		Request::redirect(null, null, null, 'tracks');
	}
	
}
?>
