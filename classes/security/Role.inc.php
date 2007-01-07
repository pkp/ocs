<?php

/**
 * Role.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 *
 * Role class.
 * Describes user roles within the system and the associated permissions.
 *
 * $Id$
 */

/** ID codes for all user roles */
define('ROLE_ID_SITE_ADMIN',						0x00000001);
define('ROLE_PATH_SITE_ADMIN',					'admin');

define('ROLE_ID_CONFERENCE_DIRECTOR',		0x00000010);
define('ROLE_PATH_CONFERENCE_DIRECTOR', 'director');

define('ROLE_ID_REGISTRATION_MANAGER',	0x00000020);
define('ROLE_PATH_REGISTRATION_MANAGER','registrationManager');

define('ROLE_ID_EDITOR',								0x00000040);
define('ROLE_PATH_EDITOR', 							'editor');

define('ROLE_ID_TRACK_EDITOR',					0x00000080);
define('ROLE_PATH_TRACK_EDITOR', 				'trackEditor');

define('ROLE_ID_REVIEWER',							0x00000100);
define('ROLE_PATH_REVIEWER',						'reviewer');

define('ROLE_ID_AUTHOR',			 					0x00001000);
define('ROLE_PATH_AUTHOR',							'author');

//define('ROLE_ID_INVITED_AUTHOR',				0x00001001);
//define('ROLE_PATH_INVITED_AUTHOR',			'invitedAuthor');

//define('ROLE_ID_DISCUSSANT',						0x00010000);
//define('ROLE_PATH_DISCUSSANT',					'discussant');

//define('ROLE_ID_REGISTRANT',						0x00020000);
//define('ROLE_PATH_REGISTRANT',					'registrant');

define('ROLE_ID_READER',								0x00008000);
define('ROLE_PATH_READER',							'reader');

class Role extends DataObject {

	/**
	 * Constructor.
	 */
	function Role() {
		parent::DataObject();
	}
	
	/**
	 * Get the i18n key name associated with this role.
	 * @return String the key
	 */
	function getRoleName() {
		return RoleDAO::getRoleName($this->getData('roleId'));
	}
	
	/**
	 * Get the URL path associated with this role's operations.
	 * @return String the path
	 */
	function getRolePath() {
		return RoleDAO::getRolePath($this->getData('roleId'));
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
	 * Get event ID associated with role.
	 * @return int
	 */
	function getEventId() {
		return $this->getData('eventId');
	}
	
	/**
	 * Set event ID associated with role.
	 * @param $conferenceId int
	 */
	function setEventId($eventId) {
		return $this->setData('eventId', $eventId);
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
	
	/**
	 * Get role ID of this role.
	 * @return int
	 */
	function getRoleId() {
		return $this->getData('roleId');
	}
	
	/**
	 * Set role ID of this role.
	 * @param $roleId int
	 */
	function setRoleId($roleId) {
		return $this->setData('roleId', $roleId);
	}
}

?>
