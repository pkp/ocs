<?php

/**
 * RegistrationDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package registration
 *
 * Class for Registration DAO.
 * Operations for retrieving and modifying Registration objects.
 *
 * $Id$
 */

import('registration.Registration');
import('registration.RegistrationType');

class RegistrationDAO extends DAO {

	/**
	 * Constructor.
	 */
	function RegistrationDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a registration by registration ID.
	 * @param $registrationId int
	 * @return Registration
	 */
	function &getRegistration($registrationId) {
		$result = &$this->retrieve(
			'SELECT * FROM registrations WHERE registration_id = ?', $registrationId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnRegistrationFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Retrieve registration event ID by registration ID.
	 * @param $registrationId int
	 * @return int
	 */
	function getRegistrationEventId($registrationId) {
		$result = &$this->retrieve(
			'SELECT event_id FROM registrations WHERE registration_id = ?', $registrationId
		);
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve registration ID by user ID.
	 * @param $userId int
	 * @param $eventId int
	 * @return int
	 */
	function getRegistrationIdByUser($userId, $eventId) {
		$result = &$this->retrieve(
			'SELECT registration_id
				FROM registrations
				WHERE user_id = ?
				AND event_id = ?',
			array(
				$userId,
				$eventId
			)
		);
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a registration exists for a given user and event.
	 * @param $userId int
	 * @param $eventId int
	 * @return boolean
	 */
	function registrationExistsByUser($userId, $eventId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM registrations
				WHERE user_id = ?
				AND   event_id = ?',
			array(
				$userId,
				$eventId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a Registration object from a row.
	 * @param $row array
	 * @return Registration
	 */
	function &_returnRegistrationFromRow(&$row) {
		$registration = &new Registration();
		$registration->setRegistrationId($row['registration_id']);
		$registration->setEventId($row['event_id']);
		$registration->setUserId($row['user_id']);
		$registration->setTypeId($row['type_id']);
		$registration->setDateRegistered($this->dateFromDB($row['date_registered']));
		$registration->setDatePaid($this->dateFromDB($row['date_paid']));
		$registration->setSpecialRequests($row['special_requests']);
		//$registration->setMembership($row['membership']);
		//$registration->setDomain($row['domain']);
		//$registration->setIPRange($row['ip_range']);
		
		HookRegistry::call('RegistrationDAO::_returnRegistrationFromRow', array(&$registration, &$row));

		return $registration;
	}

	/**
	 * Insert a new Registration.
	 * @param $registration Registration
	 * @return boolean 
	 */
	function insertRegistration(&$registration) {
		$ret = $this->update(
			sprintf('INSERT INTO registrations
				(event_id, user_id, type_id, date_registered, date_paid, special_requests)
				VALUES
				(?, ?, ?, %s, %s, ?)',
				$this->dateToDB($registration->getDateRegistered()), $this->dateToDB($registration->getDatePaid())),
			array(
				$registration->getEventId(),
				$registration->getUserId(),
				$registration->getTypeId(),
				$registration->getSpecialRequests()
			)
		);
		$registration->setRegistrationId($this->getInsertRegistrationId());
		return $registration->getRegistrationId();
	}

	/**
	 * Update an existing registration.
	 * @param $registration Registration
	 * @return boolean
	 */
	function updateRegistration(&$registration) {
		return $this->update(
			sprintf('UPDATE registrations
				SET
					event_id = ?,
					user_id = ?,
					type_id = ?,
					date_registered = %s,
					date_paid = %s,
					special_requests = ?
				WHERE registration_id = ?',
				$this->dateToDB($registration->getDateRegistered()), $this->dateToDB($registration->getDatePaid())),
			array(
				$registration->getEventId(),
				$registration->getUserId(),
				$registration->getTypeId(),
				$registration->getSpecialRequests(),
				$registration->getRegistrationId()
			)
		);
	}

	/**
	 * Delete a registration by registration ID.
	 * @param $registrationId int
	 * @return boolean
	 */
	function deleteRegistrationById($registrationId) {
		return $this->update(
			'DELETE FROM registrations WHERE registration_id = ?', $registrationId
		);
	}

	/**
	 * Delete registrations by event ID.
	 * @param $eventId int
	 */
	function deleteRegistrationsByEvent($eventId) {
		return $this->update(
			'DELETE FROM registrations WHERE event_id = ?', $eventId
		);
	}

	/**
	 * Delete registrations by user ID.
	 * @param $userId int
	 */
	function deleteRegistrationsByUserId($userId) {
		return $this->update(
			'DELETE FROM registrations WHERE user_id = ?', $userId
		);
	}

	/**
	 * Delete all registrations by registration type ID.
	 * @param $registrationTypeId int
	 * @return boolean
	 */
	function deleteRegistrationByTypeId($registrationTypeId) {
		return $this->update(
			'DELETE FROM registrations WHERE type_id = ?', $registrationTypeId
			);
	}

	/**
	 * Retrieve an array of registrations matching a particular event ID.
	 * @param $eventId int
	 * @return object DAOResultFactory containing matching Registrations
	 */
	function &getRegistrationsByEventId($eventId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM registrations WHERE event_id = ?', $eventId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnRegistrationFromRow');

		return $returner;
	}

	/**
	 * Retrieve an array of registrations matching a particular end date and event ID.
	 * @param $dateEnd date (YYYY-MM-DD)
	 * @param $eventId int
	 * @return object DAOResultFactory containing matching Registrations
	 */
	/*function &getRegistrationsByDateEnd($dateEnd, $eventId, $rangeInfo = null) {
		$dateEnd = explode('-', $dateEnd);

		$result = &$this->retrieveRange(
			'SELECT * FROM registrations
				WHERE EXTRACT(YEAR FROM date_end) = ?
				AND   EXTRACT(MONTH FROM date_end) = ?
				AND   EXTRACT(DAY FROM date_end) = ?
				AND   event_id = ?',
			array(
				$dateEnd[0],
				$dateEnd[1],
				$dateEnd[2],
				$eventId
			), $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnRegistrationFromRow');

		return $returner;
	}*/

	/**
	 * Check whether there is a valid registration for a given event.
	 * @param $domain string
	 * @param $IP string
	 * @param $userId int
	 * @param $eventId int
	 * @return boolean
	 */
	function isValidRegistration($domain, $IP, $userId, $eventId) {
		$valid = false;

		if ($domain != null) {
			$valid = $this->isValidRegistrationByDomain($domain, $eventId);
			if ($valid) { return true; }
		}	

		if ($IP != null) {
			$valid = $this->isValidRegistrationByIP($IP, $eventId);
			if ($valid) { return true; }
		}

		if ($userId != null) {
			return $this->isValidRegistrationByUser($userId, $eventId);
		}

		return false;
    }

	/**
	 * Check whether user with ID has a valid registration for a given event.
	 * @param $userId int
	 * @param $eventId int
	 * @return boolean
	 */
	function isValidRegistrationByUser($userId, $eventId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM expiry_date) AS expiry_day,
					EXTRACT(MONTH FROM expiry_date) AS expiry_month,
					EXTRACT(YEAR FROM expiry_date) AS expiry_year
			FROM registrations, registration_types
			WHERE registrations.user_id = ?
			AND   registrations.event_id = ?
			AND   registrations.type_id = registration_types.type_id',
			array(
				$userId,
				$eventId
			));

		$returner = false;

		if ($result->RecordCount() != 0) {
			$dayEnd = $result->fields['expiry_day'];
			$monthEnd = $result->fields['expiry_month'];
			$yearEnd = $result->fields['expiry_year'];

			// Ensure registration is still valid
			$curDate = getdate();

			if ( $curDate['year'] < $yearEnd ) {
				$returner = true;
			} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
				$returner = true;
			} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
				$returner = true;
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check whether there is a valid registration with given domain for a event.
	 * @param $domain string
	 * @param $eventId int
	 * @return boolean
	 */
	function isValidRegistrationByDomain($domain, $eventId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM expiry_date) AS expiry_day,
					EXTRACT(MONTH FROM expiry_date) AS expiry_month,
					EXTRACT(YEAR FROM expiry_date) AS expiry_year,
					POSITION(UPPER(domain) IN UPPER(?)) AS domain_position
			FROM registrations, registration_types
			WHERE POSITION(UPPER(domain) IN UPPER(?)) != 0   
			AND   registrations.event_id = ?
			AND   registrations.type_id = registration_types.type_id
			AND   registration_types.institutional = 1',
			array(
				$domain,
				$domain,
				$eventId
			));

		$returner = false;

		if ($result->RecordCount() != 0) {
			while (!$returner && !$result->EOF) {
				$dayEnd = $result->fields['expiry_day'];
				$monthEnd = $result->fields['expiry_month'];
				$yearEnd = $result->fields['expiry_year'];
				$posMatch = $result->fields['domain_position'];

				// Ensure we have a proper match (i.e. bar.com should not match foobar.com but should match foo.bar.com)
				if ( $posMatch > 1) {
					if ( substr($domain, $posMatch-2, 1) != '.') {
						$result->moveNext();
						continue;
					}
				}

				// Ensure registration is still valid
				$curDate = getdate();

				if ( $curDate['year'] < $yearEnd ) {
					$returner = true;
				} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
					$returner = true;
				} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
					$returner = true;
				}

				$result->moveNext();
			}
		}

		$result->Close();
		unset($result);

		// By default, not a valid registration
		return $returner;
	}

	/**
	 * Check whether there is a valid registration for the given IP for a event.
	 * @param $IP string
	 * @param $eventId int
	 * @return boolean
	 */
	function isValidRegistrationByIP($IP, $eventId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM expiry_date) AS expiry_day,
					EXTRACT(MONTH FROM expiry_date) AS expiry_month,
					EXTRACT(YEAR FROM expiry_date) AS expiry_year,
					ip_range 
			FROM registrations, registration_types
			WHERE ip_range IS NOT NULL   
			AND   registrations.event_id = ?
			AND   registrations.type_id = registration_types.type_id
			AND   registration_types.institutional = 1',
			$eventId
			);

		$returner = false;

		if ($result->RecordCount() != 0) {
			$matchFound = false;

			while (!$returner && !$result->EOF) {
				$ipRange = $result->fields[3];

				// Get all IPs and IP ranges
				$ipRanges = explode(REGISTRATION_IP_RANGE_SEPERATOR, $ipRange);

				// Check each IP and IP range
				while (list(, $curIPString) = each($ipRanges)) {
					// Parse and check single IP string
					if (strpos($curIPString, REGISTRATION_IP_RANGE_RANGE) === false) {

						// Check for wildcards in IP
						if (strpos($curIPString, REGISTRATION_IP_RANGE_WILDCARD) === false) {

							// Check non-CIDR IP
							if (strpos($curIPString, '/') === false) {
								if (ip2long(trim($curIPString)) == ip2long($IP)) {
									$matchFound = true;
									break;
								}
							// Check CIDR IP
							} else {
								list($curIPString, $cidrMask) = explode('/', trim($curIPString));
								$cidrMask = 0xffffffff << (32 - $cidrMask);

								if ((ip2long($IP) & $cidrMask) == (ip2long($curIPString) & $cidrMask)) {
									$matchFound = true;
									break;
								}
							}

						} else {
							// Turn wildcard IP into IP range
							$ipStart = sprintf('%u', ip2long(str_replace(REGISTRATION_IP_RANGE_WILDCARD, '0', trim($curIPString))));
							$ipEnd = sprintf('%u', ip2long(str_replace(REGISTRATION_IP_RANGE_WILDCARD, '255', trim($curIPString)))); 
							$IP = sprintf('%u', ip2long($IP)); 

							if ($IP >= $ipStart && $IP <= $ipEnd) {
								$matchFound = true;
								break;
							}
						}
					// Parse and check IP range string
					} else {
						list($ipStart, $ipEnd) = explode(REGISTRATION_IP_RANGE_RANGE, $curIPString);

						// Replace wildcards in start and end of range
						$ipStart = sprintf('%u', ip2long(str_replace(REGISTRATION_IP_RANGE_WILDCARD, '0', trim($ipStart))));
						$ipEnd = sprintf('%u', ip2long(str_replace(REGISTRATION_IP_RANGE_WILDCARD, '255', trim($ipEnd))));
						$IP = sprintf('%u', ip2long($IP)); 

						if ($IP >= $ipStart && $IP <= $ipEnd) {
							$matchFound = true;
							break;
						}
					}

				}

				if ($matchFound == true) {
					break;
				} else {
					$result->moveNext();
				}
			}

			// Found a match. Ensure registration is still valid
			if ($matchFound == true) {
				$dayEnd = $result->fields['expiry_day'];
				$monthEnd = $result->fields['expiry_month'];
				$yearEnd = $result->fields['expiry_year'];

				$curDate = getdate();

				if ( $curDate['year'] < $yearEnd ) {
					$returner = true;
				} elseif (( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] < $monthEnd )) {
					$returner = true;
				} elseif ((( $curDate['year'] == $yearEnd ) && ( $curDate['mon'] == $monthEnd )) && ( $curDate['mday'] <= $dayEnd ) ) {
					$returner = true;
				}
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted registration.
	 * @return int
	 */
	function getInsertRegistrationId() {
		return $this->getInsertId('registrations', 'registration_id');
	}
	
}

?>
