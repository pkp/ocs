<?php

/**
 * StatisticsHandler.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.eventDirector
 *
 * Handle requests for statistics functions. 
 *
 * $Id$
 */

class StatisticsHandler extends EventDirectorHandler {
	/**
	 * Display a list of event statistics.
	 * WARNING: This implementation should be kept roughly synchronized
	 * with the reader's statistics view in the About pages.
	 */
	function statistics() {
		parent::validate();
		parent::setupTemplate(true);

		$event = &Request::getEvent();
		$templateMgr = &TemplateManager::getManager();

		$statisticsYear = Request::getUserVar('statisticsYear');
		if (empty($statisticsYear)) $statisticsYear = date('Y');
		$templateMgr->assign('statisticsYear', $statisticsYear);

		$trackIds = $event->getSetting('statisticsTrackIds');
		if (!is_array($trackIds)) $trackIds = array();
		$templateMgr->assign('trackIds', $trackIds);

		foreach (StatisticsHandler::getPublicStatisticsNames() as $name) {
			$templateMgr->assign($name, $event->getSetting($name));
		}
		$fromDate = mktime(0, 0, 1, 1, 1, $statisticsYear);
		$toDate = mktime(23, 59, 59, 12, 31, $statisticsYear);

		$eventStatisticsDao =& DAORegistry::getDAO('EventStatisticsDAO');
		$paperStatistics = $eventStatisticsDao->getPaperStatistics($event->getEventId(), null, $fromDate, $toDate);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$limitedPaperStatistics = $eventStatisticsDao->getPaperStatistics($event->getEventId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('limitedPaperStatistics', $limitedPaperStatistics);

		$limitedPaperStatistics = $eventStatisticsDao->getPaperStatistics($event->getEventId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('paperStatistics', $paperStatistics);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$tracks =& $trackDao->getEventTracks($event->getEventId());
		$templateMgr->assign('tracks', $tracks->toArray());
		
		//$issueStatistics = $eventStatisticsDao->getIssueStatistics($event->getEventId(), $fromDate, $toDate);
		//$templateMgr->assign('issueStatistics', $issueStatistics);

		$reviewerStatistics = $eventStatisticsDao->getReviewerStatistics($event->getEventId(), $trackIds, $fromDate, $toDate);
		$templateMgr->assign('reviewerStatistics', $reviewerStatistics);

		$allUserStatistics = $eventStatisticsDao->getUserStatistics($event->getEventId(), null, $toDate);
		$templateMgr->assign('allUserStatistics', $allUserStatistics);

		$userStatistics = $eventStatisticsDao->getUserStatistics($event->getEventId(), $fromDate, $toDate);
		$templateMgr->assign('userStatistics', $userStatistics);

		$enableRegistration = $event->getSetting('enableRegistration');
		if ($enableRegistration) {
			$templateMgr->assign('enableRegistration', true);
			$allRegistrationStatistics = $eventStatisticsDao->getRegistrationStatistics($event->getEventId(), null, $toDate);
			$templateMgr->assign('allRegistrationStatistics', $allRegistrationStatistics);

			$registrationStatistics = $eventStatisticsDao->getRegistrationStatistics($event->getEventId(), $fromDate, $toDate);
			$templateMgr->assign('registrationStatistics', $registrationStatistics);
		}

		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notifiableUsers = $notificationStatusDao->getNotifiableUsersCount($event->getEventId());
		$templateMgr->assign('notifiableUsers', $notifiableUsers);

		$templateMgr->assign('reportTypes', array(
			REPORT_TYPE_CONFERENCE => 'director.statistics.reports.type.conference',
			REPORT_TYPE_EVENT => 'director.statistics.reports.type.event',
			REPORT_TYPE_EDITOR => 'director.statistics.reports.type.editor',
			REPORT_TYPE_REVIEWER => 'director.statistics.reports.type.reviewer',
			REPORT_TYPE_TRACK => 'director.statistics.reports.type.track'
		));

		$templateMgr->assign('helpTopicId', 'event.managementPages.statsAndReports');

		$templateMgr->display('eventDirector/statistics/index.tpl');
	}

	function saveStatisticsTracks() {
		// The manager wants to save the list of tracks used to
		// generate statistics.

		parent::validate();

		$event = &Request::getEvent();

		$trackIds = Request::getUserVar('trackIds');
		if (!is_array($trackIds)) {
			if (empty($trackIds)) $trackIds = array();
			else $trackIds = array($trackIds);
		}

		$event->updateSetting('statisticsTrackIds', $trackIds);
		Request::redirect(null, null, 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
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

		$event =& Request::getEvent();
		foreach (StatisticsHandler::getPublicStatisticsNames() as $name) {
			$event->updateSetting($name, Request::getUserVar($name)?true:false);
		}
		Request::redirect(null, null, 'statistics', null, array('statisticsYear' => Request::getUserVar('statisticsYear')));
	}
	
	function csvEscape($value) {
		$value = str_replace('"', '""', $value);
		return '"' . $value . '"';
	}

	function reportGenerator($args) {
		parent::validate();
		$event =& Request::getEvent();

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$eventStatisticsDao =& DAORegistry::getDAO('EventStatisticsDAO');

		$reportType = (int) Request::getUserVar('reportType');

		switch ($reportType) {
			case REPORT_TYPE_EDITOR:
				$report =& $eventStatisticsDao->getEditorReport($event->getEventId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_REVIEWER:
				$report =& $eventStatisticsDao->getReviewerReport($event->getEventId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_TRACK:
				$report =& $eventStatisticsDao->getTrackReport($event->getEventId(), $fromDate, $toDate);
				break;
			case REPORT_TYPE_EVENT:
			default:
				$reportType = REPORT_TYPE_EVENT;
				$report =& $eventStatisticsDao->getEventReport($event->getEventId(), $fromDate, $toDate);
				break;
		}

		$templateMgr =& TemplateManager::getManager();
		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$separator = ',';

		// Display the heading row.
		switch ($reportType) {
			case REPORT_TYPE_EDITOR:
				echo Locale::translate('user.role.editor') . $separator;
				break;
			case REPORT_TYPE_REVIEWER:
				echo Locale::translate('user.role.reviewer') . $separator;
				echo Locale::translate('director.statistics.reports.singleScore') . $separator;
				echo Locale::translate('user.affiliation') . $separator;
				break;
			case REPORT_TYPE_TRACK:
				echo Locale::translate('track.track') . $separator;
				break;
		}

		echo Locale::translate('paper.submissionId');
		for ($i=0; $i<$report->getMaxAuthors(); $i++) {
			echo $separator . Locale::translate('director.statistics.reports.author', array('num' => $i+1));
			echo $separator . Locale::translate('director.statistics.reports.affiliation', array('num' => $i+1));
			echo $separator . Locale::translate('director.statistics.reports.country', array('num' => $i+1));
		}
		echo $separator . Locale::translate('paper.title');

		if ($reportType !== REPORT_TYPE_TRACK) echo $separator . Locale::translate('track.track');

		echo $separator . Locale::translate('submissions.submitted');

		if ($reportType !== REPORT_TYPE_EDITOR) for ($i=0; $i<$report->getMaxEditors(); $i++) {
			echo $separator . Locale::translate('director.statistics.reports.editor', array('num' => $i+1));
		}

		if ($reportType !== REPORT_TYPE_REVIEWER) for ($i=0; $i<$report->getMaxReviewers(); $i++) {
			echo $separator . Locale::translate('director.statistics.reports.reviewer', array('num' => $i+1));
			echo $separator . Locale::translate('director.statistics.reports.score', array('num' => $i+1));
			echo $separator . Locale::translate('director.statistics.reports.recommendation', array('num' => $i+1));
		}

		echo $separator . Locale::translate('editor.paper.decision');
		echo $separator . Locale::translate('director.statistics.reports.daysToDecision');
		echo $separator . Locale::translate('director.statistics.reports.daysToPublication');

		echo "\n";

		// Display the report.
		$dateFormatShort = Config::getVar('general', 'date_format_short');
		while ($row =& $report->next()) {
			switch ($reportType) {
				case REPORT_TYPE_EDITOR:
					echo $row['editor'] . $separator;
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

			for ($i=0; $i<$report->getMaxAuthors(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['authors'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['affiliations'][$i]);
				echo $separator . StatisticsHandler::csvEscape($row['countries'][$i]);
			}

			echo $separator . StatisticsHandler::csvEscape($row['title']);

			if ($reportType !== REPORT_TYPE_TRACK) echo $separator . StatisticsHandler::csvEscape($row['track']);

			echo $separator . $row['dateSubmitted'];

			if ($reportType !== REPORT_TYPE_EDITOR) for ($i=0; $i<$report->getMaxEditors(); $i++) {
				echo $separator . StatisticsHandler::csvEscape($row['editors'][$i]);
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
	}
}

?>
