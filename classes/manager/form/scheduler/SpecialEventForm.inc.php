<?php

/**
 * @file SpecialEventForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.scheduler
 * @class SpecialEventForm
 *
 * Form for conference manager to create/edit special events for scheduler.
 *
 * $Id$
 */

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
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('specialEventId', $this->specialEventId);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.specialEvents');

		parent::display();
	}

	/**
	 * Initialize form data from current specialEvent.
	 */
	function initData() {
		if (isset($this->specialEventId)) {
			$specialEventDao = &DAORegistry::getDAO('SpecialEventDAO');
			$specialEvent = &$specialEventDao->getSpecialEvent($this->specialEventId);

			if ($specialEvent != null) {
				$this->_data = array(
					'name' => $specialEvent->getName(null), // Localized
					'description' => $specialEvent->getDescription(null) // Localized
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

	}

	/**
	 * Save specialEvent. 
	 */
	function execute() {
		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		$schedConf =& Request::getSchedConf();

		if (isset($this->specialEventId)) {
			$specialEvent = &$specialEventDao->getSpecialEvent($this->specialEventId);
		}

		if (!isset($specialEvent)) {
			$specialEvent = &new SpecialEvent();
		}

		$specialEvent->setSchedConfId($schedConf->getSchedConfId());
		$specialEvent->setName($this->getData('name'), null); // Localized
		$specialEvent->setDescription($this->getData('description'), null); // Localized
		$specialEvent->setIsMultiple($this->getData('isMultiple'));

		// Update or insert specialEvent
		if ($specialEvent->getSpecialEventId() != null) {
			$specialEventDao->updateSpecialEvent($specialEvent);
		} else {
			$specialEventDao->insertSpecialEvent($specialEvent);
		}
	}
}

?>
