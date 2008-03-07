<?php

/**
 * @file PeopleHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class PeopleHandler
 *
 * Handle requests for people management functions. 
 *
 * $Id$
 */

class PeopleHandler extends ManagerHandler {

	/**
	 * Display list of people in the selected role.
	 * @param $args array first parameter is the role ID to display
	 */	
	function people($args) {
		parent::validate();
		parent::setupTemplate(true);

		$roleDao = &DAORegistry::getDAO('RoleDAO');

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

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		$templateMgr = &TemplateManager::getManager();

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = &Handler::getRangeInfo('users', array((string) $search, (string) $searchMatch, (string) $searchType, $roleId));

		if ($roleId) {
			while (true) {
				$users = &$roleDao->getUsersByRoleId($roleId, $conference->getConferenceId(),
					($schedConf? $schedConf->getSchedConfId() : null), $searchType, $search, $searchMatch, $rangeInfo);
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
					$helpTopicId = 'conference.roles.director';
					break;
				case ROLE_ID_TRACK_DIRECTOR:
					$helpTopicId = 'conference.roles.trackDirector';
					break;
				case ROLE_ID_REVIEWER:
					$helpTopicId = 'conference.roles.reviewer';
					break;
				case ROLE_ID_PRESENTER:
					$helpTopicId = 'conference.roles.presenter';
					break;
				case ROLE_ID_READER:
					$helpTopicId = 'conference.roles.reader';
					break;
				default:
					$helpTopicId = 'conference.roles.index';
					break;
			}
		} else {
			$users = &$roleDao->getUsersByConferenceId($conference->getConferenceId(), $searchType, $search, $searchMatch, $rangeInfo);
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
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnQuality', $conference->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('qualityRatings', $conference->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($conference->getConferenceId()) : null);
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
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->assign('isSchedConfManagement', $schedConf ? true : false);
		$templateMgr->assign('isConferenceManagement', $schedConf ? false : true);
		$templateMgr->display('manager/people/enrollment.tpl');
	}

	/**
	 * Search for users to enroll in a specific role.
	 * @param $args array first parameter is the selected role ID
	 */
	function enrollSearch($args) {
		parent::validate();

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$roleId = (int)(isset($args[0])?$args[0]:Request::getUserVar('roleId'));
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$templateMgr = &TemplateManager::getManager();

		parent::setupTemplate(true);

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = &Handler::getRangeInfo('users', array((string) $search, (string) $searchMatch, (string) $searchType));

		while (true) {
			$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);
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
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
		$templateMgr->assign('helpTopicId', 'conference.users.index');
		$templateMgr->display('manager/people/searchUsers.tpl');
	}

	/**
	 * Enroll a user in a role.
	 */
	function enroll($args) {
		parent::validate();

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

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$rolePath = $roleDao->getRolePath($roleId);

		$isConferenceManager = Validation::isConferenceManager($conference->getConferenceId());

		// Don't allow scheduled conference directors (who can end up here) to enroll
		// conference managers or scheduled conference directors.
		if ($users != null &&
				is_array($users) &&
				$rolePath != '' &&
				$rolePath != ROLE_PATH_SITE_ADMIN &&
				$isConferenceManager) {

			$schedConfId = ($schedConf? $schedConf->getSchedConfId() : 0);

			for ($i=0; $i<count($users); $i++) {
				if (!$roleDao->roleExists($conference->getConferenceId(), $schedConfId, $users[$i], $roleId)) {
					if ($schedConfId == 0) {
						// In case they're enrolled in individual scheduled conferences and we want to enrol
						// them in the whole conference, ensure they don't have multiple roles
						$roleDao->deleteRoleByUserId($users[$i], $conference->getConferenceId(), $roleId);
					} else if ($roleDao->roleExists($conference->getConferenceId(), 0, $users[$i], $roleId)) {
						// If they're enrolled in the whole conference, this individual
						// enrollment isn't valuable.a
						return;
					}

					$role = &new Role();
					$role->setConferenceId($conference->getConferenceId());
					if ($schedConf) {
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
		parent::validate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$isConferenceManager = Validation::isConferenceManager($conference->getConferenceId());

		$roleDao = &DAORegistry::getDAO('RoleDAO');

		// Don't allow scheduled conference managers to unenroll scheduled conference managers or
		// conference managers. FIXME is this still relevant?
		if ($roleId != ROLE_ID_SITE_ADMIN && $isConferenceManager) {
			$roleDao->deleteRoleByUserId(Request::getUserVar('userId'), $conference->getConferenceId(), $roleId);
		}

		Request::redirect(null, null, null, 'people');
	}

	/**
	 * Show form to synchronize user enrollment with another conference.
	 */
	function enrollSyncSelect($args) {
		parent::validate();

		$rolePath = isset($args[0]) ? $args[0] : '';
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);
		if ($roleId) {
			$roleName = $roleDao->getRoleName($roleId, true);
		} else {
			$rolePath = '';
			$roleName = '';
		}

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$conferenceTitles = &$conferenceDao->getConferenceTitles();

		$conference = &Request::getConference();
		unset($conferenceTitles[$conference->getConferenceId()]);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('rolePath', $rolePath);
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign('conferenceOptions', $conferenceTitles);
		$templateMgr->display('manager/people/enrollSync.tpl');
	}

	/**
	 * Synchronize user enrollment with another conference.
	 */
	function enrollSync($args) {
		parent::validate();

		$conference = &Request::getConference();
		$schedConf =& Request::getSchedConf();
		$rolePath = Request::getUserVar('rolePath');
		$syncConference = Request::getUserVar('syncConference');

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);

		if ((!empty($roleId) || $rolePath == 'all') && !empty($syncConference)) {
			$roles = &$roleDao->getRolesByConferenceId($syncConference == 'all' ? null : $syncConference, $roleId);
			while (!$roles->eof()) {
				$role = &$roles->next();
				$role->setConferenceId($conference->getConferenceId());
				$role->setSchedConfId($schedConf->getSchedConfId());
				if ($role->getRolePath() != ROLE_PATH_SITE_ADMIN && !$roleDao->roleExists($role->getConferenceId(), $schedConf->getSchedConfId(), $role->getUserId(), $role->getRoleId())) {
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
		parent::validate();
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
		parent::validate();
		parent::setupTemplate(true);

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		if($schedConf) {
			$schedConfId = $schedConf->getSchedConfId();
		} else {
			$schedConfId = null;
		}

		$userId = isset($args[0])?$args[0]:null;

		$templateMgr = &TemplateManager::getManager();

		if ($userId !== null && !Validation::canAdminister($conference->getConferenceId(), $schedConfId, $userId)) {
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

		$userForm = &new UserManagementForm($userId);
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
		parent::validate();
		parent::setupTemplate(true);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$conference =& Request::getConference();
		$templateMgr =& TemplateManager::getManager();

		$oldUserId = Request::getUserVar('oldUserId');
		$newUserId = Request::getUserVar('newUserId');

		// Ensure that we have administrative priveleges over the specified user(s).
		if (
			(!empty($oldUserId) && !Validation::canAdminister($conference->getConferenceId(), $oldUserId)) ||
			(!empty($newUserId) && !Validation::canAdminister($conference->getConferenceId(), $newUserId))
		) {
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		if (!empty($oldUserId) && !empty($newUserId)) {
			// Both user IDs have been selected. Merge the accounts.

			$paperDao =& DAORegistry::getDAO('PaperDAO');
			foreach ($paperDao->getPapersByUserId($oldUserId) as $paper) {
				$paper->setUserId($newUserId);
				$paperDao->updatePaper($paper);
				unset($paper);
			}

			$commentDao =& DAORegistry::getDAO('CommentDAO');
			foreach ($commentDao->getCommentsByUserId($oldUserId) as $comment) {
				$comment->setUserId($newUserId);
				$commentDao->updateComment($comment);
				unset($comment);
			}

			$paperNoteDao =& DAORegistry::getDAO('PaperNoteDAO');
			$paperNotes =& $paperNoteDao->getPaperNotesByUserId($oldUserId);
			while ($paperNote =& $paperNotes->next()) {
				$paperNote->setUserId($newUserId);
				$paperNoteDao->updatePaperNote($paperNote);
				unset($paperNote);
			}

			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByUserId($oldUserId);
			while ($editAssignment =& $editAssignments->next()) {
				$editAssignment->setDirectorId($newUserId);
				$editAssignmentDao->updateEditAssignment($editAssignment);
				unset($editAssignment);
			}

			$directorSubmissionDao =& DAORegistry::getDAO('DirectorSubmissionDAO');
			$directorSubmissionDao->transferDirectorDecisions($oldUserId, $newUserId);

			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			foreach ($reviewAssignmentDao->getReviewAssignmentsByUserId($oldUserId) as $reviewAssignment) {
				$reviewAssignment->setReviewerId($newUserId);
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				unset($reviewAssignment);
			}

			$paperEmailLogDao =& DAORegistry::getDAO('PaperEmailLogDAO');
			$paperEmailLogDao->transferPaperLogEntries($oldUserId, $newUserId);
			$paperEventLogDao =& DAORegistry::getDAO('PaperEventLogDAO');
			$paperEventLogDao->transferPaperLogEntries($oldUserId, $newUserId);

			$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
			foreach ($paperCommentDao->getPaperCommentsByUserId($oldUserId) as $paperComment) {
				$paperComment->setAuthorId($newUserId);
				$paperCommentDao->updatePaperComment($paperComment);
				unset($paperComment);
			}

			$accessKeyDao =& DAORegistry::getDAO('AccessKeyDAO');
			$accessKeyDao->transferAccessKeys($oldUserId, $newUserId);

			// Delete the old user and associated info.
			$sessionDao =& DAORegistry::getDAO('SessionDAO');
			$sessionDao->deleteSessionsByUserId($oldUserId);
			$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
			$registrationDao->deleteRegistrationsByUserId($oldUserId);
			$temporaryFileDao =& DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFileDao->deleteTemporaryFilesByUserId($oldUserId);
			$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
			$notificationStatusDao->deleteNotificationStatusByUserId($oldUserId);
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
			$groupMembershipDao->deleteMembershipByUserId($oldUserId);
			$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');
			$trackDirectorsDao->deleteDirectorsByUserId($oldUserId);
			$roleDao->deleteRoleByUserId($oldUserId);
			$userDao->deleteUserById($oldUserId);

			Request::redirect(null, null, 'manager');
		}

		if (!empty($oldUserId)) {
			// Get the old username for the confirm prompt.
			$oldUser =& $userDao->getUser($oldUserId);
			$templateMgr->assign('oldUsername', $oldUser->getUsername());
			unset($oldUser);
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

		$searchType = null;
		$searchMatch = null;
		$search = Request::getUserVar('search');
		$searchInitial = Request::getUserVar('searchInitial');
		if (isset($search)) {
			$searchType = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');

		} else if (isset($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo = &Handler::getRangeInfo('users', array($roleId, (string) $search, (string) $searchMatch, (string) $searchType));

		if ($roleId) {
			while (true) {
				$users = &$roleDao->getUsersByRoleId($roleId, $conference->getConferenceId(), $schedConf->getSchedConfId(), $searchType, $search, $searchMatch, $rangeInfo);
				if ($users->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $users->getLastPageRangeInfo();
				unset($users);
			}
			$templateMgr->assign('roleId', $roleId);
		} else {
			while (true) {
				$users = &$roleDao->getUsersByConferenceId($conference->getConferenceId(), $searchType, $search, $searchMatch, $rangeInfo);
				if ($users->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $users->getLastPageRangeInfo();
				unset($users);
			}
		}

		$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));
		$templateMgr->assign('helpTopicId', 'conference.managementPages.mergeUsers');
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

		if ($roleId == ROLE_ID_REVIEWER) {
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$templateMgr->assign('rateReviewerOnQuality', $conference->getSetting('rateReviewerOnQuality'));
			$templateMgr->assign('qualityRatings', $conference->getSetting('rateReviewerOnQuality') ? $reviewAssignmentDao->getAverageQualityRatings($conference->getConferenceId()) : null);
		}
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email',
			USER_FIELD_INTERESTS => 'user.interests'
		));
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
		$templateMgr->assign('oldUserId', $oldUserId);
		$templateMgr->assign('rolePath', $roleDao->getRolePath($roleId));
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->display('manager/people/selectMergeUser.tpl');
	}

	/**
	 * Disable a user's account.
	 * @param $args array the ID of the user to disable
	 */
	function disableUser($args) {
		parent::validate();
		parent::setupTemplate(true);

		$userId = isset($args[0])?$args[0]:Request::getUserVar('userId');
		$user = &Request::getUser();
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		if($schedConf)
			$schedConfId = $schedConf->getSchedConfId();
		else
			$schedConfId = null;

		if ($userId != null && $userId != $user->getUserId()) {
			if (!Validation::canAdminister($conference->getConferenceId(), $schedConfId, $userId)) {
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'manager.people');
				$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
				$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
				return $templateMgr->display('common/error.tpl');
			}
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUser($userId);
			if ($user) {
				$user->setDisabled(1);
				$user->setDisabledReason(Request::getUserVar('reason'));
			}
			$userDao->updateUser($user);
		}

		Request::redirect(null, null, null, 'people', 'all');
	}

	/**
	 * Enable a user's account.
	 * @param $args array the ID of the user to enable
	 */
	function enableUser($args) {
		parent::validate();
		parent::setupTemplate(true);

		$userId = isset($args[0])?$args[0]:null;
		$user = &Request::getUser();

		if ($userId != null && $userId != $user->getUserId()) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$user = &$userDao->getUser($userId, true);
			if ($user) {
				$user->setDisabled(0);
			}
			$userDao->updateUser($user);
		}

		Request::redirect(null, null, null, 'people', 'all');
	}

	/**
	 * Remove a user from all roles for the current conference.
	 * @param $args array the ID of the user to remove
	 */
	function removeUser($args) {
		parent::validate();
		parent::setupTemplate(true);

		$userId = isset($args[0])?$args[0]:null;
		$user = &Request::getUser();
		$conference = &Request::getConference();

		if ($userId != null && $userId != $user->getUserId()) {
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$roleDao->deleteRoleByUserId($userId, $conference->getConferenceId());
		}

		Request::redirect(null, null, null, 'people', 'all');
	}

	/**
	 * Save changes to a user profile.
	 */
	function updateUser() {
		parent::validate();

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();
		$userId = Request::getUserVar('userId');
		$schedConf = &Request::getSchedConf();

		if($schedConf)
			$schedConfId = $schedConf->getSchedConfId();
		else
			$schedConfId = null;

		if (!empty($userId) && !Validation::canAdminister($conference->getConferenceId(), $schedConfId, $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('pageTitle', 'manager.people');
			$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		import('manager.form.UserManagementForm');

		$userForm = &new UserManagementForm($userId);
		$userForm->readInputData();

		if ($userForm->validate()) {
			$userForm->execute();

			if (Request::getUserVar('createAnother')) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));
				$templateMgr->assign('userCreated', true);
				$userForm = &new UserManagementForm();
				$userForm->initData();
				$userForm->display();

			} else {
				if ($source = Request::getUserVar('source')) Request::redirectUrl($source);
				else Request::redirect(null, null, null, 'people', 'all');
			}

		} else {
			parent::setupTemplate(true);
			$userForm->display();
		}
	}

	/**
	 * Display a user's profile.
	 * @param $args array first parameter is the ID or username of the user to display
	 */
	function userProfile($args) {
		parent::validate();
		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));
		$templateMgr->assign('helpTopicId', 'conference.users.index');

		$userDao = &DAORegistry::getDAO('UserDAO');
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
			$site = &Request::getSite();
			$conference = &Request::getConference();
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$roles = &$roleDao->getRolesByUserId($user->getUserId(), $conference->getConferenceId());

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
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->display('manager/people/userProfile.tpl');
		}
	}

	/**
	 * Sign in as another user.
	 * @param $args array ($userId)
	 */
	function signInAsUser($args) {
		parent::validate();

		if (isset($args[0]) && !empty($args[0])) {
			$userId = (int)$args[0];
			$conference = &Request::getConference();
			$schedConf = &Request::getSchedConf();

			if($schedConf)
				$schedConfId = $schedConf->getSchedConfId();
			else
				$schedConfId = null;

			if (!Validation::canAdminister($conference->getConferenceId(), $schedConfId, $userId)) {
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'manager.people');
				$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
				$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
				return $templateMgr->display('common/error.tpl');
			}

			$userDao = &DAORegistry::getDAO('UserDAO');
			$newUser = &$userDao->getUser($userId);
			$session = &Request::getSession();

			// FIXME Support "stack" of signed-in-as user IDs?
			if (isset($newUser) && $session->getUserId() != $newUser->getUserId()) {
				$session->setSessionVar('signedInAs', $session->getUserId());
				$session->setSessionVar('userId', $userId);
				$session->setUserId($userId);
				$session->setSessionVar('username', $newUser->getUsername());
				Request::redirect(null, null, 'user');
			}
		}
		Request::redirect(null, null, Request::getRequestedPage());
	}

	/**
	 * Restore original user account after signing in as a user.
	 */
	function signOutAsUser() {
		Handler::validate();

		$session = &Request::getSession();
		$signedInAs = $session->getSessionVar('signedInAs');

		if (isset($signedInAs) && !empty($signedInAs)) {
			$signedInAs = (int)$signedInAs;

			$userDao = &DAORegistry::getDAO('UserDAO');
			$oldUser = &$userDao->getUser($signedInAs);

			$session->unsetSessionVar('signedInAs');

			if (isset($oldUser)) {
				$session->setSessionVar('userId', $signedInAs);
				$session->setUserId($signedInAs);
				$session->setSessionVar('username', $oldUser->getUsername());
			}
		}

		Request::redirect(null, null, Request::getRequestedPage());
	}
}

?>
