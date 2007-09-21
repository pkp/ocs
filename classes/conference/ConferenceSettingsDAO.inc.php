<?php

/**
 * @file ConferenceSettingsDAO.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package conference
 * @class ConferenceSettingsDAO
 *
 * Class for Conference Settings DAO.
 * Operations for retrieving and modifying conference settings.
 *
 * $Id$
 */

import('db.SettingsDAO');
class ConferenceSettingsDAO extends SettingsDAO {
	function &_getCache($conferenceId) {
		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$conferenceId])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$settingCache[$conferenceId] = $cacheManager->getCache(
				'conferenceSettings', $conferenceId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$conferenceId];
	}

	/**
	 * Retrieve a conference setting value.
	 * @param $conferenceId int
	 * @param $name string
	 * @param $locale string optional
	 * @return mixed
	 */
	function &getSetting($conferenceId, $name, $locale = null) {
		$cache =& $this->_getCache($conferenceId);
		$returner = $cache->get($name);
		if ($locale !== null) {
			if (!isset($returner[$locale]) || !is_array($returner)) {
				unset($returner);
				$returner = null;
				return $returner;
			}
			return $returner[$locale];
		}
		return $returner;
	}

	function _cacheMiss(&$cache, $id) {
		$settings =& $this->getConferenceSettings($cache->getCacheId());
		if (!isset($settings[$id])) {
			// Make sure that even null values are cached
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Retrieve and cache all settings for a conference.
	 * @param $conferenceId int
	 * @return array
	 */
	function &getConferenceSettings($conferenceId) {
		$conferenceSettings = array();

		$result = &$this->retrieve(
			'SELECT setting_name, setting_value, setting_type, locale FROM conference_settings WHERE conference_id = ?', $conferenceId
		);

		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;

		} else {
			while (!$result->EOF) {
				$row = &$result->getRowAssoc(false);
				$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
				if ($row['locale'] == '') $conferenceSettings[$row['setting_name']] = $value;
				else $conferenceSettings[$row['setting_name']][$row['locale']] = $value;
				$result->MoveNext();
			}
			$result->close();
			unset($result);

			$cache =& $this->_getCache($conferenceId);
			$cache->setEntireCache($conferenceSettings);

			return $conferenceSettings;
		}
	}

	/**
	 * Add/update a conference setting.
	 * @param $conferenceId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @param $isLocalized boolean
	 */
	function updateSetting($conferenceId, $name, $value, $type = null, $isLocalized = false) {
		$cache =& $this->_getCache($conferenceId);
		$cache->setCache($name, $value);

		$keyFields = array('setting_name', 'locale', 'conference_id');

		if (!$isLocalized) {
			$value = $this->convertToDB($value, $type);
			$this->replace('conference_settings',
				array(
					'conference_id' => $conferenceId,
					'setting_name' => $name,
					'setting_value' => $value,
					'setting_type' => $type,
					'locale' => ''
				),
				$keyFields
			);
		} else {
			$this->update('DELETE FROM conference_settings WHERE conference_id = ? AND setting_name = ?', array($conferenceId, $name));
			if (is_array($value)) foreach ($value as $locale => $localeValue) {
				if (empty($localeValue)) continue;
				$type = null;
				$this->update('INSERT INTO conference_settings
					(conference_id, setting_name, setting_value, setting_type, locale)
					VALUES (?, ?, ?, ?, ?)',
					array(
						$conferenceId, $name, $this->convertToDB($localeValue, $type), $type, $locale
					)
				);
			}
		}
	}

	/**
	 * Delete a conference setting.
	 * @param $conferenceId int
	 * @param $name string
	 * @param $locale string
	 */
	function deleteSetting($conferenceId, $name, $locale = null) {
		$cache =& $this->_getCache($conferenceId);
		$cache->setCache($name, null);

		$params = array($conferenceId, $name);
		$sql = 'DELETE FROM conference_settings WHERE conference_id = ? AND setting_name = ?';
		if ($locale !== null) {
			$params[] = $locale;
			$sql .= ' AND locale = ?';
		}

		return $this->update($sql, $params);
	}

	/**
	 * Delete all settings for a conference.
	 * @param $conferenceId int
	 */
	function deleteSettingsByConference($conferenceId) {
		$cache =& $this->_getCache($conferenceId);
		$cache->flush();

		return $this->update(
				'DELETE FROM conference_settings WHERE conference_id = ?', $conferenceId
		);
	}
}

?>
