<?php

/**
 * @file ManagerPaymentHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class ManagerPaymentHandler
 *
 * Handle requests for configuring payments. 
 *
 * $Id$
 */

class ManagerPaymentHandler extends ManagerHandler {

	/**
	 * Display form to edit program settings.
	 */
	function paymentSettings() {
		parent::validate();
		parent::setupTemplate(true);
		
		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect (null, null, 'index');

		import('manager.form.PaymentSettingsForm');
		
		$settingsForm = &new PaymentSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}
	
	/**
	 * Save changes to payment settings.
	 */
	function savePaymentSettings() {
		parent::validate();
		parent::setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect (null, null, 'index');

		import('manager.form.PaymentSettingsForm');

		$settingsForm = &new PaymentSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, null, 'paymentSettings'),
				'pageTitle' => 'manager.payment.paymentSettings',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, Request::getRequestedPage()),
				'backLinkLabel' => 'manager.conferenceSiteManagement'
			));
			$templateMgr->display('common/message.tpl');
			
		} else {
			$settingsForm->display();
		}
	}
}

?>
