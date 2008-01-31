<?php

/**
 * @file OCSPaymentManager.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package payment
 * @class OCSPaymentManager
 *
 * Provides payment management functions.
 *
 * $Id$
 */

import('payment.ocs.OCSQueuedPayment');
import('payment.PaymentManager');

define('QUEUED_PAYMENT_TYPE_REGISTRATION',	0x000000001);

class OCSPaymentManager extends PaymentManager {
	function &getManager() {
		static $manager;
		if (!isset($manager)) {
			$manager =& new OCSPaymentManager();
		}
		return $manager;
	}

	function &createQueuedPayment($conferenceId, $schedConfId, $type, $userId, $assocId, $amount, $currencyCode) {
		$payment =& new OCSQueuedPayment($amount, $currencyCode, $userId, $assocId);
		$payment->setConferenceId($conferenceId);
		$payment->setSchedConfId($schedConfId);
		$payment->setType($type);
		return $payment;
	}

	function &getPaymentPlugin() {
		$schedConf =& Request::getSchedConf();
		$paymentMethodPluginName = $schedConf->getSetting('paymentMethodPluginName');
		$paymentMethodPlugin = null;
		if (!empty($paymentMethodPluginName)) {
			$plugins =& PluginRegistry::loadCategory('paymethod');
			if (isset($plugins[$paymentMethodPluginName])) $paymentMethodPlugin =& $plugins[$paymentMethodPluginName];
		}
		return $paymentMethodPlugin;
	}

	/**
	 * Fulfill a queued payment.
	 * @param $queuedPaymentId int
	 * @param $queuedPayment object
	 */
	function fulfillQueuedPayment($queuedPaymentId, &$queuedPayment) {
		if ($queuedPayment) switch ($queuedPayment->getType()) {
			case QUEUED_PAYMENT_TYPE_REGISTRATION:
				$registrationId = $queuedPayment->getAssocId();
				$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
				$registration =& $registrationDao->getRegistration($registrationId);
				if (!$registration || $registration->getUserId() != $queuedPayment->getUserId() || $registration->getSchedConfId() != $queuedPayment->getSchedConfId()) {error_log(print_r($registration, true)); return false;}

				$registration->setDatePaid(Core::getCurrentDate());
				$registrationDao->updateRegistration($registration);

				$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
				$queuedPaymentDao->deleteQueuedPayment($queuedPaymentId);
				return true;
		}
		return false;
	}
}

?>
