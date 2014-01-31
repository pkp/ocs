<?php

/**
 * @defgroup conference_log
 */
 
/**
 * @file ConferenceEventLogDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceEventLogDAO
 * @ingroup conference_log
 * @see ConferenceEventLogEntry
 *
 * @brief Class for inserting/accessing conference history log entries.
 */

//$Id$

import ('conference.log.ConferenceEventLogEntry');

class ConferenceEventLogDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ConferenceEventLogDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a log entry by ID.
	 * @param $logId int
	 * @param $conferenceId int optional
	 * @return ConferenceEventLogEntry
	 */
	function &getLogEntry($logId, $conferenceId = null, $schedConfId = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$args = array(
			'title', // Conference
			$primaryLocale,
			'title',
			$locale,
			'title', // Scheduled Conference
			$primaryLocale,
			'title',
			$locale,
			$logId
		);

		if (isset($conferenceId))
			$args[] = $conferenceId;

		if (isset($schedConfId))
			$args[] = $schedConfId;

		$result =& $this->retrieve(
			'SELECT	e.*,
				COALESCE(sctl.setting_value, sct.setting_value) AS sched_conf_title,
				COALESCE(ctl.setting_value, ct.setting_value) AS conference_title
			FROM	conference_event_log e
				LEFT JOIN sched_confs sc ON (e.sched_conf_id = sc.sched_conf_id)
				LEFT JOIN conferences c ON (e.conference_id = c.conference_id)
				LEFT JOIN conference_settings ct ON (ct.setting_name = ? AND ct.locale = ? AND ct.conference_id = e.conference_id)
				LEFT JOIN conference_settings ctl ON (ctl.setting_name = ? AND ctl.locale = ? AND ctl.conference_id = e.conference_id)
				LEFT JOIN sched_conf_settings sct ON (sct.setting_name = ? AND sct.locale = ? AND sct.sched_conf_id = e.sched_conf_id)
				LEFT JOIN sched_conf_settings sctl ON (sctl.setting_name = ? AND sctl.locale = ? AND sctl.sched_conf_id = e.sched_conf_id)
			WHERE e.log_id = ?' .
				(isset($conferenceId) ? ' AND e.conference_id = ?' : '') .
				(isset($schedConfId) ? ' AND e.sched_conf_id = ?' : ''),
				$args
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnLogEntryFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all log entries for a conference.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @return DAOResultFactory containing matching ConferenceEventLogEntry ordered by sequence
	 */
	function &getConferenceLogEntries($conferenceId, $schedConfId = null, $rangeInfo = null) {
		$returner =& $this->getConferenceLogEntriesByAssoc($conferenceId, $schedConfId, null, null, $rangeInfo);
		return $returner;
	}

	/**
	 * Retrieve all log entries for a conference matching the specified association.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $limit int limit the number of entries retrieved (default false)
	 * @param $recentFirst boolean order with most recent entries first (default true)
	 * @return DAOResultFactory containing matching ConferenceEventLogEntry ordered by sequence
	 */
	function &getConferenceLogEntriesByAssoc($conferenceId, $schedConfId = null, $assocType = null, $assocId = null, $rangeInfo = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$params = array(
			'title', // Conference
			$primaryLocale,
			'title',
			$locale,
			'title', // Scheduled Conference
			$primaryLocale,
			'title',
			$locale,
			$conferenceId
		);

		if (isset($schedConfId))
			$params[] = $schedConfId;

		if (isset($assocType)) {
			$params[] = $assocType;
			if (isset($assocId))
				$params[] = $assocId;
		}

		$result =& $this->retrieveRange(
			'SELECT	e.*,
				COALESCE(sctl.setting_value, sct.setting_value) AS sched_conf_title,
				COALESCE(ctl.setting_value, ct.setting_value)
			FROM	conference_event_log e
				LEFT JOIN sched_confs sc ON (e.sched_conf_id = sc.sched_conf_id)
				LEFT JOIN conferences c ON (e.conference_id = c.conference_id)
				LEFT JOIN conference_settings ct ON (ct.setting_name = ? AND ct.locale = ? AND ct.conference_id = e.conference_id)
				LEFT JOIN conference_settings ctl ON (ctl.setting_name = ? AND ctl.locale = ? AND ctl.conference_id = e.conference_id)
				LEFT JOIN sched_conf_settings sct ON (sct.setting_name = ? AND sct.locale = ? AND sct.sched_conf_id = e.sched_conf_id)
				LEFT JOIN sched_conf_settings sctl ON (sctl.setting_name = ? AND sctl.locale = ? AND sctl.sched_conf_id = e.sched_conf_id)
				WHERE e.conference_id = ?' .
				(isset($schedConfId) ? ' AND e.sched_conf_id = ? ':'') .
				(isset($assocType) ? ' AND e.assoc_type = ?' . (isset($assocId) ? ' AND e.assoc_id = ?' : '') : '') .
				' ORDER BY log_id DESC',
			$params, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnLogEntryFromRow');
		return $returner;
	}

	/**
	 * Internal function to return an ConferenceEventLogEntry object from a row.
	 * @param $row array
	 * @return ConferenceEventLogEntry
	 */
	function &_returnLogEntryFromRow(&$row) {
		$entry = new ConferenceEventLogEntry();
		$entry->setLogId($row['log_id']);
		$entry->setConferenceId($row['conference_id']);
		$entry->setSchedConfId($row['sched_conf_id']);
		$entry->setUserId($row['user_id']);
		$entry->setDateLogged($this->datetimeFromDB($row['date_logged']));
		$entry->setIPAddress($row['ip_address']);
		$entry->setLogLevel($row['log_level']);
		$entry->setEventType($row['event_type']);
		$entry->setAssocType($row['assoc_type']);
		$entry->setSchedConfTitle($row['sched_conf_title']);
		$entry->setConferenceTitle($row['conference_title']);
		$entry->setAssocId($row['assoc_id']);
		$entry->setIsTranslated($row['is_translated']);
		$entry->setEntryParams(unserialize($row['entry_params']));
		$entry->setMessage($row['message']);

		HookRegistry::call('ConferenceEventLogDAO::_returnLogEntryFromRow', array(&$entry, &$row));

		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry ConferenceEventLogEntry
	 */	
	function insertLogEntry(&$entry) {
		if ($entry->getDateLogged() == null) {
			$entry->setDateLogged(Core::getCurrentDate());
		}
		if ($entry->getIPAddress() == null) {
			$entry->setIPAddress(Request::getRemoteAddr());
		}
		$this->update(
			sprintf('INSERT INTO conference_event_log
				(conference_id, sched_conf_id, user_id, date_logged, ip_address, log_level, event_type, assoc_type, assoc_id, is_translated, entry_params, message)
				VALUES
				(?, ?, ?, %s, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($entry->getDateLogged())),
			array(
				$entry->getConferenceId(),
				$entry->getSchedConfId(),
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

		$entry->setLogId($this->getInsertLogId());
		return $entry->getLogId();
	}

	/**
	 * Delete a single log entry for a conference.
	 * @param $logId int
	 * @param $conferenceId int
	 * @param $schedConfId int optional
	 */
	function deleteLogEntry($logId, $conferenceId, $schedConfId = null) {
		$args = array($logId, $conferenceId);
		if(isset($schedConfId))
			$args[] = $schedConfId;

		return $this->update(
			'DELETE FROM conference_event_log WHERE log_id = ?
				AND conference_id = ?' .
			(isset($schedConfId)? ' AND sched_conf_id = ?' : ''),
			$args);
	}

	/**
	 * Delete all log entries for a conference.
	 * @param $conferenceId int
	 */
	function deleteConferenceLogEntries($conferenceId, $schedConfId = null) {
		$args = array($conferenceId);

		if(isset($schedConfId))
			$args[] = $schedConfId;

		return $this->update(
			'DELETE FROM conference_event_log WHERE conference_id = ?' .
			(isset($schedConfId) ? ' AND sched_conf_id = ?' : ''),
			(count($args)>1 ? $args : array_shift($args)));
	}

	/**
	 * Transfer all conference log entries to another user.
	 * @param $oldUserId int
	 * @param $newUserId int
	 */
	function transferConferenceLogEntries($oldUserId, $newUserId) {
		return $this->update(
			'UPDATE conference_event_log SET user_id = ? WHERE user_id = ?',
			array($newUserId, $oldUserId)
		);
	}

	/**
	 * Get the ID of the last inserted log entry.
	 * @return int
	 */
	function getInsertLogId() {
		return $this->getInsertId('conference_event_log', 'log_id');
	}

}

?>
