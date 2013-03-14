<?php

/**
 * @file pages/manager/SchedulerHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedulerHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for registration management functions. 
 */


import('pages.manager.ManagerHandler');

class SchedulerHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function SchedulerHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display the scheduler index page
	 */
	function scheduler($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.scheduler');
		$templateMgr->display('manager/scheduler/index.tpl');
	}

	/**
	 * Save scheduler settings (time block enable/disable)
	 */
	function saveSchedulerSettings($args, &$request) {
		parent::validate();
		$enableTimeBlocks = $request->getUserVar('enableTimeBlocks');
		$schedConf =& $request->getSchedConf();
		$schedConf->updateSetting('enableTimeBlocks', $enableTimeBlocks);
		$request->redirect(null, null, 'manager', 'scheduler');
	}

	/**
	 * Display a list of buildings to manage.
	 */
	function buildings($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$rangeInfo = $this->getRangeInfo($request, 'buildings', array());
		$buildingDao = DAORegistry::getDAO('BuildingDAO');
		while (true) {
			$buildings =& $buildingDao->getBuildingsBySchedConfId($schedConf->getId(), $rangeInfo);
			if ($buildings->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $buildings->getLastPageRangeInfo();
			unset($buildings);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('buildings', $buildings);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.buildings');
		$templateMgr->display('manager/scheduler/buildings.tpl');
	}

	/**
	 * Delete a building.
	 * @param $args array first parameter is the ID of the building to delete
	 */
	function deleteBuilding($args, &$request) {
		$this->validate();
		$buildingId = (int) array_shift($args);
		$schedConf =& $request->getSchedConf();
		$buildingDao = DAORegistry::getDAO('BuildingDAO');

		// Ensure building is for this conference
		if ($buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getId()) {
			$buildingDao->deleteBuildingById($buildingId);
		}

		$request->redirect(null, null, null, 'buildings');
	}

	/**
	 * Display form to edit a building.
	 * @param $args array optional, first parameter is the ID of the building to edit
	 */
	function editBuilding($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$buildingId = !isset($args) || empty($args) ? null : (int) $args[0];
		$buildingDao = DAORegistry::getDAO('BuildingDAO');

		// Ensure building is valid and for this conference
		if (($buildingId != null && $buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getId()) || ($buildingId == null)) {
			import('classes.manager.form.scheduler.BuildingForm');

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'buildings'), 'manager.scheduler.buildings'));

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
			$request->redirect(null, null, null, 'buildings');
		}
	}

	/**
	 * Display form to create new building.
	 */
	function createBuilding($args, &$request) {
		$this->editBuilding($args, $request);
	}

	/**
	 * Save changes to a building.
	 */
	function updateBuilding($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.scheduler.BuildingForm');

		$schedConf =& $request->getSchedConf();
		$buildingId = $request->getUserVar('buildingId') == null ? null : (int) $request->getUserVar('buildingId');
		$buildingDao = DAORegistry::getDAO('BuildingDAO');

		if (($buildingId != null && $buildingDao->getBuildingSchedConfId($buildingId) == $schedConf->getId()) || $buildingId == null) {

			$buildingForm = new BuildingForm($buildingId);
			$buildingForm->readInputData();

			if ($buildingForm->validate()) {
				$buildingForm->execute();

				if ($request->getUserVar('createAnother')) {
					$request->redirect(null, null, null, 'createBuilding');
				} else {
					$request->redirect(null, null, null, 'buildings');
				}

			} else {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'buildings'), 'manager.scheduler.buildings'));

				if ($buildingId == null) {
					$templateMgr->assign('buildingTitle', 'manager.scheduler.building.createBuilding');
				} else {
					$templateMgr->assign('buildingTitle', 'manager.scheduler.building.editBuilding');	
				}

				$buildingForm->display();
			}

		} else {
			$request->redirect(null, null, null, 'buildings');
		}	
	}

	/**
	 * Display a list of rooms to manage.
	 */
	function rooms($args, &$request) {
		$schedConf =& $request->getSchedConf();
		$buildingId = (int) array_shift($args);

		$this->validate();
		$this->setupTemplate($request, true);

		$buildingDao = DAORegistry::getDAO('BuildingDAO');
		$building =& $buildingDao->getBuilding($buildingId);

		if (!$schedConf || !$building || $building->getSchedConfId() != $schedConf->getId()) {
			$request->redirect(null, null, null, 'scheduler');
		}

		$rangeInfo = $this->getRangeInfo($request, 'rooms', array($buildingId));
		$roomDao = DAORegistry::getDAO('RoomDAO');
		while (true) {
			$rooms =& $roomDao->getRoomsByBuildingId($buildingId, $rangeInfo);
			if ($rooms->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $rooms->getLastPageRangeInfo();
			unset($rooms);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('rooms', $rooms);
		$templateMgr->assign('buildingId', $buildingId);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.rooms');
		$templateMgr->display('manager/scheduler/rooms.tpl');
	}

	/**
	 * Delete a room.
	 * @param $args array first parameter is the ID of the room to delete
	 */
	function deleteRoom($args, &$request) {
		$this->validate();
		$roomId = (int) array_shift($args);
		$schedConf =& $request->getSchedConf();

		$roomDao = DAORegistry::getDAO('RoomDAO');
		$buildingDao = DAORegistry::getDAO('BuildingDAO');

		// Ensure room is for a building in this conference
		$room =& $roomDao->getRoom($roomId);
		if ($room) $building =& $buildingDao->getBuilding($room->getBuildingId());

		if (	$room && $building && $schedConf &&
			$room->getBuildingId() == $building->getId() &&
			$building->getSchedConfId() == $schedConf->getId()
		) {
			$roomDao->deleteRoomById($roomId);
		}

		if ($building) $request->redirect(null, null, null, 'rooms', array($building->getId()));
		else $request->redirect(null, null, null, 'scheduler');
	}

	/**
	 * Display form to edit a room.
	 * @param $args array optional, first parameter is the ID of the room to edit
	 */
	function editRoom($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$buildingId = (int) array_shift($args);
		$roomId = (int) array_shift($args);

		$roomDao = DAORegistry::getDAO('RoomDAO');
		$buildingDao = DAORegistry::getDAO('BuildingDAO');

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
			import('classes.manager.form.scheduler.RoomForm');

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'rooms', array($building->getId())), 'manager.scheduler.rooms'));

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
			$request->redirect(null, null, null, 'rooms', array($buildingId));
		}
	}

	/**
	 * Display form to create new room.
	 */
	function createRoom($args, &$request) {
		$this->editRoom($args, $request);
	}

	/**
	 * Save changes to a room.
	 */
	function updateRoom($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.scheduler.RoomForm');

		$schedConf =& $request->getSchedConf();
		$roomId = $request->getUserVar('roomId') == null ? null : (int) $request->getUserVar('roomId');
		$buildingId = $request->getUserVar('buildingId') == null ? null : (int) $request->getUserVar('buildingId');

		$roomDao = DAORegistry::getDAO('RoomDAO');
		$buildingDao = DAORegistry::getDAO('BuildingDAO');

		$building = $buildingDao->getBuilding($buildingId);

		// Ensure that the specified parameters are valid
		if (	!$building || !$schedConf ||
			$schedConf->getId() != $building->getSchedConfId()
		) {
			$request->redirect(null, null, null, 'scheduler');
		}

		if (($roomId != null && $roomDao->getRoomBuildingId($roomId) == $buildingId) || $roomId == null) {

			$roomForm = new RoomForm($roomId, $buildingId);
			$roomForm->readInputData();

			if ($roomForm->validate()) {
				$roomForm->execute();

				if ($request->getUserVar('createAnother')) {
					$request->redirect(null, null, null, 'createRoom', array($buildingId));
				} else {
					$request->redirect(null, null, null, 'rooms', array($buildingId));
				}

			} else {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'rooms', array($buildingId)), 'manager.scheduler.rooms'));

				if ($roomId == null) {
					$templateMgr->assign('roomTitle', 'manager.scheduler.room.createRoom');
				} else {
					$templateMgr->assign('roomTitle', 'manager.scheduler.room.editRoom');	
				}

				$roomForm->display();
			}

		} else {
			$request->redirect(null, null, null, 'rooms');
		}	
	}

	/**
	 * Display a list of special events to manage.
	 */
	function specialEvents($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$rangeInfo = $this->getRangeInfo($request, 'specialEvents', array());
		$specialEventDao = DAORegistry::getDAO('SpecialEventDAO');
		while (true) {
			$specialEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConf->getId(), $rangeInfo);
			if ($specialEvents->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $specialEvents->getLastPageRangeInfo();
			unset($specialEvents);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('specialEvents', $specialEvents);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.specialEvents');
		$templateMgr->display('manager/scheduler/specialEvents.tpl');
	}

	/**
	 * Delete a special event.
	 * @param $args array first parameter is the ID of the special event to delete
	 */
	function deleteSpecialEvent($args, &$request) {
		$this->validate();
		$specialEventId = (int) array_shift($args);
		$schedConf =& $request->getSchedConf();
		$specialEventDao = DAORegistry::getDAO('SpecialEventDAO');

		// Ensure specialEvent is for this conference
		if ($specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getId()) {
			$specialEventDao->deleteSpecialEventById($specialEventId);
		}

		$request->redirect(null, null, null, 'specialEvents');
	}

	/**
	 * Display form to edit a special event.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editSpecialEvent($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$specialEventId = !isset($args) || empty($args) ? null : (int) $args[0];
		$specialEventDao = DAORegistry::getDAO('SpecialEventDAO');

		// Ensure special event is valid and for this conference
		if (($specialEventId != null && $specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getId()) || ($specialEventId == null)) {
			import('classes.manager.form.scheduler.SpecialEventForm');

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'specialEvents'), 'manager.scheduler.specialEvents'));

			if ($specialEventId == null) {
				$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.createSpecialEventShort');
			} else {
				$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.editSpecialEventShort');
			}

			$specialEventForm = new SpecialEventForm($specialEventId);
			if ($specialEventForm->isLocaleResubmit()) {
				$specialEventForm->readInputData();
			} else {
				$specialEventForm->initData();
			}
			$specialEventForm->display();

		} else {
			$request->redirect(null, null, null, 'specialEvents');
		}
	}

	/**
	 * Display form to create new special event.
	 */
	function createSpecialEvent($args, &$request) {
		$this->editSpecialEvent($args, $request);
	}

	/**
	 * Save changes to a special event.
	 */
	function updateSpecialEvent($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.scheduler.SpecialEventForm');

		$schedConf =& $request->getSchedConf();
		$specialEventId = $request->getUserVar('specialEventId') == null ? null : (int) $request->getUserVar('specialEventId');
		$specialEventDao = DAORegistry::getDAO('SpecialEventDAO');

		if (($specialEventId != null && $specialEventDao->getSpecialEventSchedConfId($specialEventId) == $schedConf->getId()) || $specialEventId == null) {

			$specialEventForm = new SpecialEventForm($specialEventId);
			$specialEventForm->readInputData();

			if ($specialEventForm->validate()) {
				$specialEventForm->execute();

				if ($request->getUserVar('createAnother')) {
					$request->redirect(null, null, null, 'createSpecialEvent');
				} else {
					$request->redirect(null, null, null, 'specialEvents');
				}

			} else {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'specialEvents'), 'manager.scheduler.specialEvents'));

				if ($specialEventId == null) {
					$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.createSpecialEvent');
				} else {
					$templateMgr->assign('specialEventTitle', 'manager.scheduler.specialEvent.editSpecialEvent');	
				}

				$specialEventForm->display();
			}
		} else {
			$request->redirect(null, null, null, 'specialEvents');
		}	
	}

	/**
	 * Display the conference schedule.
	 */
	function schedule($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();

		import('classes.manager.form.scheduler.ScheduleForm');
		$scheduleForm = new ScheduleForm();

		$scheduleForm->initData();
		$scheduleForm->display();
	}

	/**
	 * Save the schedule.
	 */
	function saveSchedule($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();

		import('classes.manager.form.scheduler.ScheduleForm');
		$scheduleForm = new ScheduleForm();

		$scheduleForm->readInputData();
		if ($scheduleForm->validate()) {
			$scheduleForm->execute();
			$request->redirect(null, null, null, 'scheduler');
		} else {
			$scheduleForm->display();
		}
	}
	
	/**
	 * Configure the layout of the schedule
	 */
	function scheduleLayout($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.scheduler.ScheduleLayoutForm');
		$scheduleLayoutForm = new ScheduleLayoutForm();

		$scheduleLayoutForm->initData();
		$scheduleLayoutForm->display();
	}

	/**
	 * Save the schedule.
	 */
	function saveScheduleLayout($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();

		import('classes.manager.form.scheduler.ScheduleLayoutForm');
		$scheduleLayoutForm = new ScheduleLayoutForm();

		$scheduleLayoutForm->readInputData();
		if ($scheduleLayoutForm->validate()) {
			$scheduleLayoutForm->execute();
			$request->redirect(null, null, null, 'scheduler');
		} else {
			$scheduleLayoutForm->display();
		}
	}

	/**
	 * Display a list of time blocks to manage.
	 */
	function timeBlocks($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$rangeInfo = $this->getRangeInfo($request, 'timeBlocks', array());
		$timeBlockDao = DAORegistry::getDAO('TimeBlockDAO');
		while (true) {
			$timeBlocks =& $timeBlockDao->getTimeBlocksBySchedConfId($schedConf->getId(), $rangeInfo);
			if ($timeBlocks->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $timeBlocks->getLastPageRangeInfo();
			unset($timeBlocks);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('timeBlocks', $timeBlocks);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.timeBlocks');
		$templateMgr->display('manager/scheduler/timeBlocks.tpl');
	}

	/**
	 * Delete a time block.
	 * @param $args array first parameter is the ID of the time block to delete
	 */
	function deleteTimeBlock($args, &$request) {
		parent::validate();
		$timeBlockId = (int) array_shift($args);
		$schedConf =& $request->getSchedConf();
		$timeBlockDao = DAORegistry::getDAO('TimeBlockDAO');

		// Ensure time block is for this conference
		if ($timeBlockDao->getTimeBlockSchedConfId($timeBlockId) == $schedConf->getId()) {
			$timeBlockDao->deleteTimeBlockById($timeBlockId);
		}

		$request->redirect(null, null, null, 'timeBlocks');
	}

	/**
	 * Display form to create new time block.
	 */
	function createTimeBlock($args, &$request) {
		$this->editTimeBlock($args, $request);
	}

	/**
	 * Display form to edit a time block.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editTimeBlock($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$timeBlockId = !isset($args) || empty($args) ? null : (int) $args[0];
		$timeBlockDao = DAORegistry::getDAO('TimeBlockDAO');

		// Ensure time block is valid and for this conference
		if (($timeBlockId != null && $timeBlockDao->getTimeBlockSchedConfId($timeBlockId) == $schedConf->getId()) || ($timeBlockId == null)) {
			import('classes.manager.form.scheduler.TimeBlockForm');

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'timeBlocks'), 'manager.scheduler.timeBlocks'));

			if ($timeBlockId == null) {
				$templateMgr->assign('timeBlockTitle', 'manager.scheduler.timeBlock.createTimeBlockShort');
			} else {
				$templateMgr->assign('timeBlockTitle', 'manager.scheduler.timeBlock.editTimeBlockShort');
			}

			$timeBlockForm = new TimeBlockForm($timeBlockId);
			if ($timeBlockForm->isLocaleResubmit()) {
				$timeBlockForm->readInputData();
			} else {
				$timeBlockForm->initData();
			}
			$timeBlockForm->display();

		} else {
			$request->redirect(null, null, null, 'timeBlocks');
		}
	}

	/**
	 * Save changes to a timeBlock.
	 */
	function updateTimeBlock($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.scheduler.TimeBlockForm');

		$schedConf =& $request->getSchedConf();
		$timeBlockId = $request->getUserVar('timeBlockId') == null ? null : (int) $request->getUserVar('timeBlockId');
		$timeBlockDao = DAORegistry::getDAO('TimeBlockDAO');

		if (($timeBlockId != null && $timeBlockDao->getTimeBlockSchedConfId($timeBlockId) == $schedConf->getId()) || $timeBlockId == null) {

			$timeBlockForm = new TimeBlockForm($timeBlockId);
			$timeBlockForm->readInputData();

			if ($timeBlockForm->validate()) {
				$timeBlockForm->execute();

				if ($request->getUserVar('createAnother')) {
					// Provide last block as template
					return $timeBlockForm->display();
				} else {
					$request->redirect(null, null, null, 'timeBlocks');
				}

			} else {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'timeBlocks'), 'manager.scheduler.timeBlocks'));

				if ($timeBlockId == null) {
					$templateMgr->assign('timeBlockTitle', 'manager.scheduler.timeBlock.createTimeBlock');
				} else {
					$templateMgr->assign('timeBlockTitle', 'manager.scheduler.timeBlock.editTimeBlock');	
				}

				$timeBlockForm->display();
			}

		} else {
			$request->redirect(null, null, null, 'timeBlocks');
		}	
	}

	/**
	 * Common template configuration function for Scheduler pages.
	 * @param $subclass boolean Whether or not the page to display is a
	 * "subclass" (sub-page) of the Scheduler (i.e. as
	 * opposed to the index)
	 */
	function setupTemplate($request, $subclass = false) {
		parent::setupTemplate($request, true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'scheduler'), 'manager.scheduler'));
		}
	}
}

?>
