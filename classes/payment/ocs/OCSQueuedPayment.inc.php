<?php

/**
 * @file classes/payment/ocs/OCSQueuedPayment.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OCSQueuedPayment
 * @ingroup payment
 *
 * @brief Queued payment data structure for OCS
 *
 */

//$Id$

import('lib.pkp.classes.payment.QueuedPayment');

class OCSQueuedPayment extends QueuedPayment {
	var $conferenceId;
	var $schedConfId;
	var $type;
	var $requestUrl;

	function setConferenceId($conferenceId) {
		$this->conferenceId = $conferenceId;
	}

	function getConferenceId() {
		return $this->conferenceId;
	}

	function setSchedConfId($schedConfId) {
		$this->schedConfId = $schedConfId;
	}

	function getSchedConfId() {
		return $this->schedConfId;
	}

	function setType($type) {
		$this->type = $type;
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
				return Locale::translate('schedConf.registration');
		}
	}

	/**
	 * Returns the name of the QueuedPayment.
	 * @return string
	 */
	function getDescription() {
		switch ($this->type) {
			case QUEUED_PAYMENT_TYPE_REGISTRATION:
				$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
				$registration =& $registrationDao->getRegistration($this->getAssocId());

				$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
				$registrationType =& $registrationTypeDao->getRegistrationType(
					$registration?$registration->getTypeId():0
				);

				$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
				$registrationOptions =& $registrationOptionDao->getRegistrationOptions($this->getAssocId());

				$options = '';
				foreach ($registrationOptions as $optionId) {
					$options .= ';' . $registrationOptionDao->getRegistrationOptionName($optionId);				
				}
				
				$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
				$schedConf =& $schedConfDao->getSchedConf(
					$registrationType?$registrationType->getSchedConfId():0
				);

				return Locale::translate('payment.type.conferenceRegistration', array(
					'schedConfTitle' => ($schedConf?$schedConf->getFullTitle():Locale::translate('common.none')),
					'registrationTypeName' => ($registrationType?$registrationType->getRegistrationTypeName():Locale::translate('common.none')),
				)) . $options;
		}
	}

	function setRequestUrl($url) {
		$this->requestUrl = $url;
	}

	function getRequestUrl() {
		return $this->requestUrl;
	}

	/**
	 * Return a useful identifier for this payment for use in correspondence.
	 */
	function getInvoiceId() {
		return $this->getSchedConfId() . '-' . $this->getUserId() . '-' . $this->getAssocId() . '-' . $this->getPaymentId();
	}
}

?>
