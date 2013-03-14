<?

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


import('pages.manager.ManagerHandler');

class RegistrationHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function RegistrationHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of registrations for the current scheduled conference.
	 */
	function registration($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$schedConf =& $request->getSchedConf();
		$rangeInfo = $this->getRangeInfo($request, 'registrations', array());
		$registrationDao = DAORegistry::getDAO('RegistrationDAO');

		// Get the user's search conditions, if any
		$searchField = $request->getUserVar('searchField');
		$dateSearchField = $request->getUserVar('dateSearchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');
		
		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'user';
		$sortDirection = $request->getUserVar('sortDirection');

		$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		while (true) {
			$registrations =& $registrationDao->getRegistrationsBySchedConfId($schedConf->getId(), $searchField, $searchMatch, $search, $dateSearchField, $fromDate, $toDate, $rangeInfo, $sort, $sortDirection);
			if ($registrations->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $registrations->getLastPageRangeInfo();
			unset($registrations);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('registrations', $registrations);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		// Set search parameters
		foreach ($this->_getSearchFormDuplicateParameters() as $param)
			$templateMgr->assign($param, $request->getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', $this->_getSearchFieldOptions());
		$templateMgr->assign('dateFieldOptions', $this->_getDateFieldOptions());
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
	function _getSearchFormDuplicateParameters() {
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
	function _getSearchFieldOptions() {
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
	function _getDateFieldOptions() {
		return array(
			REGISTRATION_DATE_REGISTERED => 'manager.registration.dateRegistered',
			REGISTRATION_DATE_PAID => 'manager.registration.datePaid'
		);
	}

	/**
	 * Delete a registration.
	 * @param $args array first parameter is the ID of the registration to delete
	 * @param $request PKPRequest
	 */
	function deleteRegistration($args, &$request) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$schedConf =& $request->getSchedConf();
			$registrationId = (int) $args[0];

			$registrationDao = DAORegistry::getDAO('RegistrationDAO');

			// Ensure registration is for this scheduled conference
			if ($registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getId()) {
				$registrationDao->deleteRegistrationById($registrationId);
			}
		}

		$request->redirect(null, null, null, 'registration');
	}

	/**
	 * Display form to edit a registration.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editRegistration($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$schedConf =& $request->getSchedConf();
		$registrationId = !isset($args) || empty($args) ? null : (int) $args[0];
		$userId = $request->getUserVar('userId');
		$registrationDao = DAORegistry::getDAO('RegistrationDAO');

		// Ensure registration is valid and for this scheduled conference
		if (($registrationId != null && $registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getId()) || ($registrationId == null && $userId)) {
			import('classes.registration.form.RegistrationForm');

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registration'), 'manager.registration'));

			if ($registrationId == null) {
				$templateMgr->assign('registrationTitle', 'manager.registration.createTitle');
			} else {
				$templateMgr->assign('registrationTitle', 'manager.registration.editTitle');	
			}

			$registrationForm = new RegistrationForm($registrationId, $userId);
			if ($registrationForm->isLocaleResubmit()) {
				$registrationForm->readInputData();
			} else {
				$registrationForm->initData();
			}
			$registrationForm->display();

		} else {
			$request->redirect(null, null, null, 'registration');
		}
	}

	/**
	 * Display form to create new registration.
	 */
	function createRegistration($args, &$request) {
		$this->editRegistration($args, $request);
	}

	/**
	 * Display a list of users from which to choose a registrant.
	 */
	function selectRegistrant($args, &$request) {
		$this->validate();
		$templateMgr =& TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registration'), 'manager.registration'));

		$userDao = DAORegistry::getDAO('UserDAO');
		$roleDao = DAORegistry::getDAO('RoleDAO');
		
		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = $request->getUserVar('sortDirection');
		
		$rangeInfo =& $this->ggetRangeInfo($request, 'users', array((string) $search, (string) $searchMatch, (string) $searchType));

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
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

		$templateMgr->assign('isSchedConfManager', true);

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');
		$templateMgr->assign('registrationId', $request->getUserVar('registrationId'));
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('registration/users.tpl');
	}

	/**
	 * Save changes to a registration.
	 */
	function updateRegistration($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.registration.form.RegistrationForm');

		$schedConf =& $request->getSchedConf();
		$registrationId = $request->getUserVar('registrationId') == null ? null : (int) $request->getUserVar('registrationId');
		$registrationDao = DAORegistry::getDAO('RegistrationDAO');

		if (($registrationId != null && $registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getId()) || $registrationId == null) {

			$registrationForm = new RegistrationForm($registrationId);
			$registrationForm->readInputData();

			if ($registrationForm->validate()) {
				$registrationForm->execute();

				if ($request->getUserVar('createAnother')) {
					$request->redirect(null, null, null, 'selectRegistrant', null, array('registrationCreated', 1));
				} else {
					$request->redirect(null, null, null, 'registration');
				}

			} else {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registration'), 'manager.registration'));

				if ($registrationId == null) {
					$templateMgr->assign('registrationTitle', 'manager.registration.createTitle');
				} else {
					$templateMgr->assign('registrationTitle', 'manager.registration.editTitle');	
				}

				$registrationForm->display();
			}

		} else {
			$request->redirect(null, null, null, 'registration');
		}
	}

	/**
	 * Display a list of registration types for the current scheduled conference.
	 */
	function registrationTypes($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$rangeInfo = $this->getRangeInfo($request, 'registrationTypes', array());
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
		while (true) {
			$registrationTypes =& $registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getId(), $rangeInfo);
			if ($registrationTypes->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $registrationTypes->getLastPageRangeInfo();
			unset($registrationTypes);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('registrationTypes', $registrationTypes);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		$templateMgr->display('registration/registrationTypes.tpl');
	}

	/**
	 * Rearrange the order of registration types.
	 */
	function moveRegistrationType($args, &$request) {
		$this->validate();

		$registrationTypeId = isset($args[0])?$args[0]:0;
		$schedConf =& $request->getSchedConf();

		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationType =& $registrationTypeDao->getRegistrationType($registrationTypeId);

		if ($registrationType && $registrationType->getSchedConfId() == $schedConf->getId()) {
			$isDown = $request->getUserVar('dir')=='d';
			$registrationType->setSequence($registrationType->getSequence()+($isDown?1.5:-1.5));
			$registrationTypeDao->updateRegistrationType($registrationType);
			$registrationTypeDao->resequenceRegistrationTypes($registrationType->getSchedConfId());
		}

		$request->redirect(null, null, null, 'registrationTypes');
	}

	/**
	 * Delete a registration type.
	 * @param $args array first parameter is the ID of the registration type to delete
	 */
	function deleteRegistrationType($args, &$request) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$schedConf =& $request->getSchedConf();
			$registrationTypeId = (int) $args[0];

			$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');

			// Ensure registration type is for this scheduled conference.
			if ($registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getId()) {
				$registrationTypeDao->deleteRegistrationTypeById($registrationTypeId);
			}
		}

		$request->redirect(null, null, null, 'registrationTypes');
	}

	/**
	 * Display form to edit a registration type.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editRegistrationType($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$registrationTypeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');

		// Ensure registration type is valid and for this scheduled conference.
		if (($registrationTypeId != null && $registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getId()) || $registrationTypeId == null) {

			import('classes.registration.form.RegistrationTypeForm');

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registrationTypes'), 'manager.registrationTypes'));

			if ($registrationTypeId == null) {
				$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.createTitle');
			} else {
				$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.editTitle');	
			}

			$registrationTypeForm = new RegistrationTypeForm($registrationTypeId);

			if ($registrationTypeForm->isLocaleResubmit()) {
				$registrationTypeForm->readInputData();
			} else {
				$registrationTypeForm->initData();
			}
			$registrationTypeForm->display();

		} else {
			$request->redirect(null, null, null, 'registrationTypes');
		}
	}

	/**
	 * Display form to create new registration type.
	 */
	function createRegistrationType($args, &$request) {
		$this->editRegistrationType($args, $request);
	}

	/**
	 * Save changes to a registration type.
	 */
	function updateRegistrationType($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.registration.form.RegistrationTypeForm');

		$schedConf =& $request->getSchedConf();
		$registrationTypeId = $request->getUserVar('typeId') == null ? null : (int) $request->getUserVar('typeId');
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');

		if (($registrationTypeId != null && $registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getId()) || $registrationTypeId == null) {

			$registrationTypeForm = new RegistrationTypeForm($registrationTypeId);
			$registrationTypeForm->readInputData();

			if ($registrationTypeForm->validate()) {
				$registrationTypeForm->execute();

				if ($request->getUserVar('createAnother')) {
					$templateMgr =& TemplateManager::getManager($request);
					$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registrationTypes'), 'manager.registrationTypes'));
					$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.createTitle');
					$templateMgr->assign('registrationTypeCreated', '1');
					unset($registrationTypeForm);
					$registrationTypeForm = new RegistrationTypeForm($registrationTypeId);
					$registrationTypeForm->initData();
					$registrationTypeForm->display();

				} else {
					$request->redirect(null, null, null, 'registrationTypes');
				}
			} else {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registrationTypes'), 'manager.registrationTypes'));

				if ($registrationTypeId == null) {
					$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.createTitle');
				} else {
					$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.editTitle');	
				}
				$registrationTypeForm->display();
			}
		} else {
			$request->redirect(null, null, null, 'registrationTypes');
		}
	}

	/**
	 * Display a list of registration options for the current scheduled conference.
	 */
	function registrationOptions($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$rangeInfo = $this->getRangeInfo($request, 'registrationOptions', array());
		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
		while (true) {
			$registrationOptions =& $registrationOptionDao->getRegistrationOptionsBySchedConfId($schedConf->getId(), $rangeInfo);
			if ($registrationOptions->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $registrationOptions->getLastPageRangeInfo();
			unset($registrationOptions);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('registrationOptions', $registrationOptions);
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.registration');

		$templateMgr->display('registration/registrationOptions.tpl');
	}

	/**
	 * Rearrange the order of registration options.
	 */
	function moveRegistrationOption($args, &$request) {
		$this->validate();

		$registrationOptionId = isset($args[0])?$args[0]:0;
		$schedConf =& $request->getSchedConf();

		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationOption =& $registrationOptionDao->getRegistrationOption($registrationOptionId);

		if ($registrationOption && $registrationOption->getSchedConfId() == $schedConf->getId()) {
			$isDown = $request->getUserVar('dir')=='d';
			$registrationOption->setSequence($registrationOption->getSequence()+($isDown?1.5:-1.5));
			$registrationOptionDao->updateRegistrationOption($registrationOption);
			$registrationOptionDao->resequenceRegistrationOptions($registrationOption->getSchedConfId());
		}

		$request->redirect(null, null, null, 'registrationOptions');
	}

	/**
	 * Delete a registration option.
	 * @param $args array first parameter is the ID of the registration type to delete
	 */
	function deleteRegistrationOption($args, &$request) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$schedConf =& $request->getSchedConf();
			$registrationOptionId = (int) $args[0];

			$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');

			// Ensure registration option is for this scheduled conference.
			if ($registrationOptionDao->getRegistrationOptionSchedConfId($registrationOptionId) == $schedConf->getId()) {
				$registrationOptionDao->deleteRegistrationOptionById($registrationOptionId);
			}
		}

		$request->redirect(null, null, null, 'registrationOptions');
	}

	/**
	 * Display form to edit a registration option.
	 * @param $args array optional, first parameter is the ID of the registration option to edit
	 */
	function editRegistrationOption($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$registrationOptionId = !isset($args) || empty($args) ? null : (int) $args[0];
		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');

		// Ensure registration option is valid and for this scheduled conference.
		if (($registrationOptionId != null && $registrationOptionDao->getRegistrationOptionSchedConfId($registrationOptionId) == $schedConf->getId()) || $registrationOptionId == null) {

			import('classes.registration.form.RegistrationOptionForm');

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registrationOptions'), 'manager.registrationOptions'));

			if ($registrationOptionId == null) {
				$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.createTitle');
			} else {
				$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.editTitle');	
			}

			$registrationOptionForm = new RegistrationOptionForm($registrationOptionId);
			if ($registrationOptionForm->isLocaleResubmit()) {
				$registrationOptionForm->readInputData();
			} else {
				$registrationOptionForm->initData();
			}
			$registrationOptionForm->display();

		} else {
			$request->redirect(null, null, null, 'registrationOptions');
		}
	}

	/**
	 * Display form to create new registration option.
	 */
	function createRegistrationOption($args, &$request) {
		$this->editRegistrationOption($args, $request);
	}

	/**
	 * Save changes to a registration option.
	 */
	function updateRegistrationOption($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.registration.form.RegistrationOptionForm');

		$schedConf =& $request->getSchedConf();
		$registrationOptionId = $request->getUserVar('optionId') == null ? null : (int) $request->getUserVar('optionId');
		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');

		if (($registrationOptionId != null && $registrationOptionDao->getRegistrationOptionSchedConfId($registrationOptionId) == $schedConf->getId()) || $registrationOptionId == null) {

			$registrationOptionForm = new RegistrationOptionForm($registrationOptionId);
			$registrationOptionForm->readInputData();

			if ($registrationOptionForm->validate()) {
				$registrationOptionForm->execute();

				if ($request->getUserVar('createAnother')) {
					$this->setupTemplate($request, true);

					$templateMgr =& TemplateManager::getManager($request);
					$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registrationOptions'), 'manager.registrationOptions'));
					$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.createTitle');
					$templateMgr->assign('registrationOptionCreated', '1');
					unset($registrationOptionForm);
					$registrationOptionForm = new RegistrationOptionForm($registrationOptionId);
					$registrationOptionForm->initData();
					$registrationOptionForm->display();

				} else {
					$request->redirect(null, null, null, 'registrationOptions');
				}

			} else {
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registrationOptions'), 'manager.registrationOptions'));

				if ($registrationOptionId == null) {
					$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.createTitle');
				} else {
					$templateMgr->assign('registrationOptionTitle', 'manager.registrationOptions.editTitle');	
				}

				$registrationOptionForm->display();
			}

		} else {
			$request->redirect(null, null, null, 'registrationOptions');
		}
	}

	/**
	 * Display registration policies for the current scheduled conference.
	 */
	function registrationPolicies($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.registration.form.RegistrationPolicyForm');

		$templateMgr =& TemplateManager::getManager($request);
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
	function saveRegistrationPolicies($args, &$request) {
		$this->validate();

		import('classes.registration.form.RegistrationPolicyForm');

		$registrationPolicyForm = new RegistrationPolicyForm();
		$registrationPolicyForm->readInputData();

		if ($registrationPolicyForm->validate()) {
			$registrationPolicyForm->execute();
			$request->redirect(null, null, 'manager', 'registration');
		}
	}

	function setupTemplate($request, $subclass = false) {
		parent::setupTemplate($request, true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->append('pageHierarchy', array($request->url(null, null, 'manager', 'registration'), 'manager.registration'));
		}
	}
}

?>
