<?php

/**
 * @file OCSQueuedPayment.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OCSQueuedPayment
 * @ingroup payment
 *
 * @brief Queued payment data structure for OCS
 */

//$Id$

import('payment.QueuedPayment');

class OCSQueuedPayment extends QueuedPayment {
	var $conferenceId;

	var $schedConfId;

	var $paperId;

	var $type;

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

	function setPaperId($paperId) {
		$this->paperId = $paperId;
	}

	function getPaperId() {
		return $this->paperId;
	}

	function setType($type) {
		$this->type = $type;
	}

	function getType() {
		return $this->type;
	}

	function getDescription() {
		switch ($this->type) {
			case QUEUED_PAYMENT_TYPE_REGISTRATION:
				$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
				$registration =& $registrationDao->getRegistration($this->getAssocId());

				$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
				$registrationType =& $registrationTypeDao->getRegistrationType(
					$registration?$registration->getTypeId():0
				);

				$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
				$schedConf =& $schedConfDao->getSchedConf(
					$registrationType?$registrationType->getSchedConfId():0
				);

				return Locale::translate('payment.type.conferenceRegistration', array(
					'schedConfTitle' => ($schedConf?$schedConf->getFullTitle():Locale::translate('common.none')),
					'registrationTypeName' => ($registrationType?$registrationType->getRegistrationTypeName():Locale::translate('common.none')),
				));
		}
	}
}

?>
