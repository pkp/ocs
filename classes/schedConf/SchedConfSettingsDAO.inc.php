<?php

/**
 * @file SchedConfSettingsDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSettingsDAO
 * @ingroup schedConf
 *
 * @brief Operations for retrieving and modifying scheduled conference settings.
 *
 */

// $Id$


import('db.SettingsDAO');
class SchedConfSettingsDAO extends SettingsDAO {
	function &_getCache($schedConfId) {
		static $settingCache;

		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$schedConfId])) {
			$cacheManager =& CacheManager::getManager();
			$settingCache[$schedConfId] = $cacheManager->getCache(
				'schedConfSettings', $schedConfId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$schedConfId];
	}

	/**
	 * Retrieve a scheduled conference setting value.
	 * @param $schedConfId int
	 * @param $name string
	 * @param $locale string optional
	 * @return mixed
	 */
	function &getSetting($schedConfId, $name, $locale = null) {
		$cache =& $this->_getCache($schedConfId);
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
		$settings =& $this->getSchedConfSettings($cache->getCacheId());
		if (!isset($settings[$id])) {
			// Make sure that even null values are cached
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Retrieve and cache all settings for a scheduled conference.
	 * @param $schedConfId int
	 * @return array
	 */
	function &getSchedConfSettings($schedConfId) {
		$schedConfSettings = array();

		$result =& $this->retrieve(
			'SELECT setting_name, setting_value, setting_type, locale FROM sched_conf_settings WHERE sched_conf_id = ?', $schedConfId 
		);

		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;

		} else {
			while (!$result->EOF) {
				$row =& $result->getRowAssoc(false);
				$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
				if ($row['locale'] == '') $schedConfSettings[$row['setting_name']] = $value;
				else $schedConfSettings[$row['setting_name']][$row['locale']] = $value;
				$result->MoveNext();
			}
			$result->close();
			unset($result);

			$cache =& $this->_getCache($schedConfId);
			$cache->setEntireCache($schedConfSettings);

			return $schedConfSettings;
		}
	}

	/**
	 * Add/update a scheduled conference setting.
	 * @param $schedConfId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @param $isLocalized boolean
	 */
	function updateSetting($schedConfId, $name, $value, $type = null, $isLocalized = false) {
		$cache =& $this->_getCache($schedConfId);
		$cache->setCache($name, $value);

		$keyFields = array('setting_name', 'locale', 'sched_conf_id');

		if (!$isLocalized) {
			$value = $this->convertToDB($value, $type);
			$this->replace('sched_conf_settings',
				array(
					'sched_conf_id' => $schedConfId,
					'setting_name' => $name,
					'setting_value' => $value,
					'setting_type' => $type,
					'locale' => ''
				),
				$keyFields
			);
		} else {
			$this->update('DELETE FROM sched_conf_settings WHERE sched_conf_id = ? AND setting_name = ?', array($schedConfId, $name));
			if (is_array($value)) foreach ($value as $locale => $localeValue) {
				if (empty($localeValue)) continue;
				$type = null;
				$this->update('INSERT INTO sched_conf_settings
					(sched_conf_id, setting_name, setting_value, setting_type, locale)
					VALUES (?, ?, ?, ?, ?)',
					array(
						$schedConfId, $name, $this->convertToDB($localeValue, $type), $type, $locale
					)
				);
			}
		}
	}

	/**
	 * Delete a scheduled conference setting.
	 * @param $schedConfId int
	 * @param $name string
	 * @param $locale string optional
	 */
	function deleteSetting($schedConfId, $name, $locale = null) {
		$cache =& $this->_getCache($schedConfId);
		$cache->setCache($name, null);

		$params = array($schedConfId, $name);
		$sql = 'DELETE FROM sched_conf_settings WHERE sched_conf_id = ? AND setting_name = ?';
		if ($locale !== null) {
			$params[] = $locale;
			$sql .= ' AND locale = ?';
		}
		return $this->update($sql, $params);
	}

	/**
	 * Delete all settings for a scheduled conference.
	 * @param $schedConfId int
	 */
	function deleteSettingsBySchedConf($schedConfId) {
		$cache =& $this->_getCache($schedConfId);
		$cache->flush();

		return $this->update(
				'DELETE FROM sched_conf_settings WHERE sched_conf_id = ?', $schedConfId
		);
	}
}

?>
