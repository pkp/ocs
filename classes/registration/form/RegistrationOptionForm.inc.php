<?php

/**
 * @file RegistrationOptionForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationOptionForm
 * @ingroup registration_form
 *
 * @brief Form for scheduled conference managers to create/edit registration options.
 */

//$Id$

import('form.Form');

class RegistrationOptionForm extends Form {
	/** @var optionId int the ID of the registration option being edited */
	var $optionId;

	/** @var validAccesses array keys are valid registration access options */
	var $validAccessOptions;

	/**
	 * Constructor
	 * @param optionId int leave as default for new registration option
	 */
	function RegistrationOptionForm($optionId = null) {
		$this->optionId = isset($optionId) ? (int) $optionId : null;
		$schedConf =& Request::getSchedConf();

		parent::Form('registration/registrationOptionForm.tpl');

		// Option name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.registrationOptions.form.optionNameRequired'));

		// Opening date must happen before closing date
		$this->addCheck(new FormValidatorCustom($this, 'openDate', 'required', 'manager.registrationOptions.form.closeBeforeOpen',
			create_function('$openDate,$form',
			'return ($openDate < $form->getData(\'closeDate\'));'),
			array(&$this)));

		// Public flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'notPublic', 'optional', 'manager.registrationOptions.form.notPublicValid', array('1')));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
		return $registrationOptionDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('dateExtentFuture', REGISTRATION_OPTION_YEAR_OFFSET_FUTURE);		
		$templateMgr->assign('optionId', $this->optionId);
		$templateMgr->assign('validAccessOptions', $this->validAccessOptions);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		parent::display();
	}

	/**
	 * Initialize form data from current registration option.
	 */
	function initData() {
		if (isset($this->optionId)) {
			$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
			$registrationOption =& $registrationOptionDao->getRegistrationOption($this->optionId);

			if ($registrationOption != null) {
				$this->_data = array(
					'name' => $registrationOption->getName(null), // Localized
					'description' => $registrationOption->getDescription(null), // Localized
					'openDate' => $registrationOption->getOpeningDate(),
					'closeDate' => $registrationOption->getClosingDate(),
					'notPublic' => $registrationOption->getPublic()?0:1,
					'code' => $registrationOption->getCode()
				);

			} else {
				$this->optionId = null;
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
		$this->readUserVars(array('name', 'description', 'notPublic', 'code'));
		$this->_data['openDate'] = Request::getUserDateVar('openDate');
		$this->_data['closeDate'] = Request::getUserDateVar('closeDate');
	}

	/**
	 * Save registration option. 
	 */
	function execute() {
		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
		$schedConf =& Request::getSchedConf();

		if (isset($this->optionId)) {
			$registrationOption =& $registrationOptionDao->getRegistrationOption($this->optionId);
		}

		if (!isset($registrationOption)) {
			$registrationOption = new RegistrationOption();
		}

		$registrationOption->setSchedConfId($schedConf->getId());
		$registrationOption->setName($this->getData('name'), null); // Localized
		$registrationOption->setDescription($this->getData('description'), null); // Localized
		$registrationOption->setOpeningDate($this->getData('openDate'));
		$registrationOption->setClosingDate($this->getData('closeDate'));
		$registrationOption->setPublic($this->getData('notPublic')?0:1);
		$registrationOption->setCode($this->getData('code'));

		// Update or insert registration option
		if ($registrationOption->getOptionId() != null) {
			$registrationOptionDao->updateRegistrationOption($registrationOption);
		} else {
			$registrationOption->setSequence(REALLY_BIG_NUMBER);
			$registrationOptionDao->insertRegistrationOption($registrationOption);

			// Re-order the registration options so the new one is at the end of the list.
			$registrationOptionDao->resequenceRegistrationOptions($registrationOption->getSchedConfId());
		}
	}
}

?>
