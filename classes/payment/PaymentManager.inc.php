<?php

/**
 * @defgroup payment
 */
 
/**
 * @file PaymentManager.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentManager
 * @ingroup payment
 *
 * @brief Provides payment management functions.
 */

//$Id$

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
	function queuePayment(&$queuedPayment, $expiryDate) {
		if (!$this->isConfigured()) return false;

		$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
		$queuedPaymentId = $queuedPaymentDao->insertQueuedPayment($queuedPayment, $expiryDate);

		// Perform periodic cleanup
		if (time() % 100 == 0) $queuedPaymentDao->deleteExpiredQueuedPayments();

		return $queuedPaymentId;
	}

	function &getPaymentPlugin() {
		$returnValue = null;
		return $returnValue; // Abstract method; subclasses should implement
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

	/**
	 * Fulfill a queued payment.
	 * @param $queuedPaymentId int
	 * @param $queuedPayment object
	 */
	function fulfillQueuedPayment($queuedPaymentId, &$queuedPayment) {
		fatalError('ABSTRACT CLASS');
	}
}

?>
