<?php

/**
 * AnnouncementDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package announcement
 *
 * Class for Announcement DAO.
 * Operations for retrieving and modifying Announcement objects.
 *
 * $Id$
 */

import('announcement.Announcement');

class AnnouncementDAO extends DAO {

	/**
	 * Constructor.
	 */
	function AnnouncementDAO() {
		parent::DAO();
	}

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
	 * Internal function to return an Announcement object from a row.
	 * @param $row array
	 * @return Announcement
	 */
	function &_returnAnnouncementFromRow(&$row) {
		$announcement = &new Announcement();
		$announcement->setAnnouncementId($row['announcement_id']);
		$announcement->setConferenceId($row['conference_id']);
		$announcement->setSchedConfId($row['sched_conf_id']);
		$announcement->setTypeId($row['type_id']);
		$announcement->setTitle($row['title']);
		$announcement->setDescriptionShort($row['description_short']);
		$announcement->setDescription($row['description']);
		$announcement->setDateExpire($this->dateFromDB($row['date_expire']));
		$announcement->setDatePosted($this->dateFromDB($row['date_posted']));
		
		return $announcement;
	}

	/**
	 * Insert a new Announcement.
	 * @param $announcement Announcement
	 * @return int 
	 */
	function insertAnnouncement(&$announcement) {
		$ret = $this->update(
			sprintf('INSERT INTO announcements
				(conference_id, sched_conf_id, type_id, title, description_short, description, date_expire, date_posted)
				VALUES
				(?, ?, ?, ?, ?, ?, %s, %s)',
				$this->dateToDB($announcement->getDateExpire()), $this->dateToDB($announcement->getDatePosted())),
			array(
				$announcement->getConferenceId(),
				$announcement->getSchedConfId(),
				$announcement->getTypeId(),
				$announcement->getTitle(),
				$announcement->getDescriptionShort(),
				$announcement->getDescription()
			)
		);
		$announcement->setAnnouncementId($this->getInsertAnnouncementId());
		return $announcement->getAnnouncementId();
	}

	/**
	 * Update an existing announcement.
	 * @param $announcement Announcement
	 * @return boolean
	 */
	function updateAnnouncement(&$announcement) {
		return $this->update(
			sprintf('UPDATE announcements
				SET
					conference_id = ?,
					sched_conf_id = ?,
					type_id = ?,
					title = ?,
					description_short = ?,
					description = ?,
					date_expire = %s
				WHERE announcement_id = ?',
				$this->dateToDB($announcement->getDateExpire())),
			array(
				$announcement->getConferenceId(),
				$announcement->getSchedConfId(),
				$announcement->getTypeId(),
				$announcement->getTitle(),
				$announcement->getDescriptionShort(),
				$announcement->getDescription(),
				$announcement->getAnnouncementId()
			)
		);
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
		return $this->update(
			'DELETE FROM announcements WHERE announcement_id = ?', $announcementId
		);
	}

	/**
	 * Delete announcements by announcement type ID.
	 * @param $typeId int
	 * @return boolean
	 */
	function deleteAnnouncementByTypeId($typeId) {
		return $this->update(
			'DELETE FROM announcements WHERE type_id = ?', $typeId
		);
	}

	/**
	 * Delete announcements by conference ID.
	 * @param $conferenceId int
	 */
	function deleteAnnouncementsByConference($conferenceId) {
		return $this->update(
			'DELETE FROM announcements WHERE conference_id = ?',
			$conferenceId
		);
	}

	/**
	 * Delete announcements by sched conf ID.
	 * @param $conferenceId int
	 */
	function deleteAnnouncementsBySchedConf($schedConfId) {
		return $this->update(
			'DELETE FROM announcements WHERE sched_conf_id = ?',
			$schedConfId
		);
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
			'ORDER BY announcement_id DESC',
			$args,
			$rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
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

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
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

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
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

		$returner = &new DAOResultFactory($result, $this, '_returnAnnouncementFromRow');
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
