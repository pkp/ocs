<?php

/**
 * @file TrackHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for track management functions. 
 */

//$Id$

class TrackHandler extends ManagerHandler {

	/**
	 * Display a list of the tracks within the current conference.
	 */
	function tracks() {
		list($conference, $schedConf) = parent::validate();
		parent::setupTemplate(true);

		$rangeInfo =& PKPHandler::getRangeInfo('tracks', array());
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		while (true) {
			$tracks = &$trackDao->getSchedConfTracks($schedConf->getSchedConfId(), $rangeInfo);
			if ($tracks->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $tracks->getLastPageRangeInfo();
			unset($tracks);
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'manager'), 'manager.schedConfManagement')));
		$templateMgr->assign_by_ref('tracks', $tracks);
		$templateMgr->assign('helpTopicId','conference.currentConferences.tracks');
		$templateMgr->display('manager/tracks/tracks.tpl');
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

		import('manager.form.TrackForm');

		$trackForm = new TrackForm(!isset($args) || empty($args) ? null : $args[0]);
		if ($trackForm->isLocaleResubmit()) {
			$trackForm->readInputData();
		} else {
			$trackForm->initData();
		}
		$trackForm->display();
	}

	/**
	 * Save changes to a track.
	 */
	function updateTrack() {
		parent::validate();

		import('manager.form.TrackForm');

		$trackForm = new TrackForm(Request::getUserVar('trackId'));
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
		list($conference, $schedConf) = parent::validate();

		if (isset($args) && !empty($args)) {
			$trackDao = &DAORegistry::getDAO('TrackDAO');
			$trackDao->deleteTrackById($args[0], $schedConf->getSchedConfId());
		}

		Request::redirect(null, null, null, 'tracks');
	}

	/**
	 * Change the sequence of a track.
	 */
	function moveTrack() {
		list($conference, $schedConf) = parent::validate();

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack(Request::getUserVar('trackId'), $schedConf->getSchedConfId());

		if ($track != null) {
			$track->setSequence($track->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$trackDao->updateTrack($track);
			$trackDao->resequenceTracks($schedConf->getSchedConfId());
		}

		Request::redirect(null, null, null, 'tracks');
	}

}
?>
