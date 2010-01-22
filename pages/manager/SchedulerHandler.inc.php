<?php

/**
 * @file SchedulerHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedulerHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for registration management functions. 
 */

//$Id$

class SchedulerHandler extends ManagerHandler {

	/**
	 * Display the scheduler index page
	 */
	function scheduler() {
		parent::validate();
		SchedulerHandler::setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.scheduler');
		$templateMgr->display('manager/scheduler/index.tpl');
	}

	/**
	 * Display a list of buildings to manage.
	 */
	function buildings() {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('buildings', array());
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		while (true) {
			$buildings =& $buildingDao->getBuildingsBySchedConfId($schedConf->getSchedConfId(), $rangeInfo);
			if ($buildings->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $buildings->getLastPageRangeInfo();
			unset($buildings);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('buildings', $buildings);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.buildings');
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
			import('manager.form.scheduler.BuildingForm');

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

		import('manager.form.scheduler.BuildingForm');

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
				SchedulerHandler::setupTemplate(true);

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

		$rangeInfo =& Handler::getRangeInfo('rooms', array($buildingId));
		$roomDao =& DAORegistry::getDAO('RoomDAO');
		while (true) {
			$rooms =& $roomDao->getRoomsByBuildingId($buildingId, $rangeInfo);
			if ($rooms->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $rooms->getLastPageRangeInfo();
			unset($rooms);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('rooms', $rooms);
		$templateMgr->assign('buildingId', $buildingId);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.rooms');
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
			import('manager.form.scheduler.RoomForm');

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

		import('manager.form.scheduler.RoomForm');

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
				SchedulerHandler::setupTemplate(true);

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
	 * Display a list of special events to manage.
	 */
	function specialEvents() {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('specialEvents', array());
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		while (true) {
			$specialEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConf->getSchedConfId(), $rangeInfo);
			if ($specialEvents->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $specialEvents->getLastPageRangeInfo();
			unset($specialEvents);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('specialEvents', $specialEvents);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.specialEvents');
		$templateMgr->display('manager/scheduler/specialEvents.tpl');
	}

	/**
	 * Delete a special event.
	 * @param $args array first parameter is the ID of the special event to delete
	 */
	function deleteSpecialEvent($args) {
		parent::validate();
		$specialEventId = (int) array_shift($args);
		$schedConf =& Request::getSchedConf();
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');

		// Ensure specialEvent is for this conference
		if ($specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getSchedConfId()) {
			$specialEventDao->deleteSpecialEventById($specialEventId);
		}

		Request::redirect(null, null, null, 'specialEvents');
	}

	/**
	 * Display form to edit a special event.
	 * @param $args array optional, first parameter is the ID of the specialEvent to edit
	 */
	function editSpecialEvent($args = array()) {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$specialEventId = !isset($args) || empty($args) ? null : (int) $args[0];
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');

		// Ensure special event is valid and for this conference
		if (($specialEventId != null && $specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getSchedConfId()) || ($specialEventId == null)) {
			import('manager.form.scheduler.SpecialEventForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'specialEvents'), 'manager.scheduler.specialEvents'));

			if ($specialEventId == null) {
				$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.createSpecialEventShort');
			} else {
				$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.editSpecialEventShort');
			}

			$specialEventForm =& new SpecialEventForm($specialEventId);
			if ($specialEventForm->isLocaleResubmit()) {
				$specialEventForm->readInputData();
			} else {
				$specialEventForm->initData();
			}
			$specialEventForm->display();

		} else {
				Request::redirect(null, null, null, 'specialEvents');
		}
	}

	/**
	 * Display form to create new special event.
	 */
	function createSpecialEvent() {
		SchedulerHandler::editSpecialEvent();
	}

	/**
	 * Save changes to a special event.
	 */
	function updateSpecialEvent() {
		parent::validate();

		import('manager.form.scheduler.SpecialEventForm');

		$schedConf =& Request::getSchedConf();
		$specialEventId = Request::getUserVar('specialEventId') == null ? null : (int) Request::getUserVar('specialEventId');
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');

		if (($specialEventId != null && $specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getSchedConfId()) || $specialEventId == null) {

			$specialEventForm =& new SpecialEventForm($specialEventId);
			$specialEventForm->readInputData();

			if ($specialEventForm->validate()) {
				$specialEventForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'createSpecialEvent');
				} else {
					Request::redirect(null, null, null, 'specialEvents');
				}

			} else {
				SchedulerHandler::setupTemplate(true);

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'specialEvents'), 'manager.scheduler.specialEvents'));

				if ($specialEventId == null) {
					$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.createSpecialEvent');
				} else {
					$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.editSpecialEvent');	
				}

				$specialEventForm->display();
			}

		} else {
				Request::redirect(null, null, null, 'specialEvents');
		}	
	}

	/**
	 * Display the conference schedule.
	 */
	function schedule($args) {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();

		import('manager.form.scheduler.ScheduleForm');
		$scheduleForm =& new ScheduleForm();

		$scheduleForm->initData();
		$scheduleForm->display();
	}

	/**
	 * Save the schedule.
	 */
	function saveSchedule() {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();

		import('manager.form.scheduler.ScheduleForm');
		$scheduleForm =& new ScheduleForm();

		$scheduleForm->readInputData();
		if ($scheduleForm->validate()) {
			$scheduleForm->execute();
			Request::redirect(null, null, null, 'scheduler');
		} else {
			$scheduleForm->display();
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
