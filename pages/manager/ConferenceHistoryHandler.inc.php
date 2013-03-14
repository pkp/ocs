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


import('pages.manager.ManagerHandler');

class ConferenceHistoryHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function ConferenceHistoryHandler() {
		parent::ManagerHandler();
	}
	/**
	 * View conference event log.
	 */
	function conferenceEventLog($args, &$request) {
		$logId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate();

		$conference =& $request->getConference();

		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager($request);

		$templateMgr->assign_by_ref('conference', $conference);

		if ($logId) {
			$logDao = DAORegistry::getDAO('ConferenceEventLogDAO');
			$logEntry =& $logDao->getLogEntry($logId);
			if ($logEntry && $logEntry->getConferenceId() != $conference->getId()) $request->redirect(null, null, null, 'index');
		}

		if (isset($logEntry)) {
			$templateMgr->assign('logEntry', $logEntry);
			$templateMgr->display('manager/conferenceEventLogEntry.tpl');
		} else {
			$rangeInfo = $this->getRangeInfo($request, 'eventLogEntries', array());

			import('classes.conference.log.ConferenceLog');
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
	function conferenceEventLogType($args, &$request) {
		$assocType = isset($args[1]) ? (int) $args[0] : null;
		$assocId = isset($args[2]) ? (int) $args[1] : null;
		$this->validate();
		$this->setupTemplate($request, true);

		$conference =& $request->getConference();

		$rangeInfo = $this->getRangeInfo($request, 'eventLogEntries', array($assocType, $assocId));
		$logDao = DAORegistry::getDAO('ConferenceEventLogDAO');
		while (true) {
			$eventLogEntries =& $logDao->getConferenceLogEntriesByAssoc($conference->getId(), null, $assocType, $assocId, $rangeInfo);
			if ($eventLogEntries->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $eventLogEntries->getLastPageRangeInfo();
			unset($eventLogEntries);
		}

		$templateMgr =& TemplateManager::getManager($request);

		$templateMgr->assign('showBackLink', true);
		$templateMgr->assign('isDirector', Validation::isDirector());
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign_by_ref('eventLogEntries', $eventLogEntries);
		$templateMgr->display('manager/conferenceEventLog.tpl');
	}

	/**
	 * Clear conference event log entries.
	 */
	function clearConferenceEventLog($args, &$request) {
		$logId = isset($args[0]) ? (int) $args[0] : 0;
		$this->validate();
		$conference =& $request->getConference();

		$logDao = DAORegistry::getDAO('ConferenceEventLogDAO');

		if ($logId) {
			$logDao->deleteLogEntry($logId, $conference->getId());
		} else {
			$logDao->deleteConferenceLogEntries($conference->getId());
		}

		$request->redirect(null, null, null, 'conferenceEventLog');
	}
}

?>
