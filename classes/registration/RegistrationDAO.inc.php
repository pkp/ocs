<?php

/**
 * @file RegistrationDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationDAO
 * @ingroup registration
 * @see Registration
 *
 * @brief Operations for retrieving and modifying Registration objects.
 *
 */

//$Id$


import('registration.Registration');
import('registration.RegistrationType');

define('REGISTRATION_DATE_REGISTERED',	0x01);
define('REGISTRATION_DATE_PAID',	0x02);

define('REGISTRATION_USER',		0x01);
define('REGISTRATION_MEMBERSHIP',	0x02);
define('REGISTRATION_DOMAIN',		0x03);
define('REGISTRATION_IP_RANGE',		0x04);

class RegistrationDAO extends DAO {
	/**
	 * Retrieve a registration by registration ID.
	 * @param $registrationId int
	 * @return Registration
	 */
	function &getRegistration($registrationId) {
		$result =& $this->retrieve(
			'SELECT * FROM registrations WHERE registration_id = ?', $registrationId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnRegistrationFromRow($result->GetRowAssoc(false));
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
		$result =& $this->retrieve(
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
		$result =& $this->retrieve(
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
	 * Retrieve all registrations by user ID.
	 * @param $userId int
	 * @return object DAOResultFactory containing matching Registrations
	 */
	function getRegistrationsByUser($userId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT *
				FROM registrations
				WHERE user_id = ?',
			$userId,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnRegistrationFromRow');

		return $returner;
	}


	/**
	 * Check if a registration exists for a given user and scheduled conf.
	 * @param $userId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function registrationExistsByUser($userId, $schedConfId) {
		$result =& $this->retrieve(
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
		$registration = new Registration();
		$registration->setId($row['registration_id']);
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
	 * Internal function to generate user based search query.
	 * @return string 
	 */
	function _generateUserNameSearchSQL($search, $searchMatch, $prefix, &$params) {
		$first_last = $this->_dataSource->Concat($prefix.'first_name', '\' \'', $prefix.'last_name');
		$first_middle_last = $this->_dataSource->Concat($prefix.'first_name', '\' \'', $prefix.'middle_name', '\' \'', $prefix.'last_name');
		$last_comma_first = $this->_dataSource->Concat($prefix.'last_name', '\', \'', $prefix.'first_name');
		$last_comma_first_middle = $this->_dataSource->Concat($prefix.'last_name', '\', \'', $prefix.'first_name', '\' \'', $prefix.'middle_name');
		if ($searchMatch === 'is') {
			$searchSql = " AND (LOWER({$prefix}last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
		} elseif ($searchMatch === 'contains') {
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$search = '%' . $search . '%';
		} else { // $searchMatch === 'startsWith'
			$searchSql = " AND (LOWER({$prefix}last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
			$search = $search . '%';
		}
		$params[] = $params[] = $params[] = $params[] = $params[] = $search;
		return $searchSql;
	}

	/**
	 * Insert a new Registration.
	 * @param $registration Registration
	 * @return boolean 
	 */
	function insertRegistration(&$registration) {
		$ret = $this->update(
			sprintf('INSERT INTO registrations
				(sched_conf_id, user_id, type_id, date_registered, date_paid, membership, domain, ip_range, special_requests)
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
		$registration->setId($this->getInsertRegistrationId());
		return $registration->getId();
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
				$registration->getId()
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
	 * Delete registration by scheduled conference ID.
	 * @param $schedConfId int
	 */
	function deleteRegistrationsBySchedConf($schedConfId) {
		return $this->update(
			'DELETE FROM registrations WHERE sched_conf_id = ?', $schedConfId
		);
	}

	/**
	 * Delete registration by user ID.
	 * @param $userId int
	 */
	function deleteRegistrationsByUserId($userId) {
		return $this->update(
			'DELETE FROM registrations WHERE user_id = ?', $userId
		);
	}

	/**
	 * Delete registration by user ID and scheduled conference ID.
	 * @param $schedConfId int
	 */
	function deleteRegistrationByUserIdSchedConf($userId, $schedConfId) {
		return $this->update(
			'DELETE
				FROM registrations
				WHERE user_id = ?
				AND sched_conf_id = ?',
				array(
					$userId,
					$schedConfId
				)
		);
	}

	/**
	 * Delete all registration by registration type ID.
	 * @param $registrationTypeId int
	 * @return boolean
	 */
	function deleteRegistrationByTypeId($registrationTypeId) {
		return $this->update(
			'DELETE FROM registrations WHERE type_id = ?', $registrationTypeId
			);
	}

	/**
	 * Retrieve an array of registration matching a particular scheduled conference ID.
	 * @param $schedConfId int
	 * @param $searchField int
	 * @param $searchMatch string "is" or "contains" or "startsWith"
	 * @param $search String to look in $searchField for
	 * @param $dateField int 
	 * @param $dateFrom String date to search from
	 * @param $dateTo String date to search to
	 * @return object DAOResultFactory containing matching Registrations
	 */
	function &getRegistrationsBySchedConfId($schedConfId, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$params = array($schedConfId);
		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case REGISTRATION_USER:
				$searchSql = $this->_generateUserNameSearchSQL($search, $searchMatch, 'u.', $params);
				break;
			case REGISTRATION_MEMBERSHIP:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(r.membership) = LOWER(?)';
				} elseif ($searchMatch === 'contains') {
					$searchSql = ' AND LOWER(r.membership) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = ' AND LOWER(r.membership) LIKE LOWER(?)';
					$search = $search . '%';
				}
				$params[] = $search;
				break;
			case REGISTRATION_DOMAIN:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(r.domain) = LOWER(?)';
				} elseif ($searchMatch === 'contains') {
					$searchSql = ' AND LOWER(r.domain) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = ' AND LOWER(r.domain) LIKE LOWER(?)';
					$search = $search . '%';
				}
				$params[] = $search;
				break;
			case REGISTRATION_IP_RANGE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(r.ip_range) = LOWER(?)';
				} elseif ($searchMatch === 'contains') {
					$searchSql = ' AND LOWER(r.ip_range) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				} else { // $searchName === 'startsWith'
					$searchSql = ' AND LOWER(r.ip_range) LIKE LOWER(?)';
					$search = $search . '%';
				}
				$params[] = $search;
				break;
		}

		if (!empty($dateFrom) || !empty($dateTo)) switch($dateField) {
			case REGISTRATION_DATE_REGISTERED:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND r.date_registered >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND r.date_registered <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case REGISTRATION_DATE_PAID:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND r.date_paid >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND r.date_paid <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		$sql = 'SELECT r.*
				FROM
					registrations r,
					users u
				WHERE r.user_id = u.user_id
				AND sched_conf_id = ?';
 
		$result =& $this->retrieveRange(
			$sql . ' ' . $searchSql . ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : ''),
			count($params)===1?array_shift($params):$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnRegistrationFromRow');

		return $returner;
	}

	/**
	 * Retrieve a list of all paid registered users.
	 * @param $schedConfId int
	 * @param $paid boolean Whether or not included users must be paid
	 * @param $dbRangeInfo object DBRangeInfo object describing range of results to return
	 * @return object ItemIterator containing matching Users
	 */
	function &getRegisteredUsers($schedConfId, $paid = true, $dbResultRange = null) {
		$result =& $this->retrieveRange(
			'SELECT DISTINCT u.*
			FROM	users u,
				registrations r
			WHERE	u.user_id = r.user_id AND
				r.sched_conf_id = ?
				' . ($paid?' AND r.date_paid IS NOT NULL':''),
			(int) $schedConfId,
			$dbResultRange
		);

		$userDao =& DAORegistry::getDAO('UserDAO');
		$returner = new DAOResultFactory($result, $userDao, '_returnUserFromRowWithData');
		return $returner;
	}

	/**
	 * Retrieve a count of all paid registered users.
	 * @param $schedConfId int
	 * @param $paid boolean Whether or not included users must be paid
	 * @return int count
	 */
	function &getRegisteredUserCount($schedConfId, $paid = true) {
		$result =& $this->retrieve(
			'SELECT COUNT(DISTINCT u.user_id) AS user_count
			FROM	users u,
				registrations r
			WHERE	u.user_id = r.user_id AND
				r.sched_conf_id = ?
				' . ($paid?' AND r.date_paid IS NOT NULL':''),
			(int) $schedConfId
		);

		$returner = $result->fields['user_count'];
		$result->Close();

		return $returner;
	}

	/**
	 * Check whether there is a valid registration for a given scheduled conference.
	 * @param $domain string
	 * @param $IP string
	 * @param $userId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function isValidRegistration($domain, $IP, $userId, $schedConfId) {
		if ($userId != null) {
			$valid = $this->isValidRegistrationByUser($userId, $schedConfId);
			if ($valid !== false) { return $valid; }
		}

		if ($domain != null) {
			$valid = $this->isValidRegistrationByDomain($domain, $schedConfId);
			if ($valid !== false) { return $valid; }
		}	

		if ($IP != null) {
			$valid = $this->isValidRegistrationByIP($IP, $schedConfId);
			if ($valid) { return $valid; }
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
		$result =& $this->retrieve(
			'SELECT registrations.registration_id,
					registration_types.expiry_date
			FROM registrations, registration_types
			WHERE registrations.user_id = ?
			AND   registrations.sched_conf_id = ?
			AND   registrations.type_id = registration_types.type_id
			AND   date_paid IS NOT NULL',
			array(
				$userId,
				$schedConfId
			));

		$returner = false;

		if ($result->RecordCount() != 0) {
			$expiryDate = $result->fields['expiry_date'];
			$registrationId = $result->fields['registration_id'];

			if ($expiryDate === null ||
				strtotime($this->datetimeFromDB($expiryDate)) > time()
			) $returner = $registrationId;
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
		$result =& $this->retrieve(
			'SELECT registrations.registration_id,
					registration_types.expiry_date,
					POSITION(UPPER(domain) IN UPPER(?)) AS domain_position
			FROM registrations, registration_types
			WHERE POSITION(UPPER(domain) IN UPPER(?)) != 0
			AND   domain != \'\'
			AND   registrations.sched_conf_id = ?
			AND   registrations.type_id = registration_types.type_id
			AND   registration_types.institutional = 1
			AND   date_paid IS NOT NULL',
			array(
				$domain,
				$domain,
				$schedConfId
			));

		$returner = false;

		if ($result->RecordCount() != 0) {
			while (!$returner && !$result->EOF) {
				$expiryDate = $result->fields['expiry_date'];
				$posMatch = $result->fields['domain_position'];
				$registrationId = $result->fields['registration_id'];

				// Ensure we have a proper match (i.e. bar.com should not match foobar.com but should match foo.bar.com)
				if ( $posMatch > 1) {
					if ( substr($domain, $posMatch-2, 1) != '.') {
						$result->moveNext();
						continue;
					}
				}

				if ($expiryDate === null ||
					strtotime($this->datetimeFromDB($expiryDate)) > time()
				) $returner = $registrationId;

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
		$result =& $this->retrieve(
			'SELECT 	r.registration_id,
					rt.expiry_date,
					r.ip_range 
			FROM registrations r, registration_types rt
			WHERE r.ip_range IS NOT NULL   
			AND   r.sched_conf_id = ?
			AND   r.type_id = rt.type_id
			AND   rt.institutional = 1
			AND   r.date_paid IS NOT NULL',
			$schedConfId
			);

		$returner = false;

		if ($result->RecordCount() != 0) {
			$matchFound = false;

			while (!$returner && !$result->EOF) {
				$ipRange = $result->fields['ip_range'];

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
				$registrationId = $result->fields['registration_id'];
				$expiryDate = $result->fields['expiry_date'];

				if ($expiryDate === null ||
					strtotime($this->datetimeFromDB($expiryDate)) > time()
				) $returner = $registrationId;
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

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'user': return 'u.last_name';
			case 'type': return 'r.type_id';
			case 'registered': return 'r.date_registered';
			case 'paid': return 'r.date_paid';
			case 'id': return 'r.registration_id';
			default: return null;
		}
	}
}

?>
