<?php

/**
 * @file ConferenceLog.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceLog
 * @ingroup conference_log
 *
 * @brief Static class for adding / accessing conference log entries.
 *
 */

// $Id$


class ConferenceLog {

	/**
	 * Add an event log entry to this conference.
	 * @param $conferenceId int
	 * @param $schedConfId int optional
	 * @param $entry ConferenceEventLogEntry
	 */
	function logEventEntry($conferenceId, $schedConfId = null, &$entry) {
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		$conference = $conferenceDao->getConference($conferenceId);
		if(!$conference) {
			// Conference is invalid.
			return false;
		}

		$schedConf = null;
		if($schedConfId != null)
			$schedConf = $schedConfDao->getSchedConf($schedConfId);

		if($conference && !$conference->getSetting('conferenceEventLog')) {
			// Event logging is disabled
			return false;
		}

		// Add the entry
		$entry->setConferenceId($conferenceId);
		$entry->setSchedConfId($schedConfId);

		if ($entry->getUserId() == null) {
			$user =& Request::getUser();
			$entry->setUserId($user == null ? 0 : $user->getId());
		}

		$logDao =& DAORegistry::getDAO('ConferenceEventLogDAO');
		return $logDao->insertLogEntry($entry);
	}

	/**
	 * Add a new event log entry with the specified parameters, at the default log level
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEvent($conferenceId, $schedConfId = null, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		return ConferenceLog::logEventLevel($conferenceId, $schedConfId, LOG_LEVEL_NOTICE, $eventType, $assocType, $assocId, $messageKey, $messageParams);
	}

	/**
	 * Add a new event log entry with the specified parameters, including log level.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @param $logLevel char
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEventLevel($conferenceId, $schedConfId, $logLevel, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		$entry = new ConferenceEventLogEntry();
		$entry->setLogLevel($logLevel);
		$entry->setEventType($eventType);
		$entry->setAssocType($assocType);
		$entry->setAssocId($assocId);

		if (isset($messageKey)) {
			$entry->setLogMessage($messageKey, $messageParams);
		}

		return ConferenceLog::logEventEntry($conferenceId, $schedConfId, $entry);
	}

	/**
	 * Get all event log entries for a conference.
	 * @param $conferenceId int
	 * @param $schedConfId int optional
	 * @return array ConferenceEventLogEntry
	 */
	function &getEventLogEntries($conferenceId, $schedConfId = null, $rangeInfo = null) {
		$logDao =& DAORegistry::getDAO('ConferenceEventLogDAO');
		$returner =& $logDao->getConferenceLogEntries($conferenceId, $schedConfId, $rangeInfo);
		return $returner;
	}
}

?>
