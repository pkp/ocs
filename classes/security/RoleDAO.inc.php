<?php

/**
 * RoleDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 *
 * Class for Role DAO.
 * Operations for retrieving and modifying Role objects.
 *
 * $Id$
 */

import('security.Role');

class RoleDAO extends DAO {

	/**
	 * Constructor.
	 */
	function RoleDAO() {
		parent::DAO();
		$this->userDao = &DAORegistry::getDAO('UserDAO');
	}
	
	/**
	 * Retrieve a role.
	 * @param $conferenceId int
	 * @param $userId int
	 * @param $roleId int
	 * @return Role
	 */
	function &getRole($conferenceId, $eventId, $userId, $roleId) {
		$result = &$this->retrieve(
			'SELECT * FROM roles WHERE conference_id = ? AND event_id = ? AND user_id = ? AND role_id = ?',
			array(
				(int) $conferenceId,
				(int) $eventId,
				(int) $userId,
				(int) $roleId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnRoleFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Internal function to return a Role object from a row.
	 * @param $row array
	 * @return Role
	 */
	function &_returnRoleFromRow(&$row) {
		$role = &new Role();
		$role->setConferenceId($row['conference_id']);
		$role->setEventId($row['event_id']);
		$role->setUserId($row['user_id']);
		$role->setRoleId($row['role_id']);
		
		HookRegistry::call('RoleDAO::_returnRoleFromRow', array(&$role, &$row));

		return $role;
	}
	
	/**
	 * Insert a new role.
	 * @param $role Role
	 */
	function insertRole(&$role) {
		return $this->update(
			'INSERT INTO roles
				(conference_id, event_id, user_id, role_id)
				VALUES
				(?, ?, ?, ?)',
			array(
				(int) $role->getConferenceId(),
				(int) $role->getEventId(),
				(int) $role->getUserId(),
				(int) $role->getRoleId()
			)
		);
	}
	
	/**
	 * Delete a role.
	 * @param $role Role
	 */
	function deleteRole(&$role) {
		return $this->update(
			'DELETE FROM roles WHERE conference_id = ? AND event_id = ? AND user_id = ? AND role_id = ?',
			array(
				(int) $role->getConferenceId(),
				(int) $role->getEventId(),
				(int) $role->getUserId(),
				(int) $role->getRoleId()
			)
		);
	}
	
	/**
	 * Retrieve a list of all roles for a specified user.
	 * @param $userId int
	 * @param $conferenceId int optional, include roles only in this conference
	 * @param $eventId int optional, include roles only in this event
	 * @return array matching Roles
	 */
	function &getRolesByUserId($userId, $conferenceId = null, $eventId = null) {
		$roles = array();
		$params = array();
		
		$params[] = $userId;
		if(isset($conferenceId)) $params[] = $conferenceId;
		if(isset($eventId)) $params[] = $eventId;
		
		$result = &$this->retrieve('SELECT * FROM roles WHERE user_id = ?' .
				(isset($conferenceId) ? ' AND conference_id = ?' : '') .
				(isset($eventId) ? ' AND event_id = ?' : ''),
			(count($params) == 1 ? array_shift($params) : $params));
		
		while (!$result->EOF) {
			$roles[] = &$this->_returnRoleFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $roles;
	}
	
	/**
	 * Retrieve a list of users in a specified role.
	 * @param $roleId int optional (can leave as null to get all users in conference)
	 * @param $conferenceId int optional, include users only in this conference
	 * @param $eventId int optional, include users only in this conference
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains')
	 * @param $dbRangeInfo object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersByRoleId($roleId = null, $conferenceId = null, $eventId = null,
			$searchType = null, $search = null, $searchMatch = null, $dbResultRange = null) {
			
		$users = array();

		$paramArray = array();
		if (isset($roleId)) $paramArray[] = (int) $roleId;
		if (isset($conferenceId)) $paramArray[] = (int) $conferenceId;
		if (isset($eventId)) $paramArray[] = (int) $eventId;

		// For security / resource usage reasons, a role, event, or conference
		// must be specified. Don't allow calls supplying none.
		if (empty($paramArray)) return null;

		$searchSql = '';

		if (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(u.first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(u.last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND LOWER(u.username) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND LOWER(u.email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND LOWER(u.interests) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}
		
		$searchSql .= ' ORDER  BY u.last_name, u.first_name';
		
		$result = &$this->retrieveRange(
			'SELECT DISTINCT u.* FROM users AS u, roles AS r WHERE u.user_id = r.user_id ' .
				(isset($roleId)?'AND r.role_id = ?':'') .
				(isset($conferenceId) ? ' AND r.conference_id = ?' : '') .
				(isset($eventId) ? ' AND r.event_id = ?' : '') .
				' ' . $searchSql,
			(count($paramArray)==1? array_shift($paramArray) : $paramArray),
			$dbResultRange
		);
		
		$returner = &new DAOResultFactory($result, $this->userDao, '_returnUserFromRow');
		return $returner;
	}
	
	/**
	 * Retrieve a list of all users with some role in the specified conference.
	 * @param $conferenceId int
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains')
	 * @param $dbRangeInfo object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersByConferenceId($conferenceId, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null) {
		$users = array();

		$paramArray = array((int) $conferenceId);
		$searchSql = '';

		if (isset($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_FIRSTNAME:
				$searchSql = 'AND LOWER(u.first_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_LASTNAME:
				$searchSql = 'AND LOWER(u.last_name) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_USERNAME:
				$searchSql = 'AND LOWER(u.username) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_EMAIL:
				$searchSql = 'AND LOWER(u.email) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INTERESTS:
				$searchSql = 'AND LOWER(u.interests) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}
		
		$searchSql .= ' ORDER BY u.last_name, u.first_name'; // FIXME Add "sort field" parameter?
		
		$result = &$this->retrieveRange(

			'SELECT DISTINCT u.* FROM users AS u, roles AS r WHERE u.user_id = r.user_id AND r.conference_id = ? ' . $searchSql,
			$paramArray,
			$dbResultRange
		);
		
		$returner = &new DAOResultFactory($result, $this->userDao, '_returnUserFromRow');
		return $returner;
	}
	
	/**
	 * Retrieve the number of users associated with the specified conference.
	 * @param $conferenceId int
	 * @return int
	 */
	function getConferenceUsersCount($conferenceId) {
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT COUNT(DISTINCT(user_id)) FROM roles WHERE conference_id = ?',
			(int) $conferenceId
		);

		$returner = $result->fields[0];

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Select all roles for a specified conference.
	 * @param $conferenceId int optional
	 * @param $roleId int optional
	 */
	function &getRolesByConferenceId($conferenceId = null, $roleId = null) {
		$params = array();
		$conditions = array();
		if (isset($conferenceId)) {
			$params[] = (int) $conferenceId;
			$conditions[] = 'conference_id = ?';
		}
		if (isset($roleId)) {
			$params[] = (int) $roleId;
			$conditions[] = 'role_id = ?';
		}
		
		$result = &$this->retrieve(
			'SELECT * FROM roles' . (empty($conditions) ? '' : ' WHERE ' . join(' AND ', $conditions)),
			$params
		);
		
		$returner = &new DAOResultFactory($result, $this, '_returnRoleFromRow');
		return $returner;
	}
	
	/**
	 * Delete all roles for a specified conference.
	 * @param $conferenceId int
	 */
	function deleteRoleByConferenceId($conferenceId) {
		return $this->update(
			'DELETE FROM roles WHERE conference_id = ?', (int) $conferenceId
		);
	}
	
	/**
	 * Delete all roles for a specified event.
	 * @param $eventId int
	 */
	function deleteRoleByEventId($eventId) {
		return $this->update(
			'DELETE FROM roles WHERE event_id = ?', (int) $eventId
		);
	}
	
	/**
	 * Delete all roles for a specified conference.
	 * @param $userId int
	 * @param $conferenceId int optional, include roles only in this conference
	 * @param $roleId int optional, include only this role
	 */
	function deleteRoleByUserId($userId, $conferenceId  = null, $roleId = null, $eventId = null) {
	
		$args = array((int)$userId);
		if(isset($conferenceId)) $args[] = (int)$conferenceId;
		if(isset($roleId)) $args[] = (int)$roleId;
		if(isset($eventId)) $args[] = (int)$eventId;
		
		return $this->update(
			'DELETE FROM roles WHERE user_id = ?' .
				(isset($conferenceId) ? ' AND conference_id = ?' : '') .
				(isset($roleId) ? ' AND role_id = ?' : '') .
				(isset($eventId) ? ' AND event_id = ?' : ''),
			(count($args) ? $args : shift($args)));
	}
	
	/**
	 * Check if a role exists.
	 * @param $conferenceId int
	 * @param $userId int
	 * @param $roleId int
	 * @return boolean
	 */
	function roleExists($conferenceId, $eventId, $userId, $roleId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM roles WHERE conference_id = ? AND event_id = ? AND user_id = ? AND role_id = ?', array((int) $conferenceId, (int)$eventId, (int) $userId, (int) $roleId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Get the i18n key name associated with the specified role.
	 * @param $roleId int
	 * @param $plural boolean get the plural form of the name
	 * @return string
	 */
	function getRoleName($roleId, $plural = false) {
		switch ($roleId) {
			case ROLE_ID_SITE_ADMIN:
				return 'user.role.siteAdmin' . ($plural ? 's' : '');

			case ROLE_ID_CONFERENCE_DIRECTOR:
				return 'user.role.director' . ($plural ? 's' : '');

			case ROLE_ID_REGISTRATION_MANAGER:
				return 'user.role.registrationManager' . ($plural ? 's' : '');
//			case ROLE_ID_SCHEDULING_MANAGER:
//				return 'user.role.schedulingManager' . ($plural ? 's' : '');

			case ROLE_ID_EDITOR:
				return 'user.role.editor' . ($plural ? 's' : '');
			case ROLE_ID_TRACK_EDITOR:
				return 'user.role.trackEditor' . ($plural ? 's' : '');
			case ROLE_ID_REVIEWER:
				return 'user.role.reviewer' . ($plural ? 's' : '');

			case ROLE_ID_AUTHOR:
				return 'user.role.author' . ($plural ? 's' : '');
//			case ROLE_ID_INVITED_AUTHOR:
//				return 'user.role.invitedAuthor' . ($plural ? 's' : '');

//			case ROLE_ID_DISCUSSANT:
//				return 'user.role.discussant' . ($plural ? 's' : '');
//			case ROLE_ID_REGISTRANT:
//				return 'user.role.registrant' . ($plural ? 's' : '');
			case ROLE_ID_READER:
				return 'user.role.reader' . ($plural ? 's' : '');
			default:
				return '';
		}
	}
	
	/**
	 * Get the URL path associated with the specified role's operations.
	 * @param $roleId int
	 * @return string
	 */
	function getRolePath($roleId) {
		switch ($roleId) {
			case ROLE_ID_SITE_ADMIN:
				return ROLE_PATH_SITE_ADMIN;

			case ROLE_ID_CONFERENCE_DIRECTOR:
				return ROLE_PATH_CONFERENCE_DIRECTOR;

			case ROLE_ID_REGISTRATION_MANAGER:
				return ROLE_PATH_REGISTRATION_MANAGER;
//			case ROLE_ID_SCHEDULING_MANAGER:
//				return ROLE_PATH_SCHEDULING_MANAGER;

			case ROLE_ID_EDITOR:
				return ROLE_PATH_EDITOR;
			case ROLE_ID_TRACK_EDITOR:
				return ROLE_PATH_TRACK_EDITOR;
			case ROLE_ID_REVIEWER:
				return ROLE_PATH_REVIEWER;

			case ROLE_ID_AUTHOR:
				return ROLE_PATH_AUTHOR;
//			case ROLE_ID_INVITED_AUTHOR:
//				return ROLE_PATH_INVITED_AUTHOR;

//			case ROLE_ID_DISCUSSANT:
//				return ROLE_PATH_DISCUSSANT;
//			case ROLE_ID_REGISTRANT:
//				return ROLE_PATH_REGISTRANT;
			case ROLE_ID_READER:
				return ROLE_PATH_READER;
			default:
				return '';
		}
	}
	
	/**
	 * Get a role's ID based on its path.
	 * @param $rolePath string
	 * @return int
	 */
	function getRoleIdFromPath($rolePath) {
		switch ($rolePath) {
			case ROLE_PATH_SITE_ADMIN:
				return ROLE_ID_SITE_ADMIN;

			case ROLE_PATH_CONFERENCE_DIRECTOR:
				return ROLE_ID_CONFERENCE_DIRECTOR;
				
			case ROLE_PATH_REGISTRATION_MANAGER:
				return ROLE_ID_REGISTRATION_MANAGER;
//			case ROLE_PATH_SCHEDULING_MANAGER:
//				return ROLE_ID_SCHEDULING_MANAGER;

			case ROLE_PATH_EDITOR:
				return ROLE_ID_EDITOR;
			case ROLE_PATH_TRACK_EDITOR:
				return ROLE_ID_TRACK_EDITOR;
			case ROLE_PATH_REVIEWER:
				return ROLE_ID_REVIEWER;

			case ROLE_PATH_AUTHOR:
				return ROLE_ID_AUTHOR;
//			case ROLE_PATH_INVITED_AUTHOR:
//				return ROLE_ID_INVITED_AUTHOR;

//			case ROLE_PATH_DISCUSSANT:
//				return ROLE_ID_DISCUSSANT;
//			case ROLE_PATH_REGISTRANT:
//				return ROLE_ID_REGISTRANT;
			case ROLE_PATH_READER:
				return ROLE_ID_READER;
			default:
				return null;
		}
	}

}

?>
