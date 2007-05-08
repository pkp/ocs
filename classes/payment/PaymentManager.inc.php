<?php

/**
 * PaymentManager.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package payment
 *
 * Provides payment management functions.
 *
 * $Id$
 */

class PaymentManager {
	/**
	 * Get the payment manager.
	 */
	function &getManager() {
		die('ABSTRACT METHOD');
	}

	/**
	 * Queue a payment for receipt.
	 */
	function queuePayment(&$queuedPayment) {
		if (!$this->isConfigured()) return false;

		$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
		$queuedPaymentId = $queuedPaymentDao->insertQueuedPayment($queuedPayment);
		return $queuedPaymentId;
	}

	function &getPaymentPlugin() {
		$returnValue = null;
		return $returnValue; // Abstract method; subclasses should impl
	}

	function isConfigured() {
		$paymentPlugin =& $this->getPaymentPlugin();
		if ($paymentPlugin !== null) return $paymentPlugin->isConfigured();
		return false;
	}

	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		$paymentPlugin =& $this->getPaymentPlugin();
		if ($paymentPlugin !== null && $paymentPlugin->isConfigured()) return $paymentPlugin->displayPaymentForm($queuedPaymentId, $queuedPayment);
		return false;
	}

	function displayConfigurationForm() {
		$paymentPlugin =& $this->getPaymentPlugin();
		if ($paymentPlugin !== null && $paymentPlugin->isConfigured()) return $paymentPlugin->displayConfigurationForm();
		return false;
	}

	function &getQueuedPayment($queuedPaymentId) {
		$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
		$queuedPayment =& $queuedPaymentDao->getQueuedPayment($queuedPaymentId);
		return $queuedPayment;
	}

	function fulfillQueuedPayment(&$queuedPayment) {
		fatalError('ABSTRACT CLASS');
	}
}

?>
