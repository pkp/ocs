<?php

/**
 * RegistrationTypeDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package registration
 *
 * Class for RegistrationType DAO.
 * Operations for retrieving and modifying RegistrationType objects.
 *
 * $Id$
 */

import('registration.RegistrationType');

class RegistrationTypeDAO extends DAO {

	/**
	 * Constructor.
	 */
	function RegistrationTypeDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a registration type by ID.
	 * @param $typeId int
	 * @return RegistrationType
	 */
	function &getRegistrationType($typeId) {
		$result = &$this->retrieve(
			'SELECT * FROM registration_types WHERE type_id = ?', $typeId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnRegistrationTypeFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve registration type event ID by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getRegistrationTypeEventId($typeId) {
		$result = &$this->retrieve(
			'SELECT event_id FROM registration_types WHERE type_id = ?', $typeId
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
		$result = &$this->retrieve(
			'SELECT type_name FROM registration_types WHERE type_id = ?', $typeId
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
		$result = &$this->retrieve(
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
		$result = &$this->retrieve(
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
		$result = &$this->retrieve(
			'SELECT pub FROM registration_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a registration type exists with the given type id for a event.
	 * @param $typeId int
	 * @param $eventId int
	 * @return boolean
	 */
	function registrationTypeExistsByTypeId($typeId, $eventId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM registration_types
				WHERE type_id = ?
				AND   event_id = ?',
			array(
				$typeId,
				$eventId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a registration type exists with the given type name for a event.
	 * @param $typeName string
	 * @param $eventId int
	 * @return boolean
	 */
	function registrationTypeExistsByTypeName($typeName, $eventId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM registration_types
				WHERE type_name = ?
				AND   event_id = ?',
			array(
				$typeName,
				$eventId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Return registration type ID based on a type name for a event.
	 * @param $typeName string
	 * @param $eventId int
	 * @return int
	 */
	function getRegistrationTypeByTypeName($typeName, $eventId) {
		$result = &$this->retrieve(
			'SELECT type_id
				FROM registration_types
				WHERE type_name = ?
				AND   event_id = ?',
			array(
				$typeName,
				$eventId
			)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

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
		$registrationType = &new RegistrationType();
		$registrationType->setTypeId($row['type_id']);
		$registrationType->setEventId($row['event_id']);
		$registrationType->setTypeName($row['type_name']);
		$registrationType->setDescription($row['description']);
		$registrationType->setCost($row['cost']);
		$registrationType->setCurrencyCodeAlpha($row['currency_code_alpha']);
		$registrationType->setOpeningDate($this->dateFromDB($row['opening_date']));
		$registrationType->setClosingDate($this->dateFromDB($row['closing_date']));
		$registrationType->setExpiryDate($this->dateFromDB($row['expiry_date']));
		$registrationType->setAccess($row['access']);
		$registrationType->setInstitutional($row['institutional']);
		$registrationType->setMembership($row['membership']);
		$registrationType->setPublic($row['pub']);
		$registrationType->setSequence($row['seq']);

		HookRegistry::call('RegistrationTypeDAO::_returnRegistrationTypeFromRow', array(&$registrationType, &$row));

		return $registrationType;
	}

	/**
	 * Insert a new RegistrationType.
	 * @param $registrationType RegistrationType
	 * @return boolean 
	 */
	function insertRegistrationType(&$registrationType) {
		$ret = $this->update(
			sprintf('INSERT INTO registration_types
				(event_id, type_name, description, cost, currency_code_alpha, opening_date, closing_date, expiry_date, access, institutional, membership, pub, seq)
				VALUES
				(?, ?, ?, ?, ?, %s, %s, %s, ?, ?, ?, ?, ?)',
				$this->dateToDB($registrationType->getOpeningDate()),
				$this->dateToDB($registrationType->getClosingDate()),
				$this->dateToDB($registrationType->getExpiryDate())),
			array(
				$registrationType->getEventId(),
				$registrationType->getTypeName(),
				$registrationType->getDescription(),
				$registrationType->getCost(),
				$registrationType->getCurrencyCodeAlpha(),
				$registrationType->getAccess(),
				$registrationType->getInstitutional(),
				$registrationType->getMembership(),
				$registrationType->getPublic(),
				$registrationType->getSequence()
			)
		);
		
		$registrationType->setTypeId($this->getInsertRegistrationTypeId());
		return $registrationType->getTypeId();
	}

	/**
	 * Update an existing registration type.
	 * @param $registrationType RegistrationType
	 * @return boolean
	 */
	function updateRegistrationType(&$registrationType) {
		return $this->update(
			sprintf('UPDATE registration_types
				SET
					event_id = ?,
					type_name = ?,
					description = ?,
					cost = ?,
					currency_code_alpha = ?,
					opening_date = %s,
					closing_date = %s,
					expiry_date = %s,
					access = ?,
					institutional = ?,
					membership = ?,
					pub = ?,
					seq = ?
				WHERE type_id = ?',
				$this->dateToDB($registrationType->getOpeningDate()),
				$this->dateToDB($registrationType->getClosingDate()),
				$this->dateToDB($registrationType->getExpiryDate())),
			array(
				$registrationType->getEventId(),
				$registrationType->getTypeName(),
				$registrationType->getDescription(),
				$registrationType->getCost(),
				$registrationType->getCurrencyCodeAlpha(),
				$registrationType->getAccess(),
				$registrationType->getInstitutional(),
				$registrationType->getMembership(),
				$registrationType->getPublic(),
				$registrationType->getSequence(),
				$registrationType->getTypeId()
			)
		);
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
		$ret = $this->update(
			'DELETE FROM registration_types WHERE type_id = ?', $typeId
			);

		// Delete all registrations with this registration type
		if ($ret) {
			$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
			return $registrationDao->deleteRegistrationByTypeId($typeId);
		} else {
			return $ret;
		}
	}

	/**
	 * Retrieve an array of registration types matching a particular event ID.
	 * @param $eventId int
	 * @return object DAOResultFactory containing matching RegistrationTypes
	 */
	function &getRegistrationTypesByEventId($eventId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM registration_types WHERE event_id = ? ORDER BY seq',
			 $eventId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnRegistrationTypeFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted registration type.
	 * @return int
	 */
	function getInsertRegistrationTypeId() {
		return $this->getInsertId('registration_types', 'type_id');
	}

	/**
	 * Sequentially renumber registration types in their sequence order.
	 */
	function resequenceRegistrationTypes($eventId) {
		$result = &$this->retrieve(
			'SELECT type_id FROM registration_types WHERE event_id = ? ORDER BY seq',
			$eventId
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

}

?>
