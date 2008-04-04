<?php

/**
 * @file AnnouncementHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class AnnouncementHandler
 *
 * Handle requests for announcement management functions. 
 *
 * $Id$
 */

class AnnouncementHandler extends ManagerHandler {

	function index() {
		AnnouncementHandler::announcements();
	}

	/**
	 * Display a list of announcements for the current conference.
	 */
	function announcements() {
		list($conference, $schedConf) = parent::validate();
		AnnouncementHandler::setupTemplate();

		$rangeInfo = &Handler::getRangeInfo('announcements', array());
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
		while (true) {
			$announcements = &$announcementDao->getAnnouncementsByConferenceId($conference->getConferenceId(), -1, $rangeInfo);
			if ($announcements->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $announcements->getLastPageRangeInfo();
			unset($announcements);
		}

		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConfs = &$schedConfDao->getSchedConfsByConferenceId($conference->getConferenceId());
		$schedConfNames = array(0 => Locale::translate('common.all'));
		foreach($schedConfs->toArray() as $schedConf) {
			$schedConfNames[$schedConf->getSchedConfId()] = $schedConf->getSchedConfTitle();
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('announcements', $announcements);
		$templateMgr->assign_by_ref('schedConfNames', $schedConfNames);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.announcements');
		$templateMgr->display('manager/announcement/announcements.tpl');
	}

	/**
	 * Delete an announcement.
	 * @param $args array first parameter is the ID of the announcement to delete
	 */
	function deleteAnnouncement($args) {
		parent::validate();

		if (isset($args) && !empty($args)) {
			$conference = &Request::getConference();
			$announcementId = (int) $args[0];

			$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');

			// Ensure announcement is for this conference
			if ($announcementDao->getAnnouncementConferenceId($announcementId) == $conference->getConferenceId()) {
				$announcementDao->deleteAnnouncementById($announcementId);
			}
		}

		Request::redirect(null, null, null, 'announcements');
	}

	/**
	 * Display form to edit an announcement.
	 * @param $args array optional, first parameter is the ID of the announcement to edit
	 */
	function editAnnouncement($args = array()) {
		parent::validate();
		AnnouncementHandler::setupTemplate();

		$conference = &Request::getConference();
		$announcementId = !isset($args) || empty($args) ? null : (int) $args[0];
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');

		// Ensure announcement is valid and for this conference
		if (($announcementId != null && $announcementDao->getAnnouncementConferenceId($announcementId) == $conference->getConferenceId()) || ($announcementId == null)) {
			import('manager.form.AnnouncementForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'announcements'), 'manager.announcements'));

			if ($announcementId == null) {
				$templateMgr->assign('announcementTitle', 'manager.announcements.createTitle');
			} else {
				$templateMgr->assign('announcementTitle', 'manager.announcements.editTitle');	
			}

			$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
			$schedConfs = &$schedConfDao->getSchedConfsByConferenceId($conference->getConferenceId());
			$templateMgr->assign('schedConfs', $schedConfs);

			$announcementForm = &new AnnouncementForm($announcementId);
			if ($announcementForm->isLocaleResubmit()) {
				$announcementForm->readInputData();
			} else {
				$announcementForm->initData();
			}
			$announcementForm->display();

		} else {
				Request::redirect(null, null, null, 'announcements');
		}
	}

	/**
	 * Display form to create new announcement.
	 */
	function createAnnouncement() {
		AnnouncementHandler::editAnnouncement();
	}

	/**
	 * Save changes to an announcement.
	 */
	function updateAnnouncement() {
		parent::validate();

		import('manager.form.AnnouncementForm');

		$conference = &Request::getConference();
		$announcementId = Request::getUserVar('announcementId') == null ? null : (int) Request::getUserVar('announcementId');
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');

		if (($announcementId != null && $announcementDao->getAnnouncementConferenceId($announcementId) == $conference->getConferenceId()) || $announcementId == null) {

			$announcementForm = &new AnnouncementForm($announcementId);
			$announcementForm->readInputData();

			if ($announcementForm->validate()) {
				$announcementForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'createAnnouncement');
				} else {
					Request::redirect(null, null, null, 'announcements');
				}

			} else {
				AnnouncementHandler::setupTemplate();

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'announcements'), 'manager.announcements'));

				if ($announcementId == null) {
					$templateMgr->assign('announcementTitle', 'manager.announcements.createTitle');
				} else {
					$templateMgr->assign('announcementTitle', 'manager.announcements.editTitle');	
				}

				$announcementForm->display();
			}

		} else {
				Request::redirect(null, null, null, 'announcements');
		}	
	}	

	/**
	 * Display a list of announcement types for the current conference.
	 */
	function announcementTypes() {
		parent::validate();
		AnnouncementHandler::setupTemplate(true);

		$conference = &Request::getConference();
		$rangeInfo = &Handler::getRangeInfo('announcementTypes', array());
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
		while (true) {
			$announcementTypes = &$announcementTypeDao->getAnnouncementTypesByConferenceId($conference->getConferenceId(), $rangeInfo);
			if ($announcementTypes->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $announcementTypes->getLastPageRangeInfo();
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('announcementTypes', $announcementTypes);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.announcements');
		$templateMgr->display('manager/announcement/announcementTypes.tpl');
	}

	/**
	 * Delete an announcement type.
	 * @param $args array first parameter is the ID of the announcement type to delete
	 */
	function deleteAnnouncementType($args) {
		parent::validate();

		if (isset($args) && !empty($args)) {
			$conference = &Request::getConference();
			$typeId = (int) $args[0];

			$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');

			// Ensure announcement is for this conference
			if ($announcementTypeDao->getAnnouncementTypeConferenceId($typeId) == $conference->getConferenceId()) {
				$announcementTypeDao->deleteAnnouncementTypeById($typeId);
			}
		}

		Request::redirect(null, null, null, 'announcementTypes');
	}

	/**
	 * Display form to edit an announcement type.
	 * @param $args array optional, first parameter is the ID of the announcement type to edit
	 */
	function editAnnouncementType($args = array()) {
		parent::validate();
		AnnouncementHandler::setupTemplate(true);

		$conference = &Request::getConference();
		$typeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');

		// Ensure announcement type is valid and for this conference
		if (($typeId != null && $announcementTypeDao->getAnnouncementTypeConferenceId($typeId) == $conference->getConferenceId()) || ($typeId == null)) {
			import('manager.form.AnnouncementTypeForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'announcementTypes'), 'manager.announcementTypes'));

			if ($typeId == null) {
				$templateMgr->assign('announcementTypeTitle', 'manager.announcementTypes.createTitle');
			} else {
				$templateMgr->assign('announcementTypeTitle', 'manager.announcementTypes.editTitle');	
			}

			$announcementTypeForm = &new AnnouncementTypeForm($typeId);
			if ($announcementTypeForm->isLocaleResubmit()) {
				$announcementTypeForm->readInputData();
			} else {
				$announcementTypeForm->initData();
			}
			$announcementTypeForm->display();

		} else {
				Request::redirect(null, null, null, 'announcementTypes');
		}
	}

	/**
	 * Display form to create new announcement type.
	 */
	function createAnnouncementType() {
		AnnouncementHandler::editAnnouncementType();
	}

	/**
	 * Save changes to an announcement type.
	 */
	function updateAnnouncementType() {
		parent::validate();

		import('manager.form.AnnouncementTypeForm');

		$conference = &Request::getConference();
		$typeId = Request::getUserVar('typeId') == null ? null : (int) Request::getUserVar('typeId');
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');

		if (($typeId != null && $announcementTypeDao->getAnnouncementTypeConferenceId($typeId) == $conference->getConferenceId()) || $typeId == null) {

			$announcementTypeForm = &new AnnouncementTypeForm($typeId);
			$announcementTypeForm->readInputData();

			if ($announcementTypeForm->validate()) {
				$announcementTypeForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'createAnnouncementType');
				} else {
					Request::redirect(null, null, null, 'announcementTypes');
				}

			} else {
				AnnouncementHandler::setupTemplate(true);

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'announcementTypes'), 'manager.announcementTypes'));

				if ($typeId == null) {
					$templateMgr->assign('announcementTypeTitle', 'manager.announcementTypes.createTitle');
				} else {
					$templateMgr->assign('announcementTypeTitle', 'manager.announcementTypes.editTitle');	
				}

				$announcementTypeForm->display();
			}

		} else {
				Request::redirect(null, null, null, 'announcementTypes');
		}	
	}	

	function setupTemplate($subclass = false) {
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, null, 'manager', 'announcements'), 'manager.announcements'));
		}
	}
}

?>
