<?php

/**
 * @file plugins/paymethod/manual/ManualPaymentPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentPlugin
 * @ingroup plugins_paymethod_manual
 *
 * @brief Manual payment plugin class
 *
 */

//$Id$

import('classes.plugins.PaymethodPlugin');

class ManualPaymentPlugin extends PaymethodPlugin {

	function getName() {
		return 'ManualPayment';
	}

	function getDisplayName() {
		return __('plugins.paymethod.manual.displayName');
	}

	function getDescription() {
		return __('plugins.paymethod.manual.description');
	}

	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	function getSettingsFormFieldNames() {
		return array('manualInstructions');
	}

	function isConfigured() {
		$schedConf =& Request::getSchedConf();
		if (!$schedConf) return false;

		// Make sure that all settings form fields have been filled in
		foreach ($this->getSettingsFormFieldNames() as $settingName) {
			$setting = $this->getSetting($schedConf->getConferenceId(), $schedConf->getId(), $settingName);
			if (empty($setting)) return false;
		}

		return true;
	}

	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		if (!$this->isConfigured()) return false;
		$schedConf =& Request::getSchedConf();
		$templateMgr =& TemplateManager::getManager();
		$user =& Request::getUser();

		$templateMgr->assign('itemName', $queuedPayment->getName());
		$templateMgr->assign('itemDescription', $queuedPayment->getDescription());
		if ($queuedPayment->getAmount() > 0) {
			$templateMgr->assign('itemAmount', $queuedPayment->getAmount());
			$templateMgr->assign('itemCurrencyCode', $queuedPayment->getCurrencyCode());
		}
		$templateMgr->assign('manualInstructions', $this->getSetting($schedConf->getConferenceId(), $schedConf->getId(), 'manualInstructions'));
		$templateMgr->assign('queuedPaymentId', $queuedPaymentId);

		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');
	}

	/**
	 * Handle incoming requests/notifications
	 */
	function handle($args) {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$templateMgr =& TemplateManager::getManager();
		$user =& Request::getUser();
		$op = isset($args[0])?$args[0]:null;
		$queuedPaymentId = isset($args[1])?((int) $args[1]):0;

		import('payment.ocs.OCSPaymentManager');
		$ocsPaymentManager =& OCSPaymentManager::getManager();
		$queuedPayment =& $ocsPaymentManager->getQueuedPayment($queuedPaymentId);
		// if the queued payment doesn't exist, redirect away from payments
		if ( !$queuedPayment ) Request::redirect(null, null, null, 'index');

		switch ( $op ) {
			case 'notify':
				import('mail.MailTemplate');
				AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
				$contactName = $schedConf->getSetting('registrationName');
				$contactEmail = $schedConf->getSetting('registrationEmail');
				$mail = new MailTemplate('MANUAL_PAYMENT_NOTIFICATION');
				$mail->setFrom($contactEmail, $contactName);
				$mail->addRecipient($contactEmail, $contactName);
				$mail->assignParams(array(
					'schedConfName' => $schedConf->getFullTitle(),
					'userFullName' => $user?$user->getFullName():('(' . __('common.none') . ')'),
					'userName' => $user?$user->getUsername():('(' . __('common.none') . ')'),
					'itemName' => $queuedPayment->getName(),
					'itemCost' => $queuedPayment->getAmount(),
					'itemCurrencyCode' => $queuedPayment->getCurrencyCode()
				));
				$mail->send();

				$templateMgr->assign(array(
					'currentUrl' => Request::url(null, null, null, 'payment', 'plugin', array('notify', $queuedPaymentId)),
					'pageTitle' => 'plugins.paymethod.manual.paymentNotification',
					'message' => 'plugins.paymethod.manual.notificationSent',
					'backLink' => Request::url(null, null, 'index'),
					'backLinkLabel' => 'common.continue'
				));
				$templateMgr->display('common/message.tpl');
				exit();
				break;
		}
		parent::handle($args); // Don't know what to do with it
	}

	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}
}

?>
