<?php

/**
 * @file classes/manager/form/scheduler/TimeBlockForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.scheduler
 * @class TimeBlockForm
 *
 * Form for conference manager to create/edit time blocks for scheduler.
 *
 * $Id$
 */

import('form.Form');

class TimeBlockForm extends Form {
	/** @var timeBlockId int the ID of the time block being edited */
	var $timeBlockId;

	/**
	 * Constructor
	 * @param timeBlockId int leave as default for new time block
	 */
	function TimeBlockForm($timeBlockId = null) {
		$this->timeBlockId = isset($timeBlockId) ? (int) $timeBlockId : null;

		parent::Form('manager/scheduler/timeBlockForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'startTime', 'required', 'manager.scheduler.timeBlocks.blockOverlap', array(&$this, 'checkBlockSequence')));
		$this->addCheck(new FormValidatorCustom($this, 'endTime', 'required', 'manager.scheduler.timeBlock.timeOrderWrong',
			create_function('$endTime,$form',
			'return ($endTime >= $form->getData(\'startTime\'));'),
			array(&$this)));
		$this->addCheck(new FormValidatorPost($this));

	}

	function checkBlockSequence($aStart) {
		$aEnd = $this->getData('endTime');

		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
		$schedConf =& Request::getSchedConf();

		$allOk = true;

		$timeBlocks =& $timeBlockDao->getTimeBlocksBySchedConfId($schedConf->getId());
		while ($timeBlock =& $timeBlocks->next()) {
			if ($this->timeBlockId != $timeBlock->getId()) {
				$bStart = strtotime($timeBlock->getStartTime());
				$bEnd = strtotime($timeBlock->getEndTime());
				if (
					($bStart >= $aStart && $bStart < $aEnd) ||
					($bEnd > $aStart && $bEnd <= $aEnd) ||
					($aStart >= $bStart && $aStart < $bEnd) ||
					($aEnd > $bStart && $aEnd <= $bEnd)
				) $allOk = false;
			}
			unset($timeBlock);
		}
		return $allOk;
	}

	/**
	 * Since the Smarty time chooser only operates in terms of actual times
	 * of day rather than durations, using this widget for durations
	 * requires a conversion. This function converts from a pair of
	 * timestamps to a number that will appear in the widget as a duration.
	 * @param $fromTime int The beginning time
	 * @param $untilTime int The end time
	 * @return int
	 */
	function timeToSmartyChooser($fromTime, $untilTime) {
		return $untilTime - $fromTime + mktime(0, 0, 0);
	}

	/**
	 * See above description of timeToSmartyChooser. This performs the
	 * opposite conversion by converting from a number that appears as a
	 * duration in a Smarty time select widget into an actual duration, i.e.
	 * a number of seconds (with an optional parameter for base timestamp).
	 * @param $smartyTime int The value of the Smarty time select widget
	 * @param $baseTimestamp int Optional timestamp to add to the duration
	 * @return int
	 */
	function smartyChooserToTime($smartyTime, $baseTimestamp = 0) {
		$time = $smartyTime - mktime(0, 0, 0);
		$time = $time % (60 * 60 * 24); // In case the interim timestamp appears on a different day
		return $time + $baseTimestamp;
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
		return $timeBlockDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('timeBlockId', $this->timeBlockId);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.timeBlocks');

		$schedConf =& Request::getSchedConf();
		$templateMgr->assign('schedConfStartDate', $schedConf->getSetting('startDate'));
		$templateMgr->assign('schedConfEndDate', $schedConf->getSetting('endDate'));

		import('classes.manager.form.TimelineForm');
		list($earliestDate, $latestDate) = TimelineForm::getOutsideDates($schedConf);
		$templateMgr->assign('firstYear', strftime('%Y', $earliestDate));
		$templateMgr->assign('lastYear', strftime('%Y', $latestDate));
		parent::display();
	}

	/**
	 * Initialize form data from current time block.
	 */
	function initData() {
		if (isset($this->timeBlockId)) {
			$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
			$timeBlock =& $timeBlockDao->getTimeBlock($this->timeBlockId);

			if ($timeBlock != null) {
				$this->_data = array(
					'startTime' => $timeBlock->getStartTime(),
					'endTime' => $timeBlock->getEndTime()
				);

			} else {
				$this->timeBlockId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$startTime = Request::getUserDateVar('startTime');
		$endTime = Request::getUserDateVar('endTime', strftime('%d', $startTime), strftime('%m', $startTime), strftime('%Y', $startTime));
		$this->setData('startTime', $startTime);
		$this->setData('endTime', $endTime);
	}

	/**
	 * Save time block.
	 */
	function execute() {
		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
		$schedConf =& Request::getSchedConf();

		if (isset($this->timeBlockId)) {
			$timeBlock =& $timeBlockDao->getTimeBlock($this->timeBlockId);
		}

		if (!isset($timeBlock)) {
			$timeBlock = new TimeBlock();
		}

		$timeBlock->setSchedConfId($schedConf->getId());
		$timeBlock->setStartTime($this->getData('startTime'));
		$timeBlock->setEndTime($this->getData('endTime'));

		// Update or insert time block
		if ($timeBlock->getId() != null) {
			$timeBlockDao->updateTimeBlock($timeBlock);
		} else {
			$timeBlockDao->insertTimeBlock($timeBlock);
		}
	}
}

?>
