<?php

/**
 * @defgroup pages_payment
 */
 
/**
 * @file pages/payment/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle requests for interactions between the payment system and external
 * sites/systems.
 *
 * @ingroup pages_payment
 */

//$Id$


switch ($op) {
	case 'plugin':
	case 'landing':
		define('HANDLER_CLASS', 'PaymentHandler');
		import('pages.payment.PaymentHandler');
		break;
}

?>
