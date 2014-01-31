<?php

/**
 * @file TrackHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for track management functions. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class TrackHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function TrackHandler() {
		parent::ManagerHandler();
	}
	/**
	 * Display a list of the tracks within the current conference.
	 */
	function tracks() {
		$this->validate();
		$this->setupTemplate();

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('tracks', array());
		$trackDao =& DAORegistry::getDAO('TrackDAO');
		while (true) {
			$tracks =& $trackDao->getSchedConfTracks($schedConf->getId(), $rangeInfo);
			if ($tracks->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $tracks->getLastPageRangeInfo();
			unset($tracks);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'manager'), 'manager.schedConfManagement')));
		$templateMgr->assign_by_ref('tracks', $tracks);
		$templateMgr->assign('helpTopicId','conference.currentConferences.tracks');
		$templateMgr->display('manager/tracks/tracks.tpl');
	}

	/**
	 * Display form to create a new track.
	 */
	function createTrack() {
		$this->editTrack();
	}

	/**
	 * Display form to create/edit a track.
	 * @param $args array optional, if set the first parameter is the ID of the track to edit
	 */
	function editTrack($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

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
		$this->setupTemplate(true);

		import('manager.form.TrackForm');

		$trackForm = new TrackForm(Request::getUserVar('trackId'));
		$trackForm->readInputData();

		if ($trackForm->validate()) {
			$trackForm->execute();
			Request::redirect(null, null, null, 'tracks');
		} else {
			$trackForm->display();
		}
	}

	/**
	 * Delete a track.
	 * @param $args array first parameter is the ID of the track to delete
	 */
	function deleteTrack($args) {
		$this->validate();

		$schedConf =& Request::getSchedConf();
		if (isset($args) && !empty($args)) {
			$trackDao =& DAORegistry::getDAO('TrackDAO');
			$trackDao->deleteTrackById($args[0], $schedConf->getId());
		}

		Request::redirect(null, null, null, 'tracks');
	}

	/**
	 * Change the sequence of a track.
	 */
	function moveTrack() {
		$this->validate();

		$schedConf =& Request::getSchedConf();
		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack(Request::getUserVar('trackId'), $schedConf->getId());

		if ($track != null) {
			$track->setSequence($track->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$trackDao->updateTrack($track);
			$trackDao->resequenceTracks($schedConf->getId());
		}

		Request::redirect(null, null, null, 'tracks');
	}

	function setupTemplate($subclass = false){
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER));
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'tracks'), 'track.tracks'));
		}
	}
}

?>
