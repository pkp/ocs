<?php

/**
 * @file PaymentHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentHandler
 * @ingroup pages_payment
 *
 * @brief Handle requests for payment functions.
 *
 */

// $Id$


import('handler.Handler');

class PaymentHandler extends Handler {
	/**
	 * Constructor
	 **/
	function PaymentHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorSchedConf($this));		
	}

	/**
	 * Display scheduled conference view page.
	 */
	function plugin($args) {
		$this->validate();
		$this->setupTemplate();
		
		$paymentMethodPlugins =& PluginRegistry::loadCategory('paymethod');
		$paymentMethodPluginName = array_shift($args);
		if (empty($paymentMethodPluginName) || !isset($paymentMethodPlugins[$paymentMethodPluginName])) {
			Request::redirect(null, null, 'index');
		}

		$paymentMethodPlugin =& $paymentMethodPlugins[$paymentMethodPluginName];
		if (!$paymentMethodPlugin->isConfigured()) {
			Request::redirect(null, null, 'index');
		}

		$paymentMethodPlugin->handle($args);
	}

	/**
	 * Display a landing page for a received registration payment.
	 */
	function landing($args) {
		$this->validate();
		$this->setupTemplate();

		$user =& Request::getUser();
		$schedConf =& Request::getSchedConf();
		if (!$user || !$schedConf) Request::redirect(null, null, 'index');

		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$registrationId = $registrationDao->getRegistrationIdByUser($user->getId(), $schedConf->getId());
		$registration =& $registrationDao->getRegistration($registrationId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('message', ($registration && $registration->getDatePaid()) ? 'schedConf.registration.landingPaid' : 'schedConf.registration.landingUnpaid');
		$templateMgr->assign('backLink', Request::url(null, null, 'index'));
		$templateMgr->assign('backLinkLabel', 'common.continue');
		$templateMgr->display('common/message.tpl');
	}
}

?>
