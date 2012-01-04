<?php

/**
 * @file Registration.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Registration
 * @ingroup registration 
 * @see RegistrationDAO
 *
 * @brief Basic class describing a registration.
 */

//$Id$

define('REGISTRATION_IP_RANGE_SEPERATOR', ';');
define('REGISTRATION_IP_RANGE_RANGE', '-');
define('REGISTRATION_IP_RANGE_WILDCARD', '*');
define('REGISTRATION_YEAR_OFFSET_PAST', '-10');
define('REGISTRATION_YEAR_OFFSET_FUTURE', '+10');


class Registration extends DataObject {

	function Registration() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the registration.
	 * @return int
	 */
	function getRegistrationId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set the ID of the registration.
	 * @param $registrationId int
	 */
	function setRegistrationId($registrationId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($registrationId);
	}

	/**
	 * Get the scheduled conference ID of the registration.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set the scheduled conference ID of the registration.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get the user ID of the registration.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set the user ID of the registration.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}

	/**
	 * Get the user's full name of the registration.
	 * @return string 
	 */
	function getUserFullName() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getUserFullName($this->getData('userId'));
	}

	/**
	 * Get the registration type ID of the registration.
	 * @return int
	 */
	function getTypeId() {
		return $this->getData('typeId');
	}

	/**
	 * Set the registration type ID of the registration.
	 * @param $typeId int
	 */
	function setTypeId($typeId) {
		return $this->setData('typeId', $typeId);
	}

	/**
	 * Get the registration type name of the registration.
	 * @return string
	 */
	function getRegistrationTypeName() {
		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
		return $registrationTypeDao->getRegistrationTypeName($this->getData('typeId'));
	}

	/**
	 * Get date of registration.
	 * @return date (YYYY-MM-DD)
	 */
	function getDateRegistered() {
		return $this->getData('dateRegistered');
	}

	/**
	 * Set date of registration.
	 * @param $dateRegistered date (YYYY-MM-DD)
	 */
	function setDateRegistered($dateRegistered) {
		return $this->setData('dateRegistered', $dateRegistered);
	}

	/**
	 * Get registration paid date.
	 * @return date (YYYY-MM-DD) 
	 */
	function getDatePaid() {
		return $this->getData('datePaid');
	}

	/**
	 * Set registration end date.
	 * @param $datePaid date (YYYY-MM-DD)
	 */
	function setDatePaid($datePaid) {
		return $this->setData('datePaid', $datePaid);
	}

	/**
	 * Get registration special requests.
	 * @return string
	 */
	function getSpecialRequests() {
		return $this->getData('specialRequests');
	}

	/**
	 * Set registration special requests.
	 * @param $specialRequests string
	 */
	function setSpecialRequests($specialRequests) {
		return $this->setData('specialRequests', $specialRequests);
	}

	/**
	 * Get registration membership.
	 * @return string
	 */
	function getMembership() {
		return $this->getData('membership');
	}

	/**
	 * Set registration membership.
	 * @param $membership string
	 */
	function setMembership($membership) {
		return $this->setData('membership', $membership);
	}

	/**
	 * Get registration domain string.
	 * @return string
	 */
	function getDomain() {
		return $this->getData('domain');
	}

	/**
	 * Set registration domain string.
	 * @param $domain string
	 */
	function setDomain($domain) {
		return $this->setData('domain', $domain);
	}

	/**
	 * Get registration ip range string.
	 * @return string
	 */
	function getIPRange() {
		return $this->getData('ipRange');
	}

	/**
	 * Set registration ip range string.
	 * @param $ipRange string
	 */
	function setIPRange($ipRange) {
		return $this->setData('ipRange', $ipRange);
	}

	/**
	 * Get registration ip ranges.
	 * @return array 
	 */
	function getIPRanges() {
		return explode(REGISTRATION_IP_RANGE_SEPERATOR, $this->getData('ipRange'));
	}

	/**
	 * Set registration ip ranges.
	 * @param ipRanges array 
	 */
	function setIPRanges($ipRanges) {
		return $this->setData(implode(REGISTRATION_IP_RANGE_SEPERATOR, $ipRanges));
	}

}

?>
