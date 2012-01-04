<?php

/**
 * @defgroup scheduler
 */
 
/**
 * @file SpecialEvent.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SpecialEvent
 * @ingroup scheduler
 * @see SpecialEvent
 *
 * @brief Basic class describing a specialEvent.
 */

//$Id$

class SpecialEvent extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get the ID of the specialEvent.
	 * @return int
	 */
	function getSpecialEventId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set the ID of the specialEvent.
	 * @param $specialEventId int
	 */
	function setSpecialEventId($specialEventId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($specialEventId);
	}

	/**
	 * Get the sched conf ID of the specialEvent.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set the sched conf ID of the specialEvent.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get the presentation start time.
	 * @return date
	 */
	function getStartTime() {
		return $this->getData('startTime');
	}

	/**
	 * Set the presentation start time.
	 * @param $startTime date
	 */
	function setStartTime($startTime) {
		return $this->setData('startTime', $startTime);
	}

	/**
	 * Get the special event end time.
	 * @return date
	 */
	function getEndTime() {
		return $this->getData('endTime');
	}

	/**
	 * Get the special event end time.
	 * @param $endTime date
	 */
	function setEndTime($endTime) {
		return $this->setData('endTime', $endTime);
	}

	/**
	 * Get the localized name of the specialEvent.
	 * @return string
	 */
	function getSpecialEventName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the name of the specialEvent.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the name of the specialEvent.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get the localized description of the specialEvent.
	 * @return string
	 */
	function getSpecialEventDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get the description of the special event.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set the description of the special event.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}
}

?>
