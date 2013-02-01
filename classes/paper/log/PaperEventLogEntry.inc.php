<?php

/**
 * @defgroup paper_log
 */
 
/**
 * @file PaperEventLogEntry.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup paper_log
 * @class PaperEventLogEntry
 * @see PaperEventLogDAO
 *
 * @brief Describes an entry in the paper history log.
 */


import('classes.log.EventLogConstants');

// Log entry event types. All types must be defined here
define('PAPER_LOG_DEFAULT', 0);

// General events 				0x10000000
define('PAPER_LOG_ABSTRACT_SUBMIT', 		0x10000001);
define('PAPER_LOG_PRESENTATION_SUBMIT', 	0x10000002);
define('PAPER_LOG_METADATA_UPDATE', 		0x10000003);
define('PAPER_LOG_SUPPFILE_UPDATE', 		0x10000004);
define('PAPER_LOG_ISSUE_SCHEDULE', 		0x10000005);
define('PAPER_LOG_ISSUE_ASSIGN', 		0x10000006);
define('PAPER_LOG_PAPER_PUBLISH', 		0x10000007);
define('PAPER_LOG_PAPER_IMPORT',		0x10000008);
define('PAPER_LOG_PAPER_EXPIRE',		0x10000009);

// Author events 				0x20000000
define('PAPER_LOG_AUTHOR_REVISION', 		0x20000001);

// Director events 				0x30000000
define('PAPER_LOG_DIRECTOR_ASSIGN', 		0x30000001);
define('PAPER_LOG_DIRECTOR_UNASSIGN',	 	0x30000002);
define('PAPER_LOG_DIRECTOR_DECISION', 		0x30000003);
define('PAPER_LOG_DIRECTOR_FILE', 		0x30000004);
define('PAPER_LOG_DIRECTOR_ARCHIVE', 		0x30000005);
define('PAPER_LOG_DIRECTOR_RESTORE', 		0x30000006);
define('PAPER_LOG_DIRECTOR_EXPEDITE', 		0x30000007);
define('PAPER_LOG_DIRECTOR_PUBLISH',		0x30000008);
define('PAPER_LOG_DIRECTOR_UNPUBLISH', 		0x30000009);

// Reviewer events 				0x40000000
define('PAPER_LOG_REVIEW_ASSIGN', 		0x40000001);
define('PAPER_LOG_REVIEW_UNASSIGN',	 	0x40000002);
define('PAPER_LOG_REVIEW_INITIATE', 		0x40000003);
define('PAPER_LOG_REVIEW_CANCEL', 		0x40000004);
define('PAPER_LOG_REVIEW_REINITIATE', 		0x40000005);
define('PAPER_LOG_REVIEW_ACCEPT', 		0x40000006);
define('PAPER_LOG_REVIEW_DECLINE', 		0x40000007);
define('PAPER_LOG_REVIEW_REVISION', 		0x40000008);
define('PAPER_LOG_REVIEW_RECOMMENDATION', 	0x40000009);
define('PAPER_LOG_REVIEW_RATE', 		0x40000010);
define('PAPER_LOG_REVIEW_SET_DUE_DATE', 	0x40000011);
define('PAPER_LOG_REVIEW_FILE', 		0x40000012);
define('PAPER_LOG_REVIEW_CLEAR', 		0x40000013);
define('PAPER_LOG_REVIEW_ACCEPT_BY_PROXY', 	0x40000014);
define('PAPER_LOG_REVIEW_RECOMMENDATION_BY_PROXY', 	0x40000015);
define('PAPER_LOG_REVIEW_FILE_BY_PROXY', 	0x40000016);

// Layout events 				0x70000000
define('PAPER_LOG_LAYOUT_SET_FILE', 		0x70000001);
define('PAPER_LOG_LAYOUT_GALLEY', 		0x70000002);

class PaperEventLogEntry extends DataObject {

	/**
	 * Constructor.
	 */
	function PaperEventLogEntry() {
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
	 * Get ID of paper.
	 * @return int
	 */
	function getPaperId() {
		return $this->getData('paperId');
	}

	/**
	 * Set ID of paper.
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		return $this->setData('paperId', $paperId);
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
			case PAPER_LOG_ABSTRACT_SUBMIT:
				return 'submission.event.general.abstractSubmitted';
			case PAPER_LOG_PRESENTATION_SUBMIT:
				return 'submission.event.general.presentationSubmitted';
			case PAPER_LOG_METADATA_UPDATE:
				return 'submission.event.general.metadataUpdated';
			case PAPER_LOG_SUPPFILE_UPDATE:
				return 'submission.event.general.suppFileUpdated';
			case PAPER_LOG_ISSUE_SCHEDULE:
				return 'submission.event.general.issueScheduled';
			case PAPER_LOG_ISSUE_ASSIGN:
				return 'submission.event.general.issueAssigned';
			case PAPER_LOG_PAPER_PUBLISH:
				return 'submission.event.general.paperPublished';

			// Author events
			case PAPER_LOG_AUTHOR_REVISION:
				return 'submission.event.author.authorRevision';

			// Director events
			case PAPER_LOG_DIRECTOR_ASSIGN:
				return 'submission.event.director.directorAssigned';
			case PAPER_LOG_DIRECTOR_UNASSIGN:
				return 'submission.event.director.directorUnassigned';
			case PAPER_LOG_DIRECTOR_DECISION:
				return 'submission.event.director.directorDecision';
			case PAPER_LOG_DIRECTOR_FILE:
				return 'submission.event.director.directorFile';
			case PAPER_LOG_DIRECTOR_ARCHIVE:
				return 'submission.event.director.submissionArchived';
			case PAPER_LOG_DIRECTOR_RESTORE:
				return 'submission.event.director.submissionRestored';

			// Reviewer events
			case PAPER_LOG_REVIEW_ASSIGN:
				return 'submission.event.reviewer.reviewerAssigned';
			case PAPER_LOG_REVIEW_UNASSIGN:
				return 'submission.event.reviewer.reviewerUnassigned';
			case PAPER_LOG_REVIEW_INITIATE:
				return 'submission.event.reviewer.reviewInitiated';
			case PAPER_LOG_REVIEW_CANCEL:
				return 'submission.event.reviewer.reviewCancelled';
			case PAPER_LOG_REVIEW_REINITIATE:
				return 'submission.event.reviewer.reviewReinitiated';
			case PAPER_LOG_REVIEW_ACCEPT_BY_PROXY:
				return 'submission.event.reviewer.reviewAcceptedByProxy';
			case PAPER_LOG_REVIEW_ACCEPT:
				return 'submission.event.reviewer.reviewAccepted';
			case PAPER_LOG_REVIEW_DECLINE:
				return 'submission.event.reviewer.reviewDeclined';
			case PAPER_LOG_REVIEW_REVISION:
				return 'submission.event.reviewer.reviewRevision';
			case PAPER_LOG_REVIEW_RECOMMENDATION:
				return 'submission.event.reviewer.reviewRecommendation';
			case PAPER_LOG_REVIEW_RATE:
				return 'submission.event.reviewer.reviewerRated';
			case PAPER_LOG_REVIEW_SET_DUE_DATE:
				return 'submission.event.reviewer.reviewDueDate';
			case PAPER_LOG_REVIEW_FILE:
				return 'submission.event.reviewer.reviewFile';

			// Layout events
			case PAPER_LOG_LAYOUT_GALLEY:
				return 'submission.event.layout.layoutGalleyCreated';

			default:
				return 'event.general.defaultEvent';
		}
	}

	/**
	 * Return the full name of the user.
	 * @return string
	 */
	function getUserFullName() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getUserFullName($this->getUserId(), true);
	}

	/**
	 * Return the email address of the user.
	 * @return string
	 */
	function getUserEmail() {
		$userDao = DAORegistry::getDAO('UserDAO');
		return $userDao->getUserEmail($this->getUserId(), true);
	}

	/**
	 * Return string representation of the associated type.
	 * @return string
	 */
	function getAssocTypeString() {
		switch ($this->getData('assocType')) {
			case LOG_TYPE_AUTHOR:
				return 'AUT';
			case LOG_TYPE_DIRECTOR:
				return 'DIR';
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
			case LOG_TYPE_DIRECTOR:
				return 'event.logType.director';
			case LOG_TYPE_REVIEW:
				return 'event.logType.review';
			default:
				return 'event.logType.paper';
		}
	}

}

?>
