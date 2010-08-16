<?php

/**
 * @file pages/announcement/AnnouncementHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_announcement
 *
 * @brief Handle requests for public announcement functions.
 */


import('lib.pkp.pages.announcement.PKPAnnouncementHandler');
import('classes.handler.validation.HandlerValidatorConference');

class AnnouncementHandler extends PKPAnnouncementHandler {
	/**
	 * Constructor
	 **/
	function AnnouncementHandler() {
		parent::PKPAnnouncementHandler();

		$this->addCheck(new HandlerValidatorConference($this));
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncementsEnabled()
	 */
	function _getAnnouncementsEnabled() {
		$conference =& Request::getConference();
		return $conference->getSetting('enableAnnouncements');
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncements()
	 */
	function &_getAnnouncements($rangeInfo = null) {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		if($schedConf) {
			$announcements =& $announcementDao->getAnnouncementsNotExpiredByConferenceId($conference->getId(), $schedConf->getId(), $rangeInfo);
		} else {
			$announcements =& $announcementDao->getAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getId(), $rangeInfo);
		}

		return $announcements;
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncementsIntroduction()
	 */
	function _getAnnouncementsIntroduction() {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if($schedConf) {
			return $schedConf->getLocalizedSetting('announcementsIntroduction');
		} else {
			return $conference->getLocalizedSetting('announcementsIntroduction');
		}
	}

	/**
	 * @see PKPAnnouncementHandler::_announcementIsValid()
	 */
	function _announcementIsValid($announcementId) {
		if ($announcementId == null) return false;

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		switch ($announcementDao->getAnnouncementAssocType($announcementId)) {
			case ASSOC_TYPE_CONFERENCE:
				$conference =& Request::getConference();
				return (
					$conference &&
					$announcementDao->getAnnouncementAssocId($announcementId) == $conference->getId()
				);
			case ASSOC_TYPE_SCHED_CONF:
				$schedConf =& Request::getSchedConf();
				return (
					$schedConf &&
					$announcementDao->getAnnouncementAssocId($announcementId) == $schedConf->getId()
				);
			default:
				return false;
		}
	}
}

?>
