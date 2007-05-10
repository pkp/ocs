<?php

/**
 * PayPalPaymentForm.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Form for conference managers to modify Payment Plugin content
 * 
 * $Id$
 */

import('form.Form');

class PayPalPaymentForm extends Form {
	/** @var $plugin object */
	var $payPalPlugin;

	/** @var $queuedPaymentId int */
	var $queuedPaymentId;

	/** @var $key string */
	var $key;

	/** @var $queuedPayment object */
	var $queuedPayment;
	
	/**
	 * Constructor
	 * @param $payPalPlugin object
	 * @param $queuedPaymentId int
	 * @param $key string
	 * @param $queuedPayment object
	 */
	function PayPalPaymentForm(&$payPalPlugin, $queuedPaymentId, $key, &$queuedPayment) {
		parent::Form($plugin->getTemplatePath() . 'paymentForm.tpl');

		$this->payPalPlugin =& $payPalPlugin;
		$this->queuedPaymentId = $queuedPaymentId;
		$this->key = $key;
		$this->queuedPayment =& $queuedPayment;
	}
	

	/**
	 * Initialize form data.
	 */
	function initData() {
		$payPalPlugin =& $this->payPalPlugin;
		$user =& Request::getUser();
		$userId = ($user)?$user->getUserId():null;

		$queuedPayment =& $this->queuedPayment;

		$this->_data = array(
			'business' => $payPalPlugin->getSetting($conferenceId, $schedConfId, 'selleraccount'),
			'item_name' => $queuedPayment->getDescription(),
			'a3' => $queuedPayment->getAmount($args),
			'quantity' => 1,
			'no_note' => 1,
			'no_shipping' => 1,
			'currency_code' => $queuedPayment->getCurrencyCode(),
			'lc' => String::substr(Locale::getLocale(), 3), 
			'custom' => $this->key,
			'notify_url' => Request::url(null, null, 'payment', 'ipn', array($queuedPayment->getQueuedPaymentId())),  
			'return' => Request::url(null, null, 'payment', 'return', array($queuedPayment->getQueuedPaymentId())),
			'cancel_return' => Request::url(null, null, 'payment', 'cancel', array($queuedPayment->getQueuedPaymentId())),
			'first_name' => ($user)?$user->getFirstName():'',  
			'last_name' => ($user)?$user->getLastname():'',
			'city' => '',
			'zip' => '',
			'item_number' => 1
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'business',
		        'item_name', 
		        'quantity',
		        'no_note',
		        'no_shipping',
		        'currency_code',
		        'lc',  
		        'custom', 
		        'notify_url',   
		        'return', 
		        'cancel_return', 
		        'first_name',  
		        'last_name',
		        'city', 
		        'zip',
		        'item_number'
	        ));
	}		
	
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('message', $this->message);
		$templateMgr->assign('paymentIsRegistration', $this->isRegistration);
		parent::display();
	}	
	
}
?>
