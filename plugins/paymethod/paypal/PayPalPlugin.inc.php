<?php

/**
 * PayPalPlugin.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * PayPal plugin class
 *
 */

import('classes.plugins.PaymethodPlugin');

class PayPalPlugin extends PaymethodPlugin {

	function getName() {
		return 'Paypal';
	}
	
	function getDisplayName() {
		return Locale::translate('plugins.paymethod.paypal.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.paymethod.paypal.description');
	}   

	function register($category, $path) {
		if (parent::register($category, $path)) {			
			$this->addLocaleData();
			$this->import('PayPalDAO');
			$payPalDao =& new PayPalDAO();
			DAORegistry::registerDAO('PayPalDAO', $payPalDao);
			return true;
		}
		return false;
	}

	function getSettingsFormFieldNames() {
		return array('paypalurl', 'selleraccount');
	}

	function isCurlInstalled() {
		return (function_exists('curl_init'));
	}

	function isConfigured() {
		$schedConf =& Request::getSchedConf();
		if (!$schedConf) return false;

		// Make sure CURL support is included.
		if (!$this->isCurlInstalled()) return false;

		// Make sure that all settings form fields have been filled in
		foreach ($this->getSettingsFormFieldNames() as $settingName) {
			$setting = $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $settingName);
			if (empty($setting)) return false;
		}
		return true;
	}

	function displayPaymentSettingsForm(&$params, &$smarty) {
		$smarty->assign('isCurlInstalled', $this->isCurlInstalled());
		return parent::displayPaymentSettingsForm($params, $smarty);
	}

	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		if (!$this->isConfigured()) return false;
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();

		$params = array(
			'business' => $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), 'selleraccount'),
			'item_name' => $queuedPayment->getDescription(),
			'amount' => $queuedPayment->getAmount(),
			'quantity' => 1,
			'no_note' => 1,
			'no_shipping' => 1,
			'currency_code' => $queuedPayment->getCurrencyCode(),
			'lc' => String::substr(Locale::getLocale(), 3), 
			'custom' => $queuedPaymentId,
			'notify_url' => Request::url(null, null, 'payment', 'plugin', array($this->getName(), 'ipn')),  
			'return' => Request::url(null, null, 'payment', 'plugin', array($this->getName(), 'return')),
			'cancel_return' => Request::url(null, null, 'payment', 'plugin', array($this->getName(), 'cancel')),
			'first_name' => ($user)?$user->getFirstName():'',  
			'last_name' => ($user)?$user->getLastname():'',
			'item_number' => 1,
			'cmd' => '_xclick'
		);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('params', $params);
		$templateMgr->assign('paypalFormUrl', $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), 'paypalurl'));
		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');
	}

	/**
	 * Handle incoming requests/notifications
	 */
	function handle($args) {
		$templateMgr =& TemplateManager::getManager();
		$schedConf =& Request::getSchedConf();
		if (!$schedConf) return parent::handle($args);

		// Just in case we need to contact someone
		import('mail.MailTemplate');
		$contactName = $schedConf->getSetting('contactName');
		$contactEmail = $schedConf->getSetting('contactEmail');
		$mail = &new MailTemplate('PAYPAL_INVESTIGATE_PAYMENT');
		$mail->setFrom($contactEmail, $contactName);
		$mail->addRecipient($contactEmail, $contactName);

		$paymentStatus = Request::getUserVar('payment_status');

		switch (array_shift($args)) {
			case 'ipn':
				// Build a confirmation transaction.
				$req = 'cmd=_notify-validate';
				foreach ($_POST as $key => $value) $req .= '&' . urlencode($key) . '=' . urlencode($value);

				// Create POST response
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), 'paypalurl'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-Type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($req)));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
				$ret = curl_exec ($ch);
				curl_close ($ch);

				// Check the confirmation response and handle as necessary.
				if (strcmp($ret, 'VERIFIED') == 0) switch ($paymentStatus) {
					case 'Completed':
						$payPalDao =& DAORegistry::getDAO('PayPalDAO');
						$transactionId = Request::getUserVar('txn_id');
						if ($payPalDao->transactionExists($transactionId)) {
							// A duplicate transaction was received; notify someone.
							$mail->assignParams(array(
								'schedConfName' => $schedConf->getFullTitle(),
								'postInfo' => print_r($_POST, true),
								'additionalInfo' => "Duplicate transaction ID: $transactionId",
								'serverVars' => print_r($_SERVER, true)
							));
							$mail->send();
							exit();
						} else {
							// New transaction succeeded. Record it.
							$payPalDao->insertTransaction(
								$transactionId,
								Request::getUserVar('txn_type'),
								Request::getUserVar('payer_email'),
								Request::getUserVar('receiver_email'),
								Request::getUserVar('item_number'),
								Request::getUserVar('payment_date'),
								Request::getUserVar('payer_id'),
								Request::getUserVar('receiver_id')
							);
							$queuedPaymentId = Request::getUserVar('custom');

							import('payment.ocs.OCSPaymentManager');
							$ocsPaymentManager =& OCSPaymentManager::getManager();

							// Verify the cost and user details as per PayPal spec.
							$queuedPayment =& $ocsPaymentManager->getQueuedPayment($queuedPaymentId);
							if (!$queuedPayment) {
								// The queued payment entry is missing. Complain.
								$mail->assignParams(array(
									'schedConfName' => $schedConf->getFullTitle(),
									'postInfo' => print_r($_POST, true),
									'additionalInfo' => "Missing queued payment ID: $queuedPaymentId",
									'serverVars' => print_r($_SERVER, true)
								));
								$mail->send();
								exit();
							}

							if (
								($queuedAmount = $queuedPayment->getAmount()) != ($grantedAmount = Request::getUserVar('mc_gross')) ||
								($queuedCurrency = $queuedPayment->getCurrencyCode()) != ($grantedCurrency = Request::getUserVar('mc_currency')) ||
								($grantedEmail = Request::getUserVar('receiver_email')) != ($queuedEmail = $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), 'selleraccount'))
							) {
								// The integrity checks for the transaction failed. Complain.
								$mail->assignParams(array(
									'schedConfName' => $schedConf->getFullTitle(),
									'postInfo' => print_r($_POST, true),
									'additionalInfo' =>
										"Granted amount: $grantedAmount\n" .
										"Queued amount: $queuedAmount\n" .
										"Granted currency: $grantedCurrency\n" .
										"Queued currency: $queuedCurrency\n" .
										"Granted to PayPal account: $grantedEmail\n" .
										"Configured PayPal account: $queuedEmail",
									'serverVars' => print_r($_SERVER, true)
								));
								$mail->send();
								exit();
							}

							// Fulfill the queued payment.
							if ($ocsPaymentManager->fulfillQueuedPayment($queuedPayment)) exit();
							
							// If we're still here, it means the payment couldn't be fulfilled.
							$mail->assignParams(array(
								'schedConfName' => $schedConf->getFullTitle(),
								'postInfo' => print_r($_POST, true),
								'additionalInfo' => "Queued payment ID $queuedPaymentId could not be fulfilled.",
								'serverVars' => print_r($_SERVER, true)
							));
							$mail->send();
						}
						exit();
					case 'Pending':
						// Ignore.
						exit();
					default:
						// An unhandled payment status was received; notify someone.
						$mail->assignParams(array(
							'schedConfName' => $schedConf->getFullTitle(),
							'postInfo' => print_r($_POST, true),
							'additionalInfo' => "Payment status: $paymentStatus",
							'serverVars' => print_r($_SERVER, true)
						));
						$mail->send();
						exit();
				} else {
					// An unknown confirmation response was received; notify someone.
					$mail->assignParams(array(
						'schedConfName' => $schedConf->getFullTitle(),
						'postInfo' => print_r($_POST, true),
						'additionalInfo' => "Confirmation return: $ret",
						'serverVars' => print_r($_SERVER, true)
					));
					$mail->send();
					exit();
				}

				break;
			case 'cancel':
				$templateMgr->assign(array(
					'currentUrl' => Request::url(null, null, 'index'),
					'pageTitle' => 'plugins.paymethod.paypal.purchase.cancelled.title',
					'message' => 'plugins.paymethod.paypal.purchase.cancelled'
				));
				$templateMgr->display('common/message.tpl');
				exit();
				break;
			case 'return':
				echo 'RETURN';exit();
				break;
		}
		parent::handle($args); // Don't know what to do with it
	}

	function getInstallSchemaFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'schema.xml');
	}

	function getInstallDataFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'data.xml');
	}
}

?>
