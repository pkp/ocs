<?php

/**
 * @file ScheduleLayoutForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScheduleLayoutForm
 * @ingroup manager_form_scheduler
 *
 * @brief Form for conference manager to modify the layout of the schedule.
 */

//$Id$

import('form.Form');

class ScheduleLayoutForm extends Form {
	/**
	 * Constructor
	 * @param buildingId int leave as default for new building
	 */
	function ScheduleLayoutForm() {
		parent::Form('manager/scheduler/scheduleLayoutForm.tpl');

		$this->addCheck(new FormValidatorPost($this));
	}


	/**
	 * Initialize form data from current building.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();
		
		$this->_data = array(
			'mergeSchedules' => $schedConf->getSetting('mergeSchedules'),
			'showEndTime' => $schedConf->getSetting('showEndTime'),
			'showAuthors' => $schedConf->getSetting('showAuthors'),
			'hideNav' => $schedConf->getSetting('hideNav'),
			'hideLocations' => $schedConf->getSetting('hideLocations'),
			'layoutType' => $schedConf->getSetting('layoutType')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('mergeSchedules', 'showEndTime', 'showAuthors', 'hideNav', 'hideLocations', 'layoutType'));
	}

	/**
	 * Save building. 
	 */
	function execute() {
		$schedConf =& Request::getSchedConf();

		$schedConf->updateSetting('mergeSchedules', $this->getData('mergeSchedules'), 'bool');
		$schedConf->updateSetting('showEndTime', $this->getData('showEndTime'), 'bool');
		$schedConf->updateSetting('showAuthors', $this->getData('showAuthors'), 'bool');
		$schedConf->updateSetting('hideNav', $this->getData('hideNav'), 'bool');
		$schedConf->updateSetting('hideLocations', $this->getData('hideLocations'), 'bool');
		$schedConf->updateSetting('layoutType', $this->getData('layoutType'), 'int');
	}
}

?>
