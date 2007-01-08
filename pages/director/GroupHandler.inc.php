<?php

/**
 * GroupHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for organizing team management functions. 
 *
 * $Id$
 */

class GroupHandler extends DirectorHandler {

	/**
	 * Display a list of groups for the current conference.
	 */
	function groups() {
		list($conference, $event) = GroupHandler::validate();
		GroupHandler::setupTemplate();

		$eventId = $event? $event->getEventId():0;
		
		$rangeInfo = &Handler::getRangeInfo('groups');

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$groups =& $groupDao->getGroups($conference->getConferenceId(), $eventId, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('groups', $groups);
		$templateMgr->assign('boardEnabled', $conference->getSetting('boardEnabled'));
		$templateMgr->display('director/groups/groups.tpl');
	}

	/**
	 * Delete a group.
	 * @param $args array first parameter is the ID of the group to delete
	 */
	function deleteGroup($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		list($conference, $event, $group) = GroupHandler::validate($groupId);

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$groupDao->deleteGroup($group);
		$groupDao->resequenceGroups($conference->getConferenceId());
		
		Request::redirect(null, null, null, 'groups');
	}

	/**
	 * Change the sequence of a group.
	 */
	function moveGroup() {
		$groupId = (int) Request::getUserVar('groupId');
		list($conference, $event, $group) = GroupHandler::validate($groupId);
		
		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$group->setSequence($group->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
		$groupDao->updateGroup($group);
		$groupDao->resequenceGroups($conference->getConferenceId());
		
		Request::redirect(null, null, null, 'groups');
	}

	/**
	 * Display form to edit a group.
	 * @param $args array optional, first parameter is the ID of the group to edit
	 */
	function editGroup($args = array()) {
		$groupId = isset($args[0])?(int)$args[0]:null;
		list($conference, $event) = GroupHandler::validate($groupId);

		if ($groupId !== null) {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$group =& $groupDao->getGroup($groupId);
			if (!$group || $conference->getConferenceId() !== $group->getConferenceId()) {
				Request::redirect(null, null, null, 'groups');
			}
		} else $group = null;
		
		GroupHandler::setupTemplate($group, true);
		import('director.form.GroupForm');

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('pageTitle',
			$group === null?
				'director.groups.createTitle':
				'director.groups.editTitle'
		);

		$groupForm = &new GroupForm($group);
		$groupForm->initData();
		$groupForm->display();
	}

	/**
	 * Display form to create new group.
	 */
	function createGroup($args) {
		GroupHandler::editGroup($args);
	}

	/**
	 * Save changes to a group.
	 */
	function updateGroup() {
		$groupId = Request::getUserVar('groupId') === null? null : (int) Request::getUserVar('groupId');
		if ($groupId === null) {
			list($conference, $event) = GroupHandler::validate();
			$group = null;
		} else {
			list($conference, $event, $group) = GroupHandler::validate($groupId);
		}
		
		import('director.form.GroupForm');
		
		$groupForm =& new GroupForm($group);
		$groupForm->readInputData();
		
		if ($groupForm->validate()) {
			$groupForm->execute();
			Request::redirect(null, null, null, 'groups');
		} else {
			GroupHandler::setupTemplate($group);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'director', 'groups'), 'director.groups'));

			$templateMgr->assign('pageTitle',
				$group?
					'director.groups.editTitle':
					'director.groups.createTitle'
			);

			$groupForm->display();
		}
	}
	
	/**
	 * View group membership.
	 */
	function groupMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		list($conference, $event, $group) = GroupHandler::validate($groupId);
		
		$rangeInfo = &Handler::getRangeInfo('memberships');

		GroupHandler::setupTemplate($group, true);
		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$memberships =& $groupMembershipDao->getMemberships($group->getGroupId(), $rangeInfo);
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('memberships', $memberships);
		$templateMgr->assign_by_ref('group', $group);
		$templateMgr->display('director/groups/memberships.tpl');
	}

	/**
	 * Add group membership (or list users if none chosen).
	 */
	function addMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		$userId = isset($args[1])?(int)$args[1]:null;

		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');

		// If a user has been selected, add them to the group.
		// Otherwise list users.
		if ($userId !== null) {
			list($conference, $event, $group, $user) = GroupHandler::validate($groupId, $userId);
			// A valid user has been chosen. Add them to
			// the membership list and redirect.

			// Avoid duplicating memberships.
			$groupMembership =& $groupMembershipDao->getMembership($group->getGroupId(), $user->getUserId());

			if (!$groupMembership) {
				$groupMembership =& new GroupMembership();
				$groupMembership->setGroupId($group->getGroupId());
				$groupMembership->setUserId($user->getUserId());
				// For now, all memberships are displayed in About
				$groupMembership->setAboutDisplayed(true);
				$groupMembershipDao->insertMembership($groupMembership);
			}
			Request::redirect(null, null, null, 'groupMembership', $group->getGroupId());
		} else {
			list($conference, $event, $group) = GroupHandler::validate($groupId);
			GroupHandler::setupTemplate($group, true);
			$searchType = null;
			$searchMatch = null;
			$search = $searchQuery = Request::getUserVar('search');
			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($search)) {
				$searchType = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
				
			} else if (isset($searchInitial)) {
				$searchInitial = String::strtoupper($searchInitial);
				$searchType = USER_FIELD_INITIAL;
				$search = $searchInitial;
			}

			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$users = $roleDao->getUsersByRoleId(null, $conference->getConferenceId(), null, $searchType, $search, $searchMatch);

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', $searchInitial);
	
			$templateMgr->assign_by_ref('users', $users);
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->assign_by_ref('group', $group);

			$templateMgr->display('director/groups/selectUser.tpl');
		}
	}

	/**
	 * Delete group membership.
	 */
	function deleteMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		$userId = isset($args[1])?(int)$args[1]:0;

		list($conference, $event, $group, $user, $groupMembership) = GroupHandler::validate($groupId, $userId, true);

		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$groupMembershipDao->deleteMembershipById($group->getGroupId(), $user->getUserId());
		$groupMembershipDao->resequenceMemberships($group->getGroupId());

		Request::redirect(null, null, null, 'groupMembership', $group->getGroupId());
	}

	/**
	 * Change the sequence of a group membership.
	 */
	function moveMembership() {
		$groupId = (int) Request::getUserVar('groupId');
		$userId = (int) Request::getUserVar('userId');
		list($conference, $event, $group, $user, $groupMembership) = GroupHandler::validate($groupId, $userId, true);
		
		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$groupMembership->setSequence($groupMembership->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
		$groupMembershipDao->updateMembership($groupMembership);
		$groupMembershipDao->resequenceMemberships($group->getGroupId());
		
		Request::redirect(null, null, null, 'groupMembership', $group->getGroupId());
	}

	function setBoardEnabled($args) {
		GroupHandler::validate();
		$conference = &Request::getConference();
		$boardEnabled = Request::getUserVar('boardEnabled')==1?true:false;
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$conferenceSettingsDao->updateSetting($conference->getConferenceId(), 'boardEnabled', $boardEnabled);
		Request::redirect(null, null, null, 'groups');
	}

	function setupTemplate($group = null, $subclass = false) {
		parent::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'director', 'groups'), 'director.groups'));
		}
		if ($group) {
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'director', 'editGroup', $group->getGroupId()), $group->getTitle(), true));
		}
		$templateMgr->assign('helpTopicId', 'conference.managementPages.groups');
	}

	/**
	 * Validate the request. If a group ID is supplied, the group object
	 * will be fetched and validated against the current conference. If,
	 * additionally, the user ID is supplied, the user and membership
	 * objects will be validated and fetched.
	 * @param $groupId int optional
	 * @param $userId int optional
	 * @param $fetchMembership boolean Whether or not to fetch membership object as last element of return array, redirecting if it doesn't exist; default false
	 * @return array [$conference] iff $groupId is null, [$conference, $group] iff $userId is null and $groupId is supplied, and [$conference, $group, $user] iff $userId and $groupId are both supplied. $fetchMembership===true will append membership info to the last case, redirecting if it doesn't exist.
	 */
	function validate($groupId = null, $userId = null, $fetchMembership = false) {
		parent::validate(false);

		$conference =& Request::getConference();
		$event =& Request::getEvent();
		
		$returner = array(&$conference, &$event);

		$passedValidation = true;

		if ($groupId !== null) {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$group =& $groupDao->getGroup($groupId);

			if (!$group) {
				$passedValidation = false;

			} elseif ($group->getConferenceId() !== $conference->getConferenceId()) {
				$passedValidation = false;

			} elseif (!$event && !Validation::isConferenceDirector($conference->getConferenceId())) {
				$passedValidation = false;
			
			} elseif ($event->getEventId() != $group->getEventId()) {
				$passedValidation = false;
			
			} else {
				$returner[] = &$group;
			}

			if ($userId !== null) {
				$userDao =& DAORegistry::getDAO('UserDAO');
				$user =& $userDao->getUser($userId);

				if (!$user) $passedValidation = false;
				else $returner[] = &$user;

				if ($fetchMembership === true) {
					$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
					$groupMembership =& $groupMembershipDao->getMembership($groupId, $userId);
					if (!$groupMembership) $validationPassed = false;
					else $returner[] = &$groupMembership;
				}
			}
		}
		if (!$passedValidation) Request::redirect(null, null, null, 'groups');
		return $returner;
	}
}

?>
