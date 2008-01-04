<?php

/**
 * @file BuildingForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class BuildingForm
 *
 * Form for conference manager to create/edit buildings for scheduler.
 *
 * $Id$
 */

import('form.Form');

class BuildingForm extends Form {
	/** @var buildingId int the ID of the building being edited */
	var $buildingId;

	/**
	 * Constructor
	 * @param buildingId int leave as default for new building
	 */
	function BuildingForm($buildingId = null) {
		$this->buildingId = isset($buildingId) ? (int) $buildingId : null;

		parent::Form('manager/scheduler/buildingForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.scheduler.building.form.nameRequired'));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		return $buildingDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('buildingId', $this->buildingId);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.buildings');

		parent::display();
	}

	/**
	 * Initialize form data from current building.
	 */
	function initData() {
		if (isset($this->buildingId)) {
			$buildingDao = &DAORegistry::getDAO('BuildingDAO');
			$building = &$buildingDao->getBuilding($this->buildingId);

			if ($building != null) {
				$this->_data = array(
					'name' => $building->getName(null), // Localized
					'description' => $building->getDescription(null) // Localized
				);

			} else {
				$this->buildingId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'description'));

	}

	/**
	 * Save building. 
	 */
	function execute() {
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$schedConf =& Request::getSchedConf();

		if (isset($this->buildingId)) {
			$building = &$buildingDao->getBuilding($this->buildingId);
		}

		if (!isset($building)) {
			$building = &new Building();
		}

		$building->setSchedConfId($schedConf->getSchedConfId());
		$building->setName($this->getData('name'), null); // Localized
		$building->setDescription($this->getData('description'), null); // Localized

		// Update or insert building
		if ($building->getBuildingId() != null) {
			$buildingDao->updateBuilding($building);
		} else {
			$buildingDao->insertBuilding($building);
		}
	}
}

?>
