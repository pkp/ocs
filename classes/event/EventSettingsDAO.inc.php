<?php

/**
 * EventSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package event
 *
 * Class for Event Settings DAO.
 * Operations for retrieving and modifying event settings.
 *
 * $Id$
 */

import('db.SettingsDAO');
class EventSettingsDAO extends SettingsDAO {
	/**
	 * Constructor.
	 */
	function EventSettingsDAO() {
		parent::DAO();
	}

	function &_getCache($eventId) {
		static $settingCache;
		
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$eventId])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$settingCache[$eventId] =& $cacheManager->getCache(
				'eventSettings', $eventId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$eventId];
	}

	/**
	 * Retrieve a event setting value.
	 * @param $eventId int
	 * @param $name
	 * @return mixed
	 */
	function &getSetting($eventId, $name, $includeParent = false) {
		$cache =& $this->_getCache($eventId);
		$returner = $cache->get($name);

		/* TODO: this doesn't allow empty event overrides */
		if($returner === null && $includeParent) {
			$eventDao = &DAORegistry::getDao('EventDAO');
			$event = &$eventDao->getEvent($eventId);
			$conference = &$event->getConference();
			return $conference->getSetting($name);
		}
		
		return $returner;
	}

	function _cacheMiss(&$cache, $id) {
		$settings =& $this->getEventSettings($cache->getCacheId());
		if (!isset($settings[$id])) {
			// Make sure that even null values are cached
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Retrieve and cache all settings for a event.
	 * @param $eventId int
	 * @return array
	 */
	function &getEventSettings($eventId, $includeParent = false) {

		/* Pre-seed with conference settings */
		if ($includeParent) {
			$conferenceSettingsDao = &DAORegistry::getDao('ConferenceSettingsDAO');
			$eventDao = &DAORegistry::getDao('EventDAO');
			$event = &$eventDao->getEvent($eventId);
			$eventSettings = &$conferenceSettingsDao->getConferenceSettings($event->getConferenceId());
		} else {
			$eventSettings = array();
		}
		
		$result = &$this->retrieve(
			'SELECT setting_name, setting_value, setting_type FROM event_settings WHERE event_id = ?', $eventId
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
				$eventSettings[$row['setting_name']] = $value;
				$result->MoveNext();
			}
			$result->close();
			unset($result);

			$cache =& $this->_getCache($eventId);
			$cache->setEntireCache($eventSettings);

			return $eventSettings;
		}
	}
	
	/**
	 * Add/update a event setting.
	 * @param $eventId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 */
	function updateSetting($eventId, $name, $value, $type = null) {
		$cache =& $this->_getCache($eventId);
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
			'SELECT COUNT(*) FROM event_settings WHERE event_id = ? AND setting_name = ?',
			array($eventId, $name)
		);
		
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO event_settings
					(event_id, setting_name, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?)',
				array($eventId, $name, $value, $type)
			);
		} else {
			$returner = $this->update(
				'UPDATE event_settings SET
					setting_value = ?,
					setting_type = ?
					WHERE event_id = ? AND setting_name = ?',
				array($value, $type, $eventId, $name)
			);
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Delete a event setting.
	 * @param $eventId int
	 * @param $name string
	 */
	function deleteSetting($eventId, $name) {
		$cache =& $this->_getCache($eventId);
		$cache->setCache($name, null);
		
		return $this->update(
			'DELETE FROM event_settings WHERE event_id = ? AND setting_name = ?',
			array($eventId, $name)
		);
	}
	
	/**
	 * Delete all settings for a event.
	 * @param $eventId int
	 */
	function deleteSettingsByEvent($eventId) {
		$cache =& $this->_getCache($eventId);
		$cache->flush();
		
		return $this->update(
				'DELETE FROM event_settings WHERE event_id = ?', $eventId
		);
	}
}

?>
