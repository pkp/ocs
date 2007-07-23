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
	/**
	 * Constructor.
	 */
	function ConferenceSettingsDAO() {
		parent::DAO();
	}

	function &_getCache($conferenceId) {
		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$conferenceId])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$settingCache[$conferenceId] =& $cacheManager->getCache(
				'conferenceSettings', $conferenceId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$conferenceId];
	}

	/**
	 * Retrieve a conference setting value.
	 * @param $conferenceId int
	 * @param $name
	 * @return mixed
	 */
	function &getSetting($conferenceId, $name) {
		$cache =& $this->_getCache($conferenceId);
		$returner = $cache->get($name);
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
			'SELECT setting_name, setting_value, setting_type FROM conference_settings WHERE conference_id = ?', $conferenceId
		);
		
		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;
			
		} else {
			while (!$result->EOF) {
				$row = &$result->getRowAssoc(false);
				switch ($row['setting_type']) {
					case 'date':
						list($y, $mon, $d, $h, $min, $s) = sscanf($row['setting_value'], "%d-%d-%d %d:%d:%d");
						$value = mktime($h,$min,$s,$mon,$d,$y);
						break;
					case 'bool':
						$value = (bool) $row['setting_value'];
						break;
					case 'int':
						$value = (int) $row['setting_value'];
						break;
					case 'float':
						$value = (float) $row['setting_value'];
						break;
					case 'object':
						$value = unserialize($row['setting_value']);
						break;
					case 'string':
					default:
						$value = $row['setting_value'];
						break;
				}
				$conferenceSettings[$row['setting_name']] = $value;
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
	 */
	function updateSetting($conferenceId, $name, $value, $type = null) {
		$cache =& $this->_getCache($conferenceId);
		$cache->setCache($name, $value);
		
		if ($type == null) {
			switch (gettype($value)) {
				case 'date':
					$type = 'date';
					break;
				case 'boolean':
				case 'bool':
					$type = 'bool';
					break;
				case 'integer':
				case 'int':
					$type = 'int';
					break;
				case 'double':
				case 'float':
					$type = 'float';
					break;
				case 'array':
				case 'object':
					$type = 'object';
					break;
				case 'string':
				default:
					$type = 'string';
					break;
			}
		}
		
		if ($type == 'object') {
			$value = serialize($value);
			
		} else if ($type == 'bool') {
			$value = isset($value) && $value ? 1 : 0;
		} else if ($type == 'date') {
			$value = date("Y-n-d H:i:s", $value);
		}
		
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM conference_settings WHERE conference_id = ? AND setting_name = ?',
			array($conferenceId, $name)
		);
		
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO conference_settings
					(conference_id, setting_name, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?)',
				array($conferenceId, $name, $value, $type)
			);
		} else {
			$returner = $this->update(
				'UPDATE conference_settings SET
					setting_value = ?,
					setting_type = ?
					WHERE conference_id = ? AND setting_name = ?',
				array($value, $type, $conferenceId, $name)
			);
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Delete a conference setting.
	 * @param $conferenceId int
	 * @param $name string
	 */
	function deleteSetting($conferenceId, $name) {
		$cache =& $this->_getCache($conferenceId);
		$cache->setCache($name, null);
		
		return $this->update(
			'DELETE FROM conference_settings WHERE conference_id = ? AND setting_name = ?',
			array($conferenceId, $name)
		);
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
