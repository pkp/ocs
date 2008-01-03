<?php

/**
 * @file Building.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package scheduler
 * @class Building
 *
 * Building class.
 * Basic class describing a building.
 *
 * $Id$
 */

class Building extends DataObject {

	function Building() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the building.
	 * @return int
	 */
	function getBuildingId() {
		return $this->getData('buildingId');
	}

	/**
	 * Set the ID of the building.
	 * @param $buildingId int
	 */
	function setBuildingId($buildingId) {
		return $this->setData('buildingId', $buildingId);
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
	 * Get the type of the building.
	 * @return string
	 */
	function getBuildingName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the type of the building.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the type of the building.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

}

?>
