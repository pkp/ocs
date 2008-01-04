<?php

/**
 * @file SchedulerHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class SchedulerHandler
 *
 * Handle requests for registration management functions. 
 *
 * $Id$
 */

class SchedulerHandler extends ManagerHandler {

	/**
	 * Display the scheduler index page
	 */
	function scheduler() {
		parent::validate();
		SchedulerHandler::setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'schedConf.managementPages.scheduler');
		$templateMgr->display('manager/scheduler/index.tpl');
	}

	/**
	 * Display a list of buildings to manage.
	 */
	function buildings() {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('buildings');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$buildings =& $buildingDao->getBuildingsBySchedConfId($schedConf->getSchedConfId(), $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('buildings', $buildings);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.buildings');
		$templateMgr->display('manager/scheduler/buildings.tpl');
	}

	/**
	 * Delete a building.
	 * @param $args array first parameter is the ID of the building to delete
	 */
	function deleteBuilding($args) {
		parent::validate();
		$buildingId = (int) array_shift($args);
		$schedConf =& Request::getSchedConf();
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		// Ensure building is for this conference
		if ($buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getSchedConfId()) {
			$buildingDao->deleteBuildingById($buildingId);
		}

		Request::redirect(null, null, null, 'buildings');
	}

	/**
	 * Display form to edit a building.
	 * @param $args array optional, first parameter is the ID of the building to edit
	 */
	function editBuilding($args = array()) {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$buildingId = !isset($args) || empty($args) ? null : (int) $args[0];
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		// Ensure building is valid and for this conference
		if (($buildingId != null && $buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getSchedConfId()) || ($buildingId == null)) {
			import('manager.form.BuildingForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'buildings'), 'manager.scheduler.buildings'));

			if ($buildingId == null) {
				$templateMgr->assign('buildingTitle', 'manager.scheduler.building.createBuildingShort');
			} else {
				$templateMgr->assign('buildingTitle', 'manager.scheduler.building.editBuildingShort');
			}

			$buildingForm =& new BuildingForm($buildingId);
			if ($buildingForm->isLocaleResubmit()) {
				$buildingForm->readInputData();
			} else {
				$buildingForm->initData();
			}
			$buildingForm->display();

		} else {
				Request::redirect(null, null, null, 'buildings');
		}
	}

	/**
	 * Display form to create new building.
	 */
	function createBuilding() {
		SchedulerHandler::editBuilding();
	}

	/**
	 * Save changes to a building.
	 */
	function updateBuilding() {
		parent::validate();

		import('manager.form.BuildingForm');

		$schedConf =& Request::getSchedConf();
		$buildingId = Request::getUserVar('buildingId') == null ? null : (int) Request::getUserVar('buildingId');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		if (($buildingId != null && $buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getSchedConfId()) || $buildingId == null) {

			$buildingForm =& new BuildingForm($buildingId);
			$buildingForm->readInputData();

			if ($buildingForm->validate()) {
				$buildingForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'createBuilding');
				} else {
					Request::redirect(null, null, null, 'buildings');
				}

			} else {
				BuildingHandler::setupTemplate(true);

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'buildings'), 'manager.scheduler.buildings'));

				if ($buildingId == null) {
					$templateMgr->assign('buildingTitle', 'manager.scheduler.building.createBuilding');
				} else {
					$templateMgr->assign('buildingTitle', 'manager.scheduler.building.editBuilding');	
				}

				$buildingForm->display();
			}

		} else {
				Request::redirect(null, null, null, 'buildings');
		}	
	}

	/**
	 * Display a list of rooms to manage.
	 */
	function rooms($args) {
		$schedConf =& Request::getSchedConf();
		$buildingId = (int) array_shift($args);

		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$building =& $buildingDao->getBuilding($buildingId);

		if (!$schedConf || !$building || $building->getSchedConfId() != $schedConf->getSchedConfId()) {
			Request::redirect(null, null, null, 'scheduler');
		}

		$rangeInfo =& Handler::getRangeInfo('rooms');
		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$rooms =& $roomDao->getRoomsByBuildingId($buildingId, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('rooms', $rooms);
		$templateMgr->assign('buildingId', $buildingId);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.rooms');
		$templateMgr->display('manager/scheduler/rooms.tpl');
	}

	/**
	 * Delete a room.
	 * @param $args array first parameter is the ID of the room to delete
	 */
	function deleteRoom($args) {
		parent::validate();
		$roomId = (int) array_shift($args);
		$schedConf =& Request::getSchedConf();

		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		// Ensure room is for a building in this conference
		$room =& $roomDao->getRoom($roomId);
		if ($room) $building =& $buildingDao->getBuilding($room->getBuildingId());

		if (	$room && $building && $schedConf &&
			$room->getBuildingId() == $building->getBuildingId() &&
			$building->getSchedConfId() == $schedConf->getSchedConfId()
		) {
			$roomDao->deleteRoomById($roomId);
		}

		if ($building) Request::redirect(null, null, null, 'rooms', array($building->getBuildingId()));
		else Request::redirect(null, null, null, 'scheduler');
	}

	/**
	 * Display form to edit a room.
	 * @param $args array optional, first parameter is the ID of the room to edit
	 */
	function editRoom($args) {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$buildingId = (int) array_shift($args);
		$roomId = (int) array_shift($args);

		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		$room =& $roomDao->getRoom($roomId);
		$building =& $buildingDao->getBuilding($buildingId);

		// Ensure room is valid and for this conference
		if (	$building && $schedConf &&
			$building->getSchedConfId() == $schedConf->getSchedConfId() &&
			((
				!$room && $roomId == 0
			) || (
				$room && $room->getBuildingId() == $building->getBuildingId()
			))
		) {
			import('manager.form.RoomForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'rooms', array($building->getBuildingId())), 'manager.scheduler.rooms'));

			if ($roomId == null) {
				$templateMgr->assign('roomTitle', 'manager.scheduler.room.createRoomShort');
			} else {
				$templateMgr->assign('roomTitle', 'manager.scheduler.room.editRoomShort');
			}

			$roomForm =& new RoomForm($roomId, $buildingId);
			if ($roomForm->isLocaleResubmit()) {
				$roomForm->readInputData();
			} else {
				$roomForm->initData();
			}
			$roomForm->display();

		} else {
				Request::redirect(null, null, null, 'rooms', array($buildingId));
		}
	}

	/**
	 * Display form to create new room.
	 */
	function createRoom($args) {
		SchedulerHandler::editRoom($args);
	}

	/**
	 * Save changes to a room.
	 */
	function updateRoom() {
		parent::validate();

		import('manager.form.RoomForm');

		$schedConf =& Request::getSchedConf();
		$roomId = Request::getUserVar('roomId') == null ? null : (int) Request::getUserVar('roomId');
		$buildingId = Request::getUserVar('buildingId') == null ? null : (int) Request::getUserVar('buildingId');

		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		$building = $buildingDao->getBuilding($buildingId);

		// Ensure that the specified parameters are valid
		if (	!$building || !$schedConf ||
			$schedConf->getSchedConfId() != $building->getSchedConfId()
		) {
			Request::redirect(null, null, null, 'scheduler');
		}

		if (($roomId != null && $roomDao->getRoomBuildingId($roomId) == $buildingId) || $roomId == null) {

			$roomForm =& new RoomForm($roomId, $buildingId);
			$roomForm->readInputData();

			if ($roomForm->validate()) {
				$roomForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'createRoom', array($buildingId));
				} else {
					Request::redirect(null, null, null, 'rooms', array($buildingId));
				}

			} else {
				RoomHandler::setupTemplate(true);

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'rooms', array($buildingId)), 'manager.scheduler.rooms'));

				if ($roomId == null) {
					$templateMgr->assign('roomTitle', 'manager.scheduler.room.createRoom');
				} else {
					$templateMgr->assign('roomTitle', 'manager.scheduler.room.editRoom');	
				}

				$roomForm->display();
			}

		} else {
				Request::redirect(null, null, null, 'rooms');
		}	
	}

	/**
	 * Common template configuration function for Scheduler pages.
	 * @param $subclass boolean Whether or not the page to display is a
	 * "subclass" (sub-page) of the Scheduler (i.e. as
	 * opposed to the index)
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'scheduler'), 'manager.scheduler'));
		}
	}
}

?>
