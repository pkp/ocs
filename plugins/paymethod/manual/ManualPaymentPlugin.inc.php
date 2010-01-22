<?php

/**
 * @file ManualPaymentPlugin.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentPlugin
 * @ingroup plugins_paymethod_manual
 *
 * @brief Manual payment plugin class
 */

import('classes.plugins.PaymethodPlugin');

class ManualPaymentPlugin extends PaymethodPlugin {

	function getName() {
		return 'ManualPayment';
	}

	function getDisplayName() {
		return Locale::translate('plugins.paymethod.manual.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.paymethod.manual.description');
	}   

	function register($category, $path) {
		if (parent::register($category, $path)) {			
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	function getSettingsFormFieldNames() {
		return array();
	}

	function isConfigured() {
		$schedConf =& Request::getSchedConf();
		if (!$schedConf) return false;

		// Make sure that all settings form fields have been filled in
		foreach ($this->getSettingsFormFieldNames() as $settingName) {
			$setting = $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $settingName);
			if (empty($setting)) return false;
		}
		return true;
	}

	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		if (!$this->isConfigured()) return false;
		$schedConf =& Request::getSchedConf();
		$templateMgr =& TemplateManager::getManager();
		$user =& Request::getUser();

		/* FIXME: This is too specific to registration payments. */
		$templateMgr->assign('message', $schedConf->getLocalizedSetting('registrationAdditionalInformation'));

		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');

		import('mail.MailTemplate');
		$contactName = $schedConf->getSetting('registrationName');
		$contactEmail = $schedConf->getSetting('registrationEmail');
		$mail = &new MailTemplate('MANUAL_PAYMENT_NOTIFICATION');
		$mail->setFrom($contactEmail, $contactName);
		$mail->addRecipient($contactEmail, $contactName);
		$mail->assignParams(array(
			'schedConfName' => $schedConf->getFullTitle(),
			'userFullName' => $user?$user->getFullName():('(' . Locale::translate('common.none') . ')'),
			'userName' => $user?$user->getUsername():('(' . Locale::translate('common.none') . ')'),
			'itemDescription' => $queuedPayment->getDescription(),
			'itemCost' => $queuedPayment->getAmount(),
			'itemCurrencyCode' => $queuedPayment->getCurrencyCode()
		));
		$mail->send();
	}

	function getInstallDataFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'data.xml');
	}
}

?>
