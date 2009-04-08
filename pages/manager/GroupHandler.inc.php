<?php

/**
 * @file GroupHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GroupHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for organizing team management functions. 
 */

//$Id$

class GroupHandler extends ManagerHandler {

	/**
	 * Display a list of groups for the current conference.
	 */
	function groups() {
		list($conference, $schedConf) = GroupHandler::validate();
		GroupHandler::setupTemplate();

		$schedConfId = $schedConf? $schedConf->getSchedConfId():0;

		$rangeInfo = &Handler::getRangeInfo('groups', array());
		$groupDao =& DAORegistry::getDAO('GroupDAO');
		while (true) {
			$groups =& $groupDao->getGroups($conference->getConferenceId(), $schedConfId, $rangeInfo);
			if ($groups->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $groups->getLastPageRangeInfo();
			unset($groups);
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('groups', $groups);
		$templateMgr->assign('boardEnabled', $schedConf->getSetting('boardEnabled'));
		$templateMgr->display('manager/groups/groups.tpl');
	}

	/**
	 * Delete a group.
	 * @param $args array first parameter is the ID of the group to delete
	 */
	function deleteGroup($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		list($conference, $schedConf, $group) = GroupHandler::validate($groupId);

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
		list($conference, $schedConf, $group) = GroupHandler::validate($groupId);

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
		list($conference, $schedConf) = GroupHandler::validate($groupId);

		if ($groupId !== null) {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$group =& $groupDao->getGroup($groupId);
			if (!$group || $conference->getConferenceId() !== $group->getConferenceId()) {
				Request::redirect(null, null, null, 'groups');
			}
		} else $group = null;

		GroupHandler::setupTemplate($group, true);
		import('manager.form.GroupForm');

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('pageTitle',
			$group === null?
				'manager.groups.createTitle':
				'manager.groups.editTitle'
		);

		$groupForm = &new GroupForm($group);
		if ($groupForm->isLocaleResubmit()) {
			$groupForm->readInputData();
		} else {
			$groupForm->initData();
		}
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
			list($conference, $schedConf) = GroupHandler::validate();
			$group = null;
		} else {
			list($conference, $schedConf, $group) = GroupHandler::validate($groupId);
		}

		import('manager.form.GroupForm');

		$groupForm =& new GroupForm($group);
		$groupForm->readInputData();

		if ($groupForm->validate()) {
			$groupForm->execute();
			Request::redirect(null, null, null, 'groups');
		} else {
			GroupHandler::setupTemplate($group);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'groups'), 'manager.groups'));

			$templateMgr->assign('pageTitle',
				$group?
					'manager.groups.editTitle':
					'manager.groups.createTitle'
			);

			$groupForm->display();
		}
	}

	/**
	 * View group membership.
	 */
	function groupMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		list($conference, $schedConf, $group) = GroupHandler::validate($groupId);

		$rangeInfo = &Handler::getRangeInfo('membership', array($groupId));

		GroupHandler::setupTemplate($group, true);
		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		while (true) {
			$memberships =& $groupMembershipDao->getMemberships($group->getGroupId(), $rangeInfo);
			if ($memberships->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $memberships->getLastPageRangeInfo();
			unset($memberships);
		}
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('memberships', $memberships);
		$templateMgr->assign_by_ref('group', $group);
		$templateMgr->display('manager/groups/memberships.tpl');
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
			list($conference, $schedConf, $group, $user) = GroupHandler::validate($groupId, $userId);
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
			list($conference, $schedConf, $group) = GroupHandler::validate($groupId);
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

			$rangeInfo = &Handler::getRangeInfo('users', array($groupId, (string) $search, (string) $searchMatch, (string) $searchType));

			$roleDao =& DAORegistry::getDAO('RoleDAO');
			while (true) {
				$users = $roleDao->getUsersByRoleId(null, $conference->getConferenceId(), null, $searchType, $search, $searchMatch, $rangeInfo);
				if ($users->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $users->getLastPageRangeInfo();
				unset($users);
			}

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('searchField', $searchType);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $searchQuery);
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));

			$templateMgr->assign_by_ref('users', $users);
			$templateMgr->assign('fieldOptions', Array(
				USER_FIELD_FIRSTNAME => 'user.firstName',
				USER_FIELD_LASTNAME => 'user.lastName',
				USER_FIELD_USERNAME => 'user.username',
				USER_FIELD_EMAIL => 'user.email'
			));
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->assign_by_ref('group', $group);

			$templateMgr->display('manager/groups/selectUser.tpl');
		}
	}

	/**
	 * Delete group membership.
	 */
	function deleteMembership($args) {
		$groupId = isset($args[0])?(int)$args[0]:0;
		$userId = isset($args[1])?(int)$args[1]:0;

		list($conference, $schedConf, $group, $user, $groupMembership) = GroupHandler::validate($groupId, $userId, true);

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
		list($conference, $schedConf, $group, $user, $groupMembership) = GroupHandler::validate($groupId, $userId, true);

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
		$schedConf =& Request::getSchedConf();
		$schedConf->updateSetting('boardEnabled', $boardEnabled);
		Request::redirect(null, null, null, 'groups');
	}

	function setupTemplate($group = null, $subclass = false) {
		parent::setupTemplate(true);
		$templateMgr = &TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'groups'), 'manager.groups'));
		}
		if ($group) {
			$templateMgr->append('pageHierarchy', array(Request::url(null, null, 'manager', 'editGroup', $group->getGroupId()), $group->getGroupTitle(), true));
		}
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.organizingTeam');
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
		$schedConf =& Request::getSchedConf();

		$returner = array(&$conference, &$schedConf);

		$passedValidation = true;

		if ($groupId !== null) {
			$groupDao =& DAORegistry::getDAO('GroupDAO');
			$group =& $groupDao->getGroup($groupId);

			if (!$group) {
				$passedValidation = false;

			} elseif ($group->getConferenceId() !== $conference->getConferenceId()) {
				$passedValidation = false;

			} elseif (!$schedConf && !Validation::isConferenceManager($conference->getConferenceId())) {
				$passedValidation = false;

			} elseif ($schedConf->getSchedConfId() != $group->getSchedConfId()) {
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
