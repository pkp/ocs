<?php

/**
 * AnnouncementTypeForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for conference manager to create/edit announcement types.
 *
 * $Id$
 */

import('form.Form');

class AnnouncementTypeForm extends Form {

	/** @var typeId int the ID of the announcement type being edited */
	var $typeId;

	/**
	 * Constructor
	 * @param typeId int leave as default for new announcement type
	 */
	function AnnouncementTypeForm($typeId = null) {

		$this->typeId = isset($typeId) ? (int) $typeId : null;
		$conference = &Request::getConference();

		parent::Form('manager/announcement/announcementTypeForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidator($this, 'typeName', 'required', 'manager.announcementTypes.form.typeNameRequired'));

		// Type name does not already exist for this conference
		if ($this->typeId == null) {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'manager.announcementTypes.form.typeNameExists', array(DAORegistry::getDAO('AnnouncementTypeDAO'), 'announcementTypeExistsByTypeName'), array($conference->getConferenceId()), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'manager.announcementTypes.form.typeNameExists', create_function('$typeName, $conferenceId, $typeId', '$announcementTypeDao = &DAORegistry::getDAO(\'AnnouncementTypeDAO\'); $checkId = $announcementTypeDao->getAnnouncementTypeByTypeName($typeName, $conferenceId); return ($checkId == 0 || $checkId == $typeId) ? true : false;'), array($conference->getConferenceId(), $this->typeId)));
		}
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('typeId', $this->typeId);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.announcements');
	
		parent::display();
	}
	
	/**
	 * Initialize form data from current announcement type.
	 */
	function initData() {
		if (isset($this->typeId)) {
			$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
			$announcementType = &$announcementTypeDao->getAnnouncementType($this->typeId);

			if ($announcementType != null) {
				$this->_data = array(
					'typeName' => $announcementType->getTypeName()
				);

			} else {
				$this->typeId = null;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeName'));
	
	}
	
	/**
	 * Save announcement type. 
	 */
	function execute() {
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
		$conference = &Request::getConference();
	
		if (isset($this->typeId)) {
			$announcementType = &$announcementTypeDao->getAnnouncementType($this->typeId);
		}
		
		if (!isset($announcementType)) {
			$announcementType = &new AnnouncementType();
		}
		
		$announcementType->setConferenceId($conference->getConferenceId());
		$announcementType->setTypeName($this->getData('typeName'));

		// Update or insert announcement type
		if ($announcementType->getTypeId() != null) {
			$announcementTypeDao->updateAnnouncementType($announcementType);
		} else {
			$announcementTypeDao->insertAnnouncementType($announcementType);
		}
	}
	
}

?>
