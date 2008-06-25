<?php

/**
 * @file UserSettingsDAO.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user
 * @class UserSettingsDAO
 *
 * Class for User Settings DAO.
 * Operations for retrieving and modifying user settings.
 *
 * $Id$
 */

class UserSettingsDAO extends DAO {
	/**
	 * Retrieve a user setting value.
	 * @param $userId int
	 * @param $name
	 * @param $conferenceId int
	 * @return mixed
	 */
	function &getSetting($userId, $name, $conferenceId = null) {

		if ($conferenceId == null) {
			$result = &$this->retrieve(
				'SELECT setting_value, setting_type FROM user_settings WHERE user_id = ? AND setting_name = ? AND conference_id = 0', array($userId, $name)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT setting_value, setting_type FROM user_settings WHERE user_id = ? AND setting_name = ? AND conference_id = ?', array($userId, $name, $conferenceId)
			);
		}

		if ($result->RecordCount() != 0) {
			$row = &$result->getRowAssoc(false);
			$returner = $this->convertFromDB($row['setting_value'], $row['setting_type']);
		} else {
			$returner = null;
		}

		return $returner;

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
		$userDao = &DAORegistry::getDAO('UserDAO');

		$value = $this->convertToDB($value, $type);
		if ($conferenceId == null) {
			$result = &$this->retrieve(
				'SELECT u.* FROM users u, user_settings s WHERE u.user_id = s.user_id AND s.setting_name = ? AND s.setting_value = ? AND s.conference_id = 0',
				array($name, $value)
			);
		} else {				
			$result = &$this->retrieve(
				'SELECT u.* FROM users u, user_settings s WHERE u.user_id = s.user_id AND s.setting_name = ? AND s.setting_value = ? AND s.conference_id = ?',
				array($name, $value, $conferenceId)
			);
		}

		$returner = &new DAOResultFactory($result, $userDao, '_returnUserFromRow');
		return $returner;
	}

	/**
	 * Retrieve all settings for a user for a conference.
	 * @param $userId int
	 * @param $conferenceId int
	 * @return array 
	 */
	function &getSettingsByConference($userId, $conferenceId = null) {

		$userSettings = array();

		if ($conferenceId == null) {
			$result = &$this->retrieve(
				'SELECT setting_name, setting_value, setting_type FROM user_settings WHERE user_id = ? AND conference_id = 0', $userId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT setting_name, setting_value, setting_type FROM user_settings WHERE user_id = ? and conference_id = ?', array($userId, $conferenceId)
			);
		}

		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;

		} else {
			while (!$result->EOF) {
				$row = &$result->getRowAssoc(false);
				$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
				$userSettings[$row['setting_name']] = $value;
				$result->MoveNext();
			}
			$result->close();
			unset($result);

			return $userSettings;
		}
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
		if ($conferenceId == null) {		
			$result = $this->retrieve(
				'SELECT COUNT(*) FROM user_settings WHERE user_id = ? AND setting_name = ? AND conference_id = 0', array($userId, $name)
			);
		} else {
			$result = $this->retrieve(
				'SELECT COUNT(*) FROM user_settings WHERE user_id = ? AND setting_name = ? AND conference_id = ?', array($userId, $name, $conferenceId)
			);
		}

		$value = $this->convertToDB($value, $type);
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO user_settings
					(user_id, setting_name, conference_id, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?, ?)',
				array($userId, $name, $conferenceId ? $conferenceId : 0, $value, $type)
			);
		} else {
			if ($conferenceId == null) {
				$returner = $this->update(
					'UPDATE user_settings SET
						setting_value = ?,
						setting_type = ?
						WHERE user_id = ? AND setting_name = ? AND conference_id = 0',
					array($value, $type, $userId, $name)
				);
			} else {
				$returner = $this->update(
					'UPDATE user_settings SET
						setting_value = ?,
						setting_type = ?
						WHERE user_id = ? AND setting_name = ? AND conference_id = ?',
					array($value, $type, $userId, $name, $conferenceId)
				);
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Delete a user setting.
	 * @param $userId int
	 * @param $name string
	 * @param $conferenceId int
	 */
	function deleteSetting($userId, $name, $conferenceId = null) {
		if ($conferenceId == null) {
			return $this->update(
				'DELETE FROM user_settings WHERE user_id = ? AND setting_name = ? AND conference_id = 0',
				array($userId, $name)
			);
		} else {		
			return $this->update(
				'DELETE FROM user_settings WHERE user_id = ? AND setting_name = ? AND conference_id = ?',
				array($userId, $name, $conferenceId)
			);
		}
	}

	/**
	 * Delete all settings for a user.
	 * @param $userId int
	 */
	function deleteSettings($userId) {
		return $this->update(
				'DELETE FROM user_settings WHERE user_id = ?', $userId
		);
	}
}

?>
