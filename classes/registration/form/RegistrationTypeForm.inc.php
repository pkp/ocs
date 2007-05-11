<?php

/**
 * RegistrationTypeForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package registration.form
 *
 * Form for scheduled conference managers to create/edit registration types.
 *
 * $Id$
 */

import('form.Form');

class RegistrationTypeForm extends Form {

	/** @var typeId int the ID of the registration type being edited */
	var $typeId;

	/** @var validAccesses array keys are valid registration access types */
	var $validAccessTypes;

	/** @var validCurrencies array keys are valid registration type currencies */	
	var $validCurrencies;

	/**
	 * Constructor
	 * @param typeId int leave as default for new registration type
	 */
	function RegistrationTypeForm($typeId = null) {

		$this->validAccessTypes = array (
			REGISTRATION_TYPE_ACCESS_ONLINE => Locale::translate('manager.registrationTypes.access.online'),
			REGISTRATION_TYPE_ACCESS_PHYSICAL => Locale::translate('manager.registrationTypes.access.physical'),
			REGISTRATION_TYPE_ACCESS_BOTH => Locale::translate('manager.registrationTypes.access.both')
		);

		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currencies = &$currencyDao->getCurrencies();
		$this->validCurrencies = array();
		while (list(, $currency) = each($currencies)) {
			$this->validCurrencies[$currency->getCodeAlpha()] = $currency->getName() . ' (' . $currency->getCodeAlpha() . ')';
		}

		$this->typeId = isset($typeId) ? (int) $typeId : null;
		$schedConf = &Request::getSchedConf();

		parent::Form('registration/registrationTypeForm.tpl');
	
		// Type name is provided
		$this->addCheck(new FormValidator($this, 'typeName', 'required', 'manager.registrationTypes.form.typeNameRequired'));

		// Type name does not already exist for this scheduled conference
		if ($this->typeId == null) {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'manager.registrationTypes.form.typeNameExists', array(DAORegistry::getDAO('RegistrationTypeDAO'), 'registrationTypeExistsByTypeName'), array($schedConf->getSchedConfId()), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'manager.registrationTypes.form.typeNameExists', create_function('$typeName, $schedConfId, $typeId', '$registrationTypeDao = &DAORegistry::getDAO(\'RegistrationTypeDAO\'); $checkId = $registrationTypeDao->getRegistrationTypeByTypeName($typeName, $schedConfId); return ($checkId == 0 || $checkId == $typeId) ? true : false;'), array($schedConf->getSchedConfId(), $this->typeId)));
		}

		// Cost	is provided and is numeric and positive	
		$this->addCheck(new FormValidator($this, 'cost', 'required', 'manager.registrationTypes.form.costRequired'));	
		$this->addCheck(new FormValidatorCustom($this, 'cost', 'required', 'manager.registrationTypes.form.costNumeric', create_function('$cost', 'return (is_numeric($cost) && $cost >= 0);')));

		// Currency is provided and is valid value
		$this->addCheck(new FormValidator($this, 'currency', 'required', 'manager.registrationTypes.form.currencyRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'currency', 'required', 'manager.registrationTypes.form.currencyValid', array_keys($this->validCurrencies)));

		// Opening date must happen before closing date
		$this->addCheck(new FormValidatorCustom($this, 'openDate', 'required', 'manager.registrationTypes.form.closeBeforeOpen',
			create_function('$openDate,$form',
			'return ($openDate < $form->getData(\'closeDate\'));'),
			array(&$this)));

		// Access type is provided and is valid value
		$this->addCheck(new FormValidator($this, 'access', 'required', 'manager.registrationTypes.form.accessRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'access', 'required', 'manager.registrationTypes.form.accessValid', array_keys($this->validAccessTypes)));

		// Institutional flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'institutional', 'optional', 'manager.registrationTypes.form.institutionalValid', array('1')));

		// Membership flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'membership', 'optional', 'manager.registrationTypes.form.membershipValid', array('1')));

		// Public flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'notPublic', 'optional', 'manager.registrationTypes.form.notPublicValid', array('1')));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('dateExtentFuture', REGISTRATION_TYPE_YEAR_OFFSET_FUTURE);
		$templateMgr->assign('typeId', $this->typeId);
		$templateMgr->assign('validCurrencies', $this->validCurrencies);
		$templateMgr->assign('validAccessTypes', $this->validAccessTypes);
		$templateMgr->assign('helpTopicId', 'schedConf.managementPages.registration');
	
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
					'access' => $registrationType->getAccess(),
					'institutional' => $registrationType->getInstitutional(),
					'membership' => $registrationType->getMembership(),
					'notPublic' => $registrationType->getPublic()?0:1,
					'code' => $registrationType->getCode()
				);

			} else {
				$this->typeId = null;
			}
		} else {
			$this->_data = array(
				'notPublic' => 0
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeName', 'description', 'cost', 'currency', 'access', 'institutional', 'membership', 'notPublic', 'code'));
		$this->_data['openDate'] = Request::getUserDateVar('openDate');
		$this->_data['closeDate'] = Request::getUserDateVar('closeDate');
		$this->_data['expiryDate'] = Request::getUserVar('expiryDate')?Request::getUserDateVar('expiryDate'):null;
	}
	
	/**
	 * Save registration type. 
	 */
	function execute() {
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$schedConf = &Request::getSchedConf();
	
		if (isset($this->typeId)) {
			$registrationType = &$registrationTypeDao->getRegistrationType($this->typeId);
		}
		
		if (!isset($registrationType)) {
			$registrationType = &new RegistrationType();
		}
		
		$registrationType->setSchedConfId($schedConf->getSchedConfId());
		$registrationType->setTypeName($this->getData('typeName'));
		$registrationType->setDescription($this->getData('description'));
		$registrationType->setCost(round($this->getData('cost'), 2));
		$registrationType->setCurrencyCodeAlpha($this->getData('currency'));
		$registrationType->setOpeningDate($this->getData('openDate'));
		$registrationType->setClosingDate($this->getData('closeDate'));
		$registrationType->setExpiryDate($this->getData('expiryDate'));
		$registrationType->setAccess($this->getData('access'));
		$registrationType->setInstitutional($this->getData('institutional')?1:0);
		$registrationType->setMembership($this->getData('membership')?1:0);
		$registrationType->setPublic($this->getData('notPublic')?0:1);
		$registrationType->setCode($this->getData('code'));

		// Update or insert registration type
		if ($registrationType->getTypeId() != null) {
			$registrationTypeDao->updateRegistrationType($registrationType);
		} else {
			// Kludge: Assume we'll have less than 10,000 registration types.
			$registrationType->setSequence(10000);

			$registrationTypeDao->insertRegistrationType($registrationType);

			// Re-order the registration types so the new one is at the end of the list.
			$registrationTypeDao->resequenceRegistrationTypes($registrationType->getSchedConfId());
		}
	}
}

?>
