<?php

/**
 * @file RegistrationHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for registration management functions. 
 *
 */

// $Id$

import('pages.manager.ManagerHandler');

class RegistrationHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function RegistrationHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of registrations for the current scheduled conference.
	 */
	function registration() {
		$this->validate();
		$this->setupTemplate();

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('registrations', array());
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');

		// Get the user's search conditions, if any
		$searchField = Request::getUserVar('searchField');
		$dateSearchField = Request::getUserVar('dateSearchField');
		$searchMatch = Request::getUserVar('searchMatch');
		$search = Request::getUserVar('search');
		
		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'user';
		$sortDirection = Request::getUserVar('sortDirection');

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		while (true) {
			$registrations =& $registrationDao->getRegistrationsBySchedConfId($schedConf->getId(), $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $rangeInfo, $sort, $sortDirection);
			if ($registrations->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $registrations->getLastPageRangeInfo();
			unset($registrations);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('registrations', $registrations);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		// Set search parameters
		foreach ($this->getSearchFormDuplicateParameters() as $param)
			$templateMgr->assign($param, Request::getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', $this->getSearchFieldOptions());
		$templateMgr->assign('dateFieldOptions', $this->getDateFieldOptions());
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);

		$templateMgr->display('registration/registrations.tpl');
	}

	/**
	 * Get the list of parameter names that should be duplicated when
	 * displaying the search form (i.e. made available to the template
	 * based on supplied user data).
	 * @return array
	 */
	function getSearchFormDuplicateParameters() {
		return array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
	}

	/**
	 * Get the list of fields that can be searched by contents.
	 * @return array
	 */
	function getSearchFieldOptions() {
		return array(
			REGISTRATION_USER => 'manager.registration.user',
			REGISTRATION_MEMBERSHIP => 'manager.registration.membership',
			REGISTRATION_DOMAIN => 'manager.registration.domain',
			REGISTRATION_IP_RANGE => 'manager.registration.ipRange'
		);
	}

	/**
	 * Get the list of date fields that can be searched.
	 * @return array
	 */
	function getDateFieldOptions() {
		return array(
			REGISTRATION_DATE_REGISTERED => 'manager.registration.dateRegistered',
			REGISTRATION_DATE_PAID => 'manager.registration.datePaid'
		);
	}

	/**
	 * Delete a registration.
	 * @param $args array first parameter is the ID of the registration to delete
	 */
	function deleteRegistration($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$schedConf =& Request::getSchedConf();
			$registrationId = (int) $args[0];

			$registrationDao =& DAORegistry::getDAO('RegistrationDAO');

			// Ensure registration is for this scheduled conference
			if ($registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getId()) {
				$registrationDao->deleteRegistrationById($registrationId);
			}
		}

		Request::redirect(null, null, null, 'registration');
	}

	/**
	 * Display form to edit a registration.
	 * @param $args array optional, first parameter is the ID of the registration to edit
	 */
	function editRegistration($args = array()) {
		$this->validate();
		$this->setupTemplate();

		$schedConf =& Request::getSchedConf();
		$registrationId = !isset($args) || empty($args) ? null : (int) $args[0];
		$userId = Request::getUserVar('userId');
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');

		// Ensure registration is valid and for this scheduled conference
		if (($registrationId != null && $registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getId()) || ($registrationId == null && $userId)) {
			import('registration.form.RegistrationForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registration'), 'manager.registration'));

			if ($registrationId == null) {
				$templateMgr->assign('registrationTitle', 'manager.registration.createTitle');
			} else {
				$templateMgr->assign('registrationTitle', 'manager.registration.editTitle');	
			}

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$registrationForm = new RegistrationForm($registrationId, $userId);
			} else {
				$registrationForm =& new RegistrationForm($registrationId, $userId);
			}
			if ($registrationForm->isLocaleResubmit()) {
				$registrationForm->readInputData();
			} else {
				$registrationForm->initData();
			}
			$registrationForm->display();

		} else {
				Request::redirect(null, null, null, 'registration');
		}
	}

	/**
	 * Display form to create new registration.
	 */
	function createRegistration() {
		$this->editRegistration();
	}

	/**
	 * Display a list of users from which to choose a registrant.
	 */
	function selectRegistrant() {
		$this->validate();
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate();
		$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registration'), 'manager.registration'));

		$userDao =& DAORegistry::getDAO('UserDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		
		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = Request::getUserVar('sortDirection');
		
		$rangeInfo =& Handler::getRangeInfo('users', array((string) $search, (string) $searchMatch, (string) $searchType));

		while (true) {
			$users =& $userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo, $sort, $sortDirection);
			if ($users->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $users->getLastPageRangeInfo();
			unset($users);
		}

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		$templateMgr->assign('isSchedConfManager', true);

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');
		$templateMgr->assign('registrationId', Request::getUserVar('registrationId'));
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('registration/users.tpl');
	}

	/**
	 * Save changes to a registration.
	 */
	function updateRegistration() {
		$this->validate();
		$this->setupTemplate();

		import('registration.form.RegistrationForm');

		$schedConf =& Request::getSchedConf();
		$registrationId = Request::getUserVar('registrationId') == null ? null : (int) Request::getUserVar('registrationId');
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');

		if (($registrationId != null && $registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getId()) || $registrationId == null) {

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$registrationForm = new RegistrationForm($registrationId);
			} else {
				$registrationForm =& new RegistrationForm($registrationId);
			}
			$registrationForm->readInputData();

			if ($registrationForm->validate()) {
				$registrationForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'selectRegistrant', null, array('registrationCreated', 1));
				} else {
					Request::redirect(null, null, null, 'registration');
				}

			} else {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registration'), 'manager.registration'));

				if ($registrationId == null) {
					$templateMgr->assign('registrationTitle', 'manager.registration.createTitle');
				} else {
					$templateMgr->assign('registrationTitle', 'manager.registration.editTitle');	
				}

				$registrationForm->display();
			}

		} else {
				Request::redirect(null, null, null, 'registration');
		}
	}

	/**
	 * Display a list of registration types for the current scheduled conference.
	 */
	function registrationTypes() {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('registrationTypes', array());
		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
		while (true) {
			$registrationTypes =& $registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getId(), $rangeInfo);
			if ($registrationTypes->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $registrationTypes->getLastPageRangeInfo();
			unset($registrationTypes);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('registrationTypes', $registrationTypes);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		$templateMgr->display('registration/registrationTypes.tpl');
	}

	/**
	 * Rearrange the order of registration types.
	 */
	function moveRegistrationType($args) {
		$this->validate();

		$registrationTypeId = isset($args[0])?$args[0]:0;
		$schedConf =& Request::getSchedConf();

		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationType =& $registrationTypeDao->getRegistrationType($registrationTypeId);

		if ($registrationType && $registrationType->getSchedConfId() == $schedConf->getId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$registrationType->setSequence($registrationType->getSequence()+($isDown?1.5:-1.5));
			$registrationTypeDao->updateRegistrationType($registrationType);
			$registrationTypeDao->resequenceRegistrationTypes($registrationType->getSchedConfId());
		}

		Request::redirect(null, null, null, 'registrationTypes');
	}

	/**
	 * Delete a registration type.
	 * @param $args array first parameter is the ID of the registration type to delete
	 */
	function deleteRegistrationType($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$schedConf =& Request::getSchedConf();
			$registrationTypeId = (int) $args[0];

			$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');

			// Ensure registration type is for this scheduled conference.
			if ($registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getId()) {
				$registrationTypeDao->deleteRegistrationTypeById($registrationTypeId);
			}
		}

		Request::redirect(null, null, null, 'registrationTypes');
	}

	/**
	 * Display form to edit a registration type.
	 * @param $args array optional, first parameter is the ID of the registration type to edit
	 */
	function editRegistrationType($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$registrationTypeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');

		// Ensure registration type is valid and for this scheduled conference.
		if (($registrationTypeId != null && $registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getId()) || $registrationTypeId == null) {

			import('registration.form.RegistrationTypeForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registrationTypes'), 'manager.registrationTypes'));

			if ($registrationTypeId == null) {
				$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.createTitle');
			} else {
				$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.editTitle');	
			}

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$registrationTypeForm = new RegistrationTypeForm($registrationTypeId);
			} else {
				$registrationTypeForm =& new RegistrationTypeForm($registrationTypeId);
			}

			if ($registrationTypeForm->isLocaleResubmit()) {
				$registrationTypeForm->readInputData();
			} else {
				$registrationTypeForm->initData();
			}
			$registrationTypeForm->display();

		} else {
				Request::redirect(null, null, null, 'registrationTypes');
		}
	}

	/**
	 * Display form to create new registration type.
	 */
	function createRegistrationType() {
		$this->editRegistrationType();
	}

	/**
	 * Save changes to a registration type.
	 */
	function updateRegistrationType() {
		$this->validate();
		$this->setupTemplate(true);

		import('registration.form.RegistrationTypeForm');

		$schedConf =& Request::getSchedConf();
		$registrationTypeId = Request::getUserVar('typeId') == null ? null : (int) Request::getUserVar('typeId');
		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');

		if (($registrationTypeId != null && $registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getId()) || $registrationTypeId == null) {

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$registrationTypeForm = new RegistrationTypeForm($registrationTypeId);
			} else {
				$registrationTypeForm =& new RegistrationTypeForm($registrationTypeId);
			}
			$registrationTypeForm->readInputData();

			if ($registrationTypeForm->validate()) {
				$registrationTypeForm->execute();

				if (Request::getUserVar('createAnother')) {
					$templateMgr =& TemplateManager::getManager();
					$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registrationTypes'), 'manager.registrationTypes'));
					$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.createTitle');
					$templateMgr->assign('registrationTypeCreated', '1');
					unset($registrationTypeForm);
					if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
						$registrationTypeForm = new RegistrationTypeForm($registrationTypeId);
					} else {
						$registrationTypeForm =& new RegistrationTypeForm($registrationTypeId);
					}
					$registrationTypeForm->initData();
					$registrationTypeForm->display();

				} else {
					Request::redirect(null, null, null, 'registrationTypes');
				}
			} else {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registrationTypes'), 'manager.registrationTypes'));

				if ($registrationTypeId == null) {
					$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.createTitle');
				} else {
					$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.editTitle');	
				}
				$registrationTypeForm->display();
			}
		} else {
				Request::redirect(null, null, null, 'registrationTypes');
		}
	}

	/**
	 * Display a list of registration options for the current scheduled conference.
	 */
	function registrationOptions() {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$rangeInfo =& Handler::getRangeInfo('registrationOptions', array());
		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
		while (true) {
			$registrationOptions =& $registrationOptionDao->getRegistrationOptionsBySchedConfId($schedConf->getId(), $rangeInfo);
			if ($registrationOptions->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $registrationOptions->getLastPageRangeInfo();
			unset($registrationOptions);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('registrationOptions', $registrationOptions);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		$templateMgr->display('registration/registrationOptions.tpl');
	}

	/**
	 * Rearrange the order of registration options.
	 */
	function moveRegistrationOption($args) {
		$this->validate();

		$registrationOptionId = isset($args[0])?$args[0]:0;
		$schedConf =& Request::getSchedConf();

		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationOption =& $registrationOptionDao->getRegistrationOption($registrationOptionId);

		if ($registrationOption && $registrationOption->getSchedConfId() == $schedConf->getId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$registrationOption->setSequence($registrationOption->getSequence()+($isDown?1.5:-1.5));
			$registrationOptionDao->updateRegistrationOption($registrationOption);
			$registrationOptionDao->resequenceRegistrationOptions($registrationOption->getSchedConfId());
		}

		Request::redirect(null, null, null, 'registrationOptions');
	}

	/**
	 * Delete a registration option.
	 * @param $args array first parameter is the ID of the registration type to delete
	 */
	function deleteRegistrationOption($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$schedConf =& Request::getSchedConf();
			$registrationOptionId = (int) $args[0];

			$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');

			// Ensure registration option is for this scheduled conference.
			if ($registrationOptionDao->getRegistrationOptionSchedConfId($registrationOptionId) == $schedConf->getId()) {
				$registrationOptionDao->deleteRegistrationOptionById($registrationOptionId);
			}
		}

		Request::redirect(null, null, null, 'registrationOptions');
	}

	/**
	 * Display form to edit a registration option.
	 * @param $args array optional, first parameter is the ID of the registration option to edit
	 */
	function editRegistrationOption($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$registrationOptionId = !isset($args) || empty($args) ? null : (int) $args[0];
		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');

		// Ensure registration option is valid and for this scheduled conference.
		if (($registrationOptionId != null && $registrationOptionDao->getRegistrationOptionSchedConfId($registrationOptionId) == $schedConf->getId()) || $registrationOptionId == null) {

			import('registration.form.RegistrationOptionForm');

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registrationOptions'), 'manager.registrationOptions'));

			if ($registrationOptionId == null) {
				$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.createTitle');
			} else {
				$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.editTitle');	
			}

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$registrationOptionForm = new RegistrationOptionForm($registrationOptionId);
			} else {
				$registrationOptionForm =& new RegistrationOptionForm($registrationOptionId);
			}
			if ($registrationOptionForm->isLocaleResubmit()) {
				$registrationOptionForm->readInputData();
			} else {
				$registrationOptionForm->initData();
			}
			$registrationOptionForm->display();

		} else {
				Request::redirect(null, null, null, 'registrationOptions');
		}
	}

	/**
	 * Display form to create new registration option.
	 */
	function createRegistrationOption() {
		$this->editRegistrationOption();
	}

	/**
	 * Save changes to a registration option.
	 */
	function updateRegistrationOption() {
		$this->validate();
		$this->setupTemplate(true);

		import('registration.form.RegistrationOptionForm');

		$schedConf =& Request::getSchedConf();
		$registrationOptionId = Request::getUserVar('optionId') == null ? null : (int) Request::getUserVar('optionId');
		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');

		if (($registrationOptionId != null && $registrationOptionDao->getRegistrationOptionSchedConfId($registrationOptionId) == $schedConf->getId()) || $registrationOptionId == null) {

			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$registrationOptionForm = new RegistrationOptionForm($registrationOptionId);
			} else {
				$registrationOptionForm =& new RegistrationOptionForm($registrationOptionId);
			}
			$registrationOptionForm->readInputData();

			if ($registrationOptionForm->validate()) {
				$registrationOptionForm->execute();

				if (Request::getUserVar('createAnother')) {
					$this->setupTemplate(true);

					$templateMgr =& TemplateManager::getManager();
					$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registrationOptions'), 'manager.registrationOptions'));
					$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.createTitle');
					$templateMgr->assign('registrationOptionCreated', '1');
					unset($registrationOptionForm);
					if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
						$registrationOptionForm = new RegistrationOptionForm($registrationOptionId);
					} else {
						$registrationOptionForm =& new RegistrationOptionForm($registrationOptionId);
					}
					$registrationOptionForm->initData();
					$registrationOptionForm->display();

				} else {
					Request::redirect(null, null, null, 'registrationOptions');
				}

			} else {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registrationOptions'), 'manager.registrationOptions'));

				if ($registrationOptionId == null) {
					$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.createTitle');
				} else {
					$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.editTitle');	
				}

				$registrationOptionForm->display();
			}

		} else {
				Request::redirect(null, null, null, 'registrationOptions');
		}
	}

	/**
	 * Display registration policies for the current scheduled conference.
	 */
	function registrationPolicies() {
		$this->validate();
		$this->setupTemplate(true);

		import('registration.form.RegistrationPolicyForm');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		$registrationPolicyForm = new RegistrationPolicyForm();
		if ($registrationPolicyForm->isLocaleResubmit()) {
			$registrationPolicyForm->readInputData();
		} else {
			$registrationPolicyForm->initData();
		}
		$registrationPolicyForm->display();
	}

	/**
	 * Save registration policies for the current scheduled conference.
	 */
	function saveRegistrationPolicies($args = array()) {
		$this->validate();

		import('registration.form.RegistrationPolicyForm');

		$registrationPolicyForm = new RegistrationPolicyForm();
		$registrationPolicyForm->readInputData();

		if ($registrationPolicyForm->validate()) {
			$registrationPolicyForm->execute();
			Request::redirect(null, null, 'manager', 'registration');
		}
	}

	function setupTemplate($subclass = false) {
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'registration'), 'manager.registration'));
		}
	}
}

?>
