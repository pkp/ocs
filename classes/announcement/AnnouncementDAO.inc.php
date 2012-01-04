<?php

/**
 * @file AnnouncementDAO.inc.php
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

// $Id$

import('announcement.Announcement');
import('announcement.PKPAnnouncementDAO');

class AnnouncementDAO extends PKPAnnouncementDAO {

	function &getAnnouncementsByConferenceId($conferenceId, $schedConfId = 0, $rangeInfo = null) {
		$conferenceArgs = array(ASSOC_TYPE_CONFERENCE, $conferenceId);
		if($schedConfId == -1) {
			$schedConfDAO =& DAORegistry::getDAO('SchedConfDAO');
			$schedConfs = $schedConfDAO->getSchedConfsByConferenceId($conferenceId);
			$schedConfArgs = array();
			while (!$schedConfs->eof()) {
				$schedConf =& $schedConfs->next();			
				$schedConfArgs[] = ASSOC_TYPE_SCHED_CONF;
				$schedConfArgs[] = $schedConf->getId();
			}
		} else {
			$schedConfArgs = array(ASSOC_TYPE_SCHED_CONF, $schedConfId);
		}

		$result =& $this->retrieveRange(
			'SELECT *
			FROM announcements
			WHERE (assoc_type = ? AND assoc_id = ?)' .
				(count($schedConfArgs) ? str_repeat(' OR (assoc_type = ? AND assoc_id = ?)', count($schedConfArgs)/2):'') .
			' ORDER BY announcement_id DESC',
			array_merge($conferenceArgs, $schedConfArgs),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	function &getAnnouncementsNotExpiredByConferenceId($conferenceId, $schedConfId = 0, $rangeInfo = null) {

		$conferenceArgs = array(ASSOC_TYPE_CONFERENCE, $conferenceId);
		if($schedConfId == -1) {
			$schedConfDAO =& DAORegistry::getDAO('SchedConfDAO');
			$schedConfs = $schedConfDAO->getSchedConfsByConferenceId($conferenceId);
			$schedConfArgs = array();
			while (!$schedConfs->eof()) {
				$schedConf =& $schedConfs->next();			
				$schedConfArgs[] = ASSOC_TYPE_SCHED_CONF;
				$schedConfArgs[] = $schedConf->getId();
			}
		} else {
			$schedConfArgs = array(ASSOC_TYPE_SCHED_CONF, $schedConfId);
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
	
	function &getNumAnnouncementsNotExpiredByConferenceId($conferenceId, $schedConfId = 0, $numAnnouncements, $rangeInfo = null) {
		$conferenceArgs = array(ASSOC_TYPE_CONFERENCE, $conferenceId);
		if($schedConfId == -1) {
			$schedConfDAO =& DAORegistry::getDAO('SchedConfDAO');
			$schedConfs = $schedConfDAO->getSchedConfsByConferenceId($conferenceId);
			$schedConfArgs = array();
			while (!$schedConfs->eof()) {
				$schedConf =& $schedConfs->next();			
				$schedConfArgs[] = ASSOC_TYPE_SCHED_CONF;
				$schedConfArgs[] = $schedConf->getId();
			}
		} else {
			$schedConfArgs = array(ASSOC_TYPE_SCHED_CONF, $schedConfId);
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
