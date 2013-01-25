<?php

/**
 * @file Role.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Role
 * @ingroup security
 * @see RoleDAO
 *
 * @brief Describes user roles within the system and the associated permissions.
 */

import('lib.pkp.classes.security.PKPRole');

/** ID codes for all user roles */
define('ROLE_ID_DIRECTOR',			0x00000040);
define('ROLE_PATH_DIRECTOR', 			'director');

define('ROLE_ID_TRACK_DIRECTOR',		0x00000080);
define('ROLE_PATH_TRACK_DIRECTOR', 		'trackDirector');

//define('ROLE_ID_INVITED_AUTHOR',		0x00001001);
//define('ROLE_PATH_INVITED_AUTHOR',		'invitedAuthor');

//define('ROLE_ID_DISCUSSANT',			0x00010000);
//define('ROLE_PATH_DISCUSSANT',		'discussant');

//define('ROLE_ID_REGISTRANT',			0x00020000);
//define('ROLE_PATH_REGISTRANT',		'registrant');

class Role extends PKPRole {

	/**
	 * Constructor.
	 * @param $roleId for this role.  Default to null for backwards
	 * 	compatibility
	 */
	function Role($roleId = null) {
		parent::PKPRole($roleId);
	}

	/**
	 * Get the i18n key name associated with the specified role.
	 * @param $plural boolean get the plural form of the name
	 * @return string
	 */
	function getRoleName($plural = false) {
		switch ($this->getId()) {
			case ROLE_ID_DIRECTOR:
				return 'user.role.director' . ($plural ? 's' : '');
			case ROLE_ID_TRACK_DIRECTOR:
				return 'user.role.trackDirector' . ($plural ? 's' : '');
			default:
				return parent::getRoleName($plural);
		}
	}

	/**
	 * Get the URL path associated with the specified role's operations.
	 * @return string
	 */
	function getPath() {
		switch ($this->getId()) {
		case ROLE_ID_DIRECTOR:
			return ROLE_PATH_DIRECTOR;
		case ROLE_ID_TRACK_DIRECTOR:
			return ROLE_PATH_TRACK_DIRECTOR;
			default:
				return parent::getPath();
		}
	}

	//
	// Get/set methods
	//

	/**
	 * Get conference ID associated with role.
	 * @return int
	 */
	function getConferenceId() {
		return $this->getData('conferenceId');
	}

	/**
	 * Set conference ID associated with role.
	 * @param $conferenceId int
	 */
	function setConferenceId($conferenceId) {
		return $this->setData('conferenceId', $conferenceId);
	}

	/**
	 * Get scheduled conference ID associated with role.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set scheduled conference ID associated with role.
	 * @param $conferenceId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get user ID associated with role.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set user ID associated with role.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}
}

?>
