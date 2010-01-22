<?php

/**
 * @file StatisticsHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StatisticsHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for statistics functions. 
 */

//$Id$

class StatisticsHandler extends ManagerHandler {
	/**
	 * Display a list of scheduled conference statistics.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the reader's statistics view in the About pages.
	 */
	function statistics() {
		parent::validate();
		parent::setupTemplate(true);

		$schedConf = &Request::getSchedConf();
		if (!$schedConf) Request::redirect(null, 'index');

		$templateMgr = &TemplateManager::getManager();

		$statisticsYear = Request::getUserVar('statisticsYear');
		if (empty($statisticsYear)) $statisticsYear = date('Y');
		$templateMgr->assign('statisticsYear', $statisticsYear);

		$trackIds = $schedConf->getSetting('statisticsTrackIds');
		if (!is_array($trackIds)) $trackIds = array();
		$templateMgr->assign('trackIds', $trackIds);

		foreach (StatisticsHandler::getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $schedConf->getSetting($name));
		}

		$fromDate = mktime(0, 0, 0, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$schedConfStatisticsDao =& DAORegistry::getDAO('SchedConfStatisticsDAO');
		$paperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getSchedConfId(), null, $fromDate, $toDate);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$limitedPaperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getSchedConfId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('limitedPaperStatistics', $limitedPaperStatistics);

		$limitedPaperStatistics = $schedConfStatisticsDao->getPaperStatistics($schedConf->getSchedConfId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$tracks =& $trackDao->getSchedConfTracks($schedConf->getSchedConfId());
		$templateMgr->assign('tracks', $tracks->toArray());

		$reviewerStatistics = $schedConfStatisticsDao->getReviewerStatistics($schedConf->getSchedConfId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$allUserStatistics = $schedConfStatisticsDao->getUserStatistics($schedConf->getSchedConfId(), null, $toDate);
		$templateMgr->assign('allUserStatistics', $allUserStatistics);

		$userStatistics = $schedConfStatisticsDao->getUserStatistics($schedConf->getSchedConfId(), $fromDate, $toDate);
		$templateMgr->assign('userStatistics', $userStatistics);

		$allRegistrationStatistics = $schedConfStatisticsDao->getRegistrationStatistics($schedConf->getSchedConfId(), null, $toDate);
		$templateMgr->assign('allRegistrationStatistics', $allRegistrationStatistics);

		$registrationStatistics = $schedConfStatisticsDao->getRegistrationStatistics($schedConf->getSchedConfId(), $fromDate, $toDate);
		$templateMgr->assign('registrationStatistics', $registrationStatistics);

		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notifiableUsers = $notificationStatusDao->getNotifiableUsersCount($schedConf->getSchedConfId());
		$templateMgr->assign('notifiableUsers', $notifiableUsers);

		$reportPlugins =& PluginRegistry::loadCategory('reports');
		$templateMgr->assign_by_ref('reportPlugins', $reportPlugins);

		$templateMgr->assign('helpTopicId', 'conference.currentConferences.statsReports');

		$templateMgr->display('manager/statistics/index.tpl');
	}

	function saveStatisticsTracks() {
		// The manager wants to save the list of tracks used to
		// generate statistics.

		parent::validate();

		$schedConf = &Request::getSchedConf();
		if (!$schedConf) Request::redirect(null, 'index');

		$trackIds = Request::getUserVar('trackIds');
		if (!is_array($trackIds)) {
			if (empty($trackIds)) $trackIds = array();
			else $trackIds = array($trackIds);
		}

		$schedConf->updateSetting('statisticsTrackIds', $trackIds);
		Request::redirect(null, null, 'manager', 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
	}

	function getPublicStatisticsNames() {
		return array(
			'statItemsPublished',
			'statNumSubmissions',
			'statPeerReviewed',
			'statCountAccept',
			'statCountDecline',
			'statCountRevise',
			'statDaysPerReview',
			'statDaysToPublication',
			'statRegisteredUsers',
			'statRegisteredReaders',
			'statRegistrations'
		);
	}

	function savePublicStatisticsList() {
		parent::validate();

		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect(null, 'index');

		foreach (StatisticsHandler::getPublicStatisticsNames() as $name) {
			$schedConf->updateSetting($name, Request::getUserVar($name)?true:false);
		}
		Request::redirect(null, null, 'manager', 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
	}

	function report($args) {
		parent::validate();

		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect(null, 'index');

		$pluginName = array_shift($args);
		$reportPlugins =& PluginRegistry::loadCategory('reports');

		if ($pluginName == '' || !isset($reportPlugins[$pluginName])) {
			Request::redirect(null, null, null, 'statistics');
		}

		$plugin =& $reportPlugins[$pluginName];
		$plugin->display($args);
	}
}

?>
