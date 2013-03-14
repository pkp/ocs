<?php

/**
 * @file StatisticsHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StatisticsHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for statistics functions.
 */


import('pages.manager.ManagerHandler');

class StatisticsHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function StatisticsHandler() {
		parent::ManagerHandler();
	}
	/**
	 * Display a list of scheduled conference statistics.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the reader's statistics view in the About pages.
	 */
	function statistics($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		if (!$schedConf) $request->redirect(null, 'index');

		$templateMgr =& TemplateManager::getManager($request);

		$trackIds = $schedConf->getSetting('statisticsTrackIds');
		if (!is_array($trackIds)) $trackIds = array();
		$templateMgr->assign('trackIds', $trackIds);

		foreach ($this->_getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $schedConf->getSetting($name));
		}

		$schedConfStatisticsDao = DAORegistry::getDAO('SchedConfStatisticsDAO');
		$paperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getId(), null);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$limitedPaperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getId(), $trackIds);
		$templateMgr->assign('limitedPaperStatistics', $limitedPaperStatistics);

		$trackDao = DAORegistry::getDAO('TrackDAO');
		$tracks =& $trackDao->getSchedConfTracks($schedConf->getId());
		$templateMgr->assign('tracks', $tracks->toArray());

		$reviewerStatistics = $schedConfStatisticsDao->getReviewerStatistics($schedConf->getId(), $trackIds);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$userStatistics = $schedConfStatisticsDao->getUserStatistics($schedConf->getId());
		$templateMgr->assign('userStatistics', $userStatistics);

		$registrationStatistics = $schedConfStatisticsDao->getRegistrationStatistics($schedConf->getId());
		$templateMgr->assign('registrationStatistics', $registrationStatistics);

		$reportPlugins =& PluginRegistry::loadCategory('reports');
		$templateMgr->assign_by_ref('reportPlugins', $reportPlugins);

		$templateMgr->assign('helpTopicId', 'conference.currentConferences.statsReports');

		$templateMgr->display('manager/statistics/index.tpl');
	}

	function saveStatisticsTracks($args, &$request) {
		// The manager wants to save the list of tracks used to
		// generate statistics.

		$this->validate();

		$schedConf =& $request->getSchedConf();
		if (!$schedConf) $request->redirect(null, 'index');

		$trackIds = $request->getUserVar('trackIds');
		if (!is_array($trackIds)) {
			if (empty($trackIds)) $trackIds = array();
			else $trackIds = array($trackIds);
		}

		$schedConf->updateSetting('statisticsTrackIds', $trackIds);
		$request->redirect(null, null, 'manager', 'statistics', null, array('statisticsYear' => $request->getUserVar('statisticsYear')));
	}

	function _getPublicStatisticsNames() {
		return array(
			'statItemsPublished',
			'statNumSubmissions',
			'statPeerReviewed',
			'statCountAccept',
			'statCountDecline',
			'statCountRevise',
			'statDaysPerReview',
			'statRegisteredUsers',
			'statRegisteredReaders',
			'statRegistrations'
		);
	}

	function savePublicStatisticsList($args, &$request) {
		$this->validate();

		$schedConf =& $request->getSchedConf();
		if (!$schedConf) $request->redirect(null, 'index');

		foreach ($this->_getPublicStatisticsNames() as $name) {
			$schedConf->updateSetting($name, $request->getUserVar($name)?true:false);
		}
		$request->redirect(null, null, 'manager', 'statistics', null, array('statisticsYear' => $request->getUserVar('statisticsYear')));
	}

	function report($args, &$request) {
		$this->validate();

		$schedConf =& $request->getSchedConf();
		if (!$schedConf) $request->redirect(null, 'index');

		$pluginName = array_shift($args);
		$reportPlugins =& PluginRegistry::loadCategory('reports');

		if ($pluginName == '' || !isset($reportPlugins[$pluginName])) {
			$request->redirect(null, null, null, 'statistics');
		}

		$plugin =& $reportPlugins[$pluginName];
		$plugin->display($args);
	}
}

?>
