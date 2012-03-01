<?php

/**
 * @file PayPalPlugin.inc.php
 *
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PayPalPlugin
 * @ingroup plugins_paymethod_paypal
 * @see PayPalDAO, PayPalPaymentForm, PayPalSettingsForm
 *
 * @brief PayPal plugin class
 *
 */

import('classes.plugins.PaymethodPlugin');

class PayPalPlugin extends PaymethodPlugin {

	function getName() {
		return 'Paypal';
	}

	function getDisplayName() {
		return __('plugins.paymethod.paypal.displayName');
	}

	function getDescription() {
		return __('plugins.paymethod.paypal.description');
	}

	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			$this->import('PayPalDAO');
			$payPalDao = new PayPalDAO();
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
			$setting = $this->getSetting($schedConf->getConferenceId(), $schedConf->getId(), $settingName);
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
			'charset' => Config::getVar('i18n', 'client_charset'),
			'business' => $this->getSetting($schedConf->getConferenceId(), $schedConf->getId(), 'selleraccount'),
			'item_name' => $queuedPayment->getDescription(),
			'amount' => $queuedPayment->getAmount(),
			'quantity' => 1,
			'no_note' => 1,
			'no_shipping' => 1,
			'currency_code' => $queuedPayment->getCurrencyCode(),
			'lc' => String::substr(AppLocale::getLocale(), 3),
			'custom' => $queuedPaymentId,
			'notify_url' => Request::url(null, null, 'payment', 'plugin', array($this->getName(), 'ipn')),
			'return' => $queuedPayment->getRequestUrl(),
			'cancel_return' => Request::url(null, null, 'payment', 'plugin', array($this->getName(), 'cancel')),
			'first_name' => ($user)?$user->getFirstName():'',
			'last_name' => ($user)?$user->getLastname():'',
			'item_number' => 1,
			'cmd' => '_xclick'
		);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('params', $params);
		$templateMgr->assign('paypalFormUrl', $this->getSetting($schedConf->getConferenceId(), $schedConf->getId(), 'paypalurl'));
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
		$mail = new MailTemplate('PAYPAL_INVESTIGATE_PAYMENT');
		$mail->setFrom($contactEmail, $contactName);
		$mail->addRecipient($contactEmail, $contactName);

		$paymentStatus = Request::getUserVar('payment_status');

		switch (array_shift($args)) {
			case 'ipn':
				// Build a confirmation transaction.
				$req = 'cmd=_notify-validate';
				if (get_magic_quotes_gpc()) {
					foreach ($_POST as $key => $value) $req .= '&' . urlencode(stripslashes($key)) . '=' . urlencode(stripslashes($value));
				} else {
					foreach ($_POST as $key => $value) $req .= '&' . urlencode($key) . '=' . urlencode($value);
				}

				// Create POST response
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->getSetting($schedConf->getConferenceId(), $schedConf->getId(), 'paypalurl'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-Type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($req)));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
				$ret = curl_exec ($ch);
				$curlError = curl_error($ch);
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
								($grantedEmail = Request::getUserVar('receiver_email')) != ($queuedEmail = $this->getSetting($schedConf->getConferenceId(), $schedConf->getId(), 'selleraccount'))
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
							if ($ocsPaymentManager->fulfillQueuedPayment($queuedPaymentId, $queuedPayment)) {
								// Send the registrant a notification that their payment was received
								$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');

								// Get registrant name and email
								$userDao =& DAORegistry::getDAO('UserDAO');
								$user =& $userDao->getUser($queuedPayment->getuserId());
								$registrantName = $user->getFullName();
								$registrantEmail = $user->getEmail();

								// Get conference contact details
								$schedConfId = $schedConf->getId();
								$registrationName = $schedConfSettingsDao->getSetting($schedConfId, 'registrationName');
								$registrationEmail = $schedConfSettingsDao->getSetting($schedConfId, 'registrationEmail');
								$registrationPhone = $schedConfSettingsDao->getSetting($schedConfId, 'registrationPhone');
								$registrationFax = $schedConfSettingsDao->getSetting($schedConfId, 'registrationFax');
								$registrationMailingAddress = $schedConfSettingsDao->getSetting($schedConfId, 'registrationMailingAddress');
								$registrationContactSignature = $registrationName;

								if ($registrationMailingAddress != '') $registrationContactSignature .= "\n" . $registrationMailingAddress;
								if ($registrationPhone != '') $registrationContactSignature .= "\n" . AppLocale::Translate('user.phone') . ': ' . $registrationPhone;
								if ($registrationFax != '')	$registrationContactSignature .= "\n" . AppLocale::Translate('user.fax') . ': ' . $registrationFax;

								$registrationContactSignature .= "\n" . AppLocale::Translate('user.email') . ': ' . $registrationEmail;

								$paramArray = array(
									'registrantName' => $registrantName,
									'conferenceName' => $schedConf->getFullTitle(),
									'registrationContactSignature' => $registrationContactSignature
								);

								import('mail.MailTemplate');
								$mail = new MailTemplate('MANUAL_PAYMENT_RECEIVED');
								$mail->setFrom($registrationEmail, $registrationName);
								$mail->assignParams($paramArray);
								$mail->addRecipient($registrantEmail, $registrantName);
								$mail->send();

								exit();
							}

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
						'additionalInfo' => "Confirmation return: $ret\nCURL error: $curlError",
						'serverVars' => print_r($_SERVER, true)
					));
					$mail->send();
					exit();
				}

				break;
			case 'cancel':
				Handler::setupTemplate();
				$templateMgr->assign(array(
					'currentUrl' => Request::url(null, null, 'index'),
					'pageTitle' => 'plugins.paymethod.paypal.purchase.cancelled.title',
					'message' => 'plugins.paymethod.paypal.purchase.cancelled'
				));
				$templateMgr->display('common/message.tpl');
				exit();
				break;
		}
		parent::handle($args); // Don't know what to do with it
	}

	function getInstallSchemaFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'schema.xml');
	}

	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}
}

?>
