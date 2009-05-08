<?php

/**
 * @defgroup manager_form_scheduler
 */
 
/**
 * @file ScheduleForm.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScheduleForm
 * @ingroup manager_form_scheduler
 *
 * @brief Form for conference manager to for schedule presentations.
 */

//$Id$

import('form.Form');

class ScheduleForm extends Form {
	/** @var $schedConf object */
	var $schedConf;

	/** @var $publishedPaperDao object */
	var $publishedPaperDao;

	/**
	 * Constructor
	 */
	function ScheduleForm() {
		parent::Form('manager/scheduler/scheduleForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
		$this->schedConf =& Request::getSchedConf();
		$this->publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
	}

	function presenterSort($a, $b) {
		$presenterA = $a->getFirstPresenter(true);
		$presenterB = $b->getFirstPresenter(true);

		return strcmp($presenterA, $presenterB);
	}

	function roomSort($a, $b) {
		static $roomMap = array();
		$roomDao =& DAORegistry::getDAO('RoomDAO');

		$aRoomId = $a->getRoomId();
		$bRoomId = $b->getRoomId();

		// Make sure we have the room info
		foreach (array($aRoomId, $bRoomId) as $roomId) {
			if (!isset($roomMap[$roomId])) {
				$room =& $roomDao->getRoom($roomId);
				if ($room) $roomMap[$roomId] =& $room;
				unset($room);
			}
		}

		if (!isset($roomMap[$aRoomId])) return 1;
		if (!isset($roomMap[$bRoomId])) return -1;
		return strcmp(
			$roomMap[$aRoomId]->getRoomName(),
			$roomMap[$bRoomId]->getRoomName()
		);
	}

	function trackSort($a, $b) {
		static $trackMap = array();
		$trackDao =& DAORegistry::getDAO('TrackDAO');

		$aTrackId = $a->getTrackId();
		$bTrackId = $b->getTrackId();

		// Make sure we have the track info
		foreach (array($aTrackId, $bTrackId) as $trackId) {
			if (!isset($trackMap[$trackId])) {
				$track =& $trackDao->getTrack($trackId);
				if ($track) $trackMap[$trackId] =& $track;
				unset($track);
			}
		}

		if (!isset($trackMap[$aTrackId])) return 1;
		if (!isset($trackMap[$bTrackId])) return -1;
		return strcmp(
			$trackMap[$aTrackId]->getTrackName(),
			$trackMap[$bTrackId]->getTrackName()
		);
	}


	function titleSort($a, $b) {
		$titleA = $a->getPaperTitle();
		$titleB = $b->getPaperTitle();

		return strcmp($titleA, $titleB);
	}

	function startTimeSort($a, $b) {
		$startTimeA = $a->getStartTime();
		$startTimeB = $b->getStartTime();
		if ($startTimeA === null) return 1;
		if ($startTimeB === null) return -1;

		return strtotime($startTimeB) - strtotime($startTimeA);
	}

	function getDefaultStartTime() {
		// Determine a good default start time.
		$schedConf =& Request::getSchedConf();
		$startDate = $schedConf->getSetting('startDate');
		if (!$startDate || !is_numeric($startDate)) $startDate = time();
		list($startDay, $startMonth, $startYear) = array(strftime('%d', $startDate), strftime('%m', $startDate), strftime('%Y', $startDate));
		return mktime(10, 0, 0, $startMonth, $startDay, $startYear);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.buildings');
		$schedConf =& Request::getSchedConf();

		import('manager.form.TimelineForm');
		list($earliestDate, $latestDate) = TimelineForm::getOutsideDates($schedConf);
		$templateMgr->assign('firstYear', strftime('%Y', $earliestDate));
		$templateMgr->assign('lastYear', strftime('%Y', $latestDate));

		// Sort the data.
		$sort = Request::getUserVar('sort');
		$sortFuncMap = array(
			'presenter' => array(&$this, 'presenterSort'),
			'title' => array(&$this, 'titleSort'),
			'startTime' => array(&$this, 'startTimeSort'),
			'track' => array(&$this, 'trackSort'),
			'room' => array(&$this, 'roomSort')
		);
		if ($sort && isset($sortFuncMap[$sort])) {
			usort($this->_data['publishedPapers'], $sortFuncMap[$sort]);
		}

		$defaultStartTime = $this->getDefaultStartTime();
		$templateMgr->assign('defaultStartTime', $defaultStartTime);

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
	 * Initialize form data from current building.
	 */
	function initData() {
		$publishedPapersIterator =& $this->publishedPaperDao->getPublishedPapers($this->schedConf->getSchedConfId());
		$publishedPapers =& $publishedPapersIterator->toArray();

		$this->_data = array(
			'publishedPapers' => &$publishedPapers
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$publishedPapers =& $this->publishedPaperDao->getPublishedPapers($this->schedConf->getSchedConfId());

		// Read in the list of changes that need applying
		$changeList = array();
		$changes = Request::getUserVar('changes');
		foreach (explode("\n", $changes) as $change) {
			if (empty($change)) continue;
			$changeParts = explode(' ', $change);
			$paperId = array_shift($changeParts);
			$changeType = array_shift($changeParts);
			$newValue = trim(join(' ', $changeParts));
			$changeList[$paperId][$changeType] = $newValue;
		}

		// Apply any relevant changes to the current paper (sub)set
		$publishedPapersArray = array();
		while ($publishedPaper =& $publishedPapers->next()) {
			$paperId = $publishedPaper->getPaperId();
			if (isset($changeList[$paperId])) foreach ($changeList[$paperId] as $type => $newValue) switch ($type) {
				case 'location':
					$publishedPaper->setRoomId((int) $newValue);
					break;
				case 'date':
					// It may be that we are unscheduling:
					// if so, set start/end to null
					if (!$newValue) {
						$publishedPaper->setStartTime(null);
						$publishedPaper->setEndTime(null);
						break;
					}
					// Otherwise, date was chosen.
					list($month, $day, $year) = explode('-', $newValue);
					// Use the old values for start and end
					// times, but the new date.
					foreach (array('Start', 'End') as $funcPart) {
						$getter = 'get' . $funcPart . 'Time'; // getStartTime getEndTime
						$setter = 'set' . $funcPart . 'Time'; // setStartTime setEndTime
						$oldDate = $publishedPaper->$getter();
						if ($oldDate) $oldDate = strtotime($oldDate);
						else $oldDate = time(); // Bonehead default
						list($hour, $minute, $second) = array(
							date('H', $oldDate),
							date('i', $oldDate),
							date('s', $oldDate)
						);
						$publishedPaper->$setter(date('Y-m-d H:i:s', mktime(
							$hour, $minute, $second,
							$month, $day, $year
						)));
					}
					break;
				case 'startTime':
				case 'endTime':
					$funcPart = ucfirst($type);
					$getter = 'get' . $funcPart; // getStartTime getEndTime
					$setter = 'set' . $funcPart; // setStartTime setEndTime
					$oldDate = $publishedPaper->$getter();
					if ($oldDate) $oldDate = strtotime($oldDate);
					else $oldDate = time(); // Bonehead default
					list($year, $month, $date) = array(
						date('Y', $oldDate),
						date('m', $oldDate),
						date('d', $oldDate)
					);
					$publishedPaper->$setter(
						date('Y-m-d H:i:s',
							strtotime(
								$thing = ($year . '-' . $month . '-' . $date . ' ' . $newValue)
							)
						)
					);
					break;
			}
			$publishedPapersArray[] =& $publishedPaper;
			unset($publishedPaper);
		}

		// Make the data available to the UI.
		$this->_data = array(
			'publishedPapers' => &$publishedPapersArray,
			'changes' => $changes
		);
	}

	/**
	 * Validate the form.
	 */
	function validate() {
		$success = true;
		$publishedPapers =& $this->_data['publishedPapers'];

		foreach (array_keys($publishedPapers) as $key) {
			$publishedPaper =& $publishedPapers[$key];
			$startTime = $publishedPaper->getStartTime();
			$endTime = $publishedPaper->getEndTime();
			if (($startTime === null || $endTime === null) && ($startTime || $endTime)) {
				$fieldName = 'paper' . $publishedPaper->getPaperId() . 'StartTime';
				$this->addError($fieldName, Locale::translate('manager.scheduler.checkTimes'));
				$this->addErrorField($fieldName);
				$success = false;
			} elseif ($startTime && $endTime && strtotime($startTime) >= strtotime($endTime)) {
				$fieldName = 'paper' . $publishedPaper->getPaperId() . 'StartTime';
				$this->addError('paper' . $publishedPaper->getPaperId() . 'StartTime', Locale::translate('manager.scheduler.checkTimes'));
				$this->addErrorField($fieldName);
				$success = false;
			}
			unset($publishedPaper);
		}

		return $success;
	}

	/**
	 * Save schedule. 
	 */
	function execute() {
		$modifiedPapersById = array();
		foreach (array_keys($this->_data['publishedPapers']) as $key) {
			$modifiedPaper =& $this->_data['publishedPapers'][$key];
			$modifiedPapersById[$modifiedPaper->getPaperId()] =& $modifiedPaper;
			unset($modifiedPaper);
		}

		$publishedPapers =& $this->publishedPaperDao->getPublishedPapers($this->schedConf->getSchedConfId());

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO'); // For modifying room IDs
		$paperDao =& DAORegistry::getDAO('PaperDAO'); // For modifying times/dates
		while ($publishedPaper =& $publishedPapers->next()) {
			$paperId = $publishedPaper->getPaperId();
			if (isset($modifiedPapersById[$paperId])) {
				// Check to see if times have been modified
				$modifiedPaper =& $modifiedPapersById[$paperId];
				if (	$modifiedPaper->getStartTime() !== $publishedPaper->getStartTime() ||
					$modifiedPaper->getEndTime() !== $publishedPaper->getEndTime()) {
					$paperDao->updatePaper($modifiedPaper);
				}
				// Check to see if rooms have been modified
				if (	$modifiedPaper->getRoomId() !== $publishedPaper->getRoomId()
				) {
					$publishedPaperDao->updatePublishedPaper($modifiedPaper);
				}
				unset($modifiedPaper);
			}
			unset($publishedPaper);
		}
	}
}

?>
