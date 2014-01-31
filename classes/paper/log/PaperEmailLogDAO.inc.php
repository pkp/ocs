<?php

/**
 * @file PaperEmailLogDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperEmailLogDAO
 * @ingroup paper_log
 * @see PaperEmailLogEntry
 *
 * @brief Class for inserting/accessing paper email log entries.
 */

//$Id$

import ('paper.log.PaperEmailLogEntry');

class PaperEmailLogDAO extends DAO {
	/**
	 * Retrieve a log entry by ID.
	 * @param $logId int
	 * @param $paperId int optional
	 * @return PaperEmailLogEntry
	 */
	function &getLogEntry($logId, $paperId = null) {
		if (isset($paperId)) {
			$result =& $this->retrieve(
				'SELECT * FROM paper_email_log WHERE log_id = ? AND paper_id = ?',
				array($logId, $paperId)
			);
		} else {
			$result =& $this->retrieve(
				'SELECT * FROM paper_email_log WHERE log_id = ?', $logId
			);
		}

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnLogEntryFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all log entries for a paper.
	 * @param $paperId int
	 * @return DAOResultFactory containing matching PaperEmailLogEntry ordered by sequence
	 */
	function &getPaperLogEntries($paperId, $rangeInfo = null) {
		$returner =& $this->getPaperLogEntriesByAssoc($paperId, null, null, $rangeInfo);
		return $returner;
	}

	/**
	 * Retrieve all log entries for a paper matching the specified association.
	 * @param $paperId int
	 * @param $assocType int
	 * @param $assocId int
	 * @return DAOResultFactory containing matching PaperEventLogEntry ordered by sequence
	 */
	function &getPaperLogEntriesByAssoc($paperId, $assocType = null, $assocId = null, $rangeInfo = null) {
		$params = array($paperId);
		if (isset($assocType)) {
			array_push($params, $assocType);
			if (isset($assocId)) {
				array_push($params, $assocId);
			}
		}

		$result =& $this->retrieveRange(
			'SELECT * FROM paper_email_log WHERE paper_id = ?' . (isset($assocType) ? ' AND assoc_type = ?' . (isset($assocId) ? ' AND assoc_id = ?' : '') : '') . ' ORDER BY log_id DESC',
			$params, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnLogEntryFromRow');
		return $returner;
	}

	/**
	 * Internal function to return an PaperEmailLogEntry object from a row.
	 * @param $row array
	 * @return PaperEmailLogEntry
	 */
	function &_returnLogEntryFromRow(&$row) {
		$entry = new PaperEmailLogEntry();
		$entry->setLogId($row['log_id']);
		$entry->setPaperId($row['paper_id']);
		$entry->setSenderId($row['sender_id']);
		$entry->setDateSent($this->datetimeFromDB($row['date_sent']));
		$entry->setIPAddress($row['ip_address']);
		$entry->setEventType($row['event_type']);
		$entry->setAssocType($row['assoc_type']);
		$entry->setAssocId($row['assoc_id']);
		$entry->setFrom($row['from_address']);
		$entry->setRecipients($row['recipients']);
		$entry->setCcs($row['cc_recipients']);
		$entry->setBccs($row['bcc_recipients']);
		$entry->setSubject($row['subject']);
		$entry->setBody($row['body']);

		HookRegistry::call('PaperEmailLogDAO::_returnLogEntryFromRow', array(&$entry, &$row));

		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry PaperEmailLogEntry
	 */	
	function insertLogEntry(&$entry) {
		if ($entry->getDateSent() == null) {
			$entry->setDateSent(Core::getCurrentDate());
		}
		if ($entry->getIPAddress() == null) {
			$entry->setIPAddress(Request::getRemoteAddr());
		}
		$this->update(
			sprintf('INSERT INTO paper_email_log
				(paper_id, sender_id, date_sent, ip_address, event_type, assoc_type, assoc_id, from_address, recipients, cc_recipients, bcc_recipients, subject, body)
				VALUES
				(?, ?, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($entry->getDateSent())),
			array(
				$entry->getPaperId(),
				$entry->getSenderId(),
				$entry->getIPAddress(),
				$entry->getEventType(),
				$entry->getAssocType(),
				$entry->getAssocId(),
				$entry->getFrom(),
				$entry->getRecipients(),
				$entry->getCcs(),
				$entry->getBccs(),
				$entry->getSubject(),
				$entry->getBody()
			)
		);

		$entry->setLogId($this->getInsertLogId());
		return $entry->getLogId();
	}

	/**
	 * Delete a single log entry for a paper.
	 * @param $logId int
	 * @param $paperId int optional
	 */
	function deleteLogEntry($logId, $paperId = null) {
		if (isset($paperId)) {
			return $this->update(
				'DELETE FROM paper_email_log WHERE log_id = ? AND paper_id = ?',
				array($logId, $paperId)
			);

		} else {
			return $this->update(
				'DELETE FROM paper_email_log WHERE log_id = ?', $logId
			);
		}
	}

	/**
	 * Delete all log entries for a paper.
	 * @param $paperId int
	 */
	function deletePaperLogEntries($paperId) {
		return $this->update(
			'DELETE FROM paper_email_log WHERE paper_id = ?', $paperId
		);
	}

	/**
	 * Transfer all paper log entries to another user.
	 * @param $paperId int
	 */
	function transferPaperLogEntries($oldUserId, $newUserId) {
		return $this->update(
			'UPDATE paper_email_log SET sender_id = ? WHERE sender_id = ?',
			array($newUserId, $oldUserId)
		);
	}

	/**
	 * Get the ID of the last inserted log entry.
	 * @return int
	 */
	function getInsertLogId() {
		return $this->getInsertId('paper_email_log', 'log_id');
	}
}

?>
