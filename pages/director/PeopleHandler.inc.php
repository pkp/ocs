<?php

/**
 * PeopleHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for people management functions. 
 *
 * $Id$
 */

class PeopleHandler extends DirectorHandler {

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
			$roleName = 'director.people.allUsers';
		}
		
		$conference = &Request::getConference();
		$event = &Request::getEvent();
		
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

		$rangeInfo = Handler::getRangeInfo('users');

		if ($roleId) {
			$users = &$roleDao->getUsersByRoleId($roleId, $conference->getConferenceId(),
				($event? $event->getEventId() : null), $searchType, $search, $searchMatch, $rangeInfo);
			$templateMgr->assign('roleId', $roleId);
			switch($roleId) {
				case ROLE_ID_CONFERENCE_DIRECTOR:
					$helpTopicId = 'conference.roles.conferenceDirector';
					break;
				case ROLE_ID_REGISTRATION_MANAGER:
					$helpTopicId = 'conference.roles.registrationManager';
					break;
				case ROLE_ID_EDITOR:
					$helpTopicId = 'conference.roles.editor';
					break;
				case ROLE_ID_TRACK_EDITOR:
					$helpTopicId = 'conference.roles.trackEditor';
					break;
				case ROLE_ID_LAYOUT_EDITOR:
					$helpTopicId = 'conference.roles.layoutEditor';
					break;
				case ROLE_ID_REVIEWER:
					$helpTopicId = 'conference.roles.reviewer';
					break;
//				case ROLE_ID_INVITEDAUTHOR:
//					$helpTopicId = 'conference.roles.invitedAuthor';
//					break;
				case ROLE_ID_AUTHOR:
					$helpTopicId = 'conference.roles.author';
					break;
//				case ROLE_ID_DISCUSSANT:
//					$helpTopicId = 'conference.roles.discussant';
//					break;
//				case ROLE_ID_REGISTRANT:
//					$helpTopicId = 'conference.roles.registrant';
//					break;
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
		$templateMgr->assign('searchInitial', $searchInitial);

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
		$templateMgr->assign('isEventManagement', $event ? true : false);
		$templateMgr->assign('isConferenceManagement', $event ? false : true);
		$templateMgr->assign('isRegistrationEnabled', ($event? $event->getSetting('enableRegistration', true) : false));
		$templateMgr->display('director/people/enrollment.tpl');
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
		$event =& Request::getEvent();

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

		$rangeInfo = Handler::getRangeInfo('users');

		$users = &$userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);
		
		$templateMgr->assign('isEventManagement', $event ? true : false);
		$templateMgr->assign('isConferenceManagement', $event ? false : true);
		$templateMgr->assign('isRegistrationEnabled', ($event? $event->getSetting('enableRegistration', true) : false));

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', $searchInitial);

		$templateMgr->assign('roleId', $roleId);
		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		));
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', Request::getUser());
		$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
		$templateMgr->assign('helpTopicId', 'conference.users.index');
		$templateMgr->display('director/people/searchUsers.tpl');
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
		$event =& Request::getEvent();

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$rolePath = $roleDao->getRolePath($roleId);

		$isConferenceDirector = Validation::isConferenceDirector($conference->getConferenceId());

		// Don't allow event directors (who can end up here) to enroll
		// conference directors or event directors.
		if ($users != null &&
				is_array($users) &&
				$rolePath != '' &&
				$rolePath != ROLE_PATH_SITE_ADMIN &&
				$isConferenceDirector) {

			$eventId = ($event? $event->getEventId() : 0);
					
			for ($i=0; $i<count($users); $i++) {
				if (!$roleDao->roleExists($conference->getConferenceId(), $eventId, $users[$i], $roleId)) {
					$role = &new Role();
					$role->setConferenceId($conference->getConferenceId());
					if ($event) {
						$role->setEventId($eventId);
					} else {
						$role->setEventId(0);
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
		$event =& Request::getEvent();

		$isConferenceDirector = Validation::isConferenceDirector($conference->getConferenceId());
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		// Don't allow event directors to unenroll event directors or
		// conference directors
		if ($roleId != ROLE_ID_SITE_ADMIN && $isConferenceDirector) {
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
		$templateMgr->display('director/people/enrollSync.tpl');
	}
	
	/**
	 * Synchronize user enrollment with another conference.
	 */
	function enrollSync($args) {
		parent::validate();
		
		$conference = &Request::getConference();
		$rolePath = Request::getUserVar('rolePath');
		$syncConference = Request::getUserVar('syncConference');
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleId = $roleDao->getRoleIdFromPath($rolePath);
		
		if ((!empty($roleId) || $rolePath == 'all') && !empty($syncConference)) {
			$roles = &$roleDao->getRolesByConferenceId($syncConference == 'all' ? null : $syncConference, $roleId);
			while (!$roles->eof()) {
				$role = &$roles->next();
				$role->setConferenceId($conference->getConferenceId());
				if ($role->getRolePath() != ROLE_PATH_SITE_ADMIN && !$roleDao->roleExists($role->getConferenceId(), $event->getEventId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
		}
		
		Request::redirect(null, null, null, 'people', $roleDao->getRolePath($roleId));
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
		$event = &Request::getEvent();
		$event = &Request::getEvent();
		
		if($event) {
			$eventId = $event->getEventId();
		} else {
			$eventId = null;
		}

		$userId = isset($args[0])?$args[0]:null;

		$templateMgr = &TemplateManager::getManager();

		if (!Validation::canAdminister($conference->getConferenceId(), $eventId, $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr->assign('pageTitle', 'director.people');
			$templateMgr->assign('errorMsg', 'director.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'director.people.allUsers');
			
			return $templateMgr->display('common/error.tpl');
		}

		import('director.form.UserManagementForm');
		
		$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));

		$userForm = &new UserManagementForm($userId);
		$userForm->initData();
		$userForm->display();
	}

	/**
	 * Allow the Conference Manager to merge user accounts, including attributed papers etc.
	 */
	/*function mergeUsers($args) {
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
			$templateMgr->assign('pageTitle', 'director.people');
			$templateMgr->assign('errorMsg', 'director.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'director.people.allUsers');
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
				$editAssignment->setEditorId($newUserId);
				$editAssignmentDao->updateEditAssignment($editAssignment);
				unset($editAssignment);
			}

			$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
			$editorSubmissionDao->transferEditorDecisions($oldUserId, $newUserId);

			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			foreach ($reviewAssignmentDao->getReviewAssignmentsByUserId($oldUserId) as $reviewAssignment) {
				$reviewAssignment->setReviewerId($newUserId);
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				unset($reviewAssignment);
			}

			$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
			$copyeditorSubmissions =& $copyeditorSubmissionDao->getCopyeditorSubmissionsByCopyeditorId($oldUserId);
			while ($copyeditorSubmission =& $copyeditorSubmissions->next()) {
				$copyeditorSubmission->setCopyeditorId($newUserId);
				$copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
				unset($copyeditorSubmission);
			}

			$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
			$layoutEditorSubmissions =& $layoutEditorSubmissionDao->getSubmissions($oldUserId);
			while ($layoutEditorSubmission =& $layoutEditorSubmissions->next()) {
				$layoutAssignment =& $layoutEditorSubmission->getLayoutAssignment();
				$layoutAssignment->setEditorId($newUserId);
				$layoutEditorSubmissionDao->updateSubmission($layoutEditorSubmission);
				unset($layoutAssignment);
				unset($layoutEditorSubmission);
			}

			$proofreaderSubmissionDao =& DAORegistry::getDAO('ProofreaderSubmissionDAO');
			$proofreaderSubmissions =& $proofreaderSubmissionDao->getSubmissions($oldUserId);
			while ($proofreaderSubmission =& $proofreaderSubmissions->next()) {
				$proofAssignment =& $proofreaderSubmission->getProofAssignment();
				$proofAssignment->setProofreaderId($newUserId);
				$proofreaderSubmissionDao->updateSubmission($proofreaderSubmission);
				unset($proofAssignment);
				unset($proofreaderSubmission);
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
			$subscriptionDao =& DAORegistry::getDAO('SubscriptionDAO');
			$subscriptionDao->deleteSubscriptionsByUserId($oldUserId);
			$temporaryFileDao =& DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFileDao->deleteTemporaryFilesByUserId($oldUserId);
			$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
			$notificationStatusDao->deleteNotificationStatusByUserId($oldUserId);
			$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
			$groupMembershipDao->deleteMembershipByUserId($oldUserId);
			$sectionEditorsDao =& DAORegistry::getDAO('SectionEditorsDAO');
			$sectionEditorsDao->deleteEditorsByUserId($oldUserId);
			$roleDao->deleteRoleByUserId($oldUserId);
			$userDao->deleteUserById($oldUserId);

			Request::redirect(null, null, 'director');
		}

		if (!empty($oldUserId)) {
			// Get the old username for the confirm prompt.
			$oldUser =& $userDao->getUser($oldUserId);
			$templateMgr->assign('oldUsername', $oldUser->getUsername());
			unset($oldUser);
		}

		// The director must select one or both IDs.
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
			$roleName = 'director.people.allUsers';
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

		$rangeInfo = Handler::getRangeInfo('users');

		if ($roleId) {
			$users = &$roleDao->getUsersByRoleId($roleId, $conference->getConferenceId(), $event->getEventId(), $searchType, $search, $searchMatch, $rangeInfo);
			$templateMgr->assign('roleId', $roleId);
		} else {
			$users = &$roleDao->getUsersByConferenceId($conference->getConferenceId(), $searchType, $search, $searchMatch, $rangeInfo);
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
		$templateMgr->assign('searchInitial', $searchInitial);

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
		$templateMgr->display('director/people/selectMergeUser.tpl');
	}*/

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
		$event = &Request::getEvent();

		if($event)
			$eventId = $event->getEventId();
		else
			$eventId = null;

		if ($userId != null && $userId != $user->getUserId()) {
			if (!Validation::canAdminister($conference->getConferenceId(), $eventId, $userId)) {
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'director.people');
				$templateMgr->assign('errorMsg', 'director.people.noAdministrativeRights');
				$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'director.people.allUsers');
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
		$event = &Request::getEvent();
		$userId = Request::getUserVar('userId');
		$event = &Request::getEvent();

		if($event)
			$eventId = $event->getEventId();
		else
			$eventId = null;

		if (!empty($userId) && !Validation::canAdminister($conference->getConferenceId(), $eventId, $userId)) {
			// We don't have administrative rights
			// over this user. Display an error.
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('pageTitle', 'director.people');
			$templateMgr->assign('errorMsg', 'director.people.noAdministrativeRights');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'director.people.allUsers');
			return $templateMgr->display('common/error.tpl');
		}

		import('director.form.UserManagementForm');

		$userForm = &new UserManagementForm($userId);
		$userForm->readInputData();
		
		if ($userForm->validate()) {
			$userForm->execute();
			
			if (Request::getUserVar('createAnother')) {
				// C
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('currentUrl', Request::url(null, null, null, 'people', 'all'));
				$templateMgr->assign('userCreated', true);
				$userForm = &new UserManagementForm();
				$userForm->initData();
				$userForm->display();
				
			} else {
				Request::redirect(null, null, null, 'people', 'all');
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
			$templateMgr->assign('pageTitle', 'director.people');
			$templateMgr->assign('errorMsg', 'director.people.invalidUser');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
			$templateMgr->assign('backLinkLabel', 'director.people.allUsers');
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

			$templateMgr->assign('isEventManagement', Request::getEvent() ? true : false);
			$templateMgr->assign('isConferenceManagement', Request::getEvent() ? false : true);

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign_by_ref('userRoles', $roles);
			$templateMgr->assign('profileLocalesEnabled', $site->getProfileLocalesEnabled());
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->display('director/people/userProfile.tpl');
		}
	}
	
	/**
	 * Select a template to send to a user or group of users.
	 */
	function selectTemplate($args) {
		parent::validate();

		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();

		$site = &Request::getSite();
		$conference = &Request::getConference();
		$user = &Request::getUser();

		$locale = Request::getUserVar('locale');
		if (!isset($locale) || $locale == null) $locale = Locale::getLocale();

		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplates = &$emailTemplateDao->getEmailTemplates($locale, $conference->getConferenceId());

		$templateMgr->assign_by_ref('emailTemplates', $emailTemplates);
		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('locales', $conference->getSetting('supportedLocales'));
		$templateMgr->assign('localeNames', Locale::getAllLocales());
		$templateMgr->assign('persistAttachments', Request::getUserVar('persistAttachments'));
		$templateMgr->assign('to', Request::getUserVar('to'));
		$templateMgr->assign('cc', Request::getUserVar('cc'));
		$templateMgr->assign('bcc', Request::getUserVar('bcc'));
		$templateMgr->assign('helpTopicId', 'conference.users.emailUsers');
		$templateMgr->display('director/people/selectTemplate.tpl');
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
			$event = &Request::getEvent();

			if($event)
				$eventId = $event->getEventId();
			else
				$eventId = null;

			if (!Validation::canAdminister($conference->getConferenceId(), $eventId, $userId)) {
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'director.people');
				$templateMgr->assign('errorMsg', 'director.people.noAdministrativeRights');
				$templateMgr->assign('backLink', Request::url(null, null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'director.people.allUsers');
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
