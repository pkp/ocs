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

import('classes.handler.Handler');

class PaymentHandler extends Handler {
	/**
	 * Constructor
	 */
	function PaymentHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorSchedConf($this));		
	}

	/**
	 * Display scheduled conference view page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function plugin($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);
		
		$paymentMethodPlugins =& PluginRegistry::loadCategory('paymethod');
		$paymentMethodPluginName = array_shift($args);
		if (empty($paymentMethodPluginName) || !isset($paymentMethodPlugins[$paymentMethodPluginName])) {
			$request->redirect(null, null, 'index');
		}

		$paymentMethodPlugin =& $paymentMethodPlugins[$paymentMethodPluginName];
		if (!$paymentMethodPlugin->isConfigured()) {
			$request->redirect(null, null, 'index');
		}

		$paymentMethodPlugin->handle($args, $request);
	}

	/**
	 * Display a landing page for a received registration payment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function landing($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$user =& $request->getUser();
		$schedConf =& $request->getSchedConf();
		if (!$user || !$schedConf) $request->redirect(null, null, 'index');

		$registrationDao = DAORegistry::getDAO('RegistrationDAO');
		$registrationId = $registrationDao->getRegistrationIdByUser($user->getId(), $schedConf->getId());
		$registration =& $registrationDao->getRegistration($registrationId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('message', ($registration && $registration->getDatePaid()) ? 'schedConf.registration.landingPaid' : 'schedConf.registration.landingUnpaid');
		$templateMgr->assign('backLink', $request->url(null, null, 'index'));
		$templateMgr->assign('backLinkLabel', 'common.continue');
		$templateMgr->display('common/message.tpl');
	}
}

?>
