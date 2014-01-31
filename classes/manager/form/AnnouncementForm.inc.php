<?php

/**
 * @defgroup manager_form
 */
 
/**
 * @file classes/manager/form/AnnouncementForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementForm
 * @ingroup manager_form
 *
 * @brief Form for conference managers to create/edit announcements.
 */

// $Id$

import('manager.form.PKPAnnouncementForm');

class AnnouncementForm extends PKPAnnouncementForm {
	/**
	 * Constructor
	 * @param announcementId int leave as default for new announcement
	 */
	function AnnouncementForm($announcementId = null) {
		parent::PKPAnnouncementForm($announcementId);

		$conference =& Request::getConference();

		// If provided, announcement type is valid 
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'optional', 'manager.announcements.form.typeIdValid', create_function('$typeId, $conferenceId', '$announcementTypeDao =& DAORegistry::getDAO(\'AnnouncementTypeDAO\'); return $announcementTypeDao->announcementTypeExistsByTypeId($typeId, ASSOC_TYPE_CONFERENCE, $conferenceId);'), array($conference->getId())));

		// If supplied, the scheduled conference exists and belongs to the conference
		$this->addCheck(new FormValidatorCustom($this, 'schedConfId', 'required', 'manager.announcements.form.schedConfIdValid', create_function('$schedConfId, $conferenceId', 'if ($schedConfId == 0) return true; $schedConfDao =& DAORegistry::getDAO(\'SchedConfDAO\'); $schedConf =& $schedConfDao->getSchedConf($schedConfId); if(!$schedConf) return false; return ($schedConf->getConferenceId() == $conferenceId);'), array($conference->getId())));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');

		$conference =& Request::getConference();
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conference->getId());
		$templateMgr->assign('schedConfs', $schedConfs);

		parent::display();
	}

	/**
	 * Initialize form data from current announcement.
	 */
	function initData() {
		parent::initData();

		if (isset($this->announcementId)) {
			$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
			$announcement =& $announcementDao->getAnnouncement($this->announcementId);

			if ($announcement != null) {
				$this->_data['schedConfId'] = ($announcement->getAssocType() == ASSOC_TYPE_SCHED_CONF)?$announcement->getAssocId():null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('schedConfId'));
	}
	
	function _getAnnouncementTypesAssocId() {
		$conference =& Request::getConference();
		return array(ASSOC_TYPE_CONFERENCE, $conference->getId()); 
	}

	/**
	 * Helper function to assign the AssocType and the AssocId
	 * @param Announcement the announcement to be modified
	 */
	function _setAnnouncementAssocId(&$announcement) {
		if ($this->getData('schedConfId') == 0) {
			$conference =& Request::getConference();
			$announcement->setAssocType(ASSOC_TYPE_CONFERENCE);
			$announcement->setAssocId($conference->getId());
		} else {
			$announcement->setAssocType(ASSOC_TYPE_SCHED_CONF);
			$announcement->setAssocId($this->getData('schedConfId'));
		}
	}

	/**
	 * Save announcement. 
	 */
	function execute() {
		parent::execute();
		$conference =& Request::getConference();
		$conferenceId = $conference->getId();
		
		// Send a notification to associated users
		import('notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$notificationUsers = array();
		$allUsers = $roleDao->getUsersByConferenceId($conferenceId);
		while (!$allUsers->eof()) {
			$user =& $allUsers->next();
			$notificationUsers[] = array('id' => $user->getId());
			unset($user);
		}

		$schedConfId = $this->getData('schedConfId');
		if ($schedConfId == 0) {
			// Associated with the conference as a whole.
			$url = Request::url(null, 'index', 'announcement', 'view', array(1));
		} else {
			// Associated with a sched conf -- determine its path.
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getSchedConf($schedConfId);
			$url = Request::url(null, $schedConf->getPath(), 'announcement', 'view', array(1));
		}

		foreach ($notificationUsers as $userRole) {
			$notificationManager->createNotification(
				$userRole['id'], 'notification.type.newAnnouncement',
				null, $url, 1, NOTIFICATION_TYPE_NEW_ANNOUNCEMENT
			);
		}
		$notificationManager->sendToMailingList(
			$notificationManager->createNotification(
				0, 'notification.type.newAnnouncement',
				null, $url, 1, NOTIFICATION_TYPE_NEW_ANNOUNCEMENT
			)
		);
	}
}

?>
