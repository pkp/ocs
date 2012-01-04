<?php

/**
 * @file ConferenceHistoryHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceHistoryHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for conference event log funcs.
 */

//$Id$

import('pages.manager.ManagerHandler');

class ConferenceHistoryHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function ConferenceHistoryHandler() {
		parent::ManagerHandler();
	}
	/**
	 * View conference event log.
	 */
	function conferenceEventLog($args) {
		$logId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate();

		$conference =& Request::getConference();

		$this->setupTemplate(true);

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('conference', $conference);

		if ($logId) {
			$logDao =& DAORegistry::getDAO('ConferenceEventLogDAO');
			$logEntry =& $logDao->getLogEntry($logId);
			if ($logEntry && $logEntry->getConferenceId() != $conference->getId()) Request::redirect(null, null, null, 'index');
		}

		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('manager/conferenceEventLogEntry.tpl');
		} else {
			$rangeInfo =& Handler::getRangeInfo('eventLogEntries', array());

			import('conference.log.ConferenceLog');
			while (true) {
				$eventLogEntries =& ConferenceLog::getEventLogEntries($conference->getId(), null, $rangeInfo);
				if ($eventLogEntries->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
				unset($eventLogEntries);
			}
			$templateMgr->assign('eventLogEntries', $eventLogEntries);
			$templateMgr->display('manager/conferenceEventLog.tpl');
		}
	}

	/**
	 * View conference event log by record type.
	 */
	function conferenceEventLogType($args) {
		$assocType = isset($args[1]) ? (int) $args[0] : null;
		$assocId = isset($args[2]) ? (int) $args[1] : null;
		$this->validate();
		$this->setupTemplate(true);

		$conference =& Request::getConference();

		$rangeInfo =& Handler::getRangeInfo('eventLogEntries', array($assocType, $assocId));
		$logDao =& DAORegistry::getDAO('ConferenceEventLogDAO');
		while (true) {
			$eventLogEntries =& $logDao->getConferenceLogEntriesByAssoc($conference->getId(), null, $assocType, $assocId, $rangeInfo);
			if ($eventLogEntries->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
			unset($eventLogEntries);
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		$templateMgr->display('manager/conferenceEventLog.tpl');
	}

	/**
	 * Clear conference event log entries.
	 */
	function clearConferenceEventLog($args) {
		$logId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate();
		$conference =& Request::getConference();

		$logDao =& DAORegistry::getDAO('ConferenceEventLogDAO');

		if ($logId) {
			$logDao->deleteLogEntry($logId, $conference->getId());
		} else {
			$logDao->deleteConferenceLogEntries($conference->getId());
		}

		Request::redirect(null, null, null, 'conferenceEventLog');
	}
}

?>
