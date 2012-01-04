<?php

/**
 * @file ConferenceEventLogEntry.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceEventLogEntry
 * @ingroup conference_log
 * @see ConferenceEventLogDAO
 *
 * @brief Describes an entry in the conference history log.
 */

//$Id$

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
		$this->setMessage($key);
		$this->setEntryParams($params);
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
	 * Get ID of sched conf.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set ID of sched conf.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get conference title.
	 * @return string
	 */
	function getConferenceTitle() {
		return $this->getData('conferenceTitle');
	}

	/**
	 * Set conference title.
	 * @param $conferenceTitle string
	 */
	function setConferenceTitle($conferenceTitle) {
		return $this->setData('conferenceTitle', $conferenceTitle);
	}

	/**
	 * Get sched conf title.
	 * @return string
	 */
	function getSchedConfTitle() {
		return $this->getData('schedConfTitle');
	}

	/**
	 * Set sched conf title.
	 * @param $schedConfTitle string
	 */
	function setSchedConfTitle($schedConfTitle) {
		return $this->setData('schedConfTitle', $schedConfTitle);
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
	 * Get is_translated value (0/1).
	 * @return int
	 */
	function getIsTranslated() {
		return $this->getData('isTranslated');
	}

	/**
	 * Set is_translated value.
	 * WARNING: This is for legacy purposes only and should be
	 * assumed to be TRUE for all new entries (i.e. messages
	 * should be locale keys, not translated strings)!
	 * @param $isTranslated int
	 */
	function setIsTranslated($isTranslated) {
		return $this->setData('isTranslated', $isTranslated);
	}
	
	/**
	 * Get parameters for log message.
	 * @return array
	 */
	function getEntryParams() {
		return $this->getData('entryParams');
	}
	
	/**
	 * Get serialized parameters for log message (to store in database).
	 * @return array
	 */
	function getEntryParamsSerialized() {
		return serialize($this->getData('entryParams'));
	}
	
	/**
	 * Set parameters for log message.
	 * @param $entryParams array
	 */
	function setEntryParams($entryParams = array()) {
		return $this->setData('entryParams', $entryParams);
	}

	/**
	 * Get log message key.
	 * @return string
	 */
	function getMessage() {
		return $this->getData('message');
	}

	/**
	 * Set log message key.
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
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getUserFullName($this->getUserId(), true);
	}

	/**
	 * Return the email address of the user.
	 * @return string
	 */
	function getUserEmail() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getUserEmail($this->getUserId(), true);
	}

	/**
	 * Return string representation of the associated type.
	 * @return string
	 */
	function getAssocTypeString() {
		switch ($this->getData('assocType')) {
			default:
				return 'CON';
		}
	}

	/**
	 * Return locale message key for the long format of the associated type.
	 * @return string
	 */
	function getAssocTypeLongString() {
		switch ($this->getData('assocType')) {
			default:
				return 'event.logType.conference';
		}
	}

}

?>
