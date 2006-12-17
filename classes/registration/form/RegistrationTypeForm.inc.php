<?php

/**
 * RegistrationTypeForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package registration.form
 *
 * Form for event directors to create/edit registration types.
 *
 * $Id$
 */

import('form.Form');

class RegistrationTypeForm extends Form {

	/** @var typeId int the ID of the registration type being edited */
	var $typeId;

	/** @var validFormats array keys are valid registration type formats */
	//var $validFormats;

	/** @var validCurrencies array keys are valid registration type currencies */	
	var $validCurrencies;

	/**
	 * Constructor
	 * @param typeId int leave as default for new registration type
	 */
	function RegistrationTypeForm($typeId = null) {

		/*$this->validFormats = array (
			REGISTRATION_TYPE_FORMAT_ONLINE => Locale::translate('director.registrationTypes.format.online'),
			REGISTRATION_TYPE_FORMAT_PRINT => Locale::translate('director.registrationTypes.format.print'),
			REGISTRATION_TYPE_FORMAT_PRINT_ONLINE => Locale::translate('director.registrationTypes.format.printOnline')
		);*/

		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currencies = &$currencyDao->getCurrencies();
		$this->validCurrencies = array();
		while (list(, $currency) = each($currencies)) {
			$this->validCurrencies[$currency->getCodeAlpha()] = $currency->getName() . ' (' . $currency->getCodeAlpha() . ')';
		}

		$this->typeId = isset($typeId) ? (int) $typeId : null;
		$event = &Request::getEvent();

		parent::Form('registration/registrationTypeForm.tpl');
	
		// Type name is provided
		$this->addCheck(new FormValidator($this, 'typeName', 'required', 'director.registrationTypes.form.typeNameRequired'));

		// Type name does not already exist for this event
		if ($this->typeId == null) {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'director.registrationTypes.form.typeNameExists', array(DAORegistry::getDAO('RegistrationTypeDAO'), 'registrationTypeExistsByTypeName'), array($event->getEventId()), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'director.registrationTypes.form.typeNameExists', create_function('$typeName, $eventId, $typeId', '$registrationTypeDao = &DAORegistry::getDAO(\'RegistrationTypeDAO\'); $checkId = $registrationTypeDao->getRegistrationTypeByTypeName($typeName, $eventId); return ($checkId == 0 || $checkId == $typeId) ? true : false;'), array($event->getEventId(), $this->typeId)));
		}

		// Cost	is provided and is numeric and positive	
		$this->addCheck(new FormValidator($this, 'cost', 'required', 'director.registrationTypes.form.costRequired'));	
		$this->addCheck(new FormValidatorCustom($this, 'cost', 'required', 'director.registrationTypes.form.costNumeric', create_function('$cost', 'return (is_numeric($cost) && $cost >= 0);')));

		// Currency is provided and is valid value
		$this->addCheck(new FormValidator($this, 'currency', 'required', 'director.registrationTypes.form.currencyRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'currency', 'required', 'director.registrationTypes.form.currencyValid', array_keys($this->validCurrencies)));

		// TODO: Opening date is valid
		/*$this->addCheck(new FormValidator($this, 'openDate', 'required', 'director.registrationTypes.form.openDateRequired'));	
		$this->addCheck(new FormValidatorCustom($this, 'openDate', 'required', 'director.registrationTypes.form.durationNumeric', create_function('$duration', 'return (is_numeric($duration) && $duration >= 0);')));*/

		// TODO: Closing date is valid and occurs after the opening date
		/*$this->addCheck(new FormValidator($this, 'closeDate', 'required', 'director.registrationTypes.form.closeDateRequired'));	
		$this->addCheck(new FormValidatorCustom($this, 'closeDate', 'required', 'director.registrationTypes.form.durationNumeric', create_function('$duration', 'return (is_numeric($duration) && $duration >= 0);')));*/

		// Format is provided and is valid value
		/*$this->addCheck(new FormValidator($this, 'format', 'required', 'director.registrationTypes.form.formatRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'format', 'required', 'director.registrationTypes.form.formatValid', array_keys($this->validFormats)));*/

		// Institutional flag is valid value
		//$this->addCheck(new FormValidatorInSet($this, 'institutional', 'optional', 'director.registrationTypes.form.institutionalValid', array('1')));

		// Membership flag is valid value
		//$this->addCheck(new FormValidatorInSet($this, 'membership', 'optional', 'director.registrationTypes.form.membershipValid', array('1')));

		// Public flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'public', 'optional', 'director.registrationTypes.form.publicValid', array('1')));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('typeId', $this->typeId);
		$templateMgr->assign('validCurrencies', $this->validCurrencies);
		//$templateMgr->assign('validFormats', $this->validFormats);
		$templateMgr->assign('helpTopicId', 'event.managementPages.registrations');
	
		parent::display();
	}
	
	/**
	 * Initialize form data from current registration type.
	 */
	function initData() {
		if (isset($this->typeId)) {
			$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
			$registrationType = &$registrationTypeDao->getRegistrationType($this->typeId);
			
			if ($registrationType != null) {
				$this->_data = array(
					'typeName' => $registrationType->getTypeName(),
					'description' => $registrationType->getDescription(),
					'cost' => $registrationType->getCost(),
					'currency' => $registrationType->getCurrencyCodeAlpha(),
					'openDate' => $registrationType->getOpeningDate(),
					'closeDate' => $registrationType->getClosingDate(),
					'expiryDate' => $registrationType->getExpiryDate(),
					//'format' => $registrationType->getFormat(),
					//'institutional' => $registrationType->getInstitutional(),
					//'membership' => $registrationType->getMembership(),
					'public' => $registrationType->getPublic()
				);

			} else {
				$this->typeId = null;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeName', 'description', 'cost', 'currency', /*'format',*/ 'institutional', 'membership', 'public'));
		$this->_data['openDate'] = Request::getUserDateVar('openDate');
		$this->_data['closeDate'] = Request::getUserDateVar('closeDate');
		$this->_data['expiryDate'] = Request::getUserDateVar('expiryDate');
	}
	
	/**
	 * Save registration type. 
	 */
	function execute() {
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$event = &Request::getEvent();
	
		if (isset($this->typeId)) {
			$registrationType = &$registrationTypeDao->getRegistrationType($this->typeId);
		}
		
		if (!isset($registrationType)) {
			$registrationType = &new RegistrationType();
		}
		
		$registrationType->setEventId($event->getEventId());
		$registrationType->setTypeName($this->getData('typeName'));
		$registrationType->setDescription($this->getData('description'));
		$registrationType->setCost(round($this->getData('cost'), 2));
		$registrationType->setCurrencyCodeAlpha($this->getData('currency'));
		$registrationType->setOpeningDate($this->getData('openDate'));
		$registrationType->setClosingDate($this->getData('closeDate'));
		$registrationType->setExpiryDate($this->getData('expiryDate'));
		//$registrationType->setFormat($this->getData('format'));
		$registrationType->setInstitutional($this->getData('institutional') == null ? 0 : $this->getData('institutional'));
		$registrationType->setMembership($this->getData('membership') == null ? 0 : $this->getData('membership'));
		$registrationType->setPublic($this->getData('public') == null ? 0 : $this->getData('public'));

		// Update or insert registration type
		if ($registrationType->getTypeId() != null) {
			$registrationTypeDao->updateRegistrationType($registrationType);
		} else {
			// Kludge: Assume we'll have less than 10,000 registration types.
			$registrationType->setSequence(10000);

			$registrationTypeDao->insertRegistrationType($registrationType);

			// Re-order the registration types so the new one is at the end of the list.
			$registrationTypeDao->resequenceRegistrationTypes($registrationType->getEventId());
		}
	}
	
}

?>
