<?php

/**
 * @file TimeBlock.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package scheduler
 * @class TimeBlock
 *
 * TimeBlock class.
 * Basic class describing a block of time available for scheduling.
 *
 * $Id$
 */

class TimeBlock extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get the ID of the timeBlock.
	 * @return int
	 */
	function getTimeBlockId() {
		return $this->getData('timeBlockId');
	}

	/**
	 * Set the ID of the timeBlock.
	 * @param $timeBlockId int
	 */
	function setTimeBlockId($timeBlockId) {
		return $this->setData('timeBlockId', $timeBlockId);
	}

	/**
	 * Get the sched conf ID of the timeBlock.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set the sched conf ID of the timeBlock.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get the start time of the timeBlock.
	 * @return string
	 */
	function getStartTime($startTime) {
		return $this->getData('startTime');
	}

	/**
	 * Set the start time of the timeBlock.
	 * @param $startTime string
	 */
	function setStartTime($startTime) {
		return $this->setData('startTime', $startTime);
	}

	/**
	 * Get the end time of the timeBlock.
	 * @return string
	 */
	function getEndTime($endTime) {
		return $this->getData('endTime');
	}

	/**
	 * Set the end time of the timeBlock.
	 * @param $endTime string
	 */
	function setEndTime($endTime) {
		return $this->setData('endTime', $endTime);
	}
}

?>
