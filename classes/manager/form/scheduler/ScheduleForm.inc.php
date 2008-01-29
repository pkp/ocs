<?php

/**
 * @file ScheduleForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.scheduler
 * @class ScheduleForm
 *
 * Form for conference manager to schedule special events and presentations
 *
 * $Id$
 */

import('form.Form');

class ScheduleForm extends Form {
	/** @var $modifiedEventIds array Set of modified special events */
	var $modifiedEvents;

	/** @var $modifiedPaperIds array Set of modified papers */
	var $modifiedPapers;

	/**
	 * Constructor
	 */
	function ScheduleForm() {
		parent::Form('manager/scheduler/scheduleForm.tpl');

		$this->modifiedEvents = array();
		$this->modifiedPapers = array();

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();
		$schedConfId = $schedConf->getSchedConfId();

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$unscheduledPresentations =& $publishedPaperDao->getPublishedPapers($schedConfId, false);
		$unscheduledPresentations =& $unscheduledPresentations->toAssociativeArray('paperId');
		$scheduledPresentations =& $publishedPaperDao->getPublishedPapers($schedConfId, true);
		$scheduledPresentations =& $scheduledPresentations->toAssociativeArray('paperId');

		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		$unscheduledEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConfId, false);
		$unscheduledEvents =& $unscheduledEvents->toAssociativeArray('specialEventId');
		$scheduledEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConfId, true);
		$scheduledEvents =& $scheduledEvents->toAssociativeArray('specialEventId');

		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
		$timeBlocks =& $timeBlockDao->getTimeBlocksBySchedConfId($schedConfId);
		$timeBlocks =& $timeBlocks->toAssociativeArray('timeBlockId');

		$this->_data = array(
			'unscheduledEvents' => &$unscheduledEvents,
			'unscheduledPresentations' => &$unscheduledPresentations,
			'scheduledEvents' => &$scheduledEvents,
			'scheduledPresentations' => &$scheduledPresentations,
			'timeBlocks' => &$timeBlocks
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$schedConf =& Request::getSchedConf();

		// Use the current database for starters, then apply actions to bring it up to date
		// with the user's input
		$this->initData();

		$unscheduledEvents =& $this->_data['unscheduledEvents'];
		$unscheduledPresentations =& $this->_data['unscheduledPresentations'];
		$scheduledEvents =& $this->_data['scheduledEvents'];
		$scheduledPresentations =& $this->_data['scheduledPresentations'];
		$timeBlocks =& $this->_data['timeBlocks'];

		$this->readUserVars(array('actions')); // Forward actions along for resubmits

		foreach (explode("\n", $this->getData('actions')) as $action) {
			$parts = explode(' ', $action);
			switch (array_shift($parts)) {
				case 'SCHEDULE': // Format: SCHEDULE [EVENT|PRESENTATION]-id TIME-timeBlockId
					$itemIdentifier = array_shift($parts);
					$itemParts = explode('-', $itemIdentifier);
					$itemType = array_shift($itemParts);
					$itemId = (int) array_shift($itemParts);
					$timeBlockParts = explode('-', array_shift($parts));
					$timeToken = array_shift($timeBlockParts);
					$timeBlockId = (int) array_shift($timeBlockParts);

					if (!isset($timeBlocks[$timeBlockId])) break;
					$timeBlock =& $timeBlocks[$timeBlockId];

					switch ($itemType) {
						case 'EVENT':
							if (isset($unscheduledEvents[$itemId])) {
								$unscheduledEvent =& $unscheduledEvents[$itemId];
								$unscheduledEvent->setTimeBlockId($timeBlockId);
								unset($unscheduledEvents[$itemId]);
								$scheduledEvents[$itemId] =& $unscheduledEvent;
								$this->modifiedEvents[$itemId] =& $unscheduledEvent;
								unset($unscheduledEvent);
							} elseif (isset($scheduledEvents[$itemId])) {
								$scheduledEvent =& $scheduledEvents[$itemId];
								$scheduledEvent->setTimeBlockId($timeBlockId);
								$this->modifiedEvents[$itemId] =& $scheduledEvent;
								unset($scheduledEvent);
							}
							break;
						case 'PRESENTATION':
							if (isset($unscheduledPresentations[$itemId])) {
								$unscheduledPresentation =& $unscheduledPresentations[$itemId];
								$unscheduledPresentation->setTimeBlockId($timeBlockId);
								unset($unscheduledPresentations[$itemId]);
								$scheduledPresentations[$itemId] =& $unscheduledPresentation;
								$this->modifiedPapers[$itemId] =& $unscheduledPresentation;
								unset($unscheduledPresentation);
							} elseif (isset($scheduledPresentations[$itemId])) {
								$scheduledPresentation =& $scheduledPresentations[$itemId];
								$scheduledPresentation->setTimeBlockId($timeBlockId);
								$this->modifiedPapers[$itemId] =& $scheduledPresentation;
								unset($scheduledPresentation);
							}
							break;
					}
					unset($timeBlock);
					break;
				case 'UNSCHEDULE': // Format: SCHEDULE [EVENT|PRESENTATION]-id TIME-timeBlockId
					$itemIdentifier = array_shift($parts);
					$itemParts = explode('-', $itemIdentifier);
					$itemType = array_shift($itemParts);
					$itemId = (int) array_shift($itemParts);

					switch ($itemType) {
						case 'EVENT':
							if (isset($scheduledEvents[$itemId])) {
								$scheduledEvent =& $scheduledEvents[$itemId];
								$scheduledEvent->setTimeBlockId(null);
								$this->modifiedEvents[$itemId] =& $scheduledEvent;
								unset($scheduledEvents[$itemId]);
								$unscheduledEvents[$itemId] =& $scheduledEvent;
								unset($scheduledEvent);
							}
							break;
						case 'PRESENTATION':
							if (isset($scheduledPresentations[$itemId])) {
								$scheduledPresentation =& $scheduledPresentations[$itemId];
								$scheduledPresentation->setTimeBlockId(null);
								$this->modifiedPapers[$itemId] =& $scheduledPresentation;
								unset($scheduledPresentations[$itemId]);
								$unscheduledPresentations[$itemId] =& $scheduledPresentation;
								unset($scheduledPresentation);
							}
							break;
					}
					break;
				case 'ASSIGN': // Format: ASSIGN [EVENT|PRESENTATION]-id-ROOM roomId
					$itemIdentifier = array_shift($parts);
					$itemParts = explode('-', $itemIdentifier);
					$itemType = array_shift($itemParts);
					$itemId = (int) array_shift($itemParts);
					$roomId = (int) array_shift($parts);
					$roomDao =& DAORegistry::getDAO('RoomDAO');
					$room =& $roomDao->getRoom($roomId);
					if (!$room || $roomDao->getRoomSchedConfId($roomId) != $schedConf->getSchedConfId()) break;
					switch ($itemType) {
						case 'EVENT':
							if (isset($scheduledEvents[$itemId])) $event =& $scheduledEvents[$itemId];
							elseif (isset($unscheduledEvents[$itemId])) $event =& $unscheduledEvents[$itemId];
							else break;
							$event->setRoomId($roomId);
							$this->modifiedEvents[$itemId] =& $event;
							unset($event);
							break;
						case 'PRESENTATION':
							if (isset($scheduledPresentations[$itemId])) $presentation =& $scheduledPresentations[$itemId];
							elseif (isset($unscheduledPresentations[$itemId])) $presentation =& $unscheduledPresentations[$itemId];
							else break;
							$presentation->setRoomId($roomId);
							$this->modifiedPapers[$itemId] =& $presentation;
							unset($presentation);
							break;
					}
					break;
				case 'UNASSIGN': // Format: UNASSIGN [EVENT|PRESENTATION]-id-ROOM
					$itemIdentifier = array_shift($parts);
					$itemParts = explode('-', $itemIdentifier);
					$itemType = array_shift($itemParts);
					$itemId = (int) array_shift($itemParts);
					switch($itemType) {
						case 'EVENT':
							if (isset($scheduledEvents[$itemId])) $event =& $scheduledEvents[$itemId];
							elseif (isset($unscheduledEvents[$itemId])) $event =& $unscheduledEvents[$itemId];
							else break;
							$event->setRoomId(null);
							$this->modifiedEvents[$itemId] =& $event;
							unset($event);
							break;
						case 'PRESENTATION':
							if (isset($scheduledPresentations[$itemId])) $presentation =& $scheduledPresentations[$itemId];
							elseif (isset($unscheduledPresentations[$itemId])) $presentation =& $unscheduledPresentations[$itemId];
							else break;
							$presentation->setRoomId(null);
							$this->modifiedPapers[$itemId] =& $presentation;
							unset($presentation);
							break;
					}
					break;
			}
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.managementPages.schedule');

		$baseDates = array(); // Array of columns representing dates
		$boundaryTimes = array(); // Array of rows representing start times

		$timeBlocks =& $this->_data['timeBlocks'];
		$timeBlockGrid = array();

		foreach (array_keys($timeBlocks) as $timeBlockKey) { // By ref
			$timeBlock =& $timeBlocks[$timeBlockKey];

			$startDate = strtotime($timeBlock->getStartTime());
			$endDate = strtotime($timeBlock->getEndTime());
			list($startDay, $startMonth, $startYear) = array(strftime('%d', $startDate), strftime('%m', $startDate), strftime('%Y', $startDate));
			$baseDate = mktime(0, 0, 1, $startMonth, $startDay, $startYear);
			$startTime = $startDate - $baseDate;
			$endTime = $endDate - $baseDate;

			$baseDates[] = $baseDate;
			$boundaryTimes[] = $startTime;
			$boundaryTimes[] = $endTime;

			$timeBlockGrid[$baseDate][$startTime]['timeBlockStarts'] =& $timeBlock;
			$timeBlockGrid[$baseDate][$endTime]['timeBlockEnds'] =& $timeBlock;
			unset($timeBlock);
		}

		// Knock out duplicates and sort the results.
		$boundaryTimes = array_unique($boundaryTimes);
		$baseDates = array_unique($baseDates);
		sort($boundaryTimes);
		sort($baseDates);

		$gridSlotUsed = array();
		// For each block, find out how long it lasts
		foreach ($baseDates as $baseDate) {
			foreach ($boundaryTimes as $boundaryTimeIndex => $boundaryTime) {
				if (!isset($timeBlockGrid[$baseDate][$boundaryTime]['timeBlockStarts'])) continue;
				$gridSlotUsed[$baseDate][$boundaryTime] = 1;
				// Establish the number of rows spanned ($i); track used grid slots
				for ($i=1; (isset($boundaryTimes[$i+$boundaryTimeIndex]) && !isset($timeBlockGrid[$baseDate][$boundaryTimes[$i+$boundaryTimeIndex]]['timeBlockEnds'])); $i++) {
					$gridSlotUsed[$baseDate][$boundaryTimes[$i+$boundaryTimeIndex]] = 1;
				}
				$timeBlockGrid[$baseDate][$boundaryTime]['rowspan'] = $i;
			}
		}

		$templateMgr->assign_by_ref('baseDates', $baseDates);
		$templateMgr->assign_by_ref('boundaryTimes', $boundaryTimes);
		$templateMgr->assign_by_ref('timeBlockGrid', $timeBlockGrid);
		$templateMgr->assign_by_ref('gridSlotUsed', $gridSlotUsed);

		$scheduledPresentationsByTimeBlockId = array();
		$scheduledPresentations =& $this->_data['scheduledPresentations'];
		foreach (array_keys($scheduledPresentations) as $key) { // By ref
			$scheduledPresentation =& $scheduledPresentations[$key];
			$scheduledPresentationsByTimeBlockId[$scheduledPresentation->getTimeBlockId()][] =& $scheduledPresentation;
			unset($scheduledPresentation);
		}
		$templateMgr->assign_by_ref('scheduledPresentationsByTimeBlockId', $scheduledPresentationsByTimeBlockId);

		$scheduledEvents =& $this->_data['scheduledEvents'];
		$scheduledEventsByTimeBlockId = array();
		foreach (array_keys($scheduledEvents) as $key) { // By ref
			$scheduledEvent =& $scheduledEvents[$key];
			$scheduledEventsByTimeBlockId[$scheduledEvent->getTimeBlockId()][] =& $scheduledEvent;
			unset($scheduledEvent);
		}
		$templateMgr->assign_by_ref('scheduledEventsByTimeBlockId', $scheduledEventsByTimeBlockId);

		$buildingsAndRooms = array();
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$schedConf =& Request::getSchedConf();
		$buildings =& $buildingDao->getBuildingsBySchedConfId($schedConf->getSchedConfId());
		while ($building =& $buildings->next()) {
			$buildingId = $building->getBuildingId();
			$rooms =& $roomDao->getRoomsByBuildingId($buildingId);
			$buildingsAndRooms[$buildingId] = array(
				'building' => &$building
			);
			while ($room =& $rooms->next()) {
				$roomId = $room->getRoomId();
				$buildingsAndRooms[$buildingId]['rooms'][$roomId] =& $room;
				unset($room);
			}
			unset($rooms);
			unset($building);
		}
		$templateMgr->assign_by_ref('buildingsAndRooms', $buildingsAndRooms);

		parent::display();
	}

	/**
	 * Create the time blocks.. 
	 */
	function execute() {
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');

		// Update events and papers using the modified set
		foreach (array_keys($this->modifiedEvents) as $eventId) {
			$specialEventDao->updateSpecialEvent($this->modifiedEvents[$eventId]);
		}

		foreach (array_keys($this->modifiedPapers) as $paperId) {
			$publishedPaperDao->updatePublishedPaper($this->modifiedPapers[$paperId]);
		}
	}
}

?>
