<?php

/**
 * @file SchedulerHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedulerHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for registration management functions. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class SchedulerHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function SchedulerHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display the scheduler index page
	 */
	function scheduler() {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.scheduler');
		$templateMgr->display('manager/scheduler/index.tpl');
	}

	/**
	 * Save scheduler settings (time block enable/disable)
	 */
	function saveSchedulerSettings() {
		parent::validate();
		$enableTimeBlocks = Request::getUserVar('enableTimeBlocks');
		$schedConf =& Request::getSchedConf();
		$schedConf->updateSetting('enableTimeBlocks', $enableTimeBlocks);
		Request::redirect(null, null, 'manager', 'scheduler');
	}

	/**
	 * Display a list of buildings to manage.
	 */
	function buildings() {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('buildings', array());
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		while (true) {
			$buildings =& $buildingDao->getBuildingsBySchedConfId($schedConf->getId(), $rangeInfo);
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
		$this->validate();
		$buildingId = (int) array_shift($args);
		$schedConf =& Request::getSchedConf();
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		// Ensure building is for this conference
		if ($buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getId()) {
			$buildingDao->deleteBuildingById($buildingId);
		}

		Request::redirect(null, null, null, 'buildings');
	}

	/**
	 * Display form to edit a building.
	 * @param $args array optional, first parameter is the ID of the building to edit
	 */
	function editBuilding($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$buildingId = !isset($args) || empty($args) ? null : (int) $args[0];
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		// Ensure building is valid and for this conference
		if (($buildingId != null && $buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getId()) || ($buildingId == null)) {
			import('manager.form.scheduler.BuildingForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'buildings'), 'manager.scheduler.buildings'));

			if ($buildingId == null) {
				$templateMgr->assign('buildingTitle', 'manager.scheduler.building.createBuildingShort');
			} else {
				$templateMgr->assign('buildingTitle', 'manager.scheduler.building.editBuildingShort');
			}

			$buildingForm = new BuildingForm($buildingId);
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
		$this->editBuilding();
	}

	/**
	 * Save changes to a building.
	 */
	function updateBuilding() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.scheduler.BuildingForm');

		$schedConf =& Request::getSchedConf();
		$buildingId = Request::getUserVar('buildingId') == null ? null : (int) Request::getUserVar('buildingId');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		if (($buildingId != null && $buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getId()) || $buildingId == null) {

			$buildingForm = new BuildingForm($buildingId);
			$buildingForm->readInputData();

			if ($buildingForm->validate()) {
				$buildingForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'createBuilding');
				} else {
					Request::redirect(null, null, null, 'buildings');
				}

			} else {
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

		$this->validate();
		$this->setupTemplate(true);

		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$building =& $buildingDao->getBuilding($buildingId);

		if (!$schedConf || !$building || $building->getSchedConfId() != $schedConf->getId()) {
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
		$this->validate();
		$roomId = (int) array_shift($args);
		$schedConf =& Request::getSchedConf();

		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		// Ensure room is for a building in this conference
		$room =& $roomDao->getRoom($roomId);
		if ($room) $building =& $buildingDao->getBuilding($room->getBuildingId());

		if (	$room && $building && $schedConf &&
			$room->getBuildingId() == $building->getId() &&
			$building->getSchedConfId() == $schedConf->getId()
		) {
			$roomDao->deleteRoomById($roomId);
		}

		if ($building) Request::redirect(null, null, null, 'rooms', array($building->getId()));
		else Request::redirect(null, null, null, 'scheduler');
	}

	/**
	 * Display form to edit a room.
	 * @param $args array optional, first parameter is the ID of the room to edit
	 */
	function editRoom($args) {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$buildingId = (int) array_shift($args);
		$roomId = (int) array_shift($args);

		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		$room =& $roomDao->getRoom($roomId);
		$building =& $buildingDao->getBuilding($buildingId);

		// Ensure room is valid and for this conference
		if (	$building && $schedConf &&
			$building->getSchedConfId() == $schedConf->getId() &&
			((
				!$room && $roomId == 0
			) || (
				$room && $room->getBuildingId() == $building->getId()
			))
		) {
			import('manager.form.scheduler.RoomForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'rooms', array($building->getId())), 'manager.scheduler.rooms'));

			if ($roomId == null) {
				$templateMgr->assign('roomTitle', 'manager.scheduler.room.createRoomShort');
			} else {
				$templateMgr->assign('roomTitle', 'manager.scheduler.room.editRoomShort');
			}

			$roomForm = new RoomForm($roomId, $buildingId);
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
		$this->editRoom($args);
	}

	/**
	 * Save changes to a room.
	 */
	function updateRoom() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.scheduler.RoomForm');

		$schedConf =& Request::getSchedConf();
		$roomId = Request::getUserVar('roomId') == null ? null : (int) Request::getUserVar('roomId');
		$buildingId = Request::getUserVar('buildingId') == null ? null : (int) Request::getUserVar('buildingId');

		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');

		$building = $buildingDao->getBuilding($buildingId);

		// Ensure that the specified parameters are valid
		if (	!$building || !$schedConf ||
			$schedConf->getId() != $building->getSchedConfId()
		) {
			Request::redirect(null, null, null, 'scheduler');
		}

		if (($roomId != null && $roomDao->getRoomBuildingId($roomId) == $buildingId) || $roomId == null) {

			$roomForm = new RoomForm($roomId, $buildingId);
			$roomForm->readInputData();

			if ($roomForm->validate()) {
				$roomForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'createRoom', array($buildingId));
				} else {
					Request::redirect(null, null, null, 'rooms', array($buildingId));
				}

			} else {
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
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('specialEvents', array());
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		while (true) {
			$specialEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConf->getId(), $rangeInfo);
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
		$this->validate();
		$specialEventId = (int) array_shift($args);
		$schedConf =& Request::getSchedConf();
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');

		// Ensure specialEvent is for this conference
		if ($specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getId()) {
			$specialEventDao->deleteSpecialEventById($specialEventId);
		}

		Request::redirect(null, null, null, 'specialEvents');
	}

	/**
	 * Display form to edit a special event.
	 * @param $args array optional, first parameter is the ID of the specialEvent to edit
	 */
	function editSpecialEvent($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$specialEventId = !isset($args) || empty($args) ? null : (int) $args[0];
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');

		// Ensure special event is valid and for this conference
		if (($specialEventId != null && $specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getId()) || ($specialEventId == null)) {
			import('manager.form.scheduler.SpecialEventForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'specialEvents'), 'manager.scheduler.specialEvents'));

			if ($specialEventId == null) {
				$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.createSpecialEventShort');
			} else {
				$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.editSpecialEventShort');
			}

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$specialEventForm = new SpecialEventForm($specialEventId);
			} else {
				$specialEventForm =& new SpecialEventForm($specialEventId);
			}
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
		$this->editSpecialEvent();
	}

	/**
	 * Save changes to a special event.
	 */
	function updateSpecialEvent() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.scheduler.SpecialEventForm');

		$schedConf =& Request::getSchedConf();
		$specialEventId = Request::getUserVar('specialEventId') == null ? null : (int) Request::getUserVar('specialEventId');
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');

		if (($specialEventId != null && $specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getId()) || $specialEventId == null) {

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$specialEventForm = new SpecialEventForm($specialEventId);
			} else {
				$specialEventForm =& new SpecialEventForm($specialEventId);
			}
			$specialEventForm->readInputData();

			if ($specialEventForm->validate()) {
				$specialEventForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'createSpecialEvent');
				} else {
					Request::redirect(null, null, null, 'specialEvents');
				}

			} else {
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
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();

		import('manager.form.scheduler.ScheduleForm');
		$scheduleForm = new ScheduleForm();

		$scheduleForm->initData();
		$scheduleForm->display();
	}

	/**
	 * Save the schedule.
	 */
	function saveSchedule() {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();

		import('manager.form.scheduler.ScheduleForm');
		$scheduleForm = new ScheduleForm();

		$scheduleForm->readInputData();
		if ($scheduleForm->validate()) {
			$scheduleForm->execute();
			Request::redirect(null, null, null, 'scheduler');
		} else {
			$scheduleForm->display();
		}
	}
	
	/**
	 * Configure the layout of the schedule
	 */
	function scheduleLayout($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.scheduler.ScheduleLayoutForm');
		$scheduleLayoutForm = new ScheduleLayoutForm();

		$scheduleLayoutForm->initData();
		$scheduleLayoutForm->display();
	}

	/**
	 * Save the schedule.
	 */
	function saveScheduleLayout() {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();

		import('manager.form.scheduler.ScheduleLayoutForm');
		$scheduleLayoutForm = new ScheduleLayoutForm();

		$scheduleLayoutForm->readInputData();
		if ($scheduleLayoutForm->validate()) {
			$scheduleLayoutForm->execute();
			Request::redirect(null, null, null, 'scheduler');
		} else {
			$scheduleLayoutForm->display();
		}
	}

	/**
	 * Display a list of time blocks to manage.
	 */
	function timeBlocks() {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('timeBlocks', array());
		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
		while (true) {
			$timeBlocks =& $timeBlockDao->getTimeBlocksBySchedConfId($schedConf->getId(), $rangeInfo);
			if ($timeBlocks->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $timeBlocks->getLastPageRangeInfo();
			unset($timeBlocks);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('timeBlocks', $timeBlocks);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.timeBlocks');
		$templateMgr->display('manager/scheduler/timeBlocks.tpl');
	}

	/**
	 * Delete a time block.
	 * @param $args array first parameter is the ID of the time block to delete
	 */
	function deleteTimeBlock($args) {
		parent::validate();
		$timeBlockId = (int) array_shift($args);
		$schedConf =& Request::getSchedConf();
		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');

		// Ensure time block is for this conference
		if ($timeBlockDao->getTimeBlockSchedConfId($timeBlockId) == $schedConf->getId()) {
			$timeBlockDao->deleteTimeBlockById($timeBlockId);
		}

		Request::redirect(null, null, null, 'timeBlocks');
	}

	/**
	 * Display form to create new time block.
	 */
	function createTimeBlock() {
		SchedulerHandler::editTimeBlock();
	}

	/**
	 * Display form to edit a time block.
	 * @param $args array optional, first parameter is the ID of the time block to edit
	 */
	function editTimeBlock($args = array()) {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$timeBlockId = !isset($args) || empty($args) ? null : (int) $args[0];
		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');

		// Ensure time block is valid and for this conference
		if (($timeBlockId != null && $timeBlockDao->getTimeBlockSchedConfId($timeBlockId) == $schedConf->getId()) || ($timeBlockId == null)) {
			import('manager.form.scheduler.TimeBlockForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'timeBlocks'), 'manager.scheduler.timeBlocks'));

			if ($timeBlockId == null) {
				$templateMgr->assign('timeBlockTitle', 'manager.scheduler.timeBlock.createTimeBlockShort');
			} else {
				$templateMgr->assign('timeBlockTitle', 'manager.scheduler.timeBlock.editTimeBlockShort');
			}

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$timeBlockForm = new TimeBlockForm($timeBlockId);
			} else {
				$timeBlockForm =& new TimeBlockForm($timeBlockId);
			}
			if ($timeBlockForm->isLocaleResubmit()) {
				$timeBlockForm->readInputData();
			} else {
				$timeBlockForm->initData();
			}
			$timeBlockForm->display();

		} else {
				Request::redirect(null, null, null, 'timeBlocks');
		}
	}

	/**
	 * Save changes to a timeBlock.
	 */
	function updateTimeBlock() {
		parent::validate();
		SchedulerHandler::setupTemplate(true);

		import('manager.form.scheduler.TimeBlockForm');

		$schedConf =& Request::getSchedConf();
		$timeBlockId = Request::getUserVar('timeBlockId') == null ? null : (int) Request::getUserVar('timeBlockId');
		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');

		if (($timeBlockId != null && $timeBlockDao->getTimeBlockSchedConfId($timeBlockId) == $schedConf->getId()) || $timeBlockId == null) {

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$timeBlockForm = new TimeBlockForm($timeBlockId);
			} else {
				$timeBlockForm =& new TimeBlockForm($timeBlockId);
			}
			$timeBlockForm->readInputData();

			if ($timeBlockForm->validate()) {
				$timeBlockForm->execute();

				if (Request::getUserVar('createAnother')) {
					// Provide last block as template
					return $timeBlockForm->display();
				} else {
					Request::redirect(null, null, null, 'timeBlocks');
				}

			} else {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'timeBlocks'), 'manager.scheduler.timeBlocks'));

				if ($timeBlockId == null) {
					$templateMgr->assign('timeBlockTitle', 'manager.scheduler.timeBlock.createTimeBlock');
				} else {
					$templateMgr->assign('timeBlockTitle', 'manager.scheduler.timeBlock.editTimeBlock');	
				}

				$timeBlockForm->display();
			}

		} else {
				Request::redirect(null, null, null, 'timeBlocks');
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
