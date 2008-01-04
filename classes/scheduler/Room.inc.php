<?php

/**
 * @file Room.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package scheduler
 * @class Room
 *
 * Room class.
 * Basic class describing a room.
 *
 * $Id$
 */

class Room extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get the ID of the room.
	 * @return int
	 */
	function getRoomId() {
		return $this->getData('roomId');
	}

	/**
	 * Set the ID of the room.
	 * @param $roomId int
	 */
	function setRoomId($roomId) {
		return $this->setData('roomId', $roomId);
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
	 * Get the localized number of the room.
	 * @return string
	 */
	function getRoomNumber() {
		return $this->getLocalizedData('number');
	}

	/**
	 * Get the number of the room.
	 * @param $locale string
	 * @return string
	 */
	function getNumber($locale) {
		return $this->getData('number', $locale);
	}

	/**
	 * Set the number of the room.
	 * @param $number string
	 * @param $locale string
	 */
	function setNumber($number, $locale) {
		return $this->setData('number', $number, $locale);
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
