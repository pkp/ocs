<?php

/**
 * PaperMailTemplate.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 *
 * Subclass of MailTemplate for sending emails related to papers.
 * This allows for paper-specific functionality like logging, etc.
 *
 * $Id$
 */

import('mail.MailTemplate');
import('paper.log.PaperEmailLogEntry'); // Bring in log constants

class PaperMailTemplate extends MailTemplate {

	/** @var object the associated paper */
	var $paper;

	/** @var object the associated conference */
	var $conference;

	/** @var object the associated event */
	var $event;

	/** @var int Event type of this email */
	var $eventType;

	/** @var int Associated type of this email */
	var $assocType;
	
	/** @var int Associated ID of this email */
	var $assocId;

	/**
	 * Constructor.
	 * @param $paper object
	 * @param $emailType int optional
	 * @param $locale string optional
	 * @param $enableAttachments boolean optional
	 * @param $conference object optional
	 * @param $event object optional
	 * @see MailTemplate::MailTemplate()
	 */
	function PaperMailTemplate($paper, $emailKey = null, $locale = null, $enableAttachments = null, $conference = null, $event = null) {
		parent::MailTemplate($emailKey, $locale, $enableAttachments, $conference, $event);
		$this->paper = $paper;
	}

	function assignParams($paramArray = array()) {
		$paper = &$this->paper;
		$conference = isset($this->conference)?$this->conference:Request::getConference();
		$event = isset($this->event)?$this->event:Request::getEvent();
		
		$paramArray['paperTitle'] = strip_tags($paper->getPaperTitle());
		$paramArray['conferenceName'] = strip_tags($conference->getTitle());
		$paramArray['eventName'] = strip_tags($event->getTitle());
		$paramArray['trackName'] = strip_tags($paper->getTrackTitle());
		$paramArray['paperAbstract'] = strip_tags($paper->getPaperAbstract());
		$paramArray['authorString'] = strip_tags($paper->getAuthorString());

		parent::assignParams($paramArray);
	}

	/**
	 * @see parent::send()
	 */
	function send() {
		if (parent::send()) {
			$this->log();
			return true;
			
		} else {
			return false;
		}
	}
	
	/**
	 * @see parent::sendWithParams()
	 */
	function sendWithParams($paramArray) {
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();
		
		$this->assignParams($paramArray);
		
		$ret = $this->send();
		
		$this->setSubject($savedSubject);
		$this->setBody($savedBody);
		
		return $ret;
	}
	
	/**
	 * Add a generic association between this email and some event type / type / ID tuple.
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 */
	function setAssoc($eventType, $assocType, $assocId) {
		$this->eventType = $eventType;
		$this->assocType = $assocType;
		$this->assocId = $assocId;
	}

	/**
	 * Set the conference this message is associated with.
	 * @param $conference object
	 */
	function setConference($conference) {
		$this->conference = $conference;
	}

	/**
	 * Set the event this message is associated with.
	 * @param $event object
	 */
	function setEvent($event) {
		$this->event = $event;
	}

	/**
	 * Save the email in the paper email log.
	 */
	function log() {
		import('paper.log.PaperEmailLogEntry');
		import('paper.log.PaperLog');
		$entry = &new PaperEmailLogEntry();
		
		// Log data
		$entry->setEventType($this->eventType);
		$entry->setAssocType($this->assocType);
		$entry->setAssocId($this->assocId);
		
		// Email data
		$entry->setSubject($this->getSubject());
		$entry->setBody($this->getBody());
		$entry->setFrom($this->getFromString());
		$entry->setRecipients($this->getRecipientString());
		$entry->setCcs($this->getCcString());
		$entry->setBccs($this->getBccString());

		// Add log entry
		$paper = &$this->paper;
		PaperLog::logEmailEntry($paper->getPaperId(), $entry);
	}

	function ccAssignedEditors($paperId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditorAssignmentsByPaperId($paperId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function toAssignedReviewingTrackEditors($paperId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getReviewingTrackEditorAssignmentsByPaperId($paperId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function toAssignedEditingTrackEditors($paperId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditingTrackEditorAssignmentsByPaperId($paperId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function ccAssignedReviewingTrackEditors($paperId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getReviewingTrackEditorAssignmentsByPaperId($paperId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function ccAssignedEditingTrackEditors($paperId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditingTrackEditorAssignmentsByPaperId($paperId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}
}

?>
