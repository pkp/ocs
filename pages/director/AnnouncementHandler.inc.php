<?php

/**
 * AnnouncementHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for announcement management functions. 
 *
 * $Id$
 */

class AnnouncementHandler extends DirectorHandler {

	function index() {
		AnnouncementHandler::announcements();
	}

	/**
	 * Display a list of announcements for the current conference.
	 */
	function announcements() {
		list($conference, $event) = parent::validate();
		AnnouncementHandler::setupTemplate();

		$rangeInfo = &Handler::getRangeInfo('announcements');
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
		$announcements = &$announcementDao->getAnnouncementsByConferenceId($conference->getConferenceId(), -1, $rangeInfo);

		$eventDao = &DAORegistry::getDAO('EventDAO');
		$events = &$eventDao->getEventsByConferenceId($conference->getConferenceId());
		$eventNames = array(0 => Locale::translate('common.all'));
		foreach($events->toArray() as $event) {
			$eventNames[$event->getEventId()] = $event->getTitle();
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('announcements', $announcements);
		$templateMgr->assign_by_ref('eventNames', $eventNames);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.announcements');
		$templateMgr->display('director/announcement/announcements.tpl');
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
			import('director.form.AnnouncementForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'director', 'announcements'), 'director.announcements'));

			if ($announcementId == null) {
				$templateMgr->assign('announcementTitle', 'director.announcements.createTitle');
			} else {
				$templateMgr->assign('announcementTitle', 'director.announcements.editTitle');	
			}

			$eventDao = &DAORegistry::getDAO('EventDAO');
			$events = &$eventDao->getEventsByConferenceId($conference->getConferenceId());
			$templateMgr->assign('events', $events);

			$announcementForm = &new AnnouncementForm($announcementId);
			$announcementForm->initData();
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
		
		import('director.form.AnnouncementForm');
		
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
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'director', 'announcements'), 'director.announcements'));

				if ($announcementId == null) {
					$templateMgr->assign('announcementTitle', 'director.announcements.createTitle');
				} else {
					$templateMgr->assign('announcementTitle', 'director.announcements.editTitle');	
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
		$rangeInfo = &Handler::getRangeInfo('announcementTypes');
		$announcementTypeDao = &DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypes = &$announcementTypeDao->getAnnouncementTypesByConferenceId($conference->getConferenceId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('announcementTypes', $announcementTypes);
		$templateMgr->assign('helpTopicId', 'conference.managementPages.announcements');
		$templateMgr->display('director/announcement/announcementTypes.tpl');
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
			import('director.form.AnnouncementTypeForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'director', 'announcementTypes'), 'director.announcementTypes'));

			if ($typeId == null) {
				$templateMgr->assign('announcementTypeTitle', 'director.announcementTypes.createTitle');
			} else {
				$templateMgr->assign('announcementTypeTitle', 'director.announcementTypes.editTitle');	
			}

			$announcementTypeForm = &new AnnouncementTypeForm($typeId);
			$announcementTypeForm->initData();
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
		
		import('director.form.AnnouncementTypeForm');
		
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
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'director', 'announcementTypes'), 'director.announcementTypes'));

				if ($typeId == null) {
					$templateMgr->assign('announcementTypeTitle', 'director.announcementTypes.createTitle');
				} else {
					$templateMgr->assign('announcementTypeTitle', 'director.announcementTypes.editTitle');	
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
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, null, 'director', 'announcements'), 'director.announcements'));
		}
	}
}

?>
