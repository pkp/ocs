<?php

/**
 * @file classes/payment/ocs/OCSQueuedPayment.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OCSQueuedPayment
 * @ingroup payment
 *
 * @brief Queued payment data structure for OCS
 *
 */

import('lib.pkp.classes.payment.QueuedPayment');

class OCSQueuedPayment extends QueuedPayment {
	/** @var $conferenceId int Conference ID */
	var $conferenceId;

	/** @var $schedConfId int Scheduled conference ID */
	var $schedConfId;

	/** @var $type int PAYMENT_TYPE_... */
	var $type;

	/** @var $requestUrl string Request URL */
	var $requestUrl;

	/**
	 * Set the conference ID
	 * @param $conferenceId int Conference ID
	 * @return New conference ID
	 */
	function setConferenceId($conferenceId) {
		return $this->conferenceId = $conferenceId;
	}

	/**
	 * Get the conference ID
	 * @return int
	 */
	function getConferenceId() {
		return $this->conferenceId;
	}

	/**
	 * Set the scheduled conference ID
	 * @param $schedConfId int Scheduled conference ID
	 * @return New scheduled conference ID
	 */
	function setSchedConfId($schedConfId) {
		return $this->schedConfId = $schedConfId;
	}

	/**
	 * Get the scheduled conference ID
	 * @return int
	 */
	function getSchedConfId() {
		return $this->schedConfId;
	}

	/**
	 * Set the payment type.
	 * @param $type int PAYMENT_TYPE_...
	 * @return int New PAYMENT_TYPE_...
	 */
	function setType($type) {
		return $this->type = $type;
	}

	function getType() {
		return $this->type;
	}

	/**
	 * Returns the name of the QueuedPayment.
	 * @return string
	 */
	function getName() {
		switch ($this->type) {
			case QUEUED_PAYMENT_TYPE_REGISTRATION:
				return __('schedConf.registration');
			default:
				// Invalid payment type.
				assert(false);
		}
	}

	/**
	 * Returns the name of the QueuedPayment.
	 * @return string
	 */
	function getDescription() {
		switch ($this->type) {
			case QUEUED_PAYMENT_TYPE_REGISTRATION:
				$registrationDao = DAORegistry::getDAO('RegistrationDAO');
				$registration =& $registrationDao->getRegistration($this->getAssocId());

				$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
				$registrationType =& $registrationTypeDao->getRegistrationType(
					$registration?$registration->getTypeId():0
				);

				$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
				$registrationOptions =& $registrationOptionDao->getRegistrationOptions($this->getAssocId());

				$options = '';
				foreach ($registrationOptions as $optionId) {
					$options .= ';' . $registrationOptionDao->getRegistrationOptionName($optionId);				
				}
				
				$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
				$schedConf = $schedConfDao->getById(
					$registrationType?$registrationType->getSchedConfId():0
				);

				return __('payment.type.conferenceRegistration', array(
					'schedConfTitle' => ($schedConf?$schedConf->getLocalizedName():__('common.none')),
					'registrationTypeName' => ($registrationType?$registrationType->getRegistrationTypeName():__('common.none')),
				)) . $options;
			default:
				// Invalid payment type.
				assert(false);
		}
	}

	/**
	 * Set the request URL.
	 * @param $url string Request URL
	 * @return string New request URL
	 */
	function setRequestUrl($url) {
		return $this->requestUrl = $url;
	}

	/**
	 * Get the request URL.
	 * @return string
	 */
	function getRequestUrl() {
		return $this->requestUrl;
	}

	/**
	 * Return a useful identifier for this payment for use in
	 * correspondence.
	 * @return string
	 */
	function getInvoiceId() {
		return $this->getSchedConfId() . '-' . $this->getUserId() . '-' . $this->getAssocId() . '-' . $this->getPaymentId();
	}
}

?>
