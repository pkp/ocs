<?php

/**
 * @file pages/manager/TrackHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for track management functions. 
 */


import('pages.manager.ManagerHandler');

class TrackHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function TrackHandler() {
		parent::ManagerHandler();
	}
	/**
	 * Display a list of the tracks within the current conference.
	 */
	function tracks($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$schedConf =& $request->getSchedConf();
		$rangeInfo =& Handler::getRangeInfo($request, 'tracks', array());
		$trackDao = DAORegistry::getDAO('TrackDAO');
		while (true) {
			$tracks =& $trackDao->getSchedConfTracks($schedConf->getId(), $rangeInfo);
			if ($tracks->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $tracks->getLastPageRangeInfo();
			unset($tracks);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('pageHierarchy', array(array($request->url(null, null, 'manager'), 'manager.schedConfManagement')));
		$templateMgr->assign_by_ref('tracks', $tracks);
		$templateMgr->assign('helpTopicId','conference.currentConferences.tracks');
		$templateMgr->display('manager/tracks/tracks.tpl');
	}

	/**
	 * Display form to create a new track.
	 */
	function createTrack($args, &$request) {
		$this->editTrack($args, $request);
	}

	/**
	 * Display form to create/edit a track.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editTrack($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.TrackForm');

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
	function updateTrack($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.TrackForm');

		$trackForm = new TrackForm($request->getUserVar('trackId'));
		$trackForm->readInputData();

		if ($trackForm->validate()) {
			$trackForm->execute();
			$request->redirect(null, null, null, 'tracks');
		} else {
			$trackForm->display();
		}
	}

	/**
	 * Delete a track.
	 * @param $args array first parameter is the ID of the track to delete
	 */
	function deleteTrack($args, &$request) {
		$this->validate();

		$schedConf =& $request->getSchedConf();
		if (isset($args) && !empty($args)) {
			$trackDao = DAORegistry::getDAO('TrackDAO');
			$trackDao->deleteTrackById($args[0], $schedConf->getId());
		}

		$request->redirect(null, null, null, 'tracks');
	}

	/**
	 * Change the sequence of a track.
	 */
	function moveTrack($args, &$request) {
		$this->validate();

		$schedConf =& $request->getSchedConf();
		$trackDao = DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($request->getUserVar('trackId'), $schedConf->getId());

		if ($track != null) {
			$track->setSequence($track->getSequence() + ($request->getUserVar('d') == 'u' ? -1.5 : 1.5));
			$trackDao->updateTrack($track);
			$trackDao->resequenceTracks($schedConf->getId());
		}

		$request->redirect(null, null, null, 'tracks');
	}

	function setupTemplate($request, $subclass = false){
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);
		parent::setupTemplate($request, true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'tracks'), 'track.tracks'));
		}
	}
}

?>
