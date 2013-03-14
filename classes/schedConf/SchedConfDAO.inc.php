<?php

/**
 * @file classes/schedConf/SchedConfDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfDAO
 * @ingroup schedConf
 * @see SchedConf
 *
 * @brief Operations for retrieving and modifying SchedConf objects.
 */

import('lib.pkp.classes.context.ContextDAO');
import('classes.schedConf.SchedConf');

class SchedConfDAO extends ContextDAO {
	/**
	 * Constructor
	 */
	function SchedConfDAO() {
		parent::ContextDAO();
	}

	/**
	 * Generate a new data object.
	 * @return SchedConf
	 */
	function newDataObject() {
		return new SchedConf();
	}

	/**
	 * Retrieve a scheduled conference by ID.
	 * @param $schedConfId int
	 * @param $conferenceId int optional
	 * @return SchedConf
	 */
	function getById($schedConfId, $conferenceId = null) {
		// If only $schedConfId specified, fall back on parent impl
		if ($conferenceId === null) return parent::getById($schedConfId);

		$result =& $this->retrieve(
			'SELECT * FROM sched_confs WHERE sched_conf_id = ? AND conference_id = ?',
			array(
				(int) $schedConfId,
				(int) $conferenceId
			)
		);

		if ($result->RecordCount() == 0) return null;
		$returner = $this->_fromRow($result->GetRowAssoc(false));
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a scheduled conference by path.
	 * @param $path string
	 * @return SchedConf
	 */
	function getByPath($path, $conferenceId = null) {
		$params = array($path);
		if ($conferenceId) $params[] = (int) $conferenceId;

		$result = $this->retrieve(
			'SELECT * FROM sched_confs WHERE path = ?' . ($conferenceId?' AND conference_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Internal function to return a scheduled conference object from a row.
	 * @param $row array
	 * @return SchedConf
	 */
	function _fromRow($row) {
		$schedConf = parent::_fromRow($row);
		$schedConf->setConferenceId($row['conference_id']);
		$schedConf->setStartDate($this->datetimeFromDB($row['start_date']));
		$schedConf->setEndDate($this->datetimeFromDB($row['end_date']));

		HookRegistry::call('SchedConfDAO::_returnSchedConfFromRow', array(&$schedConf, &$row));

		return $schedConf;
	}

	/**
	 * Insert a new scheduled conference.
	 * @param $schedConf SchedConf
	 */	
	function insertObject(&$schedConf) {
		$this->update(
			sprintf('INSERT INTO sched_confs
				(conference_id, path, seq, start_date, end_date)
				VALUES
				(?, ?, ?, %s, %s)',
				$this->datetimeToDB($schedConf->getStartDate()),
				$this->datetimeToDB($schedConf->getEndDate())),
			array(
				$schedConf->getConferenceId(),
				$schedConf->getPath(),
				$schedConf->getSequence() == null ? 0 : $schedConf->getSequence()
			)
		);

		$schedConf->setId($this->getInsertId());
		return $schedConf->getId();
	}

	/**
	 * Update an existing scheduled conference.
	 * @param $schedConf SchedConf
	 */
	function updateObject(&$schedConf) {
		return $this->update(
			sprintf('UPDATE sched_confs
				SET
					conference_id = ?,
					path = ?,
					seq = ?,
					start_date = %s,
					end_date = %s
				WHERE sched_conf_id = ?',
				$this->datetimeToDB($schedConf->getStartDate()),
				$this->datetimeToDB($schedConf->getEndDate())),
			array(
				$schedConf->getConferenceId(),
				$schedConf->getPath(),
				$schedConf->getSequence(),
				$schedConf->getId()
			)
		);
	}

	/**
	 * Retrieve the IDs and titles of all scheduled conferences for a conference in an associative array.
	 * @return array
	 */
	function getNames($conferenceId) {
		$schedConfs = array();
		$schedConfIterator = $this->getAll(false, $conferenceId);
		while ($schedConf = $schedConfIterator->next()) {
			$schedConfs[$schedConf->getId()] = $schedConf->getLocalizedName();
		}
		return $schedConfs;
	}

	/**
	 * Delete all scheduled conferences by conference ID.
	 * @param $schedConfId int
	 */
	function deleteByConferenceId($conferenceId) {
		$schedConfs = $this->getAll(false, $conferenceId);

		while (!$schedConfs->eof()) {
			$schedConf = $schedConfs->next();
			$this->deleteById($schedConf->getId());
		}
	}

	/**
	 * Delete a scheduled conference by ID, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $schedConfId int
	 */
	function deleteById($schedConfId) {
		$schedConfSettingsDao = DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfSettingsDao->deleteById($schedConfId);

		$trackDao = DAORegistry::getDAO('TrackDAO');
		$trackDao->deleteTracksBySchedConf($schedConfId);

		$registrationDao = DAORegistry::getDAO('RegistrationDAO');
		$registrationDao->deleteRegistrationsBySchedConf($schedConfId);

		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationTypeDao->deleteRegistrationTypesBySchedConf($schedConfId);

		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationOptionDao->deleteRegistrationOptionsBySchedConf($schedConfId);

		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteByAssoc(ASSOC_TYPE_SCHED_CONF, $schedConfId);

		$buildingDao = DAORegistry::getDAO('BuildingDAO');
		$buildingDao->deleteBuildingsBySchedConfId($schedConfId);

		$specialEventDao = DAORegistry::getDAO('SpecialEventDAO');
		$specialEventDao->deleteSpecialEventsBySchedConfId($schedConfId);

		$paperDao = DAORegistry::getDAO('PaperDAO');
		$paperDao->deletePapersBySchedConfId($schedConfId);

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleDao->deleteRoleBySchedConfId($schedConfId);

		$groupDao = DAORegistry::getDAO('GroupDAO');
		$groupDao->deleteGroupsByAssocId(ASSOC_TYPE_SCHED_CONF, $schedConfId);

		parent::deleteById($schedConfId);
	}

	/**
	 * Retrieve all scheduled conferences.
	 * @param $enabledOnly boolean True iff only enabled sched confs wanted
	 * @param $rangeInfo object optional
	 * @return DAOResultFactory containing matching scheduled conferences
	 */
	function getAll($enabledOnly = false, $conferenceId = null, $rangeInfo = null) {
		$params = array();
		if ($conferenceId) $params[] = (int) $conferenceId;

		$result =& $this->retrieveRange(
			'SELECT	sc.*
			FROM	sched_confs sc,
				conferences c
			WHERE	c.conference_id = sc.conference_id ' .
			($enabledOnly?'AND c.enabled=1 ':'') .
			($conferenceId?'AND c.conference_id=? ':'') .
			'ORDER BY c.seq, sc.seq',
			$params, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_fromRow');
		return $returner;
	}

	/**
	 * Sequentially renumber scheduled conferences in their sequence order.
	 */
	function resequence($conferenceId) {
		$result =& $this->retrieve(
			'SELECT sched_conf_id FROM sched_confs WHERE conference_id = ? ORDER BY seq',
			(int) $conferenceId
		);

		for ($i=1; !$result->EOF; $i+=2) {
			list($schedConfId) = $result->fields;
			$this->update(
				'UPDATE sched_confs SET seq = ? WHERE sched_conf_id = ?',
				array(
					(int) $i,
					(int) $schedConfId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Retrieve most recent enabled scheduled conference of a given conference
	 * @return array SchedConfs ordered by sequence
	 */
	function &getCurrentSchedConfs($conferenceId) {
		$result =& $this->retrieve('
			SELECT i.* FROM sched_confs i
				LEFT JOIN conferences c ON (i.conference_id = c.conference_id)
			WHERE c.enabled = 1
				AND i.conference_id = ?
				AND i.start_date < NOW()
				AND i.end_date > NOW()
			ORDER BY c.seq, i.seq',
			(int) $conferenceId
		);

		$resultFactory = new DAOResultFactory($result, $this, '_fromRow');
		return $resultFactory;
	}

	/**
	 * Check if one or more archived scheduled conferences exist for a conference.
	 * @param $conferenceId the conference owning the scheduled conference
	 * @return boolean
	 */
	function archivedSchedConfsExist($conferenceId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM sched_confs WHERE conference_id = ? AND end_date < now()',
			(int) $conferenceId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] >= 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if one or more archived scheduled conferences exist for a conference.
	 * @param $conferenceId the conference owning the scheduled conference
	 * @return boolean
	 */
	function currentSchedConfsExist($conferenceId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM sched_confs WHERE conference_id = ? AND start_date < now() AND end_date > now()',
			(int) $conferenceId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] >= 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get a list of localized settings.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return parent::getLocaleFieldNames() + array('acronym');
	}

	//
	// Protected methods
	//
	/**
	 * Get the table name for this context.
	 * @return string
	 */
	protected function _getTableName() {
		return 'sched_confs';
	}

	/**
	 * Get the table name for this context's settings table.
	 * @return string
	 */
	protected function _getSettingsTableName() {
		return 'sched_conf_settings';
	}

	/**
	 * Get the name of the primary key column for this context.
	 * @return string
	 */
	protected function _getPrimaryKeyColumn() {
		return 'sched_conf_id';
	}
}

?>
