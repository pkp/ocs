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
	/**
	 * Constructor
	 */
	function ScheduleForm() {
		parent::Form('manager/scheduler/scheduleForm.tpl');

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
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.managementPages.schedule');

		parent::display();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();

		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
		$timeBlocks =& $timeBlockDao->getTimeBlocksBySchedConfId($schedConf->getSchedConfId());
		$timeBlockGrid = array();

		$baseDates = array(); // Array of columns representing dates
		$boundaryTimes = array(); // Array of rows representing start times

		while ($timeBlock =& $timeBlocks->next()) {
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
				if (!isset($timeBlockGrid[$baseDate][$boundaryTime])) continue;
				$gridSlotUsed[$baseDate][$boundaryTime] = 1;
				// Establish the number of rows spanned ($i); track used grid slots
				for ($i=1; (isset($boundaryTimes[$i+$boundaryTimeIndex]) && !isset($timeBlockGrid[$baseDate][$boundaryTimes[$i+$boundaryTimeIndex]]['timeBlockEnds'])); $i++) {
					$gridSlotUsed[$baseDate][$boundaryTime+$i] = 1;
				}
				$timeBlockGrid[$baseDate][$boundaryTime]['rowspan'] = $i;
			}
		}


		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$unscheduledPresentations =& $publishedPaperDao->getPublishedPapers($schedConf->getSchedConfId(), false);
		$unscheduledPresentations =& $unscheduledPresentations->toArray();
		$scheduledPresentations =& $publishedPaperDao->getPublishedPapers($schedConf->getSchedConfId(), true);
		$scheduledPresentations =& $scheduledPresentations->toArray();
		$scheduledPresentationsByTimeBlockId = array();
		foreach (array_keys($scheduledPresentations) as $key) { // By ref
			$scheduledPresentation =& $scheduledPresentations[$key];
			$scheduledPresentationsByTimeBlockId[$scheduledPresentation->getTimeBlockId()][] =& $scheduledPresentation;
			unset($scheduledPresentation);
		}

		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		$unscheduledEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConf->getSchedConfId(), false);
		$unscheduledEvents =& $unscheduledEvents->toArray();
		$scheduledEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConf->getSchedConfId(), true);
		$scheduledEvents =& $scheduledEvents->toArray();
		$scheduledEventsByTimeBlockId = array();
		foreach (array_keys($scheduledEvents) as $key) { // By ref
			$scheduledEvent =& $scheduledEvents[$key];
			$scheduledEventsByTimeBlockId[$scheduledEvent->getTimeBlockId()][] =& $scheduledEvent;
			unset($scheduledEvent);
		}

		$this->_data = array(
			'unscheduledEvents' => &$unscheduledEvents,
			'scheduledEventsByTimeBlockId' => &$scheduledEventsByTimeBlockId,
			'unscheduledPresentations' => &$unscheduledPresentations,
			'scheduledPresentationsByTimeBlockId' => &$scheduledPresentationsByTimeBlockId,
			'baseDates' => &$baseDates,
			'boundaryTimes' => &$boundaryTimes,
			'timeBlockGrid' => $timeBlockGrid,
			'gridSlotUsed' => $gridSlotUsed
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array());
	}

	/**
	 * Create the time blocks.. 
	 */
	function execute() {
	}
}

?>
