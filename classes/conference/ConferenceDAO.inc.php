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

import('lib.pkp.classes.context.ContextDAO');
import('classes.conference.Conference');

class ConferenceDAO extends ContextDAO {
	/**
	 * Constructor
	 */
	function ConferenceDAO() {
		parent::ContextDAO();
	}

	/**
	 * Create a new data object.
	 * @return Conference
	 */
	function newDataObject() {
		return new Conference();
	}

	/**
	 * Internal function to return a Conference object from a row.
	 * @param $row array
	 * @return Conference
	 */
	function _fromRow($row) {
		$conference = parent::_fromRow($row);
		$conference->setPrimaryLocale($row['primary_locale']);
		$conference->setEnabled($row['enabled']);
		HookRegistry::call('ConferenceDAO::_returnConferenceFromRow', array(&$conference, &$row));
		return $conference;
	}

	/**
	 * Insert a new conference.
	 * @param $conference Conference
	 */	
	function insertObject(&$conference) {
		$this->update(
			'INSERT INTO conferences
				(primary_locale, path, seq, enabled)
				VALUES
				(?, ?, ?, ?)',
			array(
				$conference->getPrimaryLocale(),
				$conference->getPath(),
				(int) $conference->getSequence(),
				(int) $conference->getEnabled()
			)
		);

		$conference->setId($this->getInsertId());
		return $conference->getId();
	}

	/**
	 * Update an existing conference.
	 * @param $conference Conference
	 */
	function updateObject(&$conference) {
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
				(int) $conference->getSequence(),
				(int) $conference->getEnabled(),
				(int) $conference->getId()
			)
		);
	}

	/**
	 * Delete a conference by ID, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $conferenceId int
	 */
	function deleteById($conferenceId) {
		$conferenceSettingsDao = DAORegistry::getDAO('ConferenceSettingsDAO');
		$conferenceSettingsDao->deleteById($conferenceId);

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByConference($conferenceId);

		$rtDao = DAORegistry::getDAO('RTDAO');
		$rtDao->deleteVersionsByConference($conferenceId);

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleDao->deleteRoleByConferenceId($conferenceId);

		$groupDao = DAORegistry::getDAO('GroupDAO');
		$groupDao->deleteGroupsByAssocId(ASSOC_TYPE_CONFERENCE, $conferenceId);

		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		$pluginSettingsDao->deleteSettingsByConferenceId($conferenceId);
		
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormDao->deleteByAssoc(ASSOC_TYPE_CONFERENCE, $conferenceId);

		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteByAssoc(ASSOC_TYPE_CONFERENCE, $conferenceId);

		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypeDao->deleteByAssoc(ASSOC_TYPE_CONFERENCE, $conferenceId);

		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$schedConfDao->deleteByConferenceId($conferenceId);

		parent::deleteById($conferenceId);
	}

	/**
	 * Retrieve all conferences.
	 * @return DAOResultFactory containing matching conferences
	 * @param $enabledOnly boolean True if only enabled conferences wanted
	 * @param $rangeInfo object optional
	 */
	function &getConferences($enabledOnly = false, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT c.* FROM conferences c ' .
			($enabledOnly ? 'WHERE c.enabled = 1 ':'') .
			'ORDER BY c.seq',
			false, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_fromRow');
		return $returner;
	}

	//
	// Protected methods
	//
	/**
	 * Get the table name for this context.
	 * @return string
	 */
	protected function _getTableName() {
		return 'conferences';
	}

	/**
	 * Get the table name for this context's settings table.
	 * @return string
	 */
	protected function _getSettingsTableName() {
		return 'conference_settings';
	}

	/**
	 * Get the name of the primary key column for this context.
	 * @return string
	 */
	protected function _getPrimaryKeyColumn() {
		return 'conference_id';
	}
}

?>
