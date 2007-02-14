<?php

/**
 * SchedConfSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package schedConf
 *
 * Class for Scheduled Conference Settings DAO.
 * Operations for retrieving and modifying scheduled conference settings.
 *
 * $Id$
 */

import('db.SettingsDAO');
class SchedConfSettingsDAO extends SettingsDAO {
	/**
	 * Constructor.
	 */
	function SchedConfSettingsDAO() {
		parent::DAO();
	}

	function &_getCache($schedConfId) {
		static $settingCache;
		
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$schedConfId])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$settingCache[$schedConfId] =& $cacheManager->getCache(
				'schedConfSettings', $schedConfId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$schedConfId];
	}

	/**
	 * Retrieve a scheduled conference setting value.
	 * @param $schedConfId int
	 * @param $name
	 * @return mixed
	 */
	function &getSetting($schedConfId, $name, $includeParent = false) {
		$cache =& $this->_getCache($schedConfId);
		$returner = $cache->get($name);

		/* TODO: this doesn't allow empty scheduled conference overrides */
		if($returner === null && $includeParent) {
			$schedConfDao = &DAORegistry::getDao('SchedConfDAO');
			$schedConf = &$schedConfDao->getSchedConf($schedConfId);
			$conference = &$schedConf->getConference();
			return $conference->getSetting($name);
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
	function &getSchedConfSettings($schedConfId, $includeParent = false) {

		/* Pre-seed with conference settings */
		if ($includeParent) {
			$conferenceSettingsDao = &DAORegistry::getDao('ConferenceSettingsDAO');
			$schedConfDao = &DAORegistry::getDao('SchedConfDAO');
			$schedConf = &$schedConfDao->getSchedConf($schedConfId);
			$schedConfSettings = &$conferenceSettingsDao->getConferenceSettings($schedConf->getConferenceId());
		} else {
			$schedConfSettings = array();
		}
		
		$result = &$this->retrieve(
			'SELECT setting_name, setting_value, setting_type FROM sched_conf_settings WHERE sched_conf_id = ?', $schedConfId 
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
				$schedConfSettings[$row['setting_name']] = $value;
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
	 */
	function updateSetting($schedConfId, $name, $value, $type = null) {
		$cache =& $this->_getCache($schedConfId);
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
			'SELECT COUNT(*) FROM sched_conf_settings WHERE sched_conf_id = ? AND setting_name = ?',
			array($schedConfId, $name)
		);
		
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO sched_conf_settings
					(sched_conf_id, setting_name, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?)',
				array($schedConfId, $name, $value, $type)
			);
		} else {
			$returner = $this->update(
				'UPDATE sched_conf_settings SET
					setting_value = ?,
					setting_type = ?
					WHERE sched_conf_id = ? AND setting_name = ?',
				array($value, $type, $schedConfId, $name)
			);
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Delete a scheduled conference setting.
	 * @param $schedConfId int
	 * @param $name string
	 */
	function deleteSetting($schedConfId, $name) {
		$cache =& $this->_getCache($schedConfId);
		$cache->setCache($name, null);
		
		return $this->update(
			'DELETE FROM sched_conf_settings WHERE sched_conf_id = ? AND setting_name = ?',
			array($schedConfId, $name)
		);
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
