<?php

/**
 * @file classes/user/UserSettingsDAO.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserSettingsDAO
 * @ingroup user
 * @see User
 *
 * @brief Operations for retrieving and modifying user settings.
 */

// $Id$


import('user.PKPUserSettingsDAO');

class UserSettingsDAO extends PKPUserSettingsDAO {
	/**
	 * Retrieve a user setting value.
	 * @param $userId int
	 * @param $name
	 * @param $conferenceId int
	 * @return mixed
	 */
	function &getSetting($userId, $name, $conferenceId = null) {
		return parent::getSetting($userId, $name, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Retrieve all users by setting name and value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string
	 * @param $conferenceId int
	 * @return DAOResultFactory matching Users
	 */
	function &getUsersBySetting($name, $value, $type = null, $conferenceId = null) {
		return parent::getUsersBySetting($name, $value, $type, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Retrieve all settings for a user for a conference.
	 * @param $userId int
	 * @param $conferenceId int
	 * @return array 
	 */
	function &getSettingsByConference($userId, $conferenceId = null) {
		return parent::getSettingsByAssoc($userId, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Add/update a user setting.
	 * @param $userId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @param $conferenceId int
	 */
	function updateSetting($userId, $name, $value, $type = null, $conferenceId = null) {
		return parent::updateSetting($userId, $name, $value, $type, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Delete a user setting.
	 * @param $userId int
	 * @param $name string
	 * @param $conferenceId int
	 */
	function deleteSetting($userId, $name, $conferenceId = null) {
		return parent::deleteSetting($userId, $name, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}
}

?>
