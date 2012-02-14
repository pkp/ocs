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

import('lib.pkp.classes.manager.form.PKPAnnouncementForm');

class AnnouncementForm extends PKPAnnouncementForm {
	/**
	 * Constructor
	 * @param $conferenceId int
	 * @param announcementId int leave as default for new announcement
	 */
	function AnnouncementForm($conferenceId, $announcementId = null) {
		parent::PKPAnnouncementForm($conferenceId, $announcementId);

		// If provided, announcement type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'optional', 'manager.announcements.form.typeIdValid', create_function('$typeId, $conferenceId', '$announcementTypeDao =& DAORegistry::getDAO(\'AnnouncementTypeDAO\'); return $announcementTypeDao->announcementTypeExistsByTypeId($typeId, ASSOC_TYPE_CONFERENCE, $conferenceId);'), array($conferenceId)));

		// If supplied, the scheduled conference exists and belongs to the conference
		$this->addCheck(new FormValidatorCustom($this, 'schedConfId', 'required', 'manager.announcements.form.schedConfIdValid', create_function('$schedConfId, $conferenceId', 'if ($schedConfId == 0) return true; $schedConfDao =& DAORegistry::getDAO(\'SchedConfDAO\'); $schedConf =& $schedConfDao->getSchedConf($schedConfId); if(!$schedConf) return false; return ($schedConf->getConferenceId() == $conferenceId);'), array($conferenceId)));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');

		$conferenceId = $this->getContextId();
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConfs =& $schedConfDao->getSchedConfs(false, $conferenceId);
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
		$conferenceId = $this->getContextId();
		return array(ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Helper function to assign the AssocType and the AssocId
	 * @param Announcement the announcement to be modified
	 */
	function _setAnnouncementAssocId(&$announcement) {
		if ($this->getData('schedConfId') == 0) {
			$conferenceId = $this->getContextId();
			$announcement->setAssocType(ASSOC_TYPE_CONFERENCE);
			$announcement->setAssocId($conferenceId);
		} else {
			$announcement->setAssocType(ASSOC_TYPE_SCHED_CONF);
			$announcement->setAssocId($this->getData('schedConfId'));
		}
	}

	/**
	 * Save announcement.
	 * @param $request Request
	 */
	function execute(&$request) {
		$announcement = parent::execute();
		$conferenceId = $this->getContextId();

		// Send a notification to associated users
		import('classes.notification.NotificationManager');
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
			$url = Request::url(null, 'index', 'announcement', 'view', array($announcement->getId()));
		} else {
			// Associated with a sched conf -- determine its path.
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getSchedConf($schedConfId);
			$url = Request::url(null, $schedConf->getPath(), 'announcement', 'view', array($announcement->getId()));
		}

		foreach ($notificationUsers as $userRole) {
			$notificationManager->createNotification(
				$request, $userRole['id'], NOTIFICATION_TYPE_NEW_ANNOUNCEMENT,
				$conferenceId, ASSOC_TYPE_ANNOUNCEMENT, $announcement->getId()
			);
		}
		$notificationManager->sendToMailingList($request,
			$notificationManager->createNotification(
				$request, $userRole['id'], NOTIFICATION_TYPE_NEW_ANNOUNCEMENT,
				$conferenceId, ASSOC_TYPE_ANNOUNCEMENT, $announcement->getId()
			)
		);
	}
}

?>
