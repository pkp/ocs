<?php

/**
 * @file RoomDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoomDAO
 * @ingroup scheduler
 * @see Room
 *
 * @brief Operations for retrieving and modifying Room objects.
 */

//$Id$

import('scheduler.Room');

class RoomDAO extends DAO {
	/**
	 * Retrieve a room by ID.
	 * @param $roomId int
	 * @return object Room
	 */
	function &getRoom($roomId) {
		$result =& $this->retrieve(
			'SELECT * FROM rooms WHERE room_id = ?', $roomId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnRoomFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve room building ID by room ID.
	 * @param $roomId int
	 * @return int
	 */
	function getRoomBuildingId($roomId) {
		$result =& $this->retrieve(
			'SELECT building_id FROM rooms WHERE room_id = ?', $roomId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;
	}

	/**
	 * Retrieve sched conf ID by room ID.
	 * @param $roomId int
	 * @return int
	 */
	function getRoomSchedConfId($roomId) {
		$result =& $this->retrieve(
			'SELECT b.sched_conf_id FROM rooms r LEFT JOIN buildings b ON (r.building_id = b.building_id) WHERE r.room_id = ?', $roomId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;
	}

	/**
	 * Retrieve a list of localized fields for this object.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name', 'abbrev', 'description');
	}

	/**
	 * Internal function to return a Room object from a row.
	 * @param $row array
	 * @return Room
	 */
	function &_returnRoomFromRow(&$row) {
		$room = new Room();
		$room->setId($row['room_id']);
		$room->setBuildingId($row['building_id']);
		$this->getDataObjectSettings('room_settings', 'room_id', $row['room_id'], $room);

		return $room;
	}

	/**
	 * Update the localized settings for this object
	 * @param $room object
	 */
	function updateLocaleFields(&$room) {
		$this->updateDataObjectSettings('room_settings', $room, array(
			'room_id' => $room->getId()
		));
	}

	/**
	 * Insert a new Room.
	 * @param $room Room
	 * @return int 
	 */
	function insertRoom(&$room) {
		$this->update(
			sprintf('INSERT INTO rooms
				(building_id)
				VALUES
				(?)'),
			array(
				$room->getBuildingId()
			)
		);
		$room->setId($this->getInsertRoomId());
		$this->updateLocaleFields($room);
		return $room->getId();
	}

	/**
	 * Update an existing room.
	 * @param $room Room
	 * @return boolean
	 */
	function updateRoom(&$room) {
		$returner = $this->update(
			sprintf('UPDATE rooms
				SET
					building_id = ?
				WHERE room_id = ?'),
			array(
				$room->getBuildingId(),
				$room->getId()
			)
		);
		$this->updateLocaleFields($room);
		return $returner;
	}

	/**
	 * Delete a room and all dependent items.
	 * @param $room Room 
	 * @return boolean
	 */
	function deleteRoom($room) {
		return $this->deleteRoomById($room->getId());
	}

	/**
	 * Delete a room by ID. Deletes dependents.
	 * @param $roomId int
	 * @return boolean
	 */
	function deleteRoomById($roomId) {
		$this->update('DELETE FROM room_settings WHERE room_id = ?', $roomId);
		$ret = $this->update('DELETE FROM rooms WHERE room_id = ?', $roomId);
		return $ret;
	}

	/**
	 * Delete rooms by scheduled conference ID.
	 * @param $buildingId int
	 */
	function deleteRoomsByBuildingId($buildingId) {
		$rooms =& $this->getRoomsByBuildingId($buildingId);
		while (($room =& $rooms->next())) {
			$this->deleteRoom($room);
			unset($room);
		}
	}

	/**
	 * Retrieve an array of rooms matching a particular building ID.
	 * @param $buildingId int
	 * @return object DAOResultFactory containing matching Rooms
	 */
	function &getRoomsByBuildingId($buildingId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM rooms WHERE building_id = ? ORDER BY building_id',
			$buildingId,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnRoomFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted room.
	 * @return int
	 */
	function getInsertRoomId() {
		return $this->getInsertId('rooms', 'room_id');
	}
}

?>
