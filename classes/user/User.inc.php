<?php

/**
 * @defgroup user
 */

/**
 * @file User.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class User
 * @ingroup user
 *
 * @brief Basic class describing users existing in the system.
 *
 */

// $Id$


import('user.PKPUser');

class User extends PKPUser {

	function User() {
		parent::PKPUser();
	}

	function setTimeZone($timeZone) {
		return $this->updateSetting('timeZone', $timeZone);
	}

	function getTimeZone() {
		return $this->getSetting('timeZone');
	}

	/**
	 * Retrieve array of user settings.
	 * @param conferenceId int
	 * @return array
	 */
	function &getSettings($conferenceId = null) {
		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
		$settings =& $userSettingsDao->getSettingsByConference($this->getId(), $conferenceId);
		return $settings;
	}

	/**
	 * Retrieve a user setting value.
	 * @param $name
	 * @param $conferenceId int
	 * @return mixed
	 */
	function &getSetting($name, $conferenceId = null) {
		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
		$setting =& $userSettingsDao->getSetting($this->getId(), $name, $conferenceId);
		return $setting;
	}

	/**
	 * Set a user setting value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string optional
	 */
	function updateSetting($name, $value, $type = null, $conferenceId = null) {
		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
		return $userSettingsDao->updateSetting($this->getId(), $name, $value, $type, $conferenceId);
	}
}

?>
