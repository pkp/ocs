<?php

/**
 * @file Building.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Building
 * @ingroup scheduler
 * @see BuildingDAO
 *
 * @brief Basic class describing a building.
 */

//$Id$

class Building extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get the ID of the building.
	 * @return int
	 */
	function getBuildingId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set the ID of the building.
	 * @param $buildingId int
	 */
	function setBuildingId($buildingId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($buildingId);
	}

	/**
	 * Get the sched conf ID of the building.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set the sched conf ID of the building.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get the localized name of the building.
	 * @return string
	 */
	function getBuildingName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the name of the building.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the name of the building.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get the localized abbreviation of the building.
	 * @return string
	 */
	function getBuildingAbbrev() {
		return $this->getLocalizedData('abbrev');
	}

	/**
	 * Get the abbreviation of the building.
	 * @param $locale string
	 * @return string
	 */
	function getAbbrev($locale) {
		return $this->getData('abbrev', $locale);
	}

	/**
	 * Set the abbreviation of the building.
	 * @param $abbrev string
	 * @param $locale string
	 */
	function setAbbrev($abbrev, $locale) {
		return $this->setData('abbrev', $abbrev, $locale);
	}

	/**
	 * Get the localized description of the building.
	 * @return string
	 */
	function getBuildingDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get the description of the building.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set the description of the building.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}
}

?>
