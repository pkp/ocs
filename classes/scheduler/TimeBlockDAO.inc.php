<?php

/**
 * @file TimeBlockDAO.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package scheduler
 * @class TimeBlockDAO
 *
 * Class for TimeBlock DAO.
 * Operations for retrieving and modifying TimeBlock objects.
 *
 * $Id$
 */

import('scheduler.TimeBlock');

class TimeBlockDAO extends DAO {
	/**
	 * Retrieve a timeBlock by ID.
	 * @param $timeBlockId int
	 * @return object TimeBlock
	 */
	function &getTimeBlock($timeBlockId) {
		$result = &$this->retrieve(
			'SELECT * FROM time_blocks WHERE time_block_id = ?', $timeBlockId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnTimeBlockFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve timeBlock sched conf ID by time block ID.
	 * @param $timeBlockId int
	 * @return int
	 */
	function getTimeBlockSchedConfId($timeBlockId) {
		$result = &$this->retrieve(
			'SELECT sched_conf_id FROM time_blocks WHERE time_block_id = ?', $timeBlockId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;
	}

	/**
	 * Check if a timeBlock exists with the given buliding id for a sched conf.
	 * @param $timeBlockId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function timeBlockExistsForSchedConf($timeBlockId, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT	COUNT(*)
				FROM time_blocks
				WHERE time_block_id = ?
				AND   sched_conf_id = ?',
			array(
				$timeBlockId,
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
	 * Internal function to return a TimeBlock object from a row.
	 * @param $row array
	 * @return TimeBlock
	 */
	function &_returnTimeBlockFromRow(&$row) {
		$timeBlock = &new TimeBlock();
		$timeBlock->setTimeBlockId($row['time_block_id']);
		$timeBlock->setSchedConfId($row['sched_conf_id']);
		$timeBlock->setStartTime($this->datetimeFromDB($row['start_time']));
		$timeBlock->setEndTime($this->datetimeFromDB($row['end_time']));
		return $timeBlock;
	}

	/**
	 * Insert a new TimeBlock.
	 * @param $timeBlock TimeBlock
	 * @return int 
	 */
	function insertTimeBlock(&$timeBlock) {
		$this->update(
			sprintf('INSERT INTO time_blocks
				(sched_conf_id, start_time, end_time)
				VALUES
				(?, %s, %s)',
				$this->datetimeToDB($timeBlock->getStartTime()),
				$this->datetimeToDB($timeBlock->getEndTime())
			),
			array(
				$timeBlock->getSchedConfId()
			)
		);
		$timeBlock->setTimeBlockId($this->getInsertTimeBlockId());
		return $timeBlock->getTimeBlockId();
	}

	/**
	 * Update an existing timeBlock.
	 * @param $timeBlock TimeBlock
	 * @return boolean
	 */
	function updateTimeBlock(&$timeBlock) {
		$returner = $this->update(
			sprintf('UPDATE time_blocks
				SET
					sched_conf_id = ?,
					start_time = %s,
					end_time = %s
				WHERE time_block_id = ?',
				$this->datetimeToDB($timeBlock->getStartTime()),
				$this->datetimeToDB($timeBlock->getEndTime())
			),
			array(
				$timeBlock->getSchedConfId(),
				$timeBlock->getTimeBlockId()
			)
		);
		return $returner;
	}

	/**
	 * Delete a timeBlock and all dependent items.
	 * @param $timeBlock TimeBlock 
	 * @return boolean
	 */
	function deleteTimeBlock($timeBlock) {
		return $this->deleteTimeBlockById($timeBlock->getTimeBlockId());
	}

	/**
	 * Delete a timeBlock by ID. Deletes dependents.
	 * @param $timeBlockId int
	 * @return boolean
	 */
	function deleteTimeBlockById($timeBlockId) {
		return $this->update('DELETE FROM time_blocks WHERE time_block_id = ?', $timeBlockId);
	}

	/**
	 * Delete time blocks by scheduled conference ID.
	 * @param $conferenceId int
	 */
	function deleteTimeBlocksBySchedConfId($schedConfId) {
		$timeBlocks =& $this->getTimeBlocksBySchedConfId($schedConfId);
		while (($timeBlock =& $timeBlocks->next())) {
			$this->deleteTimeBlock($timeBlock);
			unset($timeBlock);
		}
	}

	/**
	 * Retrieve an array of timeBlocks matching a particular sched conf ID.
	 * @param $schedConfId int
	 * @return object DAOResultFactory containing matching TimeBlocks
	 */
	function &getTimeBlocksBySchedConfId($schedConfId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM time_blocks WHERE sched_conf_id = ? ORDER BY sched_conf_id',
			$schedConfId,
			$rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnTimeBlockFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted timeBlock.
	 * @return int
	 */
	function getInsertTimeBlockId() {
		return $this->getInsertId('time_blocks', 'time_block_id');
	}
}

?>
