<?php

/**
 * @file RegistrationTypeForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationTypeForm
 * @ingroup registration_form
 *
 * @brief Form for scheduled conference managers to create/edit registration types.
 */


import('lib.pkp.classes.form.Form');

class RegistrationTypeForm extends Form {
	/** @var typeId int the ID of the registration type being edited */
	var $typeId;

	/** @var validAccesses array keys are valid registration access types */
	var $validAccessTypes;

	/** @var validCurrencies array keys are valid registration type currencies */	
	var $validCurrencies;

	/** @var $registrationOptionCosts array Associates registration option ID with cost */
	var $registrationOptionCosts;

	/** @var $registrationTypeDao object */
	var $registrationTypeDao;

	/**
	 * Constructor
	 * @param typeId int leave as default for new registration type
	 */
	function RegistrationTypeForm($typeId = null) {
		$this->registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');

		$this->validAccessTypes = array (
			REGISTRATION_TYPE_ACCESS_ONLINE => __('manager.registrationTypes.access.online'),
			REGISTRATION_TYPE_ACCESS_PHYSICAL => __('manager.registrationTypes.access.physical'),
			REGISTRATION_TYPE_ACCESS_BOTH => __('manager.registrationTypes.access.both')
		);

		$currencyDao = DAORegistry::getDAO('CurrencyDAO');
		$currencies =& $currencyDao->getCurrencies();
		$this->validCurrencies = array();
		while (list(, $currency) = each($currencies)) {
			$this->validCurrencies[$currency->getCodeAlpha()] = $currency->getName() . ' (' . $currency->getCodeAlpha() . ')';
		}

		if (isset($typeId)) {
			$this->typeId = (int) $typeId;
			$this->registrationOptionCosts = $this->registrationTypeDao->getRegistrationOptionCosts($this->typeId);
		} else {
			$this->typeId = null;
		}

		parent::Form('registration/registrationTypeForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.registrationTypes.form.typeNameRequired'));

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

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		return $this->registrationTypeDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('dateExtentFuture', REGISTRATION_TYPE_YEAR_OFFSET_FUTURE);
		$templateMgr->assign('typeId', $this->typeId);
		$templateMgr->assign('validCurrencies', $this->validCurrencies);
		$templateMgr->assign('validAccessTypes', $this->validAccessTypes);
		$templateMgr->assign('registrationOptionCosts', $this->registrationOptionCosts);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		$schedConf =& Request::getSchedConf();
		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationOptions =& $registrationOptionDao->getRegistrationOptionsBySchedConfId($schedConf->getId());
		$registrationOptionsArray =& $registrationOptions->toArray();
		$templateMgr->assign_by_ref('registrationOptions', $registrationOptionsArray);

		parent::display();
	}

	/**
	 * Initialize form data from current registration type.
	 */
	function initData() {
		if (isset($this->typeId)) {
			$registrationType =& $this->registrationTypeDao->getRegistrationType($this->typeId);

			if ($registrationType != null) {
				$this->_data = array(
					'name' => $registrationType->getName(null), // Localized
					'description' => $registrationType->getDescription(null), // Localized
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
		$this->readUserVars(array('name', 'description', 'cost', 'currency', 'access', 'institutional', 'membership', 'notPublic', 'code', 'registrationOptionCosts'));
		$this->_data['openDate'] = Request::getUserDateVar('openDate');
		$this->_data['closeDate'] = Request::getUserDateVar('closeDate');
		$this->_data['expiryDate'] = Request::getUserVar('expiryDate')?Request::getUserDateVar('expiryDate'):null;
	}

	/**
	 * Save registration type. 
	 */
	function execute() {
		$schedConf =& Request::getSchedConf();

		if (isset($this->typeId)) {
			$registrationType =& $this->registrationTypeDao->getRegistrationType($this->typeId);
		}

		if (!isset($registrationType)) {
			$registrationType = new RegistrationType();
		}

		$registrationType->setSchedConfId($schedConf->getId());
		$registrationType->setName($this->getData('name'), null); // Localized
		$registrationType->setDescription($this->getData('description'), null); // Localized
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
			$this->registrationTypeDao->updateRegistrationType($registrationType);
			$this->registrationTypeDao->deleteRegistrationOptionCosts($registrationType->getTypeId());
		} else {
			$registrationType->setSequence(REALLY_BIG_NUMBER);
			$this->registrationTypeDao->insertRegistrationType($registrationType);

			// Re-order the registration types so the new one is at the end of the list.
			$this->registrationTypeDao->resequenceRegistrationTypes($registrationType->getSchedConfId());
		}

		$registrationOptionCosts = (array) $this->getData('registrationOptionCosts');
		foreach ($registrationOptionCosts as $optionId => $cost) {
			$this->registrationTypeDao->insertRegistrationOptionCost($registrationType->getTypeId(), $optionId, $cost);
		}
	}
}

?>
