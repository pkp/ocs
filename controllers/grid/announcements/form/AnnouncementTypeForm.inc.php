<?php
/**
 * @file controllers/grid/announcements/form/AnnouncementTypeForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeForm
 * @ingroup controllers_grid_announcements_form
 *
 * @brief Form for to read/create/edit announcement types.
 */


import('lib.pkp.classes.manager.form.PKPAnnouncementTypeForm');

class AnnouncementTypeForm extends PKPAnnouncementTypeForm {
	/** @var $conferenceId int */
	var $conferenceId;

	/**
	 * Constructor
	 * @param $conferenceId int
	 * @param $announcementTypeId int leave as default for new announcement
	 */
	function AnnouncementTypeForm($conferenceId, $announcementTypeId = null) {
		parent::PKPAnnouncementTypeForm($announcementTypeId);
		$this->conferenceId = $conferenceId;
	}


	//
	// Extended methods from Form
	//
	/**
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('typeId', $this->typeId);
		return parent::fetch($request, 'controllers/grid/announcements/form/announcementTypeForm.tpl');
	}

	//
	// Private helper methdos.
	//
	/**
	 * Helper function to assign the AssocType and the AssocId
	 * @param AnnouncementType the announcement type to be modified
	 */
	function _setAnnouncementTypeAssocId(&$announcementType) {
		$conferenceId = $this->conferenceId;
		$announcementType->setAssocType(ASSOC_TYPE_CONFERENCE);
		$announcementType->setAssocId($conferenceId);
	}
}

?>
