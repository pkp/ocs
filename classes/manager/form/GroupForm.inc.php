<?php

/**
 * @file GroupForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GroupForm
 * @ingroup manager_form
 *
 * @brief Form for conference managers to create/edit groups.
 */

//$Id$

import('form.Form');

class GroupForm extends Form {
	/** @var groupId int the ID of the group being edited */
	var $group;

	/**
	 * Constructor
	 * @param group Group object; null to create new
	 */
	function GroupForm($group = null) {
		$conference =& Request::getConference();

		parent::Form('manager/groups/groupForm.tpl');

		// Group title is provided
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.groups.form.groupTitleRequired'));
		$this->addCheck(new FormValidatorPost($this));

		$this->group =& $group;
	}

	/**
	 * Get the list of localized field names for this object
	 * @return array
	 */
	function getLocaleFieldNames() {
		$groupDao =& DAORegistry::getDAO('GroupDAO');
		return $groupDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('group', $this->group);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.organizingTeam');
		parent::display();
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		if ($this->group != null) {
			$this->_data = array(
				'title' => $this->group->getTitle(null) // Localized
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title'));
	}

	/**
	 * Save group group. 
	 */
	function execute() {
		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if (!isset($this->group)) {
			$this->group = new Group();
		}

		$this->group->setAssocType(ASSOC_TYPE_SCHED_CONF);
		$this->group->setAssocId($schedConf->getId());
		$this->group->setTitle($this->getData('title'), null); // Localized

		// Eventually this will be a general Groups feature; for now,
		// we're just using it to display conference team entries in About.
		$this->group->setAboutDisplayed(true);

		// Update or insert group group
		if ($this->group->getId() != null) {
			$groupDao->updateObject($this->group);
		} else {
			$this->group->setSequence(REALLY_BIG_NUMBER);
			$groupDao->insertGroup($this->group);

			// Re-order the groups so the new one is at the end of the list.
			$groupDao->resequenceGroups($this->group->getAssocType(), $this->group->getAssocId());
		}
	}

}

?>
