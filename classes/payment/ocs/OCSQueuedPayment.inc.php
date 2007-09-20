<?php

/**
 * @file OCSQueuedPayment.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package payment
 * @class OCSQueuedPayment
 *
 * Queued payment data structure for OCS
 *
 * $Id$
 */

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
				return Locale::translate('payment.type.registration');
		}
	}
}

?>
