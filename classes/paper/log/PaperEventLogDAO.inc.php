<?php

/**
 * @file PaperEventLogDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperEventLogDAO
 * @ingroup paper_log
 * @see PaperEventLogEntry
 *
 * @brief Class for inserting/accessing paper history log entries.
 */


import ('classes.paper.log.PaperEventLogEntry');

class PaperEventLogDAO extends DAO {
	/**
	 * Retrieve a log entry by ID.
	 * @param $logId int
	 * @param $paperId int optional
	 * @return PaperEventLogEntry
	 */
	function &getLogEntry($logId, $paperId = null) {
		if (isset($paperId)) {
			$result =& $this->retrieve(
				'SELECT * FROM paper_event_log WHERE log_id = ? AND paper_id = ?',
				array($logId, $paperId)
			);
		} else {
			$result =& $this->retrieve(
				'SELECT * FROM paper_event_log WHERE log_id = ?', $logId
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
	 * @return DAOResultFactory containing matching PaperEventLogEntry PaperEventLogEntry ordered by sequence
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
	 * @param $limit int limit the number of entries retrieved (default false)
	 * @param $recentFirst boolean order with most recent entries first (default true)
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
			'SELECT * FROM paper_event_log WHERE paper_id = ?' . (isset($assocType) ? ' AND assoc_type = ?' . (isset($assocId) ? ' AND assoc_id = ?' : '') : '') . ' ORDER BY log_id DESC',
			$params, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnLogEntryFromRow');
		return $returner;
	}

	/**
	 * Internal function to return an PaperEventLogEntry object from a row.
	 * @param $row array
	 * @return PaperEventLogEntry
	 */
	function &_returnLogEntryFromRow(&$row) {
		$entry = new PaperEventLogEntry();
		$entry->setLogId($row['log_id']);
		$entry->setPaperId($row['paper_id']);
		$entry->setUserId($row['user_id']);
		$entry->setDateLogged($this->datetimeFromDB($row['date_logged']));
		$entry->setIPAddress($row['ip_address']);
		$entry->setLogLevel($row['log_level']);
		$entry->setEventType($row['event_type']);
		$entry->setAssocType($row['assoc_type']);
		$entry->setAssocId($row['assoc_id']);
		$entry->setIsTranslated($row['is_translated']);
		$entry->setEntryParams(unserialize($row['entry_params']));
		$entry->setMessage($row['message']);

		HookRegistry::call('PaperEventLogDAO::_returnLogEntryFromRow', array(&$entry, &$row));

		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry PaperEventLogEntry
	 */	
	function insertLogEntry(&$entry) {
		if ($entry->getDateLogged() == null) {
			$entry->setDateLogged(Core::getCurrentDate());
		}
		if ($entry->getIPAddress() == null) {
			$entry->setIPAddress(Request::getRemoteAddr());
		}
		$this->update(
			sprintf('INSERT INTO paper_event_log
				(paper_id, user_id, date_logged, ip_address, log_level, event_type, assoc_type, assoc_id, is_translated, entry_params, message)
				VALUES
				(?, ?, %s, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($entry->getDateLogged())),
			array(
				$entry->getPaperId(),
				$entry->getUserId(),
				$entry->getIPAddress(),
				$entry->getLogLevel(),
				$entry->getEventType(),
				$entry->getAssocType(),
				$entry->getAssocId(),
				1, // is_translated: All new entries are.
				$entry->getEntryParamsSerialized(),
				$entry->getMessage()
			)
		);

		$entry->setLogId($this->getInsertId());
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
				'DELETE FROM paper_event_log WHERE log_id = ? AND paper_id = ?',
				array($logId, $paperId)
			);

		} else {
			return $this->update(
				'DELETE FROM paper_event_log WHERE log_id = ?', $logId
			);
		}
	}

	/**
	 * Delete all log entries for a paper.
	 * @param $paperId int
	 */
	function deletePaperLogEntries($paperId) {
		return $this->update(
			'DELETE FROM paper_event_log WHERE paper_id = ?', $paperId
		);
	}

	/**
	 * Transfer all paper log entries to another user.
	 * @param $paperId int
	 */
	function transferPaperLogEntries($oldUserId, $newUserId) {
		return $this->update(
			'UPDATE paper_event_log SET user_id = ? WHERE user_id = ?',
			array($newUserId, $oldUserId)
		);
	}

	/**
	 * Get the ID of the last inserted log entry.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('paper_event_log', 'log_id');
	}
}

?>
