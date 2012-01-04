<?php

/**
 * @file BuildingDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BuildingDAO
 * @ingroup scheduler
 * @see Building
 *
 * @brief Operations for retrieving and modifying Building objects.
 */

//$Id$

import('scheduler.Building');

class BuildingDAO extends DAO {
	/**
	 * Retrieve a building by ID.
	 * @param $buildingId int
	 * @return object Building
	 */
	function &getBuilding($buildingId) {
		$result =& $this->retrieve(
			'SELECT * FROM buildings WHERE building_id = ?', $buildingId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnBuildingFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve building sched conf ID by building ID.
	 * @param $buildingId int
	 * @return int
	 */
	function getBuildingSchedConfId($buildingId) {
		$result =& $this->retrieve(
			'SELECT sched_conf_id FROM buildings WHERE building_id = ?', $buildingId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;
	}

	/**
	 * Check if a building exists with the given building id for a sched conf.
	 * @param $buildingId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function buildingExistsForSchedConf($buildingId, $schedConfId) {
		$result =& $this->retrieve(
			'SELECT	COUNT(*)
				FROM buildings
				WHERE building_id = ?
				AND   sched_conf_id = ?',
			array(
				$buildingId,
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
		return array('name', 'abbrev', 'description');
	}

	/**
	 * Internal function to return a Building object from a row.
	 * @param $row array
	 * @return Building
	 */
	function &_returnBuildingFromRow(&$row) {
		$building = new Building();
		$building->setId($row['building_id']);
		$building->setSchedConfId($row['sched_conf_id']);
		$this->getDataObjectSettings('building_settings', 'building_id', $row['building_id'], $building);

		return $building;
	}

	/**
	 * Update the localized settings for this object
	 * @param $building object
	 */
	function updateLocaleFields(&$building) {
		$this->updateDataObjectSettings('building_settings', $building, array(
			'building_id' => $building->getId()
		));
	}

	/**
	 * Insert a new Building.
	 * @param $building Building
	 * @return int 
	 */
	function insertBuilding(&$building) {
		$this->update(
			sprintf('INSERT INTO buildings
				(sched_conf_id)
				VALUES
				(?)'),
			array(
				$building->getSchedConfId()
			)
		);
		$building->setId($this->getInsertBuildingId());
		$this->updateLocaleFields($building);
		return $building->getId();
	}

	/**
	 * Update an existing building.
	 * @param $building Building
	 * @return boolean
	 */
	function updateBuilding(&$building) {
		$returner = $this->update(
			sprintf('UPDATE buildings
				SET
					sched_conf_id = ?
				WHERE building_id = ?'),
			array(
				$building->getSchedConfId(),
				$building->getId()
			)
		);
		$this->updateLocaleFields($building);
		return $returner;
	}

	/**
	 * Delete a building and all dependent items.
	 * @param $building Building 
	 * @return boolean
	 */
	function deleteBuilding($building) {
		return $this->deleteBuildingById($building->getId());
	}

	/**
	 * Delete a building by ID. Deletes dependents.
	 * @param $buildingId int
	 * @return boolean
	 */
	function deleteBuildingById($buildingId) {
		// Delete dependent rooms first.
		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$rooms =& $roomDao->deleteRoomsByBuildingId($buildingId);

		$this->update('DELETE FROM building_settings WHERE building_id = ?', $buildingId);
		$ret = $this->update('DELETE FROM buildings WHERE building_id = ?', $buildingId);
		return $ret;
	}

	/**
	 * Delete buildings by scheduled conference ID.
	 * @param $conferenceId int
	 */
	function deleteBuildingsBySchedConfId($schedConfId) {
		$buildings =& $this->getBuildingsBySchedConfId($schedConfId);
		while (($building =& $buildings->next())) {
			$this->deleteBuilding($building);
			unset($building);
		}
	}

	/**
	 * Retrieve an array of buildings matching a particular sched conf ID.
	 * @param $schedConfId int
	 * @return object DAOResultFactory containing matching Buildings
	 */
	function &getBuildingsBySchedConfId($schedConfId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM buildings WHERE sched_conf_id = ? ORDER BY sched_conf_id',
			$schedConfId,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnBuildingFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted building.
	 * @return int
	 */
	function getInsertBuildingId() {
		return $this->getInsertId('buildings', 'building_id');
	}
}

?>
