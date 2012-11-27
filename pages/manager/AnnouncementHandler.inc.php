<?php

/**
 * @file pages/manager/AnnouncementHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for announcement management functions.
 */

import('lib.pkp.pages.manager.PKPAnnouncementHandler');

class AnnouncementHandler extends PKPAnnouncementHandler {
	/**
	 * Constructor
	 */
	function AnnouncementHandler() {
		parent::PKPAnnouncementHandler();
	}

	/**
	 * Display a list of announcements for the current conference.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function announcements($args, &$request) {
		$templateMgr =& TemplateManager::getManager();

 		$conferenceId = $this->getContextId($request);
		if ($conferenceId) {
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConfs =& $schedConfDao->getSchedConfs(false, $conferenceId);
			$schedConfNames = array(0 => __('common.all'));
			foreach($schedConfs->toArray() as $schedConf) {
				$schedConfNames[$schedConf->getId()] = $schedConf->getLocalizedName();
			}

			$templateMgr->assign_by_ref('schedConfNames', $schedConfNames);
		}

		//TODO: move this assignment to the abstracted templates or generalize the key
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');
		parent::announcements($args, $request);
	}

	/**
	 * Display a list of announcement types for the current conference.
	 * @see PKPAnnouncementHandler::announcementTypes
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function announcementTypes($args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		//TODO: move this assignment to the abstracted templates or generalize the key
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');
		parent::announcementTypes($args, $request);
	}

	/**
	 * @see PKPAnnouncementHandler::getContextId()
	 */
	function getContextId(&$request) {
		$conference =& $request->getConference();
		if ($conference) {
			return $conference->getId();
		} else {
			return null;
		}
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncements
	 * @param $request PKPRequest
	 * @param $rangeInfo object optional
	 */
	function &_getAnnouncements($request, $rangeInfo = null) {
		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcements =& $announcementDao->getByAssocId($this->getContextId($request), -1, $rangeInfo);

		return $announcements;
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncementTypes
	 * @param $request PKPRequest
	 * @param $rangeInfo object optional
	 */
	function &_getAnnouncementTypes($request, $rangeInfo = null) {
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcements =& $announcementTypeDao->getByAssoc(ASSOC_TYPE_CONFERENCE, $this->getContextId($request), $rangeInfo);

		return $announcements;
	}

	/**
	 * Checks the announcement to see if it belongs to this conference or scheduled conference
	 * @param $request PKPRequest
	 * @param $announcementId int
	 * return bool
	 */
	function _announcementIsValid($request, $announcementId) {
		if ($announcementId == null) return true;

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcement =& $announcementDao->getById($announcementId);
		if (!$announcement) return false;

		$conferenceId = $this->getContextId($request);
		if ($conferenceId
			&& $announcement->getAssocType() == ASSOC_TYPE_CONFERENCE
			&& $announcement->getAssocId() == $conferenceId)
				return true;

		// if its a schedConf announcements, make sure it is for a schedConf that belongs to the current conference
		if ($announcement->getAssocType() == ASSOC_TYPE_SCHED_CONF) {
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getSchedConf($announcement->getAssocId());
			if ($schedConf
				&& $conferenceId
				&& $schedConf->getConferenceId() == $conferenceId)
					return true;
		}

		return false;
	}

	/**
	 * Checks the announcement type to see if it belongs to this conference.  All announcement types are set at the conference level.
	 * @param $request PKPRequest
	 * @param $typeId int
	 * return bool
	 */
	function _announcementTypeIsValid($request, $typeId) {
		$conferenceId = $this->getContextId($request);
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		return (($typeId != null && $announcementTypeDao->getAnnouncementTypeAssocId($typeId) == $conferenceId) || $typeId == null);
	}
}

?>
