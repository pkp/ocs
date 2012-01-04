<?php

/**
 * @file classes/scheduler/TimeBlock.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set the ID of the timeBlock.
	 * @param $timeBlockId int
	 */
	function setTimeBlockId($timeBlockId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($timeBlockId);
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
	function getStartTime() {
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
	function getEndTime() {
		return $this->getData('endTime');
	}

	/**
	 * Set the end time of the timeBlock.
	 * @param $endTime string
	 */
	function setEndTime($endTime) {
		return $this->setData('endTime', $endTime);
	}

	/**
	 * Get the name of the time block.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the name of the time block.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get the colour of this time block for use when it has been assigned to a presentation or special event.
	 * @return string
	 */
	function getAssignedColour() {
		return $this->getData('assignedColour');
	}

	/**
	 * Set the colour of this time block for use when it has been assigned to a presentation or special event.
	 * @param $assignedColour string
	 */
	function setAssignedColour($assignedColour) {
		return $this->setData('assignedColour', $assignedColour);
	}

	/**
	 * Get the colour of this time block for use when it has not been assigned to a presentation or special event.
	 * @return string
	 */
	function getUnassignedColour() {
		return $this->getData('unassignedColour');
	}

	/**
	 * Set the colour of this time block for use when it has not been assigned to a presentation or special event.
	 * @param $unassignedColour string
	 */
	function setUnassignedColour($unassignedColour) {
		return $this->setData('unassignedColour', $unassignedColour);
	}
}

?>
