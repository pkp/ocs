<?php

/**
 * @file classes/announcement/AnnouncementDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementDAO
 * @ingroup announcement
 * @see Announcement
 *
 * @brief Operations for retrieving and modifying Announcement objects.
 */


import('classes.announcement.Announcement');
import('lib.pkp.classes.announcement.PKPAnnouncementDAO');

class AnnouncementDAO extends PKPAnnouncementDAO {
	/**
	 * Constructor
	 */
	function AnnouncementDAO() {
		parent::PKPAnnouncementDAO();
	}

	/**
	 * @see PKPAnnouncementDAO::newDataObject
	 */
	function newDataObject() {
		return new Announcement();
	}

	/**
	 * Get non-expired announcements by conference ID.
	 * @param $conferenceId int
	 * @param $schedConfId int optional
	 * @param $rangeInfo Object optional
	 */
	function &getAnnouncementsNotExpiredByConferenceId($conferenceId, $schedConfId = 0, $rangeInfo = null) {

		$conferenceArgs = array(ASSOC_TYPE_CONFERENCE, $conferenceId);
		if($schedConfId == -1) {
			$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
			$schedConfs = $schedConfDao->getAll(false, $conferenceId);
			$schedConfArgs = array();
			while (!$schedConfs->eof()) {
				$schedConf =& $schedConfs->next();			
				$schedConfArgs[] = ASSOC_TYPE_SCHED_CONF;
				$schedConfArgs[] = (int) $schedConf->getId();
			}
		} else {
			$schedConfArgs = array(ASSOC_TYPE_SCHED_CONF, (int) $schedConfId);
		}

		$result =& $this->retrieveRange(
			'SELECT *
			FROM announcements
			WHERE (assoc_type = ? AND assoc_id = ?)' .
				(count($schedConfArgs) ? str_repeat(' OR (assoc_type = ? AND assoc_id = ?)', count($schedConfArgs)/2):'') .
				' AND (date_expire IS NULL OR date_expire > CURRENT_DATE)
				ORDER BY announcement_id DESC',
			array_merge($conferenceArgs, $schedConfArgs),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}	

	/**
	 * Get the number of non-expired announcements by conference ID.
	 * @param $conferenceId int
	 * @param $schedConfId int optional
	 * @param $rangeInfo Object optional
	 */
	function &getNumAnnouncementsNotExpiredByConferenceId($conferenceId, $schedConfId = 0, $numAnnouncements, $rangeInfo = null) {
		$conferenceArgs = array(ASSOC_TYPE_CONFERENCE, $conferenceId);
		if($schedConfId == -1) {
			$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
			$schedConfs = $schedConfDao->getAll(false, $conferenceId);
			$schedConfArgs = array();
			while (!$schedConfs->eof()) {
				$schedConf =& $schedConfs->next();			
				$schedConfArgs[] = ASSOC_TYPE_SCHED_CONF;
				$schedConfArgs[] = (int) $schedConf->getId();
			}
		} else {
			$schedConfArgs = array(ASSOC_TYPE_SCHED_CONF, (int) $schedConfId);
		}

		$result =& $this->retrieveRange(
			'SELECT *
			FROM announcements
			WHERE (assoc_type = ? AND assoc_id = ?)' .
				(count($schedConfArgs) ? str_repeat(' OR (assoc_type = ? AND assoc_id = ?)', count($schedConfArgs)/2):'') .
				' AND (date_expire IS NULL OR date_expire > CURRENT_DATE)
				ORDER BY announcement_id DESC LIMIT ?',
			array_merge($conferenceArgs, $schedConfArgs, array($numAnnouncements)),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}	
}

?>
