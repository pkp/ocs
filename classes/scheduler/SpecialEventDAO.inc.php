<?php

/**
 * @file SpecialEventDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SpecialEventDAO
 * @ingroup scheduler
 * @see SpecialEvent
 *
 * @brief Operations for retrieving and modifying SpecialEvent objects.
 */

//$Id$

import('scheduler.SpecialEvent');

class SpecialEventDAO extends DAO {
	/**
	 * Retrieve a special event by ID.
	 * @param $specialEventId int
	 * @return object SpecialEvent
	 */
	function &getSpecialEvent($specialEventId) {
		$result =& $this->retrieve(
			'SELECT * FROM special_events WHERE special_event_id = ?', $specialEventId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSpecialEventFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve special event sched conf ID by special_event ID.
	 * @param $specialEventId int
	 * @return int
	 */
	function getSpecialEventSchedConfId($specialEventId) {
		$result =& $this->retrieve(
			'SELECT sched_conf_id FROM special_events WHERE special_event_id = ?', $specialEventId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;
	}

	/**
	 * Check if a special event exists with the given special event id for a sched conf.
	 * @param $specialEventId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function specialEventExistsForSchedConf($specialEventId, $schedConfId) {
		$result =& $this->retrieve(
			'SELECT	COUNT(*)
				FROM special_events
				WHERE special_event_id = ?
				AND   sched_conf_id = ?',
			array(
				$specialEventId,
				$schedConfId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a list of localized fields for this object.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name', 'description');
	}

	/**
	 * Internal function to return a SpecialEvent object from a row.
	 * @param $row array
	 * @return SpecialEvent
	 */
	function &_returnSpecialEventFromRow(&$row) {
		$specialEvent = new SpecialEvent();
		$specialEvent->setId($row['special_event_id']);
		$specialEvent->setSchedConfId($row['sched_conf_id']);
		$specialEvent->setStartTime($this->datetimeFromDB($row['start_time']));
		$specialEvent->setEndTime($this->datetimeFromDB($row['end_time']));
		$this->getDataObjectSettings('special_event_settings', 'special_event_id', $row['special_event_id'], $specialEvent);

		return $specialEvent;
	}

	/**
	 * Update the localized settings for this object
	 * @param $specialEvent object
	 */
	function updateLocaleFields(&$specialEvent) {
		$this->updateDataObjectSettings('special_event_settings', $specialEvent, array(
			'special_event_id' => $specialEvent->getId()
		));
	}

	/**
	 * Insert a new SpecialEvent.
	 * @param $specialEvent SpecialEvent
	 * @return int 
	 */
	function insertSpecialEvent(&$specialEvent) {
		$this->update(
			sprintf('INSERT INTO special_events
				(sched_conf_id, start_time, end_time)
				VALUES
				(?, %s, %s)',
				$this->datetimeToDB($specialEvent->getStartTime()), $this->datetimeToDB($specialEvent->getEndTime())
			), array(
				(int) $specialEvent->getSchedConfId()
			)
		);
		$specialEvent->setId($this->getInsertSpecialEventId());
		$this->updateLocaleFields($specialEvent);
		return $specialEvent->getId();
	}

	/**
	 * Update an existing special event.
	 * @param $specialEvent SpecialEvent
	 * @return boolean
	 */
	function updateSpecialEvent(&$specialEvent) {
		$returner = $this->update(
			sprintf('UPDATE	special_events
				SET	sched_conf_id = ?,
					start_time = %s,
					end_time = %s
				WHERE special_event_id = ?',
				$this->datetimeToDB($specialEvent->getStartTime()), $this->datetimeToDB($specialEvent->getEndTime())
			), array(
				(int) $specialEvent->getSchedConfId(),
				(int) $specialEvent->getId()
			)
		);
		$this->updateLocaleFields($specialEvent);
		return $returner;
	}

	/**
	 * Delete a special event and all dependent items.
	 * @param $specialEvent SpecialEvent 
	 * @return boolean
	 */
	function deleteSpecialEvent($specialEvent) {
		return $this->deleteSpecialEventById($specialEvent->getId());
	}

	/**
	 * Delete a special event by ID. Deletes dependents.
	 * @param $specialEventId int
	 * @return boolean
	 */
	function deleteSpecialEventById($specialEventId) {
		$this->update('DELETE FROM special_event_settings WHERE special_event_id = ?', $specialEventId);
		$ret = $this->update('DELETE FROM special_events WHERE special_event_id = ?', $specialEventId);
		return $ret;
	}

	/**
	 * Delete special events by scheduled conference ID.
	 * @param $conferenceId int
	 */
	function deleteSpecialEventsBySchedConfId($schedConfId) {
		$specialEvents =& $this->getSpecialEventsBySchedConfId($schedConfId);
		while (($specialEvent =& $specialEvents->next())) {
			$this->deleteSpecialEvent($specialEvent);
			unset($specialEvent);
		}
	}

	/**
	 * Retrieve an array of special events matching a particular sched conf ID.
	 * @param $schedConfId int
	 * @return object DAOResultFactory containing matching special events
	 */
	function &getSpecialEventsBySchedConfId($schedConfId, $rangeInfo = null) {
		$result =& $this->retrieveRange('SELECT * FROM special_events WHERE sched_conf_id = ? ORDER BY start_time, end_time', $schedConfId, $rangeInfo);
		$returner = new DAOResultFactory($result, $this, '_returnSpecialEventFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted special event.
	 * @return int
	 */
	function getInsertSpecialEventId() {
		return $this->getInsertId('special_events', 'special_event_id');
	}
}

?>
