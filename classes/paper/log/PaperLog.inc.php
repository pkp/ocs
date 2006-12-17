<?php

/**
 * PaperLog.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package paper.log
 *
 * PaperLog class.
 * Static class for adding / accessing paper log entries.
 *
 * $Id$
 */

class PaperLog {
	
	/**
	 * Add an event log entry to this paper.
	 * @param $paperId int
	 * @param $entry PaperEventLogEntry
	 */
	function logEventEntry($paperId, &$entry) {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$eventId = $paperDao->getPaperEventId($paperId);
		
		if (!$eventId) {
			// Invalid paper
			return false;
		}
		
		$settingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		if (!$settingsDao->getSetting($eventId, 'paperEventLog', true)) {
			// Event logging is disabled
			return false;
		}
	
		// Add the entry
		$entry->setPaperId($paperId);
		
		if ($entry->getUserId() == null) {
			$user = &Request::getUser();
			$entry->setUserId($user == null ? 0 : $user->getUserId());
		}
		
		$logDao = &DAORegistry::getDAO('PaperEventLogDAO');
		return $logDao->insertLogEntry($entry);
	}
	
	/**
	 * Add a new event log entry with the specified parameters, at the default log level
	 * @param $paperId int
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEvent($paperId, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		return PaperLog::logEventLevel($paperId, LOG_LEVEL_NOTICE, $eventType, $assocType, $assocId, $messageKey, $messageParams);
	}
	
	/**
	 * Add a new event log entry with the specified parameters, including log level.
	 * @param $paperId int
	 * @param $logLevel char
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $messageKey string
	 * @param $messageParams array
	 */
	function logEventLevel($paperId, $logLevel, $eventType, $assocType = 0, $assocId = 0, $messageKey = null, $messageParams = array()) {
		$entry = &new PaperEventLogEntry();
		$entry->setLogLevel($logLevel);
		$entry->setEventType($eventType);
		$entry->setAssocType($assocType);
		$entry->setAssocId($assocId);
		
		if (isset($messageKey)) {
			$entry->setLogMessage($messageKey, $messageParams);
		}
		
		return PaperLog::logEventEntry($paperId, $entry);
	}
	
	/**
	 * Get all event log entries for an paper.
	 * @param $paperId int
	 * @return array PaperEventLogEntry
	 */
	function &getEventLogEntries($paperId, $rangeInfo = null) {
		$logDao = &DAORegistry::getDAO('PaperEventLogDAO');
		$returner = &$logDao->getPaperLogEntries($paperId, $rangeInfo);
		return $returner;
	}
	
	/**
	 * Add an email log entry to this paper.
	 * @param $paperId int
	 * @param $entry PaperEmailLogEntry
	 */
	function logEmailEntry($paperId, &$entry) {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$eventId = $paperDao->getPaperEventId($paperId);
		
		if (!$eventId) {
			// Invalid paper
			return false;
		}
		
		$settingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		if (!$settingsDao->getSetting($eventId, 'paperEmailLog', true)) {
			// Email logging is disabled
			return false;
		}
	
		// Add the entry
		$entry->setPaperId($paperId);
		
		if ($entry->getSenderId() == null) {
			$user = &Request::getUser();
			$entry->setSenderId($user == null ? 0 : $user->getUserId());
		}
		
		$logDao = &DAORegistry::getDAO('PaperEmailLogDAO');
		return $logDao->insertLogEntry($entry);
	}
	
	/**
	 * Get all email log entries for an paper.
	 * @param $paperId int
	 * @return array PaperEmailLogEntry
	 */
	function &getEmailLogEntries($paperId, $rangeInfo = null) {
		$logDao = &DAORegistry::getDAO('PaperEmailLogDAO');
		$result = &$logDao->getPaperLogEntries($paperId, $rangeInfo);
		return $result;
	}
	
}

?>
