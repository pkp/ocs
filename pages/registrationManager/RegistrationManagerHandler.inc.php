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
	 * Display a list of registrations for the current event.
	 */
	function registrations() {
		RegistrationManagerHandler::validate();
		RegistrationManagerHandler::setupTemplate();

		$event = &Request::getEvent();
		$rangeInfo = &Handler::getRangeInfo('registrations');
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
		$registrations = &$registrationDao->getRegistrationsByEventId($event->getEventId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('registrations', $registrations);
		$templateMgr->assign('helpTopicId', 'event.managementPages.registrations');
		$templateMgr->display('registration/registrations.tpl');
	}

	/**
	 * Delete a registration.
	 * @param $args array first parameter is the ID of the registration to delete
	 */
	function deleteRegistration($args) {
		RegistrationManagerHandler::validate();
		
		if (isset($args) && !empty($args)) {
			$event = &Request::getEvent();
			$registrationId = (int) $args[0];
		
			$registrationDao = &DAORegistry::getDAO('RegistrationDAO');

			// Ensure registration is for this event
			if ($registrationDao->getRegistrationEventId($registrationId) == $event->getEventId()) {
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

		$event = &Request::getEvent();
		$registrationId = !isset($args) || empty($args) ? null : (int) $args[0];
		$userId = Request::getUserVar('userId');
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');

		// Ensure registration is valid and for this event
		if (($registrationId != null && $registrationDao->getRegistrationEventId($registrationId) == $event->getEventId()) || ($registrationId == null && $userId)) {
			import('registration.form.RegistrationForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager'), 'registrationManager.registrationManagement'));

			if ($registrationId == null) {
				$templateMgr->assign('registrationTitle', 'director.registrations.createTitle');
			} else {
				$templateMgr->assign('registrationTitle', 'director.registrations.editTitle');	
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

		$templateMgr->assign('isEventManager', false);

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign('helpTopicId', 'event.managementPages.registrations');
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
		
		$event = &Request::getEvent();
		$registrationId = Request::getUserVar('registrationId') == null ? null : (int) Request::getUserVar('registrationId');
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');

		if (($registrationId != null && $registrationDao->getRegistrationEventId($registrationId) == $event->getEventId()) || $registrationId == null) {

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
					$templateMgr->assign('registrationTitle', 'director.registrations.createTitle');
				} else {
					$templateMgr->assign('registrationTitle', 'director.registrations.editTitle');	
				}

				$registrationForm->display();
			}
			
		} else {
				Request::redirect(null, null, null, 'registrations');
		}
	}

	/**
	 * Display a list of registration types for the current event.
	 */
	function registrationTypes() {
		RegistrationManagerHandler::validate();
		RegistrationManagerHandler::setupTemplate(true);

		$event = &Request::getEvent();
		$rangeInfo = &Handler::getRangeInfo('registrationTypes');
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationTypes = &$registrationTypeDao->getRegistrationTypesByEventId($event->getEventId(), $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('registrationTypes', $registrationTypes);
		$templateMgr->assign('helpTopicId', 'event.managementPages.registrations');

		$templateMgr->display('registration/registrationTypes.tpl');
	}

	/**
	 * Rearrange the order of registration types.
	 */
	function moveRegistrationType($args) {
		RegistrationManagerHandler::validate();

		$registrationTypeId = isset($args[0])?$args[0]:0;
		$event = &Request::getEvent();

		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationType = &$registrationTypeDao->getRegistrationType($registrationTypeId);

		if ($registrationType && $registrationType->getEventId() == $event->getEventId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$registrationType->setSequence($registrationType->getSequence()+($isDown?1.5:-1.5));
			$registrationTypeDao->updateRegistrationType($registrationType);
			$registrationTypeDao->resequenceRegistrationTypes($registrationType->getEventId());
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
			$event = &Request::getEvent();
			$registrationTypeId = (int) $args[0];
		
			$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

			// Ensure registration type is for this event
			if ($registrationTypeDao->getRegistrationTypeEventId($registrationTypeId) == $event->getEventId()) {
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

		$event = &Request::getEvent();
		$registrationTypeId = !isset($args) || empty($args) ? null : (int) $args[0];
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

		// Ensure registration type is valid and for this event
		if (($registrationTypeId != null && $registrationTypeDao->getRegistrationTypeEventId($registrationTypeId) == $event->getEventId()) || $registrationTypeId == null) {

			import('registration.form.RegistrationTypeForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'director', 'registrationTypes'), 'director.registrationTypes'));

			if ($registrationTypeId == null) {
				$templateMgr->assign('registrationTypeTitle', 'director.registrationTypes.createTitle');
			} else {
				$templateMgr->assign('registrationTypeTitle', 'director.registrationTypes.editTitle');	
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
		
		$event = &Request::getEvent();
		$registrationTypeId = Request::getUserVar('typeId') == null ? null : (int) Request::getUserVar('typeId');
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

		if (($registrationTypeId != null && $registrationTypeDao->getRegistrationTypeEventId($registrationTypeId) == $event->getEventId()) || $registrationTypeId == null) {

			$registrationTypeForm = &new RegistrationTypeForm($registrationTypeId);
			$registrationTypeForm->readInputData();
			
			if ($registrationTypeForm->validate()) {
				$registrationTypeForm->execute();

				if (Request::getUserVar('createAnother')) {
					RegistrationManagerHandler::setupTemplate(true);

					$templateMgr = &TemplateManager::getManager();
					$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager', 'registrationTypes'), 'director.registrationTypes'));
					$templateMgr->assign('registrationTypeTitle', 'director.registrationTypes.createTitle');
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
				$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'registrationManager', 'registrationTypes'), 'director.registrationTypes'));

				if ($registrationTypeId == null) {
					$templateMgr->assign('registrationTypeTitle', 'director.registrationTypes.createTitle');
				} else {
					$templateMgr->assign('registrationTypeTitle', 'director.registrationTypes.editTitle');	
				}

				$registrationTypeForm->display();
			}
			
		} else {
				Request::redirect(null, null, null, 'registrationTypes');
		}
	}

	/**
	 * Display registration policies for the current event.
	 */
	function registrationPolicies() {
		RegistrationManagerHandler::validate();
		RegistrationManagerHandler::setupTemplate(true);

		import('registration.form.RegistrationPolicyForm');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'event.managementPages.registrations');

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		$registrationPolicyForm = &new RegistrationPolicyForm();
		$registrationPolicyForm->initData();
		$registrationPolicyForm->display();
	}
	
	/**
	 * Save registration policies for the current event.
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
			$templateMgr->assign('helpTopicId', 'event.managementPages.registrations');
			$templateMgr->assign('registrationPoliciesSaved', '1');

			if (Config::getVar('general', 'scheduled_tasks')) {
				$templateMgr->assign('scheduledTasksEnabled', true);
			}

			$registrationPolicyForm->display();
		} else {
			RegistrationManagerHandler::setupTemplate(true);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('helpTopicId', 'event.managementPages.registrations');
			//$templateMgr->assign('registrationPoliciesSaved', '1');

			if (Config::getVar('general', 'scheduled_tasks')) {
				$templateMgr->assign('scheduledTasksEnabled', true);
			}

			$registrationPolicyForm->display();
		}
	}

	/**
	 * Validate that user has permissions to manage registrations for the
	 * selected event. Redirects to user index page if not properly
	 * authenticated.
	 */
	function validate() {
		parent::validate();
		$event =& Request::getEvent();
		$conference =& Request::getConference();
		if (!$event || !Validation::isRegistrationManager($conference->getConferenceId(), $event->getEventId())) {
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
