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
	 * Retrieve registration scheduled conference ID by registration ID.
	 * @param $registrationId int
	 * @return int
	 */
	function getRegistrationSchedConfId($registrationId) {
		$result = &$this->retrieve(
			'SELECT sched_conf_id FROM registrations WHERE registration_id = ?', $registrationId
		);
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve registration ID by user ID.
	 * @param $userId int
	 * @param $schedConfId int
	 * @return int
	 */
	function getRegistrationIdByUser($userId, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT registration_id
				FROM registrations
				WHERE user_id = ?
				AND sched_conf_id = ?',
			array(
				$userId,
				$schedConfId
			)
		);
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a registration exists for a given user and scheduled conf.
	 * @param $userId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function registrationExistsByUser($userId, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM registrations
				WHERE user_id = ?
				AND   sched_conf_id = ?',
			array(
				$userId,
				$schedConfId
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
		$registration->setSchedConfId($row['sched_conf_id']);
		$registration->setUserId($row['user_id']);
		$registration->setTypeId($row['type_id']);
		$registration->setDateRegistered($this->dateFromDB($row['date_registered']));
		$registration->setDatePaid($this->dateFromDB($row['date_paid']));
		$registration->setMembership($row['membership']);
		$registration->setDomain($row['domain']);
		$registration->setIPRange($row['ip_range']);
		$registration->setSpecialRequests($row['special_requests']);
		
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
				(sched_conf_id, user_id, type_id, date_registered, date_paid, membership, domain, iprange, special_requests)
				VALUES
				(?, ?, ?, %s, %s, ?, ?, ?, ?)',
				$this->dateToDB($registration->getDateRegistered()), $this->dateToDB($registration->getDatePaid())),
			array(
				$registration->getSchedConfId(),
				$registration->getUserId(),
				$registration->getTypeId(),
				$registration->getMembership(),
				$registration->getDomain(),
				$registration->getIPRange(),
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
					sched_conf_id = ?,
					user_id = ?,
					type_id = ?,
					date_registered = %s,
					date_paid = %s,
					membership = ?,
					domain = ?,
					ip_range = ?,
					special_requests = ?
				WHERE registration_id = ?',
				$this->dateToDB($registration->getDateRegistered()), $this->dateToDB($registration->getDatePaid())),
			array(
				$registration->getSchedConfId(),
				$registration->getUserId(),
				$registration->getTypeId(),
				$registration->getMembership(),
				$registration->getDomain(),
				$registration->getIPRange(),
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
	 * Delete registrations by scheduled conference ID.
	 * @param $schedConfId int
	 */
	function deleteRegistrationsBySchedConf($schedConfId) {
		return $this->update(
			'DELETE FROM registrations WHERE sched_conf_id = ?', $schedConfId
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
	 * Retrieve an array of registrations matching a particular scheduled conference ID.
	 * @param $schedConfId int
	 * @return object DAOResultFactory containing matching Registrations
	 */
	function &getRegistrationsBySchedConfId($schedConfId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM registrations WHERE sched_conf_id = ?', $schedConfId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnRegistrationFromRow');

		return $returner;
	}

	/**
	 * Retrieve an array of registrations matching a particular end date and scheduled conference ID.
	 * @param $dateEnd date (YYYY-MM-DD)
	 * @param $schedConfId int
	 * @return object DAOResultFactory containing matching Registrations
	 */
	/*function &getRegistrationsByDateEnd($dateEnd, $schedConfId, $rangeInfo = null) {
		$dateEnd = explode('-', $dateEnd);

		$result = &$this->retrieveRange(
			'SELECT * FROM registrations
				WHERE EXTRACT(YEAR FROM date_end) = ?
				AND   EXTRACT(MONTH FROM date_end) = ?
				AND   EXTRACT(DAY FROM date_end) = ?
				AND   sched_conf_id = ?',
			array(
				$dateEnd[0],
				$dateEnd[1],
				$dateEnd[2],
				$schedConfId
			), $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnRegistrationFromRow');

		return $returner;
	}*/

	/**
	 * Check whether there is a valid registration for a given scheduled conference.
	 * @param $domain string
	 * @param $IP string
	 * @param $userId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function isValidRegistration($domain, $IP, $userId, $schedConfId) {
		$valid = false;

		if ($domain != null) {
			$valid = $this->isValidRegistrationByDomain($domain, $schedConfId);
			if ($valid) { return true; }
		}	

		if ($IP != null) {
			$valid = $this->isValidRegistrationByIP($IP, $schedConfId);
			if ($valid) { return true; }
		}

		if ($userId != null) {
			return $this->isValidRegistrationByUser($userId, $schedConfId);
		}

		return false;
    }

	/**
	 * Check whether user with ID has a valid registration for a given scheduled conference.
	 * @param $userId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function isValidRegistrationByUser($userId, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM expiry_date) AS expiry_day,
					EXTRACT(MONTH FROM expiry_date) AS expiry_month,
					EXTRACT(YEAR FROM expiry_date) AS expiry_year
			FROM registrations, registration_types
			WHERE registrations.user_id = ?
			AND   registrations.sched_conf_id = ?
			AND   registrations.type_id = registration_types.type_id',
			array(
				$userId,
				$schedConfId
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
	 * Check whether there is a valid registration with given domain for a scheduled conference.
	 * @param $domain string
	 * @param $schedConfId int
	 * @return boolean
	 */
	function isValidRegistrationByDomain($domain, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM expiry_date) AS expiry_day,
					EXTRACT(MONTH FROM expiry_date) AS expiry_month,
					EXTRACT(YEAR FROM expiry_date) AS expiry_year,
					POSITION(UPPER(domain) IN UPPER(?)) AS domain_position
			FROM registrations, registration_types
			WHERE POSITION(UPPER(domain) IN UPPER(?)) != 0   
			AND   registrations.sched_conf_id = ?
			AND   registrations.type_id = registration_types.type_id
			AND   registration_types.institutional = 1',
			array(
				$domain,
				$domain,
				$schedConfId
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
	 * Check whether there is a valid registration for the given IP for a sc heduled conference.
	 * @param $IP string
	 * @param $schedConfId int
	 * @return boolean
	 */
	function isValidRegistrationByIP($IP, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT EXTRACT(DAY FROM expiry_date) AS expiry_day,
					EXTRACT(MONTH FROM expiry_date) AS expiry_month,
					EXTRACT(YEAR FROM expiry_date) AS expiry_year,
					ip_range 
			FROM registrations, registration_types
			WHERE ip_range IS NOT NULL   
			AND   registrations.sched_conf_id = ?
			AND   registrations.type_id = registration_types.type_id
			AND   registration_types.institutional = 1',
			$schedConfId
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
