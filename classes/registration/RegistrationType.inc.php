<?php

/**
 * @file RegistrationType.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationType
 * @ingroup registration 
 * @see RegistrationTypeDAO
 *
 * @brief Basic class describing a registration type.
 */


/**
 * Registration access types
 */
define('REGISTRATION_TYPE_ACCESS_ONLINE', 0x01); 
define('REGISTRATION_TYPE_ACCESS_PHYSICAL', 0x10);
define('REGISTRATION_TYPE_ACCESS_BOTH', 0x11);

define('REGISTRATION_TYPE_YEAR_OFFSET_FUTURE',	'+10');

class RegistrationType extends DataObject {

	function RegistrationType() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the registration type.
	 * @return int
	 */
	function getTypeId() {
		return $this->getData('typeId');
	}

	/**
	 * Set the ID of the registration type.
	 * @param $typeId int
	 */
	function setTypeId($typeId) {
		return $this->setData('typeId', $typeId);
	}

	/**
	 * Get the scheduled conference ID of the registration type.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set the scheduled conference ID of the registration type.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get the localized registration type name
	 * @return string
	 */
	function getRegistrationTypeName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get registration type name.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set registration type name.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get registration type code.
	 * @return string
	 */
	function getCode() {
		return $this->getData('code');
	}

	/**
	 * Set registration type code.
	 * @param $typeCode string
	 */
	function setCode($code) {
		return $this->setData('code', $code);
	}

	/**
	 * Get the localized registration type description
	 * @return string
	 */
	function getRegistrationTypeDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get registration type description.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set registration type description.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}

	/**
	 * Get registration type cost.
	 * @return float 
	 */
	function getCost() {
		return $this->getData('cost');
	}

	/**
	 * Set registration type cost.
	 * @param $cost float
	 */
	function setCost($cost) {
		return $this->setData('cost', $cost);
	}

	/**
	 * Get registration type currency code.
	 * @return string
	 */
	function getCurrencyCodeAlpha() {
		return $this->getData('currencyCodeAlpha');
	}

	/**
	 * Set registration type currency code.
	 * @param $currencyCodeAlpha string
	 */
	function setCurrencyCodeAlpha($currencyCodeAlpha) {
		return $this->setData('currencyCodeAlpha', $currencyCodeAlpha);
	}

	/**
	 * Get registration type currency string.
	 * @return int
	 */
	function getCurrencyString() {
		$currencyDao = DAORegistry::getDAO('CurrencyDAO');
		$currency =& $currencyDao->getCurrencyByAlphaCode($this->getData('currencyCodeAlpha'));

		if ($currency != null) {
			return $currency->getName();
		} else {
			return 'manager.registrationTypes.currency';
		}
	}

	/**
	 * Get registration type currency abbreviated string.
	 * @return int
	 */
	function getCurrencyStringShort() {
		$currencyDao = DAORegistry::getDAO('CurrencyDAO');
		$currency =& $currencyDao->getCurrencyByAlphaCode($this->getData('currencyCodeAlpha'));

		if ($currency != null) {
			return $currency->getCodeAlpha();
		} else {
			return 'manager.registrationTypes.currency';
		}
	}

	/**
	 * Get registration type opening date.
	 * @return date
	 */
	function getOpeningDate() {
		return $this->getData('openingDate');
	}

	/**
	 * Set registration type opening date.
	 * @param $duration date
	 */
	function setOpeningDate($openingDate) {
		return $this->setData('openingDate', $openingDate);
	}

	/**
	 * Get registration type closing date.
	 * @return date
	 */
	function getClosingDate() {
		return $this->getData('closingDate');
	}

	/**
	 * Set registration type closing date.
	 * @param $duration date
	 */
	function setClosingDate($closingDate) {
		return $this->setData('closingDate', $closingDate);
	}

	/**
	 * Get registration type expiry date.
	 * @return date
	 */
	function getExpiryDate() {
		return $this->getData('expiryDate');
	}

	/**
	 * Set registration type expiry date.
	 * @param $duration date
	 */
	function setExpiryDate($expiryDate) {
		return $this->setData('expiryDate', $expiryDate);
	}

	/**
	 * Get registration type duration in years and months.
	 * @return string
	 */
	function getDurationYearsMonths() {
		$years = (int)floor($this->getData('duration')/12);
		$months = (int)fmod($this->getData('duration'), 12);
		$yearsMonths = '';

		if ($years == 1) {
			$yearsMonths = '1 ' . AppLocale::Translate('manager.registrationTypes.year');
		} elseif ($years > 1) {
			$yearsMonths = $years . ' ' . AppLocale::Translate('manager.registrationTypes.years');
		}

		if ($months == 1) {
			$yearsMonths .= $yearsMonths == ''  ? '1 ' : ' 1 ';
			$yearsMonths .= AppLocale::Translate('manager.registrationTypes.month'); 
		} elseif ($months > 1){
			$yearsMonths .= $yearsMonths == ''  ? $months . ' ' : ' ' . $months . ' ';
			$yearsMonths .= AppLocale::Translate('manager.registrationTypes.months');
		}

		return $yearsMonths;
	}

	/**
	 * Get registration access type.
	 * @return int
	 */
	function getAccess() {
		return $this->getData('access');
	}

	/**
	 * Set registration access type.
	 * @param $access int
	 */
	function setAccess($access) {
		return $this->setData('access', $access);
	}

	/**
	 * Get registration access type locale key.
	 * @return int
	 */
	function getAccessString() {
		switch ($this->getData('access')) {
			case REGISTRATION_TYPE_ACCESS_ONLINE:
				return 'manager.registrationTypes.access.online';
			case REGISTRATION_TYPE_ACCESS_PHYSICAL:
				return 'manager.registrationTypes.access.physical';
			case REGISTRATION_TYPE_ACCESS_BOTH:
				return 'manager.registrationTypes.access.both';
			default:
				return 'manager.registrationTypes.access';
		}
	}

	/**
	 * Check if this registration type is for an institution.
	 * @return boolean
	 */
	function getInstitutional() {
		return $this->getData('institutional');
	}

	/**
	 * Set whether or not this registration type is for an institution.
	 * @param $institutional boolean
	 */
	function setInstitutional($institutional) {
		return $this->setData('institutional', $institutional);
	}

	/**
	 * Check if this registration type requires a membership.
	 * @return boolean
	 */
	function getMembership() {
		return $this->getData('membership');
	}

	/**
	 * Set whether or not this registration type requires a membership.
	 * @param $membership boolean
	 */
	function setMembership($membership) {
		return $this->setData('membership', $membership);
	}

	/**
	 * Check if this registration type should be publicly visible.
	 * @return boolean
	 */
	function getPublic() {
		return $this->getData('public');
	}

	/**
	 * Set whether or not this registration should be publicly visible.
	 * @param $public boolean
	 */
	function setPublic($public) {
		return $this->setData('public', $public);
	}

	/**
	 * Get registration type display sequence.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set registration type display sequence.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Get registration type summary in the form: TypeName - Duration - Cost (CurrencyShort).
	 * @return string
	 */
	function getSummaryString() {
		$durationYearsMonths = $this->getDurationYearsMonths();
		return $this->getRegistrationTypeName() . (!empty($durationYearsMonths)?(' - ' . $durationYearsMonths):'') . ' - ' . sprintf('%.2f', $this->getCost()) . ' ' . $this->getCurrencyStringShort();
	}
}

?>
