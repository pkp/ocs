<?php

/**
 * RegistrationManagerHandler.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.registrationManager
 *
 * Handle requests for registration management functions. 
 *
 * $Id$
 */

class RegistrationManagerHandler extends Handler {
	function index() {
		RegistrationManagerHandler::registrations();
	}

	/**
	 * Display a list of registrations for the current scheduled conference.
	 */
	function registrations() {
		RegistrationManagerHandler::validate();
		RegistrationManagerHandler::setupTemplate();

		$schedConf = &Request::getSchedConf();
		$rangeInfo = &Handler::getRangeInfo('registrations');
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
		$registrations = &$registrationDao->getRegistrationsBySchedConfId($schedConf->getSchedConfId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('registrations', $registrations);
		$templateMgr->assign('helpTopicId', 'schedConf.managementPages.registrations');
		$templateMgr->display('registration/registrations.tpl');
	}

	/**
	 * Delete a registration.
	 * @param $args array first parameter is the ID of the registration to delete
	 */
	function deleteRegistration($args) {
		RegistrationManagerHandler::validate();
		
		if (isset($args) && !empty($args)) {
			$schedConf = &Request::getSchedConf();
			$registrationId = (int) $args[0];
		
			$registrationDao = &DAORegistry::getDAO('RegistrationDAO');

			// Ensure registration is for this scheduled conference
			if ($registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getSchedConfId()) {
				$registrationDao->deleteRegistrationById($registrationId);
			}
		}
		
		Request::redirect(null, null, null, 'registrations');
	}

	/**
	 * Display form to edit a registration.
	 * @param $args array optional, first parameter is the ID of the registration to edit
	 */
	function editRegistration($args = array()) {
		RegistrationManagerHandler::validate();
		RegistrationManagerHandler::setupTemplate();

		$schedConf = &Request::getSchedConf();
		$registrationId = !isset($args) || empty($args) ? null : (int) $args[0];
		$userId = Request::getUserVar('userId');
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');

		// Ensure registration is valid and for this scheduled conference
		if (($registrationId != null && $registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getSchedConfId()) || ($registrationId == null && $userId)) {
			import('registration.form.RegistrationForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager'), 'registrationManager.registrationManagement'));

			if ($registrationId == null) {
				$templateMgr->assign('registrationTitle', 'manager.registrations.createTitle');
			} else {
				$templateMgr->assign('registrationTitle', 'manager.registrations.editTitle');	
			}

			$registrationForm = &new RegistrationForm($registrationId, $userId);
			$registrationForm->initData();
			$registrationForm->display();
		
		} else {
				Request::redirect(null, null, null, 'registrations');
		}
	}

	/**
	 * Display form to create new registration.
	 */
	function createRegistration() {
		RegistrationManagerHandler::editRegistration();
	}

	/**
	 * Display a list of users from which to choose a registrant.
	 */
	function selectRegistrant() {
		RegistrationManagerHandler::validate();
		$templateMgr = &TemplateManager::getManager();
		RegistrationManagerHandler::setupTemplate();
		$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager'), 'registrationManager.registrationManagement'));

		$userDao = &DAORegistry::getDAO('UserDAO');

		$searchType = null;
		$searchMatch = null;
		$search = $searchQuery = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');
			
		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = Handler::getRangeInfo('users');

		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);
		
		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $searchQuery);
		$templateMgr->assign('searchInitial', $searchInitial);

		$templateMgr->assign('isSchedConfManager', false);

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('helpTopicId', 'schedConf.managementPages.registrations');
		$templateMgr->assign('registrationId', Request::getUserVar('registrationId'));
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
		$templateMgr->display('registration/users.tpl');
	}

	/**
	 * Save changes to a registration.
	 */
	function updateRegistration() {
		RegistrationManagerHandler::validate();
		
		import('registration.form.RegistrationForm');
		
		$schedConf = &Request::getSchedConf();
		$registrationId = Request::getUserVar('registrationId') == null ? null : (int) Request::getUserVar('registrationId');
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');

		if (($registrationId != null && $registrationDao->getRegistrationSchedConfId($registrationId) == $schedConf->getSchedConfId()) || $registrationId == null) {

			$registrationForm = &new RegistrationForm($registrationId);
			$registrationForm->readInputData();
			
			if ($registrationForm->validate()) {
				$registrationForm->execute();

				if (Request::getUserVar('createAnother')) {
					Request::redirect(null, null, null, 'selectRegistrant', null, array('registrationCreated', 1));
				} else {
					Request::redirect(null, null, null, 'registrations');
				}
				
			} else {
				RegistrationManagerHandler::setupTemplate();

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager'), 'registrationManager.registrationManagement'));

				if ($registrationId == null) {
					$templateMgr->assign('registrationTitle', 'manager.registrations.createTitle');
				} else {
					$templateMgr->assign('registrationTitle', 'manager.registrations.editTitle');	
				}

				$registrationForm->display();
			}
			
		} else {
				Request::redirect(null, null, null, 'registrations');
		}
	}

	/**
	 * Display a list of registration types for the current scheduled conference.
	 */
	function registrationTypes() {
		RegistrationManagerHandler::validate();
		RegistrationManagerHandler::setupTemplate(true);

		$schedConf = &Request::getSchedConf();
		$rangeInfo = &Handler::getRangeInfo('registrationTypes');
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationTypes = &$registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getSchedConfId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('registrationTypes', $registrationTypes);
		$templateMgr->assign('helpTopicId', 'schedConf.managementPages.registrations');

		$templateMgr->display('registration/registrationTypes.tpl');
	}

	/**
	 * Rearrange the order of registration types.
	 */
	function moveRegistrationType($args) {
		RegistrationManagerHandler::validate();

		$registrationTypeId = isset($args[0])?$args[0]:0;
		$schedConf = &Request::getSchedConf();

		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationType = &$registrationTypeDao->getRegistrationType($registrationTypeId);

		if ($registrationType && $registrationType->getSchedConfId() == $schedConf->getSchedConfId()) {
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
		RegistrationManagerHandler::validate();
		
		if (isset($args) && !empty($args)) {
			$schedConf = &Request::getSchedConf();
			$registrationTypeId = (int) $args[0];
		
			$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

			// Ensure registration type is for this scheduled conference
			if ($registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getSchedConfId()) {
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
		RegistrationManagerHandler::validate();
		RegistrationManagerHandler::setupTemplate(true);

		$schedConf = &Request::getSchedConf();
		$registrationTypeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

		// Ensure registration type is valid and for this scheduled conference
		if (($registrationTypeId != null && $registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getSchedConfId()) || $registrationTypeId == null) {

			import('registration.form.RegistrationTypeForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager', 'registrationTypes'), 'manager.registrationTypes'));

			if ($registrationTypeId == null) {
				$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.createTitle');
			} else {
				$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.editTitle');	
			}

			$registrationTypeForm = &new RegistrationTypeForm($registrationTypeId);
			$registrationTypeForm->initData();
			$registrationTypeForm->display();
		
		} else {
				Request::redirect(null, null, null, 'registrationTypes');
		}
	}

	/**
	 * Display form to create new registration type.
	 */
	function createRegistrationType() {
		RegistrationManagerHandler::editRegistrationType();
	}

	/**
	 * Save changes to a registration type.
	 */
	function updateRegistrationType() {
		RegistrationManagerHandler::validate();
		
		import('registration.form.RegistrationTypeForm');
		
		$schedConf = &Request::getSchedConf();
		$registrationTypeId = Request::getUserVar('typeId') == null ? null : (int) Request::getUserVar('typeId');
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

		if (($registrationTypeId != null && $registrationTypeDao->getRegistrationTypeSchedConfId($registrationTypeId) == $schedConf->getSchedConfId()) || $registrationTypeId == null) {

			$registrationTypeForm = &new RegistrationTypeForm($registrationTypeId);
			$registrationTypeForm->readInputData();
			
			if ($registrationTypeForm->validate()) {
				$registrationTypeForm->execute();

				if (Request::getUserVar('createAnother')) {
					RegistrationManagerHandler::setupTemplate(true);

					$templateMgr = &TemplateManager::getManager();
					$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager', 'registrationTypes'), 'manager.registrationTypes'));
					$templateMgr->assign('registrationTypeTitle', 'manager.registrationTypes.createTitle');
					$templateMgr->assign('registrationTypeCreated', '1');

					$registrationTypeForm = &new RegistrationTypeForm($registrationTypeId);
					$registrationTypeForm->initData();
					$registrationTypeForm->display();
	
				} else {
					Request::redirect(null, null, null, 'registrationTypes');
				}
				
			} else {
				RegistrationManagerHandler::setupTemplate(true);

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager', 'registrationTypes'), 'manager.registrationTypes'));

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
	 * Display registration policies for the current scheduled conference.
	 */
	function registrationPolicies() {
		RegistrationManagerHandler::validate();
		RegistrationManagerHandler::setupTemplate(true);

		import('registration.form.RegistrationPolicyForm');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'schedConf.managementPages.registrations');

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		$registrationPolicyForm = &new RegistrationPolicyForm();
		$registrationPolicyForm->initData();
		$registrationPolicyForm->display();
	}
	
	/**
	 * Save registration policies for the current scheduled conference.
	 */
	function saveRegistrationPolicies($args = array()) {
		RegistrationManagerHandler::validate();

		import('registration.form.RegistrationPolicyForm');

		$registrationPolicyForm = &new RegistrationPolicyForm();
		$registrationPolicyForm->readInputData();

		if ($registrationPolicyForm->validate()) {
			$registrationPolicyForm->execute();

			RegistrationManagerHandler::setupTemplate(true);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('helpTopicId', 'schedConf.managementPages.registrations');
			$templateMgr->assign('registrationPoliciesSaved', '1');

			if (Config::getVar('general', 'scheduled_tasks')) {
				$templateMgr->assign('scheduledTasksEnabled', true);
			}

			$registrationPolicyForm->display();
		} else {
			RegistrationManagerHandler::setupTemplate(true);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('helpTopicId', 'schedConf.managementPages.registrations');
			//$templateMgr->assign('registrationPoliciesSaved', '1');

			if (Config::getVar('general', 'scheduled_tasks')) {
				$templateMgr->assign('scheduledTasksEnabled', true);
			}

			$registrationPolicyForm->display();
		}
	}

	/**
	 * Validate that user has permissions to manage registrations for the
	 * selected scheduled conference. Redirects to user index page if not properly
	 * authenticated.
	 */
	function validate() {
		parent::validate();
		$schedConf =& Request::getSchedConf();
		$conference =& Request::getConference();
		if (!$schedConf || !Validation::isRegistrationManager($conference->getConferenceId(), $schedConf->getSchedConfId())) {
			Validation::redirectLogin();
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, null, 'user'), 'navigation.user'), array(Request::url(null, null, 'registrationManager'), 'registrationManager.registrationManagement'))
				: array(array(Request::url(null, null, 'user'), 'navigation.user'))
		);
	}
}

?>
