<?php

/**
 * @file RegistrationOptionDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationOptionDAO
 * @ingroup registration
 * @see RegistrationOption
 *
 * @brief Operations for retrieving and modifying RegistrationOption objects.
 */

//$Id$

import('registration.RegistrationOption');

class RegistrationOptionDAO extends DAO {
	/**
	 * Retrieve a registration option by ID.
	 * @param $optionId int
	 * @param $code string Optional registration code "password"
	 * @return RegistrationOption
	 */
	function &getRegistrationOption($optionId, $code = null) {
		$params = array((int) $optionId);
		if ($code !== null) $params[] = $code;
		$result =& $this->retrieve(
			'SELECT * FROM registration_options WHERE option_id = ?' .
			($code !== null ? ' AND code = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnRegistrationOptionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve registration option scheduled conference ID by ID.
	 * @param $optionId int
	 * @return int
	 */
	function getRegistrationOptionSchedConfId($optionId) {
		$result =& $this->retrieve(
			'SELECT sched_conf_id FROM registration_options WHERE option_id = ?', (int) $optionId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve registration option name by ID.
	 * @param $optionId int
	 * @return string
	 */
	function getRegistrationOptionName($optionId) {
		$result =& $this->retrieve(
			'SELECT COALESCE(l.setting_value, p.setting_value) FROM registration_option_settings l LEFT JOIN registration_option_settings p ON (p.option_id = ? AND p.setting_name = ? AND p.locale = ?) WHERE l.option_id = ? AND l.setting_name = ? AND l.locale = ?', 
			array(
				$optionId, 'name', AppLocale::getLocale(),
				$optionId, 'name', AppLocale::getPrimaryLocale()
			)
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a registration option exists with the given option id for a scheduled conference.
	 * @param $optionId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function registrationOptionExistsByOptionId($optionId, $schedConfId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
				FROM registration_options
				WHERE option_id = ?
				AND   sched_conf_id = ?',
			array(
				(int) $optionId,
				(int) $schedConfId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if an open registration option exists with the given option id for a scheduled conference.
	 * @param $optionId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function openRegistrationOptionExistsByOptionId($optionId, $schedConfId) {
		$time = $this->dateToDB(time());

		$result =& $this->retrieve(
			'SELECT COUNT(*)
				FROM registration_options
				WHERE option_id = ?
				AND   sched_conf_id = ?
				AND   opening_date <= ' . $time . '
				AND   closing_date > ' . $time . '
				AND   pub = 1',
			array(
				(int) $optionId,
				(int) $schedConfId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a registration option exists with the given option id and fee code for a scheduled conference.
	 * @param $optionId int
	 * @param $schedConfId int
	 * @param $code string
	 * @return boolean
	 */
	function checkCode($optionId, $schedConfId, $code) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
				FROM registration_options
				WHERE option_id = ?
				AND   sched_conf_id = ?
				AND   code = ?',
			array(
				(int) $optionId,
				(int) $schedConfId,
				$code
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a RegistrationOption object from a row.
	 * @param $row array
	 * @return RegistrationOption
	 */
	function &_returnRegistrationOptionFromRow(&$row) {
		$registrationOption = new RegistrationOption();
		$registrationOption->setOptionId($row['option_id']);
		$registrationOption->setSchedConfId($row['sched_conf_id']);
		$registrationOption->setCode($row['code']);
		$registrationOption->setOpeningDate($this->dateFromDB($row['opening_date']));
		$registrationOption->setClosingDate($this->datetimeFromDB($row['closing_date']));
		$registrationOption->setSequence($row['seq']);
		$registrationOption->setPublic($row['pub']);

		$this->getDataObjectSettings('registration_option_settings', 'option_id', $row['option_id'], $registrationOption);

		HookRegistry::call('RegistrationOptionDAO::_returnRegistrationOptionFromRow', array(&$registrationOption, &$row));

		return $registrationOption;
	}

	/**
	 * Get the list of field names for which localized data is used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name', 'description');
	}

	/**
	 * Update the localized settings for this object
	 * @param $registrationOption object
	 */
	function updateLocaleFields(&$registrationOption) {
		$this->updateDataObjectSettings('registration_option_settings', $registrationOption, array(
			'option_id' => $registrationOption->getOptionId()
		));
	}

	/**
	 * Insert a new RegistrationOption.
	 * @param $registrationOption RegistrationOption
	 * @return boolean 
	 */
	function insertRegistrationOption(&$registrationOption) {
		$this->update(
			sprintf('INSERT INTO registration_options
				(sched_conf_id, opening_date, closing_date, pub, seq, code)
				VALUES
				(?, %s, %s, ?, ?, ?)',
				$this->dateToDB($registrationOption->getOpeningDate()),
				$this->datetimeToDB($registrationOption->getClosingDate())
			), array(
				(int) $registrationOption->getSchedConfId(),
				(int) $registrationOption->getPublic(),
				(float) $registrationOption->getSequence(),
				$registrationOption->getCode()
			)
		);

		$registrationOption->setOptionId($this->getInsertRegistrationOptionId());
		$this->updateLocaleFields($registrationOption);
		return $registrationOption->getOptionId();
	}

	/**
	 * Update an existing registration option.
	 * @param $registrationOption RegistrationOption
	 * @return boolean
	 */
	function updateRegistrationOption(&$registrationOption) {
		$returner = $this->update(
			sprintf('UPDATE	registration_options
				SET
					sched_conf_id = ?,
					opening_date = %s,
					closing_date = %s,
					pub = ?,
					seq = ?,
					code = ?
				WHERE	option_id = ?',
				$this->dateToDB($registrationOption->getOpeningDate()),
				$this->datetimeToDB($registrationOption->getClosingDate())
			), array(
				(int) $registrationOption->getSchedConfId(),
				(int) $registrationOption->getPublic(),
				(float) $registrationOption->getSequence(),
				$registrationOption->getCode(),
				(int) (int) (int) (int) (int) (int) (int) (int) (int) $registrationOption->getOptionId()
			)
		);
		$this->updateLocaleFields($registrationOption);
		return $returner;
	}

	/**
	 * Delete a registration option.
	 * @param $registrationOption RegistrationOption
	 * @return boolean 
	 */
	function deleteRegistrationOption(&$registrationOption) {
		return $this->deleteRegistrationOptionById($registrationOption->getOptionId());
	}

	/**
	 * Delete registration / registration option associations by
	 * registration option ID.
	 * @param $optionId int
	 * @return int
	 */
	function deleteRegistrationOptionAssocByOptionId($optionId) {
		return $this->update('DELETE FROM registration_option_assoc WHERE option_id = ?', (int) $optionId);
	}

	/**
	 * Delete registration / registration option associations by
	 * registration ID.
	 * @param $registrationId int
	 * @return int
	 */
	function deleteRegistrationOptionAssocByRegistrationId($registrationId) {
		return $this->update('DELETE FROM registration_option_assoc WHERE registration_id = ?', (int) $registrationId);
	}

	/**
	 * Add a registration option association for a registration.
	 * @param $registrationId int
	 * @param $optionId int
	 * @return boolean 
	 */
	function insertRegistrationOptionAssoc($registrationId, $optionId) {
		return $this->update('INSERT INTO registration_option_assoc
			(registration_id, option_id)
			VALUES
			(?, ?)',
			array(
				(int) $registrationId,
				(int) $optionId
			)
		);
	}

	/**
	 * Get registration option associations for a registration.
	 * @param $registrationId int
	 * @return array $optionIds
	 */
	function &getRegistrationOptions($registrationId) {
		$result =& $this->retrieve(
			'SELECT option_id FROM registration_option_assoc WHERE registration_id = ?',
			array((int) $registrationId)
		);
		
		$returner = array();
		for ($i=1; !$result->EOF; $i++) {
			list($optionId) = $result->fields;
			$returner[] = $optionId;
			$result->moveNext();
		}

		$result->close();
		unset($result);
		return $returner;
	}

	/**
	 * Delete a registration option by ID. Note that all registrations with this
	 * option ID are also deleted.
	 * @param $optionId int
	 * @return boolean
	 */
	function deleteRegistrationOptionById($optionId) {
		// Delete registration option
		$returner = $this->update('DELETE FROM registration_options WHERE option_id = ?', (int) $optionId);

		// Delete all localization settings and registrations associated with this registration option
		if ($returner) {
			$this->update('DELETE FROM registration_option_settings WHERE option_id = ?', $optionId);
			return $this->deleteRegistrationOptionAssocByOptionId($optionId);
		} else {
			return $returner;
		}
	}

	function deleteRegistrationOptionsBySchedConf($schedConfId) {
		$registrationOptions =& $this->getRegistrationOptionsBySchedConfId($schedConfId);
		while ($registrationOption =& $registrationOptions->next()) {
			$this->deleteRegistrationOption($registrationOption);
			unset($registrationOption);
		}
	}

	/**
	 * Retrieve an array of registration options matching a particular scheduled conference ID.
	 * @param $schedConfId int
	 * @return object DAOResultFactory containing matching RegistrationOptions
	 */
	function &getRegistrationOptionsBySchedConfId($schedConfId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM registration_options WHERE sched_conf_id = ? ORDER BY seq',
			(int) $schedConfId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnRegistrationOptionFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted registration option.
	 * @return int
	 */
	function getInsertRegistrationOptionId() {
		return $this->getInsertId('registration_options', 'option_id');
	}

	/**
	 * Sequentially renumber registration options in their sequence order.
	 */
	function resequenceRegistrationOptions($schedConfId) {
		$result =& $this->retrieve(
			'SELECT option_id FROM registration_options WHERE sched_conf_id = ? ORDER BY seq',
			(int) $schedConfId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($registrationOptionId) = $result->fields;
			$this->update(
				'UPDATE registration_options SET seq = ? WHERE option_id = ?',
				array(
					(int) $i,
					(int) $registrationOptionId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}
}

?>
