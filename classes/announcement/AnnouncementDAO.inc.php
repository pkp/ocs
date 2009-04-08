<?php

/**
 * @file AnnouncementDAO.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
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

class AnnouncementDAO extends DAO {
	/**
	 * Retrieve an announcement by announcement ID.
	 * @param $announcementId int
	 * @return Announcement
	 */
	function &getAnnouncement($announcementId) {
		$result = &$this->retrieve(
			'SELECT * FROM announcements WHERE announcement_id = ?', $announcementId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnAnnouncementFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve announcement conference ID by announcement ID.
	 * @param $announcementId int
	 * @return int
	 */
	function getAnnouncementConferenceId($announcementId) {
		$result = &$this->retrieve(
			'SELECT conference_id FROM announcements WHERE announcement_id = ?', $announcementId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Get the list of localized field names for this table
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'descriptionShort', 'description');
	}

	/**
	 * Internal function to return an Announcement object from a row.
	 * @param $row array
	 * @return Announcement
	 */
	function &_returnAnnouncementFromRow(&$row) {
		$announcement = new Announcement();
		$announcement->setAnnouncementId($row['announcement_id']);
		$announcement->setConferenceId($row['conference_id']);
		$announcement->setSchedConfId($row['sched_conf_id']);
		$announcement->setTypeId($row['type_id']);
		$announcement->setDateExpire($this->dateFromDB($row['date_expire']));
		$announcement->setDatePosted($this->datetimeFromDB($row['date_posted']));

		$this->getDataObjectSettings('announcement_settings', 'announcement_id', $row['announcement_id'], $announcement);

		return $announcement;
	}

	/**
	 * Update the settings for this object
	 * @param $announcement object
	 */
	function updateLocaleFields(&$announcement) {
		$this->updateDataObjectSettings('announcement_settings', $announcement, array(
			'announcement_id' => $announcement->getAnnouncementId()
		));
	}

	/**
	 * Insert a new Announcement.
	 * @param $announcement Announcement
	 * @return int 
	 */
	function insertAnnouncement(&$announcement) {
		$this->update(
			sprintf('INSERT INTO announcements
				(conference_id, sched_conf_id, type_id, date_expire, date_posted)
				VALUES
				(?, ?, ?, %s, %s)',
				$this->dateToDB($announcement->getDateExpire()), $this->dateToDB($announcement->getDatetimePosted())),
			array(
				$announcement->getConferenceId(),
				$announcement->getSchedConfId(),
				$announcement->getTypeId()
			)
		);
		$announcement->setAnnouncementId($this->getInsertAnnouncementId());
		$this->updateLocaleFields($announcement);
		return $announcement->getAnnouncementId();
	}

	/**
	 * Update an existing announcement.
	 * @param $announcement Announcement
	 * @return boolean
	 */
	function updateAnnouncement(&$announcement) {
		$returner = $this->update(
			sprintf('UPDATE announcements
				SET
					conference_id = ?,
					sched_conf_id = ?,
					type_id = ?,
					date_expire = %s
				WHERE announcement_id = ?',
				$this->dateToDB($announcement->getDateExpire())),
			array(
				$announcement->getConferenceId(),
				$announcement->getSchedConfId(),
				$announcement->getTypeId(),
				$announcement->getAnnouncementId()
			)
		);
		$this->updateLocaleFields($announcement);
		return $returner;
	}

	/**
	 * Delete an announcement.
	 * @param $announcement Announcement 
	 * @return boolean
	 */
	function deleteAnnouncement($announcement) {
		return $this->deleteAnnouncementById($announcement->getAnnouncementId());
	}

	/**
	 * Delete an announcement by announcement ID.
	 * @param $announcementId int
	 * @return boolean
	 */
	function deleteAnnouncementById($announcementId) {
		$this->update('DELETE FROM announcement_settings WHERE announcement_id = ?', $announcementId);
		return $this->update('DELETE FROM announcements WHERE announcement_id = ?', $announcementId);
	}

	/**
	 * Delete announcements by announcement type ID.
	 * @param $typeId int
	 * @return boolean
	 */
	function deleteAnnouncementByTypeId($typeId) {
		$announcements =& $this->getAnnouncementsByTypeId($typeId);
		while (($announcement =& $announcements->next())) {
			$this->deleteAnnouncement($announcement->getAnnouncementId());
			unset($announcement);
		}
	}

	/**
	 * Delete announcements by conference ID.
	 * @param $conferenceId int
	 */
	function deleteAnnouncementsByConference($conferenceId) {
		$announcements =& $this->getAnnouncementsByConferenceId($conferenceId, -1);
		while (($announcement =& $announcements->next())) {
			$this->deleteAnnouncementById($announcement->getAnnouncementId());
			unset($announcement);
		}
		return true;
	}

	/**
	 * Delete announcements by sched conf ID.
	 * @param $conferenceId int
	 */
	function deleteAnnouncementsBySchedConf($schedConfId) {
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($schedConfId);
		if (!$schedConf) return false;

		$announcements =& $this->getAnnouncementsByConferenceId($schedConf->getConferenceId(), $schedConf->getSchedConfId());
		while (($announcement =& $announcements->next())) {
			// Watch out for conference-level announcements; do not delete those
			if ($announcement->getSchedConfId() == $schedConfId) {
				$this->deleteAnnouncementById($announcement->getAnnouncementId());
			}
			unset($announcement);
		}
		return true;
	}

	/**
	 * Retrieve an array of announcements matching a particular conference ID.
	 * @param $conferenceId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getAnnouncementsByConferenceId($conferenceId, $schedConfId = 0, $rangeInfo = null) {
		$args = $conferenceId;
		if($schedConfId !== -1) {
			$args = array($args, $schedConfId);
		}

		$result = &$this->retrieveRange(
			'SELECT *
			FROM announcements
			WHERE conference_id = ?' .
				($schedConfId !== -1 ? ' AND (sched_conf_id = ? OR sched_conf_id = 0)':'') .
			' ORDER BY announcement_id DESC',
			$args,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of announcements matching a particular type ID.
	 * @param $typeId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getAnnouncementsByTypeId($typeId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM announcements WHERE type_id = ? ORDER BY announcement_id DESC', $typeId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of numAnnouncements announcements matching a particular conference ID.
	 * @param $conferenceId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getNumAnnouncementsByConferenceId($conferenceId, $schedConfId, $numAnnouncements, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT *
			FROM announcements
			WHERE conference_id = ?
				AND (sched_conf_id = ? OR sched_conf_id = 0)
			ORDER BY announcement_id DESC LIMIT ?',
			array($conferenceId, $schedConfId, $numAnnouncements),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of announcements with no/valid expiry date matching a particular conference ID.
	 * @param $conferenceId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getAnnouncementsNotExpiredByConferenceId($conferenceId, $schedConfId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT *
			FROM announcements
			WHERE conference_id = ?
				AND (sched_conf_id = ? OR sched_conf_id = 0)
				AND (date_expire IS NULL OR date_expire > CURRENT_DATE)
			ORDER BY announcement_id DESC',
			array($conferenceId, $schedConfId),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve an array of numAnnouncements announcements with no/valid expiry date matching a particular conference ID.
	 * @param $conferenceId int
	 * @return object DAOResultFactory containing matching Announcements
	 */
	function &getNumAnnouncementsNotExpiredByConferenceId($conferenceId, $schedConfId, $numAnnouncements, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT *
			FROM announcements
			WHERE conference_id = ?
				AND (sched_conf_id = ? OR sched_conf_id = 0)
				AND (date_expire IS NULL OR date_expire > CURRENT_DATE)
			ORDER BY announcement_id DESC LIMIT ?',
			array($conferenceId, $schedConfId, $numAnnouncements), $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
		return $returner;
	}

	/**
	 * Retrieve most recent announcement by conference ID.
	 * @param $conferenceId int
	 * @return Announcement
	 */
	function &getMostRecentAnnouncementByConferenceId($conferenceId, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT *
			FROM announcements
			WHERE conference_id = ?
				AND (sched_conf_id = ? OR sched_conf_id = 0)
			ORDER BY announcement_id DESC LIMIT 1',
			array($conferenceId, $schedConfId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnAnnouncementFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get the ID of the last inserted announcement.
	 * @return int
	 */
	function getInsertAnnouncementId() {
		return $this->getInsertId('announcements', 'announcement_id');
	}
}

?>
