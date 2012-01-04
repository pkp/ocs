<?php

/**
 * @file PeopleHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PeopleHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for people management functions.
 */

//$Id$

import('pages.manager.ManagerHandler');

class PeopleHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function PeopleHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display list of people in the selected role.
	 * @param $args array first parameter is the role ID to display
	 */
	function people($args) {
		$this->validate();
		$this->setupTemplate(true);

		$roleDao =& DAORegistry::getDAO('RoleDAO');

		if (Request::getUserVar('roleSymbolic')!=null) $roleSymbolic = Request::getUserVar('roleSymbolic');
		else $roleSymbolic = isset($args[0])?$args[0]:'all';

		if ($roleSymbolic != 'all' && String::regexp_match_get('/^(\w+)s$/', $roleSymbolic, $matches)) {
			$roleId = $roleDao->getRoleIdFromPath($matches[1]);
			if ($roleId == null) {
				Request::redirect(null, null, null, 'all');
			}
			$roleName = $roleDao->getRoleName($roleId, true);

		} else {
			$roleId = 0;
			$roleName = 'manager.people.allUsers';
		}

		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = Request::getUserVar('sortDirection');

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$templateMgr =& TemplateManager::getManager();

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo =& Handler::getRangeInfo('users', array((string) $search, (string) $searchMatch, (string) $searchType, $roleId));

		if ($roleId) {
			while (true) {
				$users =& $roleDao->getUsersByRoleId($roleId, $conference->getId(),
					($schedConf? $schedConf->getId() : null), $searchType, $search, $searchMatch, $rangeInfo, $sort, $sortDirection);
				if ($users->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $users->getLastPageRangeInfo();
				unset($users);
			}
			$templateMgr->assign('roleId', $roleId);
			switch($roleId) {
				case ROLE_ID_CONFERENCE_MANAGER:
					$helpTopicId = 'conference.roles.conferenceManager';
					break;
				case ROLE_ID_DIRECTOR:
					$helpTopicId = 'conference.roles.directors';
					break;
				case ROLE_ID_TRACK_DIRECTOR:
					$helpTopicId = 'conference.roles.trackDirectors';
					break;
				case ROLE_ID_REVIEWER:
					$helpTopicId = 'conference.roles.reviewers';
					break;
				case ROLE_ID_AUTHOR:
					$helpTopicId = 'conference.roles.authors';
					break;
				case ROLE_ID_READER:
					$helpTopicId = 'conference.roles.readers';
					break;
				default:
					$helpTopicId = 'conference.roles.indexs';
					break;
			}
		} else {
			$users =& $roleDao->getUsersByConferenceId($conference->getId(), $searchType, $search, $searchMatch, $rangeInfo, $sort, $sortDirection);
			$helpTopicId = 'conference.users.allUsers';
		}

		$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnQuality', $conference->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('qualityRatings', $conference->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($conference->getId()) : null);
		}
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email',
			USER_FIELD_INTERESTS => 'user.interests'
		));
		$templateMgr->assign('rolePath', $roleDao->getRolePath($roleId));
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->assign('isSchedConfManagement', $schedConf ? true : false);
		$templateMgr->assign('isConferenceManagement', $schedConf ? false : true);
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('manager/people/enrollment.tpl');
	}

	/**
	 * Search for users to enroll in a specific role.
	 * @param $args array first parameter is the selected role ID
	 */
	function enrollSearch($args) {
		$this->validate();
		$this->setupTemplate(true);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$roleId = (int)(isset($args[0])?$args[0]:Request::getUserVar('roleId'));
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$templateMgr =& TemplateManager::getManager();

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
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

		$templateMgr->assign('isSchedConfManagement', $schedConf ? true : false);
		$templateMgr->assign('isConferenceManagement', $schedConf ? false : true);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_INTERESTS => 'user.interests',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('helpTopicId', 'conference.users.index');
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('manager/people/searchUsers.tpl');
	}

	/**
	 * Enroll a user in a role.
	 */
	function enroll($args) {
		$this->validate();

		$roleId = (int)(isset($args[0])?$args[0]:Request::getUserVar('roleId'));

		// Get a list of users to enroll -- either from the
		// submitted array 'users', or the single user ID in
		// 'userId'
		$users = Request::getUserVar('users');
		if (!isset($users) && Request::getUserVar('userId') != null) {
			$users = array(Request::getUserVar('userId'));
		}

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$rolePath = $roleDao->getRolePath($roleId);

		$isConferenceManager = Validation::isConferenceManager($conference->getId()) || Validation::isSiteAdmin();

		// Don't allow scheduled conference directors (who can end up here) to enroll
		// conference managers or scheduled conference directors.
		if ($users != null &&
				is_array($users) &&
				$rolePath != '' &&
				$rolePath != ROLE_PATH_SITE_ADMIN &&
				$isConferenceManager) {

			$schedConfId = ($schedConf? $schedConf->getId() : 0);

			for ($i=0; $i<count($users); $i++) {
				if (!$roleDao->roleExists($conference->getId(), $schedConfId, $users[$i], $roleId)) {
					if ($schedConfId == 0) {
						// In case they're enrolled in individual scheduled conferences and we want to enrol
						// them in the whole conference, ensure they don't have multiple roles
						$roleDao->deleteRoleByUserId($users[$i], $conference->getId(), $roleId);
					} else if ($roleDao->roleExists($conference->getId(), 0, $users[$i], $roleId)) {
						// If they're enrolled in the whole conference, this individual
						// enrollment isn't valuable.
						return;
					}

					$role = new Role();
					$role->setConferenceId($conference->getId());
					if ($schedConf && $rolePath != ROLE_PATH_CONFERENCE_MANAGER) {
						$role->setSchedConfId($schedConfId);
					} else {
						$role->setSchedConfId(0);
					}
					$role->setUserId($users[$i]);
					$role->setRoleId($roleId);
					$roleDao->insertRole($role);
				}
			}
		}

		Request::redirect(null, null, null, 'people', (empty($rolePath) ? null : $rolePath . 's'));
	}

	/**
	 * Unenroll a user from a role.
	 */
	function unEnroll($args) {
		$roleId = isset($args[0])?$args[0]:0;
		$this->validate();

		$conference =& Request::getConference();
		$isConferenceManager = Validation::isConferenceManager($conference->getId()) || Validation::isSiteAdmin();
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		// Don't allow scheduled conference managers to unenroll scheduled conference managers or
		// conference managers. FIXME is this still relevant?
		if ($roleId != ROLE_ID_SITE_ADMIN && $isConferenceManager) {
			$roleDao->deleteRoleByUserId(Request::getUserVar('userId'), $conference->getId(), $roleId);
		}

		Request::redirect(null, null, null, 'people', $roleDao->getRolePath($roleId) . 's');
	}

	/**
	 * Show form to synchronize user enrollment with another conference.
	 */
	function enrollSyncSelect($args) {
		$this->validate();
		$this->setupTemplate(true);

		$rolePath = isset($args[0]) ? $args[0] : '';
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);
		if ($roleId) {
			$roleName = $roleDao->getRoleName($roleId, true);
		} else {
			$rolePath = '';
			$roleName = '';
		}

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conferenceTitles =& $conferenceDao->getConferenceTitles();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		if (!$schedConf) Request::redirect(null, null, 'manager');

		unset($conferenceTitles[$conference->getId()]);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('rolePath', $rolePath);
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign('conferenceOptions', $conferenceTitles);
		$templateMgr->display('manager/people/enrollSync.tpl');
	}

	/**
	 * Synchronize user enrollment with another conference.
	 */
	function enrollSync($args) {
		$this->validate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$rolePath = Request::getUserVar('rolePath');
		$syncConference = Request::getUserVar('syncConference');

		if (!$schedConf) Request::redirect(null, null, 'manager');

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);

		if ((!empty($roleId) || $rolePath == 'all') && !empty($syncConference)) {
			$roles =& $roleDao->getRolesByConferenceId($syncConference == 'all' ? null : $syncConference, $roleId);
			while (!$roles->eof()) {
				$role =& $roles->next();
				$role->setConferenceId($conference->getId());
				$role->setSchedConfId($schedConf->getId());
				if ($role->getRolePath() != ROLE_PATH_SITE_ADMIN && !$roleDao->roleExists($role->getConferenceId(), $schedConf->getId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
		}

		Request::redirect(null, null, null, 'people', $roleDao->getRolePath($roleId));
	}

	/**
	 * Get a suggested username, making sure it's not
	 * already used by the system. (Poor-man's AJAX.)
	 */
	function suggestUsername() {
		$this->validate();
		$suggestion = Validation::suggestUsername(
			Request::getUserVar('firstName'),
			Request::getUserVar('lastName')
		);
		echo $suggestion;
	}

	/**
	 * Display form to create a new user.
	 */
	function createUser() {
		PeopleHandler::editUser();
	}

	/**
	 * Display form to create/edit a user profile.
	 * @param $args array optional, if set the first parameter is the ID of the user to edit
	 */
	function editUser($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		$conference =& Request::getConference();

		$userId = isset($args[0])?$args[0]:null;

		$templateMgr =& TemplateManager::getManager();

		if ($userId !== null && !Validation::canAdminister($conference->getId(), $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');

			return $templateMgr->display('common/error.tpl');
		}

		import('manager.form.UserManagementForm');

		$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$userForm = new UserManagementForm($userId);
		} else {
			$userForm =& new UserManagementForm($userId);
		}
		if ($userForm->isLocaleResubmit()) {
			$userForm->readInputData();
		} else {
			$userForm->initData();
		}
		$userForm->display();
	}

	/**
	 * Allow the Conference Manager to merge user accounts, including attributed papers etc.
	 */
	function mergeUsers($args) {
		$this->validate();
		$this->setupTemplate(true);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$schedConfId = isset($schedConf)? $schedConf->getId() : null;
		$templateMgr =& TemplateManager::getManager();

		$oldUserIds = (array) Request::getUserVar('oldUserIds');
		$newUserId = Request::getUserVar('newUserId');

		// Ensure that we have administrative priveleges over the specified user(s).
		$canAdministerAll = true;
		foreach ($oldUserIds as $oldUserId) {
			if (!Validation::canAdminister($conference->getId(), $oldUserId)) $canAdministerAll = false;
		}
		if (
			(!empty($oldUserIds) && !$canAdministerAll) ||
			(!empty($newUserId) && !Validation::canAdminister($conference->getId(), $newUserId))
		) {
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		if (!empty($oldUserIds) && !empty($newUserId)) {
			import('user.UserAction');
			foreach ($oldUserIds as $oldUserId) {
				UserAction::mergeUsers($oldUserId, $newUserId);
			}
			Request::redirect(null, null, 'manager');
		}

		// The manager must select one or both IDs.
		if (Request::getUserVar('roleSymbolic')!=null) $roleSymbolic = Request::getUserVar('roleSymbolic');
		else $roleSymbolic = isset($args[0])?$args[0]:'all';

		if ($roleSymbolic != 'all' && String::regexp_match_get('/^(\w+)s$/', $roleSymbolic, $matches)) {
			$roleId = $roleDao->getRoleIdFromPath($matches[1]);
			if ($roleId == null) {
				Request::redirect(null, null, null, null, 'all');
			}
			$roleName = $roleDao->getRoleName($roleId, true);
		} else {
			$roleId = 0;
			$roleName = 'manager.people.allUsers';
		}

		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'name';
		$sortDirection = Request::getUserVar('sortDirection');

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo =& Handler::getRangeInfo('users', array($roleId, (string) $search, (string) $searchMatch, (string) $searchType));

		if ($roleId) {
			while (true) {
				$users =& $roleDao->getUsersByRoleId($roleId, $conference->getId(), $schedConfId, $searchType, $search, $searchMatch, $rangeInfo, $sort);
				if ($users->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $users->getLastPageRangeInfo();
				unset($users);
			}
			$templateMgr->assign('roleId', $roleId);
		} else {
			while (true) {
				$users =& $roleDao->getUsersByConferenceId($conference->getId(), $searchType, $search, $searchMatch, $rangeInfo, $sort);
				if ($users->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $users->getLastPageRangeInfo();
				unset($users);
			}
		}

		$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));
		$templateMgr->assign('helpTopicId', 'conference.users.mergeUsers');
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnQuality', $conference->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('qualityRatings', $conference->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($conference->getId()) : null);
		}
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email',
			USER_FIELD_INTERESTS => 'user.interests'
		));
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('oldUserIds', $oldUserIds);
		$templateMgr->assign('rolePath', $roleDao->getRolePath($roleId));
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->display('manager/people/selectMergeUser.tpl');
	}

	/**
	 * Disable a user's account.
	 * @param $args array the ID of the user to disable
	 */
	function disableUser($args) {
		$this->validate();
		$this->setupTemplate(true);

		$userId = isset($args[0])?$args[0]:Request::getUserVar('userId');
		$user =& Request::getUser();
		$conference =& Request::getConference();

		if ($userId != null && $userId != $user->getId()) {
			if (!Validation::canAdminister($conference->getId(), $userId)) {
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'manager.people');
				$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
				$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
				return $templateMgr->display('common/error.tpl');
			}
			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getUser($userId);
			if ($user) {
				$user->setDisabled(1);
				$user->setDisabledReason(Request::getUserVar('reason'));
			}
			$userDao->updateObject($user);
		}

		Request::redirect(null, null, null, 'people', 'all');
	}

	/**
	 * Enable a user's account.
	 * @param $args array the ID of the user to enable
	 */
	function enableUser($args) {
		$this->validate();
		$this->setupTemplate(true);

		$userId = isset($args[0])?$args[0]:null;
		$user =& Request::getUser();

		if ($userId != null && $userId != $user->getId()) {
			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getUser($userId, true);
			if ($user) {
				$user->setDisabled(0);
			}
			$userDao->updateObject($user);
		}

		Request::redirect(null, null, null, 'people', 'all');
	}

	/**
	 * Remove a user from all roles for the current conference.
	 * @param $args array the ID of the user to remove
	 */
	function removeUser($args) {
		$this->validate();
		$this->setupTemplate(true);

		$userId = isset($args[0])?$args[0]:null;
		$user =& Request::getUser();
		$conference =& Request::getConference();

		if ($userId != null && $userId != $user->getId()) {
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roleDao->deleteRoleByUserId($userId, $conference->getId());
		}

		Request::redirect(null, null, null, 'people', 'all');
	}

	/**
	 * Save changes to a user profile.
	 */
	function updateUser() {
		$this->validate();
		$this->setupTemplate(true);

		$conference =& Request::getConference();
		$userId = Request::getUserVar('userId');

		if (!empty($userId) && !Validation::canAdminister($conference->getId(), $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		import('manager.form.UserManagementForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$userForm = new UserManagementForm($userId);
		} else {
			$userForm =& new UserManagementForm($userId);
		}
		$userForm->readInputData();

		if ($userForm->validate()) {
			$userForm->execute();

			if (Request::getUserVar('createAnother')) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));
				$templateMgr->assign('userCreated', true);
				unset($userForm);
				if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
					$userForm = new UserManagementForm();
				} else {
					$userForm =& new UserManagementForm();
				}
				$userForm->initData();
				$userForm->display();

			} else {
				if ($source = Request::getUserVar('source')) Request::redirectUrl($source);
				else Request::redirect(null, null, null, 'people', 'all');
			}
		} else {
			$userForm->display();
		}
	}

	/**
	 * Display a user's profile.
	 * @param $args array first parameter is the ID or username of the user to display
	 */
	function userProfile($args) {
		$this->validate();
		$this->setupTemplate(true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));
		$templateMgr->assign('helpTopicId', 'conference.users.index');

		$userDao =& DAORegistry::getDAO('UserDAO');
		$userId = isset($args[0]) ? $args[0] : 0;
		if (is_numeric($userId)) {
			$userId = (int) $userId;
			$user = $userDao->getUser($userId);
		} else {
			$user = $userDao->getUserByUsername($userId);
		}


		if ($user == null) {
			// Non-existent user requested
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.invalidUser');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			$templateMgr->display('common/error.tpl');

		} else {
			$site =& Request::getSite();
			$conference =& Request::getConference();
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$roles =& $roleDao->getRolesByUserId($user->getId(), $conference->getId());

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$country = null;
			if ($user->getCountry() != '') {
				$country = $countryDao->getCountry($user->getCountry());
			}
			$templateMgr->assign('country', $country);

			$templateMgr->assign('isSchedConfManagement', Request::getSchedConf() ? true : false);
			$templateMgr->assign('isConferenceManagement', Request::getSchedConf() ? false : true);

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign_by_ref('userRoles', $roles);
			$templateMgr->assign('localeNames', AppLocale::getAllLocales());
			$templateMgr->display('manager/people/userProfile.tpl');
		}
	}
}

?>
