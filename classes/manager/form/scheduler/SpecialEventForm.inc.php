<?php

/**
 * @file SpecialEventForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SpecialEventForm
 * @ingroup manager_form_scheduler
 *
 * @brief Form for conference manager to create/edit special events for scheduler.
 */

//$Id$

import('form.Form');

class SpecialEventForm extends Form {
	/** @var specialEventId int the ID of the specialEvent being edited */
	var $specialEventId;

	/**
	 * Constructor
	 * @param specialEventId int leave as default for new specialEvent
	 */
	function SpecialEventForm($specialEventId = null) {
		$this->specialEventId = isset($specialEventId) ? (int) $specialEventId : null;

		parent::Form('manager/scheduler/specialEventForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.scheduler.specialEvent.form.nameRequired'));

		// Special event must start before it ends
		$this->addCheck(new FormValidatorCustom($this, 'endTime', 'required', 'manager.scheduler.specialEvent.form.checkTimes',
			create_function('$endTime,$form',
			'return ($endTime >= $form->getData(\'startTime\'));'),
			array(&$this)));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		return $specialEventDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$schedConf =& Request::getSchedConf();

		$templateMgr->assign('specialEventId', $this->specialEventId);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.specialEvents');

		import('manager.form.TimelineForm');
		list($earliestDate, $latestDate) = TimelineForm::getOutsideDates($schedConf);
		$templateMgr->assign('firstYear', strftime('%Y', $earliestDate));
		$templateMgr->assign('lastYear', strftime('%Y', $latestDate));

		// Get a good default start time
		import('manager.form.scheduler.ScheduleForm');
		$defaultStartTime = ScheduleForm::getDefaultStartTime();
		$templateMgr->assign('defaultStartTime', $defaultStartTime);

		parent::display();
	}

	/**
	 * Initialize form data from current specialEvent.
	 */
	function initData() {
		if (isset($this->specialEventId)) {
			$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
			$specialEvent =& $specialEventDao->getSpecialEvent($this->specialEventId);

			if ($specialEvent != null) {
				$this->_data = array(
					'name' => $specialEvent->getName(null), // Localized
					'description' => $specialEvent->getDescription(null), // Localized
					'startTime' => $specialEvent->getStartTime(),
					'endTime' => $specialEvent->getEndTime()
				);

			} else {
				$this->specialEventId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'description', 'isMultiple'));
		$this->readUserDateVars(array('startTime', 'endTime'));
		$startTime = $this->getData('startTime');
		$endTime = $this->getData('endTime');
		list($year, $month, $day) = explode('-', date('Y-m-d', $startTime));
		$this->setData('endTime', mktime(
			(int) date('H', $endTime),
			(int) date('i', $endTime),
			(int) date('s', $endTime),
			$month, $day, $year
		));
	}

	/**
	 * Save specialEvent.
	 */
	function execute() {
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		$schedConf =& Request::getSchedConf();

		if (isset($this->specialEventId)) {
			$specialEvent =& $specialEventDao->getSpecialEvent($this->specialEventId);
		}

		if (!isset($specialEvent)) {
			$specialEvent = new SpecialEvent();
		}

		$specialEvent->setSchedConfId($schedConf->getId());
		$specialEvent->setName($this->getData('name'), null); // Localized
		$specialEvent->setDescription($this->getData('description'), null); // Localized
		$specialEvent->setStartTime(date('Y-m-d H:i:s', $this->getData('startTime')));
		$specialEvent->setEndTime(date('Y-m-d H:i:s', $this->getData('endTime')));

		// Update or insert specialEvent
		if ($specialEvent->getId() != null) {
			$specialEventDao->updateSpecialEvent($specialEvent);
		} else {
			$specialEventDao->insertSpecialEvent($specialEvent);
		}
	}
}

?>
