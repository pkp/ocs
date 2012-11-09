<?php

/**
 * @file pages/manager/ManagerPaymentHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerPaymentHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for configuring payments. 
 */


import('pages.manager.ManagerHandler');

class ManagerPaymentHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function ManagerPaymentHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display form to edit program settings.
	 */
	function paymentSettings($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		if (!$schedConf) $request->redirect (null, null, 'index');

		import('classes.manager.form.PaymentSettingsForm');

		$settingsForm = new PaymentSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}

	/**
	 * Save changes to payment settings.
	 */
	function savePaymentSettings($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		if (!$schedConf) $request->redirect (null, null, 'index');

		import('classes.manager.form.PaymentSettingsForm');

		$settingsForm = new PaymentSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'currentUrl' => $request->url(null, null, null, 'paymentSettings'),
				'pageTitle' => 'manager.payment.paymentSettings',
				'message' => 'common.changesSaved',
				'backLink' => $request->url(null, null, Request::getRequestedPage()),
				'backLinkLabel' => 'manager.conferenceSiteManagement'
			));
			$templateMgr->display('common/message.tpl');

		} else {
			$settingsForm->display();
		}
	}
}

?>
