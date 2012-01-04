<?php

/**
 * @file RoomForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoomForm
 * @ingroup manager_form_scheduler
 *
 * @brief Form for conference manager to create/edit rooms for scheduler.
 */

//$Id$

import('form.Form');

class RoomForm extends Form {
	/** @var roomId int the ID of the room being edited */
	var $roomId;

	/** @var buildingId int the ID of the building this room is in */
	var $buildingId;

	/**
	 * Constructor
	 * @param roomId int leave as default for new room
	 */
	function RoomForm($roomId, $buildingId) {
		$this->roomId = isset($roomId) ? (int) $roomId : null;
		$this->buildingId = (int) $buildingId;
		$schedConf =& Request::getSchedConf();

		parent::Form('manager/scheduler/roomForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.scheduler.room.form.nameRequired'));
		
		// If provided, building ID is valid
		$this->addCheck(new FormValidatorCustom($this, 'buildingId', 'required', 'manager.scheduler.room.form.buildingIdValid', create_function('$buildingId, $schedConfId', '$buildingDao =& DAORegistry::getDAO(\'BuildingDAO\'); return $buildingDao->buildingExistsForSchedConf($buildingId, $schedConfId);'), array($schedConf->getId())));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$roomDao =& DAORegistry::getDAO('RoomDAO');
		return $roomDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('roomId', $this->roomId);
		$templateMgr->assign('buildingId', $this->buildingId);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.rooms');

		parent::display();
	}

	/**
	 * Initialize form data from current room.
	 */
	function initData() {
		if (isset($this->roomId)) {
			$roomDao =& DAORegistry::getDAO('RoomDAO');
			$room =& $roomDao->getRoom($this->roomId);

			if ($room != null) {
				$this->_data = array(
					'name' => $room->getName(null), // Localized
					'abbrev' => $room->getAbbrev(null), // Localized
					'description' => $room->getDescription(null) // Localized
				);

			} else {
				$this->roomId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'buildingId', 'description', 'abbrev'));

	}

	/**
	 * Save room. 
	 */
	function execute() {
		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$schedConf =& Request::getSchedConf();

		if (isset($this->roomId)) {
			$room =& $roomDao->getRoom($this->roomId);
		}

		if (!isset($room)) {
			$room = new Room();
		}

		$room->setBuildingId($this->buildingId);
		$room->setName($this->getData('name'), null); // Localized
		$room->setAbbrev($this->getData('abbrev'), null); // Localized
		$room->setDescription($this->getData('description'), null); // Localized

		// Update or insert room
		if ($room->getId() != null) {
			$roomDao->updateRoom($room);
		} else {
			$roomDao->insertRoom($room);
		}
	}
}

?>
