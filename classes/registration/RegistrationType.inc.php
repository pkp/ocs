<?php

/**
 * RegistrationType.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package registration 
 *
 * Registration type class.
 * Basic class describing a registration type.
 *
 * $Id$
 */

/**
 * Registration type formats
 */
define('REGISTRATION_TYPE_FORMAT_ONLINE',		0x01); 
define('REGISTRATION_TYPE_FORMAT_PRINT',		0x10);
define('REGISTRATION_TYPE_FORMAT_PRINT_ONLINE',	0x11);


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
	 * Get the event ID of the registration type.
	 * @return int
	 */
	function getEventId() {
		return $this->getData('eventId');
	}
	
	/**
	 * Set the event ID of the registration type.
	 * @param $eventId int
	 */
	function setEventId($eventId) {
		return $this->setData('eventId', $eventId);
	}
	
	/**
	 * Get registration type name.
	 * @return string
	 */
	function getTypeName() {
		return $this->getData('typeName');
	}
	
	/**
	 * Set registration type name.
	 * @param $typeName string
	 */
	function setTypeName($typeName) {
		return $this->setData('typeName', $typeName);
	}

	/**
	 * Get registration type description.
	 * @return string
	 */
	function getDescription() {
		return $this->getData('description');
	}
	
	/**
	 * Set registration type description.
	 * @param $description string
	 */
	function setDescription($description) {
		return $this->setData('description', $description);
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
		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currency =& $currencyDao->getCurrencyByAlphaCode($this->getData('currencyCodeAlpha'));

		if ($currency != null) {
			return $currency->getName();
		} else {
			return 'director.registrationTypes.currency';
		}
	}

	/**
	 * Get registration type currency abbreviated string.
	 * @return int
	 */
	function getCurrencyStringShort() {
		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currency =& $currencyDao->getCurrencyByAlphaCode($this->getData('currencyCodeAlpha'));

		if ($currency != null) {
			return $currency->getCodeAlpha();
		} else {
			return 'director.registrationTypes.currency';
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
			$yearsMonths = '1 ' . Locale::Translate('director.registrationTypes.year');
		} elseif ($years > 1) {
			$yearsMonths = $years . ' ' . Locale::Translate('director.registrationTypes.years');
		}

		if ($months == 1) {
			$yearsMonths .= $yearsMonths == ''  ? '1 ' : ' 1 ';
			$yearsMonths .= Locale::Translate('director.registrationTypes.month'); 
		} elseif ($months > 1){
			$yearsMonths .= $yearsMonths == ''  ? $months . ' ' : ' ' . $months . ' ';
			$yearsMonths .= Locale::Translate('director.registrationTypes.months');
		}

		return $yearsMonths;
	}

	/**
	 * Get registration type format.
	 * @return int
	 */
	/*function getFormat() {
		return $this->getData('format');
	}*/
	
	/**
	 * Set registration type format.
	 * @param $format int
	 */
	/*function setFormat($format) {
		return $this->setData('format', $format);
	}*/

	/**
	 * Get registration type format locale key.
	 * @return int
	 */
	/*function getFormatString() {
		switch ($this->getData('format')) {
			case REGISTRATION_TYPE_FORMAT_ONLINE:
				return 'director.registrationTypes.format.online';
			case REGISTRATION_TYPE_FORMAT_PRINT:
				return 'director.registrationTypes.format.print';
			case REGISTRATION_TYPE_FORMAT_PRINT_ONLINE:
				return 'director.registrationTypes.format.printOnline';
			default:
				return 'director.registrationTypes.format';
		}
	}*/

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
		return $this->getTypeName() . ' - ' . $this->getDurationYearsMonths() . ' - ' . sprintf('%.2f', $this->getCost()) . ' ' . $this->getCurrencyStringShort();
	}
}

?>
