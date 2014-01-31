<?php

/**
 * @file PaperEmailLogEntry.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperEmailLogEntry
 * @ingroup paper_log
 * @see PaperEmailLogDAO
 *
 * @brief Describes an entry in the paper email log.
 */

//$Id$

// Email associative types. All types must be defined here
define('PAPER_EMAIL_TYPE_DEFAULT', 		0);
define('PAPER_EMAIL_TYPE_AUTHOR', 		0x01);
define('PAPER_EMAIL_TYPE_DIRECTOR', 		0x02);
define('PAPER_EMAIL_TYPE_REVIEW', 		0x03);

// General events 				0x10000000

// Author events 				0x20000000

// Director events 				0x30000000
define('PAPER_EMAIL_DIRECTOR_NOTIFY_AUTHOR',	0x30000001);
define('PAPER_EMAIL_DIRECTOR_ASSIGN',		0x30000002);
define('PAPER_EMAIL_DIRECTOR_NOTIFY_AUTHOR_UNSUITABLE',	0x30000003);
define('PAPER_EMAIL_DIRECTOR_NOTIFY_AUTHOR_EXPIRED',		0x30000004);

// Reviewer events 				0x40000000
define('PAPER_EMAIL_REVIEW_NOTIFY_REVIEWER', 		0x40000001);
define('PAPER_EMAIL_REVIEW_THANK_REVIEWER', 		0x40000002);
define('PAPER_EMAIL_REVIEW_CANCEL',		0x40000003);
define('PAPER_EMAIL_REVIEW_REMIND',		0x40000004);
define('PAPER_EMAIL_REVIEW_CONFIRM',		0x40000005);
define('PAPER_EMAIL_REVIEW_DECLINE',		0x40000006);
define('PAPER_EMAIL_REVIEW_COMPLETE',		0x40000007);
define('PAPER_EMAIL_REVIEW_CONFIRM_ACK',	0x40000008);

class PaperEmailLogEntry extends DataObject {

	/**
	 * Constructor.
	 */
	function PaperEmailLogEntry() {
		parent::DataObject();
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
	 * Get user ID of sender.
	 * @return int
	 */
	function getSenderId() {
		return $this->getData('senderId');
	}

	/**
	 * Set user ID of sender.
	 * @param $senderId int
	 */
	function setSenderId($senderId) {
		return $this->setData('senderId', $senderId);
	}

	/**
	 * Get date email was sent.
	 * @return datestamp
	 */
	function getDateSent() {
		return $this->getData('dateSent');
	}

	/**
	 * Set date email was sent.
	 * @param $dateSent datestamp
	 */
	function setDateSent($dateSent) {
		return $this->setData('dateSent', $dateSent);
	}

	/**
	 * Get IP address of sender.
	 * @return string
	 */
	function getIPAddress() {
		return $this->getData('ipAddress');
	}

	/**
	 * Set IP address of sender.
	 * @param $ipAddress string
	 */
	function setIPAddress($ipAddress) {
		return $this->setData('ipAddress', $ipAddress);
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
	 * Return the full name of the sender (not necessarily the same as the from address).
	 * @return string
	 */
	function getSenderFullName() {
		static $senderFullName;

		if(!isset($senderFullName)) {
			$userDao =& DAORegistry::getDAO('UserDAO');
			$senderFullName = $userDao->getUserFullName($this->getSenderId(), true);
		}

		return $senderFullName ? $senderFullName : '';
	}

	/**
	 * Return the email address of sender.
	 * @return string
	 */
	function getSenderEmail() {
		static $senderEmail;

		if(!isset($senderEmail)) {
			$userDao =& DAORegistry::getDAO('UserDAO');
			$senderEmail = $userDao->getUserEmail($this->getSenderId(), true);
		}

		return $senderEmail ? $senderEmail : '';
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
				return 'ART';
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


	//
	// Email data
	//

	function getFrom() {
		return $this->getData('from');
	}

	function setFrom($from) {
		return $this->setData('from', $from);
	}

	function getRecipients() {
		return $this->getData('recipients');
	}

	function setRecipients($recipients) {
		return $this->setData('recipients', $recipients);
	}

	function getCcs() {
		return $this->getData('ccs');
	}

	function setCcs($ccs) {
		return $this->setData('ccs', $ccs);
	}

	function getBccs() {
		return $this->getData('bccs');
	}

	function setBccs($bccs) {
		return $this->setData('bccs', $bccs);
	}

	function getSubject() {
		return $this->getData('subject');
	}

	function setSubject($subject) {
		return $this->setData('subject', $subject);
	}

	function getBody() {
		return $this->getData('body');
	}

	function setBody($body) {
		return $this->setData('body', $body);
	}

}

?>
