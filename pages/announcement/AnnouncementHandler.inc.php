<?php

/**
 * @file AnnouncementHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_announcement
 *
 * @brief Handle requests for public announcement functions. 
 */

//$Id$

class AnnouncementHandler extends Handler {

	/**
	 * Display announcement index page.
	 */
	function index() {
		AnnouncementHandler::setupTemplate();

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		$announcementsEnabled = $conference->getSetting('enableAnnouncements');

		if ($announcementsEnabled) {
			$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
			$rangeInfo = &Handler::getRangeInfo('announcements');

			if($schedConf) {
				$announcements = &$announcementDao->getAnnouncementsNotExpiredByConferenceId($conference->getConferenceId(), $schedConf->getSchedConfId(), $rangeInfo);
				$announcementsIntroduction = $schedConf->getLocalizedSetting('announcementsIntroduction');
			} else {
				$announcements = &$announcementDao->getAnnouncementsNotExpiredByConferenceId($conference->getConferenceId(), 0, $rangeInfo);
				$announcementsIntroduction = $conference->getLocalizedSetting('announcementsIntroduction');
			}


			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('announcements', $announcements);
			$templateMgr->assign('announcementsIntroduction', $announcementsIntroduction);
			$templateMgr->display('announcement/index.tpl');
		} else {
			Request::redirect();
		}

	}

	/**
	 * View announcement details.
	 * @param $args array optional, first parameter is the ID of the announcement to display 
	 */
	function view($args = array()) {
		AnnouncementHandler::setupTemplate();

		$conference = &Request::getConference();

		$announcementsEnabled = $conference->getSetting('enableAnnouncements');
		$announcementId = !isset($args) || empty($args) ? null : (int) $args[0];
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');

		if ($announcementsEnabled && $announcementId != null && $announcementDao->getAnnouncementConferenceId($announcementId) == $conference->getConferenceId()) {
			$announcement = &$announcementDao->getAnnouncement($announcementId);

			if ($announcement->getDateExpire() == null || strtotime($announcement->getDateExpire()) > time()) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('announcement', $announcement);
				if ($announcement->getTypeId() == null) {
					$templateMgr->assign('announcementTitle', $announcement->getAnnouncementTitle());
				} else {
					$templateMgr->assign('announcementTitle', $announcement->getAnnouncementTypeName() . ": " . $announcement->getAnnouncementTitle());
				}
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'announcement'), 'announcement.announcements'));
				$templateMgr->display('announcement/view.tpl');
			} else {
				Request::redirect(null, null, null, 'announcement');
			}
		} else {
				Request::redirect(null, null, null, 'announcement');
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::validate();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		$templateMgr->assign('pageHierachy', array(array(Request::url(null, 'announcements'), 'announcement.announcements')));
	}
}

?>
