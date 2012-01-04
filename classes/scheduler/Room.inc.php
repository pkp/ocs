<?php

/**
 * @file Room.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Room
 * @ingroup scheduler
 * @see RoomDAO
 *
 * @brief Basic class describing a room.
 */

//$Id$

class Room extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get the ID of the room.
	 * @return int
	 */
	function getRoomId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set the ID of the room.
	 * @param $roomId int
	 */
	function setRoomId($roomId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($roomId);
	}

	/**
	 * Get the building ID of the room.
	 * @return int
	 */
	function getBuildingId() {
		return $this->getData('buildingId');
	}

	/**
	 * Set the building ID of the room.
	 * @param $buildingId int
	 */
	function setBuildingId($buildingId) {
		return $this->setData('buildingId', $buildingId);
	}

	/**
	 * Get the localized name of the room.
	 * @return string
	 */
	function getRoomName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the name of the room.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the name of the room.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get the localized abbreviation of the room.
	 * @return string
	 */
	function getRoomAbbrev() {
		return $this->getLocalizedData('abbrev');
	}

	/**
	 * Get the abbreviation of the room.
	 * @param $locale string
	 * @return string
	 */
	function getAbbrev($locale) {
		return $this->getData('abbrev', $locale);
	}

	/**
	 * Set the abbreviation of the room.
	 * @param $abbrev string
	 * @param $locale string
	 */
	function setAbbrev($abbrev, $locale) {
		return $this->setData('abbrev', $abbrev, $locale);
	}

	/**
	 * Get the localized description of the room.
	 * @return string
	 */
	function getRoomDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get the description of the room.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set the description of the room.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}
}

?>
