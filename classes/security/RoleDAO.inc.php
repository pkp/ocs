<?php

/**
 * @file RoleDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleDAO
 * @ingroup security
 * @see Role
 *
 * @brief Operations for retrieving and modifying Role objects.
 */

//$Id$

import('security.Role');

class RoleDAO extends DAO {
	/**
	 * Constructor.
	 */
	function RoleDAO() {
		parent::DAO();
		$this->userDao =& DAORegistry::getDAO('UserDAO');
	}

	/**
	 * Retrieve a role.
	 * @param $conferenceId int
	 * @param $userId int
	 * @param $roleId int
	 * @return Role
	 */
	function &getRole($conferenceId, $schedConfId, $userId, $roleId) {
		$result =& $this->retrieve(
			'SELECT * FROM roles WHERE conference_id = ? AND sched_conf_id = ? AND user_id = ? AND role_id = ?',
			array(
				(int) $conferenceId,
				(int) $schedConfId,
				(int) $userId,
				(int) $roleId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnRoleFromRow($result->GetRowAssoc(false));
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
		$role = new Role();
		$role->setConferenceId($row['conference_id']);
		$role->setSchedConfId($row['sched_conf_id']);
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
				(conference_id, sched_conf_id, user_id, role_id)
				VALUES
				(?, ?, ?, ?)',
			array(
				(int) $role->getConferenceId(),
				(int) $role->getSchedConfId(),
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
			'DELETE FROM roles WHERE conference_id = ? AND sched_conf_id = ? AND user_id = ? AND role_id = ?',
			array(
				(int) $role->getConferenceId(),
				(int) $role->getSchedConfId(),
				(int) $role->getUserId(),
				(int) $role->getRoleId()
			)
		);
	}

	/**
	 * Retrieve a list of all roles for a specified user.
	 * @param $userId int
	 * @param $conferenceId int optional, include roles only in this conference
	 * @param $schedConfId int optional, include roles only in this scheduled conference
	 * @return array matching Roles
	 */
	function &getRolesByUserId($userId, $conferenceId = null, $schedConfId = null) {
		$roles = array();
		$params = array();

		$params[] = $userId;
		if(isset($conferenceId)) $params[] = $conferenceId;
		if(isset($schedConfId)) $params[] = $schedConfId;

		$result =& $this->retrieve('SELECT * FROM roles WHERE user_id = ?' .
				(isset($conferenceId) ? ' AND conference_id = ?' : '') .
				(isset($schedConfId) ? ' AND sched_conf_id = ?' : ''),
			(count($params) == 1 ? array_shift($params) : $params));

		while (!$result->EOF) {
			$roles[] =& $this->_returnRoleFromRow($result->GetRowAssoc(false));
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
	 * @param $schedConfId int optional, include users only in this conference
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains' vs. 'startsWith')
	 * @param $dbResultRange object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersByRoleId($roleId = null, $conferenceId = null, $schedConfId = null,
			$searchType = null, $search = null, $searchMatch = null, $dbResultRange = null,
			$sortBy = null, $sortDirection = SORT_DIRECTION_ASC ) {

		$users = array();

		$paramArray = array('interests');
		if (isset($roleId)) $paramArray[] = (int) $roleId;
		if (isset($conferenceId)) $paramArray[] = (int) $conferenceId;
		if (isset($schedConfId)) $paramArray[] = (int) $schedConfId;

		// For security / resource usage reasons, a role, scheduled conference, or conference
		// must be specified. Don't allow calls supplying none.
		if ($conferenceId === null && $schedConfId === null && $roleId === null) return null;

		$searchSql = '';

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 's.setting_value'
		);

		if (!empty($search) && isset($searchTypeMap[$searchType])) {
			$fieldName = $searchTypeMap[$searchType];
			switch ($searchMatch) {
				case 'is':
					$searchSql = "AND LOWER($fieldName) = LOWER(?)";
					$paramArray[] = $search;
					break;
				case 'contains':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = '%' . $search . '%';
					break;
				case 'startsWith':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = $search . '%';
					break;
			}
		} elseif (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}

		$searchSql .= ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange(
			'SELECT DISTINCT u.* FROM users AS u LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?), roles AS r WHERE u.user_id = r.user_id ' .
				(isset($roleId)?'AND r.role_id = ?':'') .
				(isset($conferenceId) ? ' AND r.conference_id = ?' : '') .
				(isset($schedConfId) ? ' AND r.sched_conf_id = ?' : '') .
				' ' . $searchSql,
			$paramArray,
			$dbResultRange
		);

		$returner = new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}

	/**
	 * Retrieve a list of all users with some role in the specified conference.
	 * @param $conferenceId int
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains' vs. 'startsWith')
	 * @param $dbRangeInfo object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersByConferenceId($conferenceId, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$users = array();

		$paramArray = array('interests', (int) $conferenceId);
		$searchSql = '';

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 's.setting_value'
		);

		if (!empty($search) && isset($searchTypeMap[$searchType])) {
			$fieldName = $searchTypeMap[$searchType];
			switch ($searchMatch) {
				case 'is':
					$searchSql = "AND LOWER($fieldName) = LOWER(?)";
					$paramArray[] = $search;
					break;
				case 'contains':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = '%' . $search . '%';
					break;
				case 'startsWith':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = $search . '%';
					break;
			}
		} elseif (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}

		$searchSql .= ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange(

			'SELECT DISTINCT u.* FROM users AS u LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?), roles AS r WHERE u.user_id = r.user_id AND r.conference_id = ? ' . $searchSql,
			$paramArray,
			$dbResultRange
		);

		$returner = new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}

	/**
	 * Retrieve a list of all users with some role in the specified scheduled conference.
	 * @param $schedConfId int
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of match ('is' vs. 'contains')
	 * @param $dbRangeInfo object DBRangeInfo object describing range of results to return
	 * @return array matching Users
	 */
	function &getUsersBySchedConfId($schedConfId, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$users = array();

		$paramArray = array('interests', (int) $schedConfId);
		$searchSql = '';

		if (!empty($search)) switch ($searchType) {
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
				$searchSql = 'AND LOWER(s.setting_value) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = ($searchMatch=='is'?$search:'%' . $search . '%');
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND LOWER(u.last_name) LIKE LOWER(?)';
				$paramArray[] = $search . '%';
				break;
		}

		$searchSql .= ($sortBy?(' ORDER BY ' . $this->getSortMapping($sortBy) . ' ' . $this->getDirectionMapping($sortDirection)) : '');

		$result =& $this->retrieveRange(

			'SELECT DISTINCT u.* FROM users AS u LEFT JOIN user_settings s ON (u.user_id = s.user_id AND s.setting_name = ?), roles AS r WHERE u.user_id = r.user_id AND r.sched_conf_id = ? ' . $searchSql,
			$paramArray,
			$dbResultRange
		);

		$returner = new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}

	/**
	 * Retrieve the number of users associated with the specified conference.
	 * @param $conferenceId int
	 * @return int
	 */
	function getConferenceUsersCount($conferenceId) {
		$userDao =& DAORegistry::getDAO('UserDAO');

		$result =& $this->retrieve(
			'SELECT COUNT(DISTINCT(user_id)) FROM roles WHERE conference_id = ?',
			(int) $conferenceId
		);

		$returner = $result->fields[0];

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve the number of users associated with the specified scheduled conference.
	 * @param $schedConfId int
	 * @param $roleId int ROLE_ID_... (optional) role to count
	 * @return int
	 */
	function getSchedConfUsersCount($schedConfId, $roleId = null) {
		$userDao =& DAORegistry::getDAO('UserDAO');

		$params = array((int) $schedConfId);
		if ($roleId !== null) $params[] = (int) $roleId;

		$result =& $this->retrieve(
			'SELECT COUNT(DISTINCT(user_id)) FROM roles WHERE sched_conf_id = ?' . ($roleId === null?'':' AND role_id = ?'),
			$params
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

		$result =& $this->retrieve(
			'SELECT * FROM roles' . (empty($conditions) ? '' : ' WHERE ' . join(' AND ', $conditions)),
			$params
		);

		$returner = new DAOResultFactory($result, $this, '_returnRoleFromRow');
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
	 * Delete all roles for a specified scheduled conference.
	 * @param $schedConfId int
	 */
	function deleteRoleBySchedConfId($schedConfId) {
		return $this->update(
			'DELETE FROM roles WHERE sched_conf_id = ?', (int) $schedConfId
		);
	}

	/**
	 * Delete all roles for a specified conference.
	 * @param $userId int
	 * @param $conferenceId int optional, include roles only in this conference
	 * @param $roleId int optional, include only this role
	 */
	function deleteRoleByUserId($userId, $conferenceId  = null, $roleId = null, $schedConfId = null) {

		$args = array((int)$userId);
		if(isset($conferenceId)) $args[] = (int)$conferenceId;
		if(isset($roleId)) $args[] = (int)$roleId;
		if(isset($schedConfId)) $args[] = (int)$schedConfId;

		return $this->update(
			'DELETE FROM roles WHERE user_id = ?' .
				(isset($conferenceId) ? ' AND conference_id = ?' : '') .
				(isset($roleId) ? ' AND role_id = ?' : '') .
				(isset($schedConfId) ? ' AND sched_conf_id = ?' : ''),
			(count($args) ? $args : shift($args)));
	}

	/**
	 * Check if a role exists.
	 * @param $conferenceId int
	 * @param $userId int
	 * @param $roleId int
	 * @return boolean
	 */
	function roleExists($conferenceId, $schedConfId, $userId, $roleId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM roles WHERE conference_id = ? AND sched_conf_id = ? AND user_id = ? AND role_id = ?', array((int) $conferenceId, (int)$schedConfId, (int) $userId, (int) $roleId)
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
			case ROLE_ID_CONFERENCE_MANAGER:
				return 'user.role.manager' . ($plural ? 's' : '');
			case ROLE_ID_DIRECTOR:
				return 'user.role.director' . ($plural ? 's' : '');
			case ROLE_ID_TRACK_DIRECTOR:
				return 'user.role.trackDirector' . ($plural ? 's' : '');
			case ROLE_ID_REVIEWER:
				return 'user.role.reviewer' . ($plural ? 's' : '');
			case ROLE_ID_AUTHOR:
				return 'user.role.author' . ($plural ? 's' : '');
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
			case ROLE_ID_CONFERENCE_MANAGER:
				return ROLE_PATH_CONFERENCE_MANAGER;
			case ROLE_ID_DIRECTOR:
				return ROLE_PATH_DIRECTOR;
			case ROLE_ID_TRACK_DIRECTOR:
				return ROLE_PATH_TRACK_DIRECTOR;
			case ROLE_ID_REVIEWER:
				return ROLE_PATH_REVIEWER;
			case ROLE_ID_AUTHOR:
				return ROLE_PATH_AUTHOR;
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
			case ROLE_PATH_CONFERENCE_MANAGER:
				return ROLE_ID_CONFERENCE_MANAGER;
			case ROLE_PATH_DIRECTOR:
				return ROLE_ID_DIRECTOR;
			case ROLE_PATH_TRACK_DIRECTOR:
				return ROLE_ID_TRACK_DIRECTOR;
			case ROLE_PATH_REVIEWER:
				return ROLE_ID_REVIEWER;
			case ROLE_PATH_AUTHOR:
				return ROLE_ID_AUTHOR;
			case ROLE_PATH_READER:
				return ROLE_ID_READER;
			default:
				return null;
		}
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param string
	 * @return string
	 */
	function getSortMapping($heading) {
		switch ($heading) {
			case 'username': return 'u.username';
			case 'name': return 'u.last_name';
			case 'email': return 'u.email';
			case 'id': return 'u.user_id';
			default: return null;
		}
	}
}

?>
