<?php

/**
 * @file ConferenceDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceDAO
 * @ingroup conference
 * @see ConferenceDAO
 *
 * @brief Operations for retrieving and modifying Conference objects.
 */

//$Id$

import ('conference.Conference');

class ConferenceDAO extends DAO {
	/**
	 * Retrieve a conference by ID.
	 * @param $conferenceId int
	 * @return Conference
	 */
	function &getConference($conferenceId) {
		$result =& $this->retrieve(
			'SELECT * FROM conferences WHERE conference_id = ?', $conferenceId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnConferenceFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Retrieves the most recently created conference.
	 * @param $conferenceId int
	 * @return Conference
	 */
	function &getFreshestConference() {
		$returner = null;
		$result =& $this->retrieve(
			'SELECT * FROM conferences ORDER BY conference_id DESC LIMIT 1'
		);

		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnConferenceFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Retrieve a conference by path.
	 * @param $path string
	 * @return Conference
	 */
	function &getConferenceByPath($path) {
		$returner = null;
		$result =& $this->retrieve(
			'SELECT * FROM conferences WHERE path = ?', $path
		);

		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnConferenceFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Internal function to return a Conference object from a row.
	 * @param $row array
	 * @return Conference
	 */
	function &_returnConferenceFromRow(&$row) {
		$conference = new Conference();
		$conference->setConferenceId($row['conference_id']);
		$conference->setPath($row['path']);
		$conference->setSequence($row['seq']);
		$conference->setEnabled($row['enabled']);
		$conference->setPrimaryLocale($row['primary_locale']);

		HookRegistry::call('ConferenceDAO::_returnConferenceFromRow', array(&$conference, &$row));

		return $conference;
	}

	/**
	 * Insert a new conference.
	 * @param $conference Conference
	 */
	function insertConference(&$conference) {
		$this->update(
			'INSERT INTO conferences
				(primary_locale, path, seq, enabled)
				VALUES
				(?, ?, ?, ?)',
			array(
				$conference->getPrimaryLocale(),
				$conference->getPath(),
				$conference->getSequence() == null ? 0 : $conference->getSequence(),
				$conference->getEnabled() ? 1 : 0
			)
		);

		$conference->setConferenceId($this->getInsertConferenceId());
		return $conference->getId();
	}

	/**
	 * Update an existing conference.
	 * @param $conference Conference
	 */
	function updateConference(&$conference) {
		return $this->update(
			'UPDATE conferences
				SET
					primary_locale = ?,
					path = ?,
					seq = ?,
					enabled = ?
				WHERE conference_id = ?',
			array(
				$conference->getPrimaryLocale(),
				$conference->getPath(),
				$conference->getSequence(),
				$conference->getEnabled() ? 1 : 0,
				$conference->getId()
			)
		);
	}

	/**
	 * Delete a conference, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $conference Conference
	 */
	function deleteConference(&$conference) {
		return $this->deleteConferenceById($conference->getId());
	}

	/**
	 * Delete a conference by ID, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $conferenceId int
	 */
	function deleteConferenceById($conferenceId) {
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$conferenceSettingsDao->deleteSettingsByConference($conferenceId);

		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByConference($conferenceId);

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$rtDao->deleteVersionsByConference($conferenceId);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleDao->deleteRoleByConferenceId($conferenceId);

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$groupDao->deleteGroupsByAssocId(ASSOC_TYPE_CONFERENCE, $conferenceId);

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->deleteSettingsByConferenceId($conferenceId);

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormDao->deleteByAssocId(ASSOC_TYPE_CONFERENCE, $conferenceId);

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteAnnouncementsByAssocId(ASSOC_TYPE_CONFERENCE, $conferenceId);

		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypeDao->deleteAnnouncementTypesByAssocId(ASSOC_TYPE_CONFERENCE, $conferenceId);

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConfDao->deleteSchedConfsByConferenceId($conferenceId);

		return $this->update(
			'DELETE FROM conferences WHERE conference_id = ?', $conferenceId
		);
	}

	/**
	 * Retrieve all conferences.
	 * @return DAOResultFactory containing matching conferences
	 */
	function &getConferences($rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM conferences ORDER BY seq',
			false, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnConferenceFromRow');
		return $returner;
	}

	/**
	 * Retrieve all enabled conferences
	 * @return array Conferences ordered by sequence
	 */
	function &getEnabledConferences() {
		$result =& $this->retrieve(
			'SELECT * FROM conferences WHERE enabled=1 ORDER BY seq'
		);

		$resultFactory = new DAOResultFactory($result, $this, '_returnConferenceFromRow');
		return $resultFactory;
	}

	/**
	 * Retrieve the IDs and titles of all conferences in an associative array.
	 * @return array
	 */
	function &getConferenceTitles() {
		$conferences = array();
		$conferenceIterator =& $this->getConferences();
		while ($conference =& $conferenceIterator->next()) {
			$conferences[$conference->getId()] = $conference->getConferenceTitle();
			unset($conference);
		}
		return $conferences;
	}

	/**
	 * Retrieve enabled conference IDs and titles in an associative array
	 * @return array
	 */
	function &getEnabledConferenceTitles() {
		$conferences = array();
		$conferenceIterator =& $this->getEnabledConferences();
		while ($conference =& $conferenceIterator->next()) {
			$conferences[$conference->getId()] = $conference->getConferenceTitle();
			unset($conference);
		}
		return $conferences;
	}

	/**
	 * Check if a conference exists with a specified path.
	 * @param $path the path of the conference
	 * @return boolean
	 */
	function conferenceExistsByPath($path) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM conferences WHERE path = ?', $path
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber conferences in their sequence order.
	 */
	function resequenceConferences() {
		$result =& $this->retrieve(
			'SELECT conference_id FROM conferences ORDER BY seq'
		);

		for ($i=1; !$result->EOF; $i++) {
			list($conferenceId) = $result->fields;
			$this->update(
				'UPDATE conferences SET seq = ? WHERE conference_id = ?',
				array(
					$i,
					$conferenceId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted conference.
	 * @return int
	 */
	function getInsertConferenceId() {
		return $this->getInsertId('conferences', 'conference_id');
	}
}

?>
