<?php

/**
 * @file classes/manager/form/AnnouncementTypeForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeForm
 * @ingroup manager_form
 * @see AnnouncementType
 *
 * @brief Form for conference manager to create/edit announcement types.
 */

//$Id$

import('manager.form.PKPAnnouncementTypeForm');

class AnnouncementTypeForm extends PKPAnnouncementTypeForm {
	/**
	 * Constructor
	 * @param typeId int leave as default for new announcement type
	 */
	function AnnouncementTypeForm($typeId = null) {
		parent::PKPAnnouncementTypeForm($typeId);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');

		parent::display();
	}

	/**
	 * Helper function to assign the AssocType and the AssocId
	 * @param Announcement the announcement to be modified
	 */
	function _setAnnouncementTypeAssocId(&$announcementType) {
		$conference =& Request::getConference();
		$announcementType->setAssocType(ASSOC_TYPE_CONFERENCE);
		$announcementType->setAssocId($conference->getId());
	}
}

?>
