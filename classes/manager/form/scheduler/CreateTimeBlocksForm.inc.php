<?php

/**
 * @file BuildingForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.scheduler
 * @class CreateTimeBlocksForm
 *
 * Form for conference manager to populate the scheduler's time blocks set
 *
 * $Id$
 */

import('form.Form');

class CreateTimeBlocksForm extends Form {
	/**
	 * Constructor
	 */
	function CreateTimeBlocksForm() {
		parent::Form('manager/scheduler/createTimeBlocksForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'timeBlocks', 'required', 'manager.scheduler.timeBlocks.blockNames', array(&$this, 'checkBlockNames')));
		$this->addCheck(new FormValidatorCustom($this, 'timeBlocks', 'required', 'manager.scheduler.timeBlocks.blockOverlap', array(&$this, 'checkBlockSequence')));
		$this->addCheck(new FormValidatorCustom($this, 'endDate', 'required', 'manager.scheduler.timeBlocks.startEndDatesOverlap',
			create_function('$endDate,$form',
			'return ($endDate >= $form->getData(\'startDate\'));'),
			array(&$this)));

		$this->addCheck(new FormValidatorPost($this));

		// Bring in conversion functions for using Smarty widgets for durations
		import('manager.form.scheduler.TimeBlockForm');
	}

	/**
	 * Check to make sure that time blocks do not overlap.
	 * @param $timeBlocks array
	 * @return boolean
	 */
	function checkBlockSequence($timeBlocks) {
		foreach ($timeBlocks as $aIndex => $aBlock) {
			$aStart = $aBlock['start'];
			$aEnd = TimeBlockForm::smartyChooserToTime($aBlock['duration'], $aStart);
			foreach ($timeBlocks as $bIndex => $bBlock) {
				if ($aIndex == $bIndex) continue;
				$bStart = $bBlock['start'];
				$bEnd = TimeBlockForm::smartyChooserToTime($bBlock['duration'], $bStart);
				if (
					($bStart >= $aStart && $bStart < $aEnd) ||
					($bEnd > $aStart && $bEnd <= $aEnd) ||
					($aStart >= $bStart && $aStart < $bEnd) ||
					($aEnd > $bStart && $aEnd <= $bEnd)
				) {
					// Overlap.
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Check to make sure that time blocks have names.
	 * @param $timeBlocks array
	 * @return boolean
	 */
	function checkBlockNames($timeBlocks) {
		$primaryLocale = Locale::getPrimaryLocale();
		foreach ($timeBlocks as $block) {
			$names = $block['name'];
			if (!is_array($names) || !isset($names[$primaryLocale]) || empty($names[$primaryLocale])) {
				// Missing name.
				return false;
			}
		}
		return true;
		
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('newBlockName');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.managementPages.createTimeBlocks');

		parent::display();
	}

	/**
	 * Initialize form data with sensible defaults.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();

		$startDate = $schedConf->getSetting('startDate');
		$endDate = $schedConf->getSetting('endDate');

		// Make sure we have a valid start and end date
		if (!$startDate || !is_numeric($startDate)) $startDate = time();
		if (!$endDate || !is_numeric($endDate) || $endDate < $startDate) $endDate = $startDate + (60 * 60 * 24 * 3); // Default 3 days after start

		$timeBlocks = array( // Provide some defaults to start with
			array( // First Session
				'start' => mktime(9, 0, 0), // 9:00am
				'end' => mktime(10, 30, 0), // 10:30am
				'name' => array(Locale::getLocale() => Locale::translate('manager.scheduler.timeBlock.firstSession'))
			),
			array( // Coffee Break
				'start' => mktime(10, 30, 0), // 10:30am
				'end' => mktime(10, 45, 0), // 10:45am
				'name' => array(Locale::getLocale() => Locale::translate('manager.scheduler.timeBlock.coffeeBreak'))
			),
			array( // Second Session
				'start' => mktime(10, 45, 0), // 10:45am
				'end' => mktime(12, 00, 0), // 12:00pm
				'name' => array(Locale::getLocale() => Locale::translate('manager.scheduler.timeBlock.secondSession'))
			),
			array( // Lunch
				'start' => mktime(12, 0, 0), // 12:00pm
				'end' => mktime(13, 00, 0), // 1:00pm
				'name' => array(Locale::getLocale() => Locale::translate('manager.scheduler.timeBlock.lunch'))
			),
			array( // Afternoon Session
				'start' => mktime(13, 0, 0), // 1:00pm
				'end' => mktime(16, 0, 0), // 4:00pm
				'name' => array(Locale::getLocale() => Locale::translate('manager.scheduler.timeBlock.afternoonSession'))
			)
		);

		// Calculate durations in a format that can be chosen by the
		// Smarty time chooser
		foreach ($timeBlocks as $index => $block) {
			$timeBlocks[$index]['duration'] = TimeBlockForm::timeToSmartyChooser($block['start'], $block['end']);
			// Remove possibility of ambiguity
			unset($timeBlocks[$index]['end']);
		}

		$defaultNewBlockStart = mktime(8, 0, 0);

		$this->_data = array(
			'startDate' => $startDate,
			'endDate' => $endDate,
			'timeBlocks' => $timeBlocks,
			'newBlockStart' => $defaultNewBlockStart,
			'newBlockDuration' => TimeBlockForm::timeToSmartyChooser($defaultNewBlockStart, $defaultNewBlockStart + (60 * 60)) // 1 hour long
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'newBlockName'));
		list($day, $month, $year) = array(strftime('%d'), strftime('%m'), strftime('%Y'));
		$this->setData('newBlockStart', Request::getUserDateVar('newBlockStart', $day, $month, $year));
		$this->setData('newBlockDuration', Request::getUserDateVar('newBlockDuration', $day, $month, $year));
		$this->setData('startDate', Request::getUserDateVar('startDate'));
		$this->setData('endDate', Request::getUserDateVar('endDate'));

		$timeBlocks = array();

		foreach ((array) Request::getUserVar('timeBlockIndexes') as $index) {
			$name = (array) Request::getUserVar('name');
			if (isset($name[$index]) && is_array($name[$index])) $name = $name[$index];
			else $name = array();
			$timeBlocks[$index] = array(
				'start' => Request::getUserDateVar('start-' . $index . '-', $day, $month, $year),
				'duration' => Request::getUserDateVar('duration-' . $index . '-', $day, $month, $year),
				'name' => $name
			);
		}
		$this->setData('timeBlocks', $timeBlocks);
	}

	/**
	 * Sort the set of time blocks by start time.
	 */
	function sortTimeBlocks() {
		$blocks = $this->getData('timeBlocks');
		usort($blocks, create_function('$a, $b', 'return $a[\'start\'] - $b[\'start\'];'));
		$this->setData('timeBlocks', $blocks);
	}

	/**
	 * Add a new time block using the POSTed parameters provided by the
	 * user.
	 */
	function addTimeBlock() {
		$timeBlocks =& $this->_data['timeBlocks'];
		$timeBlocks = (array) $timeBlocks;

		list($day, $month, $year) = array(strftime('%d'), strftime('%m'), strftime('%Y'));

		$timeBlocks[] = array(
			'start' => Request::getUserDateVar('newBlockStart', $day, $month, $year),
			'duration' => Request::getUserDateVar('newBlockDuration', $day, $month, $year),
			'name' => Request::getUserVar('newBlockName')
		);
	}

	/**
	 * Delete a time block from the current list with the specified index.
	 * @param $timeBlockIndex int
	 * @return boolean
	 */
	function deleteTimeBlock($timeBlockIndex) {
		$timeBlocks =& $this->_data['timeBlocks'];
		if (!isset($timeBlocks[$timeBlockIndex])) return false;
		unset ($timeBlocks[$timeBlockIndex]);
		return true;
	}

	/**
	 * Create the time blocks.. 
	 */
	function execute() {
		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
		$schedConf =& Request::getSchedConf();

		$startDate = $this->getData('startDate');
		list($startDay, $startMonth, $startYear) = array(strftime('%d', $startDate), strftime('%m', $startDate), strftime('%Y', $startDate));

		$endDate = $this->getData('endDate');
		list($endDay, $endMonth, $endYear) = array(strftime('%d', $endDate), strftime('%m', $endDate), strftime('%Y', $endDate));
		$boundaryDate = mktime(23, 59, 59, $endMonth, $endDay, $endYear);
		for ($thisDate = $startDate; $thisDate < $boundaryDate; $thisDate += 60 * 60 * 24) {
			list($thisDay, $thisMonth, $thisYear) = array(strftime('%d', $thisDate), strftime('%m', $thisDate), strftime('%Y', $thisDate));
			foreach ($this->getData('timeBlocks') as $block) {
				$timeBlock =& new TimeBlock();
				$timeBlock->setSchedConfId($schedConf->getSchedConfId());
				$timeBlock->setStartTime(mktime(
					strftime('%H', $block['start']),
					strftime('%M', $block['start']),
					strftime('%S', $block['start']),
					$thisMonth, $thisDay, $thisYear
				));
				$timeBlock->setEndTime(TimeBlockForm::smartyChooserToTime($block['duration'], $timeBlock->getStartTime()));
				$timeBlock->setName($block['name'], null); // Localized
				$timeBlockDao->insertTimeBlock($timeBlock);
				unset($timeBlock);
			}
		}
	}
}

?>
