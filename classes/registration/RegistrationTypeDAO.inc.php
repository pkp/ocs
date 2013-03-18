<?php

/**
 * @file RegistrationTypeDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationTypeDAO
 * @ingroup registration
 * @see RegistrationType
 *
 * @brief Operations for retrieving and modifying RegistrationType objects.
 */


import('classes.registration.RegistrationType');

class RegistrationTypeDAO extends DAO {
	/**
	 * Retrieve a registration type by ID.
	 * @param $typeId int
	 * @param $code string Optional registration code "password"
	 * @return RegistrationType
	 */
	function &getRegistrationType($typeId, $code = null) {
		$params = array($typeId);
		if ($code !== null) $params[] = $code;
		$result =& $this->retrieve(
			'SELECT * FROM registration_types WHERE type_id = ?' .
			($code !== null ? ' AND code = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnRegistrationTypeFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve registration type scheduled conference ID by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getRegistrationTypeSchedConfId($typeId) {
		$result =& $this->retrieve(
			'SELECT sched_conf_id FROM registration_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve registration type name by ID.
	 * @param $typeId int
	 * @return string
	 */
	function getRegistrationTypeName($typeId) {
		$result =& $this->retrieve(
			'SELECT COALESCE(l.setting_value, p.setting_value) FROM registration_type_settings l LEFT JOIN registration_type_settings p ON (p.type_id = ? AND p.setting_name = ? AND p.locale = ?) WHERE l.type_id = ? AND l.setting_name = ? AND l.locale = ?', 
			array(
				$typeId, 'name', AppLocale::getLocale(),
				$typeId, 'name', AppLocale::getPrimaryLocale()
			)
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve institutional flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getRegistrationTypeInstitutional($typeId) {
		$result =& $this->retrieve(
			'SELECT institutional FROM registration_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve membership flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getRegistrationTypeMembership($typeId) {
		$result =& $this->retrieve(
			'SELECT membership FROM registration_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve public flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getRegistrationTypePublic($typeId) {
		$result =& $this->retrieve(
			'SELECT pub FROM registration_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a registration type exists with the given type id for a scheduled conference.
	 * @param $typeId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function registrationTypeExistsByTypeId($typeId, $schedConfId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
				FROM registration_types
				WHERE type_id = ?
				AND   sched_conf_id = ?',
			array(
				$typeId,
				$schedConfId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if an open registration type exists with the given type id for a scheduled conference.
	 * @param $typeId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function openRegistrationTypeExistsByTypeId($typeId, $schedConfId) {
		$time = $this->dateToDB(time());

		$result =& $this->retrieve(
			'SELECT COUNT(*)
				FROM registration_types
				WHERE type_id = ?
				AND   sched_conf_id = ?
				AND   opening_date <= ' . $time . '
				AND   closing_date > ' . $time . '
				AND   pub = 1',
			array(
				$typeId,
				$schedConfId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a registration type exists with the given type id and fee code for a scheduled conference.
	 * @param $typeId int
	 * @param $schedConfId int
	 * @param $code string
	 * @return boolean
	 */
	function checkCode($typeId, $schedConfId, $code) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
				FROM registration_types
				WHERE type_id = ?
				AND   sched_conf_id = ?
				AND   code = ?',
			array(
				$typeId,
				$schedConfId,
				$code
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a RegistrationType object from a row.
	 * @param $row array
	 * @return RegistrationType
	 */
	function &_returnRegistrationTypeFromRow(&$row) {
		$registrationType = new RegistrationType();
		$registrationType->setTypeId($row['type_id']);
		$registrationType->setSchedConfId($row['sched_conf_id']);
		$registrationType->setCode($row['code']);
		$registrationType->setCost($row['cost']);
		$registrationType->setCurrencyCodeAlpha($row['currency_code_alpha']);
		$registrationType->setOpeningDate($this->dateFromDB($row['opening_date']));
		$registrationType->setClosingDate($this->datetimeFromDB($row['closing_date']));
		$registrationType->setExpiryDate($this->datetimeFromDB($row['expiry_date']));
		$registrationType->setAccess($row['access']);
		$registrationType->setInstitutional($row['institutional']);
		$registrationType->setMembership($row['membership']);
		$registrationType->setPublic($row['pub']);
		$registrationType->setSequence($row['seq']);

		$this->getDataObjectSettings('registration_type_settings', 'type_id', $row['type_id'], $registrationType);

		HookRegistry::call('RegistrationTypeDAO::_returnRegistrationTypeFromRow', array(&$registrationType, &$row));

		return $registrationType;
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
	 * @param $registrationType object
	 */
	function updateLocaleFields(&$registrationType) {
		$this->updateDataObjectSettings('registration_type_settings', $registrationType, array(
			'type_id' => $registrationType->getTypeId()
		));
	}

	/**
	 * Insert a new RegistrationType.
	 * @param $registrationType RegistrationType
	 * @return boolean 
	 */
	function insertRegistrationType(&$registrationType) {
		$expiryDate = $registrationType->getExpiryDate();
		$this->update(
			sprintf('INSERT INTO registration_types
				(sched_conf_id, cost, currency_code_alpha, opening_date, closing_date, expiry_date, access, institutional, membership, pub, seq, code)
				VALUES
				(?, ?, ?, %s, %s, %s, ?, ?, ?, ?, ?, ?)',
				$this->dateToDB($registrationType->getOpeningDate()),
				$this->datetimeToDB($registrationType->getClosingDate()),
				$expiryDate === null?'null':$this->datetimeToDB($expiryDate)
			), array(
				(int) $registrationType->getSchedConfId(),
				(float) $registrationType->getCost(),
				$registrationType->getCurrencyCodeAlpha(),
				(int) $registrationType->getAccess(),
				(int) $registrationType->getInstitutional(),
				(int) $registrationType->getMembership(),
				(int) $registrationType->getPublic(),
				(float) $registrationType->getSequence(),
				$registrationType->getCode()
			)
		);

		$registrationType->setTypeId($this->getInsertId());
		$this->updateLocaleFields($registrationType);
		return $registrationType->getTypeId();
	}

	/**
	 * Update an existing registration type.
	 * @param $registrationType RegistrationType
	 * @return boolean
	 */
	function updateRegistrationType(&$registrationType) {
		$expiryDate = $registrationType->getExpiryDate();
		$returner = $this->update(
			sprintf('UPDATE registration_types
				SET
					sched_conf_id = ?,
					cost = ?,
					currency_code_alpha = ?,
					opening_date = %s,
					closing_date = %s,
					expiry_date = %s,
					access = ?,
					institutional = ?,
					membership = ?,
					pub = ?,
					seq = ?,
					code = ?
				WHERE type_id = ?',
				$this->dateToDB($registrationType->getOpeningDate()),
				$this->datetimeToDB($registrationType->getClosingDate()),
				$expiryDate === null?'null':$this->datetimeToDB($expiryDate)
			), array(
				(int) $registrationType->getSchedConfId(),
				(float) $registrationType->getCost(),
				$registrationType->getCurrencyCodeAlpha(),
				(int) $registrationType->getAccess(),
				(int) $registrationType->getInstitutional(),
				(int) $registrationType->getMembership(),
				(int) $registrationType->getPublic(),
				(float) $registrationType->getSequence(),
				$registrationType->getCode(),
				(int) $registrationType->getTypeId()
			)
		);
		$this->updateLocaleFields($registrationType);
		return $returner;
	}

	/**
	 * Delete a registration type.
	 * @param $registrationType RegistrationType
	 * @return boolean 
	 */
	function deleteRegistrationType(&$registrationType) {
		return $this->deleteRegistrationTypeById($registrationType->getTypeId());
	}

	/**
	 * Delete a registration type by ID. Note that all registrations with this
	 * type ID are also deleted.
	 * @param $typeId int
	 * @return boolean
	 */
	function deleteRegistrationTypeById($typeId) {
		// Delete registration type
		$this->update(
			'DELETE FROM registration_types WHERE type_id = ?',
			array((int) $typeId)
		);

		// Delete all localization settings and registrations associated with this registration type
		$this->deleteRegistrationOptionCosts($typeId);
		$this->update('DELETE FROM registration_type_settings WHERE type_id = ?', $typeId);

		$registrationDao = DAORegistry::getDAO('RegistrationDAO');
		return $registrationDao->deleteRegistrationByTypeId($typeId);
	}

	function deleteRegistrationTypesBySchedConf($schedConfId) {
		$registrationTypes =& $this->getRegistrationTypesBySchedConfId($schedConfId);
		while ($registrationType =& $registrationTypes->next()) {
			$this->deleteRegistrationType($registrationType);
			unset($registrationType);
		}
	}

	/**
	 * Retrieve an array of registration types matching a particular scheduled conference ID.
	 * @param $schedConfId int
	 * @return object DAOResultFactory containing matching RegistrationTypes
	 */
	function &getRegistrationTypesBySchedConfId($schedConfId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM registration_types WHERE sched_conf_id = ? ORDER BY seq',
			$schedConfId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnRegistrationTypeFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted registration type.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('registration_types', 'type_id');
	}

	/**
	 * Sequentially renumber registration types in their sequence order.
	 */
	function resequenceRegistrationTypes($schedConfId) {
		$result =& $this->retrieve(
			'SELECT type_id FROM registration_types WHERE sched_conf_id = ? ORDER BY seq',
			$schedConfId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($registrationTypeId) = $result->fields;
			$this->update(
				'UPDATE registration_types SET seq = ? WHERE type_id = ?',
				array(
					$i,
					$registrationTypeId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Insert pricing for a registration type for a registration option.
	 * @param $typeId int
	 * @param $optionId int
	 * @param $cost number
	 * @return boolean 
	 */
	function insertRegistrationOptionCost($typeId, $optionId, $cost) {
		return $this->update(
			'INSERT INTO registration_option_costs
				(type_id, option_id, cost)
				VALUES
				(?, ?, ?)',
			array(
				(int) $typeId,
				(int) $optionId,
				$cost
			)
		);
	}

	/**
	 * Get cost information for a registration type.
	 * @param $typeId int
	 * @return array $optionId => $cost
	 */
	function getRegistrationOptionCosts($typeId) {
		$result =& $this->retrieve(
			'SELECT option_id, cost FROM registration_option_costs WHERE type_id = ?',
			array((int) $typeId)
		);
		
		$returner = array();
		for ($i=1; !$result->EOF; $i++) {
			list($optionId, $cost) = $result->fields;
			$returner[$optionId] = $cost;
			$result->moveNext();
		}

		$result->close();
		unset($result);
		return $returner;
	}

	/**
	 * Delete registration options for a type.
	 * @param $typeId int
	 */
	function deleteRegistrationOptionCosts($typeId) {
		return $this->update(
			'DELETE FROM registration_option_costs WHERE type_id = ?',
			array((int) $typeId)
		);
		
	}
}

?>
