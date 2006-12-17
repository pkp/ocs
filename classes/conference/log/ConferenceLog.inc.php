<?php

/**
 * ConferenceLog.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package conference.log
 *
 * ConferenceLog class.
 * Static class for adding / accessing conference log entries.
 *
 * $Id$
 */

class ConferenceLog {
	
	/**
	 * Add an event log entry to this conference.
	 * @param $conferenceId int
	 * @param $eventId int optional
	 * @param $entry ConferenceEventLogEntry
	 */
	function logEventEntry($conferenceId, $eventId = null, &$entry) {
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$eventDao = &DAORegistry::getDAO('EventDAO');
		
		$conference = $conferenceDao->getConference($conferenceId);
		if(!$conference) {
			// Conference is invalid.
			return false;
		}
		
		$event = null;
		if($eventId != null)
			$event = $eventDao->getEvent($eventId);
		
		if(($event && !$event->getSetting('conferenceEventLog', true)) ||
			($conference && !$conference->getSetting('conferenceEventLog'))) {
			// Event logging is disabled
			return false;
		}

		// Add the entry
		$entry->setConferenceId($conferenceId);
		$entry->setEventId($eventId);
		
		if ($entry->getUserId() == null) {
			$user = &Request::getUser();
			$entry->setUserId($user == null ? 0 : $user->getUserId());
		}
		
		$logDao = &DAORegistry::getDAO('ConferenceEventLogDAO');
		return $logDao->insertLogEntry($entry);
	}
	
	/**
	 * Add a new event log entry with the specified parameters, at the default log level
	 * @param $conferenceId int
	 * @param $eventId int
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEvent($conferenceId, $eventId = null, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		return ConferenceLog::logEventLevel($conferenceId, $eventId, LOG_LEVEL_NOTICE, $eventType, $assocType, $assocId, $messageKey, $messageParams);
	}
	
	/**
	 * Add a new event log entry with the specified parameters, including log level.
	 * @param $conferenceId int
	 * @param $eventId int
	 * @param $logLevel char
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEventLevel($conferenceId, $eventId, $logLevel, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		$entry = &new ConferenceEventLogEntry();
		$entry->setLogLevel($logLevel);
		$entry->setEventType($eventType);
		$entry->setAssocType($assocType);
		$entry->setAssocId($assocId);
		
		if (isset($messageKey)) {
			$entry->setLogMessage($messageKey, $messageParams);
		}
		
		return ConferenceLog::logEventEntry($conferenceId, $eventId, $entry);
	}
	
	/**
	 * Get all event log entries for a conference.
	 * @param $conferenceId int
	 * @param $eventId int optional
	 * @return array ConferenceEventLogEntry
	 */
	function &getEventLogEntries($conferenceId, $eventId = null, $rangeInfo = null) {
		$logDao = &DAORegistry::getDAO('ConferenceEventLogDAO');
		$returner = &$logDao->getConferenceLogEntries($conferenceId, $eventId, $rangeInfo);
		return $returner;
	}
}

?>
