<?php

/**
 * @file SchedConfDAO.inc.php
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

//$Id$

import ('schedConf.SchedConf');

class SchedConfDAO extends DAO {
	/**
	 * Retrieve a scheduled conference by ID.
	 * @param $schedConfId int
	 * @param $conferenceId int optional
	 * @return SchedConf
	 */
	function &getSchedConf($schedConfId, $conferenceId = null) {
		$params = array($schedConfId);
		if ($conferenceId !== null) $params[] = $conferenceId;
		$result =& $this->retrieve(
			'SELECT * FROM sched_confs WHERE sched_conf_id = ?' . ($conferenceId !== null?' AND conference_id = ?':''), $params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSchedConfFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Retrieve a scheduled conference by path.
	 * @param $path string
	 * @return SchedConf
	 */
	function &getSchedConfByPath($path, $conferenceId = null) {
		$params = array($path);
		if ($conferenceId) $params[] = (int) $conferenceId;

		$result =& $this->retrieve(
			'SELECT * FROM sched_confs WHERE path = ?' . ($conferenceId?' AND conference_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSchedConfFromRow($result->GetRowAssoc(false));
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
	function &_returnSchedConfFromRow(&$row) {
		$schedConf = new SchedConf();
		$schedConf->setSchedConfId($row['sched_conf_id']);
		$schedConf->setPath($row['path']);
		$schedConf->setSequence($row['seq']);
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
	function insertSchedConf(&$schedConf) {
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

		$schedConf->setSchedConfId($this->getInsertSchedConfId());
		return $schedConf->getId();
	}

	/**
	 * Update an existing scheduled conference.
	 * @param $schedConf SchedConf
	 */
	function updateSchedConf(&$schedConf) {
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
	 * Delete a scheduled conference, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $schedConf SchedConf
	 */
	function deleteSchedConf(&$schedConf) {
		return $this->deleteSchedConfById($schedConf->getId());
	}

	/**
	 * Retrieve the IDs and titles of all scheduled conferences for a conference in an associative array.
	 * @return array
	 */
	function &getSchedConfTitles($conferenceId) {
		$schedConfs = array();
		$schedConfIterator =& $this->getSchedConfsByConferenceId($conferenceId);
		while ($schedConf =& $schedConfIterator->next()) {
			$schedConfs[$schedConf->getId()] = $schedConf->getSchedConfTitle();
			unset($schedConf);
		}
		return $schedConfs;
	}

	/**
	 * Retrieves all scheduled conferences for a conference
	 * @param $conferenceId
	 * @param $rangeInfo object
	 */
	function &getSchedConfsByConferenceId($conferenceId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT i.*
			FROM sched_confs i
				WHERE i.conference_id = ?
				ORDER BY seq',
			array($conferenceId),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSchedConfFromRow');
		return $returner;
	}

	/**
	 * Delete all scheduled conferences by conference ID.
	 * @param $schedConfId int
	 */
	function deleteSchedConfsByConferenceId($conferenceId) {
		$schedConfs = $this->getSchedConfsByConferenceId($conferenceId);

		while (!$schedConfs->eof()) {
			$schedConf =& $schedConfs->next();
			$this->deleteSchedConfById($schedConf->getId());
		}
	}

	/**
	 * Delete a scheduled conference by ID, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $schedConfId int
	 */
	function deleteSchedConfById($schedConfId) {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfSettingsDao->deleteSettingsBySchedConf($schedConfId);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$trackDao->deleteTracksBySchedConf($schedConfId);

		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$registrationDao->deleteRegistrationsBySchedConf($schedConfId);

		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationTypeDao->deleteRegistrationTypesBySchedConf($schedConfId);

		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationOptionDao->deleteRegistrationOptionsBySchedConf($schedConfId);

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteAnnouncementsByAssocId(ASSOC_TYPE_SCHED_CONF, $schedConfId);

		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$buildingDao->deleteBuildingsBySchedConfId($schedConfId);

		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		$specialEventDao->deleteSpecialEventsBySchedConfId($schedConfId);

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$paperDao->deletePapersBySchedConfId($schedConfId);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleDao->deleteRoleBySchedConfId($schedConfId);

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$groupDao->deleteGroupsByAssocId(ASSOC_TYPE_SCHED_CONF, $schedConfId);

		return $this->update(
			'DELETE FROM sched_confs WHERE sched_conf_id = ?', $schedConfId
		);
	}

	/**
	 * Retrieve all scheduled conferences.
	 * @return DAOResultFactory containing matching scheduled conferences
	 */
	function &getSchedConfs($rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM sched_confs ORDER BY seq',
			false, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSchedConfFromRow');
		return $returner;
	}

	/**
	 * Retrieve all scheduled conferences
	 * @param conferenceId optional conference ID
	 * @return array SchedConfs ordered by sequence
	 */
	function &getEnabledSchedConfs($conferenceId = null) {
		$result =& $this->retrieve('
			SELECT i.* FROM sched_confs i
				LEFT JOIN conferences c ON (i.conference_id = c.conference_id)
			WHERE c.enabled = 1'
				. ($conferenceId?' AND i.conference_id = ?':'')
			. ' ORDER BY c.seq, i.seq',
			$conferenceId===null?false:$conferenceId);

		$resultFactory = new DAOResultFactory($result, $this, '_returnSchedConfFromRow');
		return $resultFactory;
	}

	/**
	 * Check if a scheduled conference exists with a specified path.
	 * @param $path the path of the scheduled conference
	 * @return boolean
	 */
	function schedConfExistsByPath($path) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM sched_confs WHERE path = ?', $path
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber scheduled conferences in their sequence order.
	 */
	function resequenceSchedConfs($conferenceId) {
		$result =& $this->retrieve(
			'SELECT sched_conf_id FROM sched_confs WHERE conference_id = ? ORDER BY seq',
			$conferenceId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($schedConfId) = $result->fields;
			$this->update(
				'UPDATE sched_confs SET seq = ? WHERE sched_conf_id = ?',
				array(
					$i,
					$schedConfId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted scheduled conference.
	 * @return int
	 */
	function getInsertSchedConfId() {
		return $this->getInsertId('sched_confs', 'sched_conf_id');
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
			$conferenceId);

		$resultFactory = new DAOResultFactory($result, $this, '_returnSchedConfFromRow');
		return $resultFactory;
	}

	/**
	 * Check if one or more archived scheduled conferences exist for a conference.
	 * @param $conferenceId the conference owning the scheduled conference
	 * @return boolean
	 */
	function archivedSchedConfsExist($conferenceId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM sched_confs WHERE conference_id = ? AND end_date < now()', $conferenceId
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
			'SELECT COUNT(*) FROM sched_confs WHERE conference_id = ? AND start_date < now() AND end_date > now()', $conferenceId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] >= 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
