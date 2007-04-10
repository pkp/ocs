<?php

/**
 * StatisticsHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for statistics functions. 
 *
 * $Id$
 */

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
		$fromDate = mktime(0, 0, 1, 1, 1, $statisticsYear);
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
		
		//$issueStatistics = $schedConfStatisticsDao->getIssueStatistics($schedConf->getSchedConfId(), $fromDate, $toDate);
		//$templateMgr->assign('issueStatistics', $issueStatistics);

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

		$templateMgr->assign('reportTypes', array(
			REPORT_TYPE_CONFERENCE => 'manager.statistics.reports.type.conference',
			REPORT_TYPE_SCHED_CONF => 'manager.statistics.reports.type.schedConf',
			REPORT_TYPE_DIRECTOR => 'manager.statistics.reports.type.director',
			REPORT_TYPE_REVIEWER => 'manager.statistics.reports.type.reviewer',
			REPORT_TYPE_TRACK => 'manager.statistics.reports.type.track'
		));

		$templateMgr->assign('helpTopicId', 'schedConf.managementPages.statsAndReports');

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
			'statRegistration'
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
	
	function csvEscape($value) {
		$value = str_replace('"', '""', $value);
		return '"' . $value . '"';
	}

	/* --- Deferred for now ---
	function reportGenerator($args) {
		parent::validate();

		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect(null, 'index');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$schedConfStatisticsDao =& DAORegistry::getDAO('SchedConfStatisticsDAO');

		$reportType = (int) Request::getUserVar('reportType');

		switch ($reportType) {
			case REPORT_TYPE_DIRECTOR:
				$report =& $schedConfStatisticsDao->getDirectorReport($schedConf->getSchedConfId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_REVIEWER:
				$report =& $schedConfStatisticsDao->getReviewerReport($schedConf->getSchedConfId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_TRACK:
				$report =& $schedConfStatisticsDao->getTrackReport($schedConf->getSchedConfId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_SCHED_CONF:
			default:
				$reportType = REPORT_TYPE_SCHED_CONF;
				$report =& $schedConfStatisticsDao->getSchedConfReport($schedConf->getSchedConfId(), $fromDate, $toDate);
				break;
		}

		$templateMgr =& TemplateManager::getManager();
		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$separator = ',';

		// Display the heading row.
		switch ($reportType) {
			case REPORT_TYPE_DIRECTOR:
				echo Locale::translate('user.role.director') . $separator;
				break;
			case REPORT_TYPE_REVIEWER:
				echo Locale::translate('user.role.reviewer') . $separator;
				echo Locale::translate('manager.statistics.reports.singleScore') . $separator;
				echo Locale::translate('user.affiliation') . $separator;
				break;
			case REPORT_TYPE_TRACK:
				echo Locale::translate('track.track') . $separator;
				break;
		}

		echo Locale::translate('paper.submissionId');
		for ($i=0; $i<$report->getMaxPresenters(); $i++) {
			echo $separator . Locale::translate('manager.statistics.reports.presenter', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.affiliation', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.country', array('num' => $i+1));
		}
		echo $separator . Locale::translate('paper.title');

		if ($reportType !== REPORT_TYPE_TRACK) echo $separator . Locale::translate('track.track');

		echo $separator . Locale::translate('submissions.submitted');

		if ($reportType !== REPORT_TYPE_DIRECTOR) for ($i=0; $i<$report->getMaxDirectors(); $i++) {
			echo $separator . Locale::translate('manager.statistics.reports.director', array('num' => $i+1));
		}

		if ($reportType !== REPORT_TYPE_REVIEWER) for ($i=0; $i<$report->getMaxReviewers(); $i++) {
			echo $separator . Locale::translate('manager.statistics.reports.reviewer', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.score', array('num' => $i+1));
			echo $separator . Locale::translate('manager.statistics.reports.recommendation', array('num' => $i+1));
		}

		echo $separator . Locale::translate('director.paper.decision');
		echo $separator . Locale::translate('manager.statistics.reports.daysToDecision');
		echo $separator . Locale::translate('manager.statistics.reports.daysToPublication');

		echo "\n";

		// Display the report.
		$dateFormatShort = Config::getVar('general', 'date_format_short');
		while ($row =& $report->next()) {
			switch ($reportType) {
				case REPORT_TYPE_DIRECTOR:
					echo $row['director'] . $separator;
					break;
				case REPORT_TYPE_REVIEWER:
					echo $row['reviewer'] . $separator;
					echo $row['score'] . $separator;
					echo $row['affiliation'] . $separator;
					break;
				case REPORT_TYPE_TRACK:
					echo $row['track'] . $separator;
					break;
			}

			echo $row['paperId'];

			for ($i=0; $i<$report->getMaxPresenters(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['presenters'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['affiliations'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['countries'][$i]);
			}

			echo $separator . StatisticsHandler::csvEscape($row['title']);

			if ($reportType !== REPORT_TYPE_TRACK) echo $separator . StatisticsHandler::csvEscape($row['track']);

			echo $separator . $row['dateSubmitted'];

			if ($reportType !== REPORT_TYPE_DIRECTOR) for ($i=0; $i<$report->getMaxDirectors(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['directors'][$i]);
			}

			if ($reportType !== REPORT_TYPE_REVIEWER) for ($i=0; $i<$report->getMaxReviewers(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['reviewers'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['scores'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['recommendations'][$i]);
			}

			echo $separator . StatisticsHandler::csvEscape($row['decision']);
			echo $separator . StatisticsHandler::csvEscape($row['daysToDecision']);
			echo $separator . StatisticsHandler::csvEscape($row['daysToPublication']);
			echo "\n";
		}
	} */
}

?>
