<?php

/**
 * ConferenceEventLogEntry.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package conference.log
 *
 * Conference event log entry class.
 * Describes an entry in the conference history log.
 *
 * $Id$
 */

import('log.EventLogConstants');

// Log entry event types. All types must be defined here
define('CONFERENCE_LOG_DEFAULT', 0);

// General events 				0x10000000
define('CONFERENCE_LOG_DEADLINE', 0x10000001);
define('CONFERENCE_LOG_CONFIGURATION', 0x10000002);

class ConferenceEventLogEntry extends DataObject {

	/**
	 * Constructor.
	 */
	function ConferenceEventLogEntry() {
		parent::DataObject();
	}
	
	/**
	 * Set localized log message (in the conference's primary locale)
	 * @param $key localization message key
	 * @param $params array optional array of parameters
	 */
	function setLogMessage($key, $params = array()) {
		$this->setMessage(Locale::translate($key, $params, Locale::getPrimaryLocale()));
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get ID of log entry.
	 * @return int
	 */
	function getLogId() {
		return $this->getData('logId');
	}
	
	/**
	 * Set ID of log entry.
	 * @param $logId int
	 */
	function setLogId($logId) {
		return $this->setData('logId', $logId);
	}
	
	/**
	 * Get ID of conference.
	 * @return int
	 */
	function getConferenceId() {
		return $this->getData('conferenceId');
	}
	
	/**
	 * Set ID of conference.
	 * @param $conferenceId int
	 */
	function setConferenceId($conferenceId) {
		return $this->setData('conferenceId', $conferenceId);
	}
	
	/**
	 * Get ID of event.
	 * @return int
	 */
	function getEventId() {
		return $this->getData('eventId');
	}
	
	/**
	 * Set ID of event.
	 * @param $eventId int
	 */
	function setEventId($eventId) {
		return $this->setData('eventId', $eventId);
	}
	
	/**
	 * Get user ID of user that initiated the event.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}
	
	/**
	 * Set user ID of user that initiated the event.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}
	
	/**
	 * Get date entry was logged.
	 * @return datestamp
	 */
	function getDateLogged() {
		return $this->getData('dateLogged');
	}
	
	/**
	 * Set date entry was logged.
	 * @param $dateLogged datestamp
	 */
	function setDateLogged($dateLogged) {
		return $this->setData('dateLogged', $dateLogged);
	}
	
	/**
	 * Get IP address of user that initiated the event.
	 * @return string
	 */
	function getIPAddress() {
		return $this->getData('ipAddress');
	}
	
	/**
	 * Set IP address of user that initiated the event.
	 * @param $ipAddress string
	 */
	function setIPAddress($ipAddress) {
		return $this->setData('ipAddress', $ipAddress);
	}
	
	/**
	 * Get the log level.
	 * @return int
	 */
	function getLogLevel() {
		return $this->getData('logLevel');
	}
	
	/**
	 * Set the log level.
	 * @param $logLevel char
	 */
	function setLogLevel($logLevel) {
		return $this->setData('logLevel', $logLevel);
	}
	
	/**
	 * Get event type.
	 * @return int
	 */
	function getEventType() {
		return $this->getData('eventType');
	}
	
	/**
	 * Set event type.
	 * @param $eventType int
	 */
	function setEventType($eventType) {
		return $this->setData('eventType', $eventType);
	}
	
	/**
	 * Get associated type.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}
	
	/**
	 * Set associated type.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		return $this->setData('assocType', $assocType);
	}
	
	/**
	 * Get associated ID.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}
	
	/**
	 * Set associated ID.
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		return $this->setData('assocId', $assocId);
	}
	
	/**
	 * Get custom log message (non-localized).
	 * @return string
	 */
	function getMessage() {
		return $this->getData('message');
	}
	
	/**
	 * Set custom log message (non-localized).
	 * @param $message string
	 */
	function setMessage($message) {
		return $this->setData('message', $message);
	}
	
	/**
	 * Return locale message key for the log level.
	 * @return string
	 */
	function getLogLevelString() {
		switch ($this->getData('logLevel')) {
			case LOG_LEVEL_INFO:
				return 'event.logLevel.info';
			case LOG_LEVEL_NOTICE:
				return 'event.logLevel.notice';
			case LOG_LEVEL_WARNING:
				return 'event.logLevel.warning';
			case LOG_LEVEL_ERROR:
				return 'event.logLevel.error';
			default:
				return 'event.logLevel.notice';
		}
	}
	
	/**
	 * Return locale message key describing event type.
	 * @return string
	 */
	function getEventTitle() {
		switch ($this->getData('eventType')) {
			// General events
			case CONFERENCE_LOG_CONFIGURATION:
				return 'conference.event.general.configuration';
			case CONFERENCE_LOG_DEADALINE:
				return 'conference.event.general.deadline';
				
			default:
				return 'event.general.defaultEvent';
		}
	}
	
	/**
	 * Return the full name of the user.
	 * @return string
	 */
	function getUserFullName() {
		static $userFullName;
		
		if(!isset($userFullName)) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$userFullName = $userDao->getUserFullName($this->getUserId(), true);
		}
		
		return $userFullName ? $userFullName : '';
	}
	
	/**
	 * Return the email address of the user.
	 * @return string
	 */
	function getUserEmail() {
		static $userEmail;
		
		if(!isset($userEmail)) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$userEmail = $userDao->getUserEmail($this->getUserId(), true);
		}
		
		return $userEmail ? $userEmail : '';
	}
	
	/**
	 * Return string representation of the associated type.
	 * @return string
	 */
	function getAssocTypeString() {
		switch ($this->getData('assocType')) {
			case LOG_TYPE_AUTHOR:
				return 'AUT';
			case LOG_TYPE_EDITOR:
				return 'EDR';
			case LOG_TYPE_REVIEW:
				return 'REV';
			default:
				return 'PAP';
		}
	}
	
	/**
	 * Return locale message key for the long format of the associated type.
	 * @return string
	 */
	function getAssocTypeLongString() {
		switch ($this->getData('assocType')) {
			case LOG_TYPE_AUTHOR:
				return 'event.logType.author';
			case LOG_TYPE_EDITOR:
				return 'event.logType.editor';
			case LOG_TYPE_REVIEW:
				return 'event.logType.review';
			default:
				return 'event.logType.paper';
		}
	}
	
}

?>
