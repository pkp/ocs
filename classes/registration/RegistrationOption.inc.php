<?php

/**
 * @file RegistrationOption.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationOption
 * @ingroup registration 
 * @see RegistrationOptionDAO
 *
 * @brief Basic class describing a registration option.
 */

//$Id$

/**
 * Registration access options
 */
 
define('REGISTRATION_OPTION_YEAR_OFFSET_FUTURE',	'+10');

class RegistrationOption extends DataObject {

	function RegistrationOption() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the registration option.
	 * @return int
	 */
	function getOptionId() {
		return $this->getData('optionId');
	}

	/**
	 * Set the ID of the registration option.
	 * @param $optionId int
	 */
	function setOptionId($optionId) {
		return $this->setData('optionId', $optionId);
	}

	/**
	 * Get the scheduled conference ID of the registration option.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set the scheduled conference ID of the registration option.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get the localized registration option name
	 * @return string
	 */
	function getRegistrationOptionName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get registration option name.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set registration option name.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get registration option code.
	 * @return string
	 */
	function getCode() {
		return $this->getData('code');
	}

	/**
	 * Set registration option code.
	 * @param $optionCode string
	 */
	function setCode($code) {
		return $this->setData('code', $code);
	}

	/**
	 * Get the localized registration option description
	 * @return string
	 */
	function getRegistrationOptionDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get registration option description.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set registration option description.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}

	/**
	 * Get registration option currency string.
	 * @return int
	 */
	function getCurrencyString() {
		$currencyDao =& DAORegistry::getDAO('CurrencyDAO');
		$currency =& $currencyDao->getCurrencyByAlphaCode($this->getData('currencyCodeAlpha'));

		if ($currency != null) {
			return $currency->getName();
		} else {
			return 'manager.registrationOptions.currency';
		}
	}

	/**
	 * Get registration option currency abbreviated string.
	 * @return int
	 */
	function getCurrencyStringShort() {
		$currencyDao =& DAORegistry::getDAO('CurrencyDAO');
		$currency =& $currencyDao->getCurrencyByAlphaCode($this->getData('currencyCodeAlpha'));

		if ($currency != null) {
			return $currency->getCodeAlpha();
		} else {
			return 'manager.registrationOptions.currency';
		}
	}

	/**
	 * Get registration option opening date.
	 * @return date
	 */
	function getOpeningDate() {
		return $this->getData('openingDate');
	}

	/**
	 * Set registration option opening date.
	 * @param $duration date
	 */
	function setOpeningDate($openingDate) {
		return $this->setData('openingDate', $openingDate);
	}

	/**
	 * Get registration option closing date.
	 * @return date
	 */
	function getClosingDate() {
		return $this->getData('closingDate');
	}

	/**
	 * Set registration option closing date.
	 * @param $duration date
	 */
	function setClosingDate($closingDate) {
		return $this->setData('closingDate', $closingDate);
	}

	/**
	 * Check if this registration option should be publicly visible.
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
	 * Get registration option display sequence.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set registration option display sequence.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}
}

?>