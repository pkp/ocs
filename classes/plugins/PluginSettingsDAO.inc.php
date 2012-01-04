<?php

/**
 * @file PluginSettingsDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginSettingsDAO
 * @ingroup plugins
 * @see Plugin, PluginRegistry
 *
 * @brief Operations for retrieving and modifying plugin settings.
 */

//$Id$

class PluginSettingsDAO extends DAO {
	function &_getCache($conferenceId, $schedConfId, $pluginName) {
		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$conferenceId])) {
			$settingCache[$conferenceId] = array();
		}
		if (!isset($settingCache[$conferenceId][$schedConfId])) {
			$settingCache[$conferenceId][$schedConfId] = array();
		}
		if (!isset($settingCache[$conferenceId][$schedConfId][$pluginName])) {
			$cacheManager =& CacheManager::getManager();
			$settingCache[$conferenceId][$schedConfId][$pluginName] = $cacheManager->getCache(
				'pluginSettings-' . ((int) $conferenceId) . '-' . ((int) $schedConfId), $pluginName,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$conferenceId][$schedConfId][$pluginName];
	}

	/**
	 * Retrieve a plugin setting value.
	 * @param $conferenceId int
	 * @param $schedConfIf int
	 * @param $pluginName string
	 * @param $name
	 * @return mixed
	 */
	function getSetting($conferenceId, $schedConfId, $pluginName, $name) {
		$cache =& $this->_getCache($conferenceId, $schedConfId, $pluginName);
		return $cache->get($name);
	}

	function _cacheMiss(&$cache, $id) {
		$contextParts = explode('-', $cache->getContext());
		$schedConfId = array_pop($contextParts);
		$conferenceId = array_pop($contextParts);
		$settings =& $this->getPluginSettings($conferenceId, $schedConfId, $cache->getCacheId());
		if (!isset($settings[$id])) {
			// Make sure that even null values are cached
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Retrieve and cache all settings for a plugin.
	 * @param $conferenceId int
	 * @param $schedConfIf int
	 * @param $pluginName string
	 * @return array
	 */
	function &getPluginSettings($conferenceId, $schedConfId, $pluginName) {
		$pluginSettings[$pluginName] = array();

		$result =& $this->retrieve(
			'SELECT setting_name, setting_value, setting_type FROM plugin_settings WHERE plugin_name = ? AND conference_id = ? AND sched_conf_id = ?', array($pluginName, $conferenceId, $schedConfId)
		);

		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;

		} else {
			while (!$result->EOF) {
				$row =& $result->getRowAssoc(false);
				$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
				$pluginSettings[$pluginName][$row['setting_name']] = $value;
				$result->MoveNext();
			}
			$result->close();
			unset($result);

			$cache =& $this->_getCache($conferenceId, $schedConfId, $pluginName);
			$cache->setEntireCache($pluginSettings[$pluginName]);

			return $pluginSettings[$pluginName];
		}
	}

	/**
	 * Add/update a plugin setting.
	 * @param $conferenceId int
	 * @param $schedConfIf int
	 * @param $pluginName string
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 */
	function updateSetting($conferenceId, $schedConfId, $pluginName, $name, $value, $type = null) {
		$cache =& $this->_getCache($conferenceId, $schedConfId, $pluginName);
		$cache->setCache($name, $value);

		$result = $this->retrieve(
			'SELECT COUNT(*) FROM plugin_settings WHERE plugin_name = ? AND setting_name = ? AND conference_id = ? AND sched_conf_id = ?',
			array($pluginName, $name, $conferenceId, $schedConfId)
		);

		$value = $this->convertToDB($value, $type);
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO plugin_settings
					(plugin_name, conference_id, sched_conf_id, setting_name, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?, ?, ?)',
				array($pluginName, $conferenceId, $schedConfId, $name, $value, $type)
			);
		} else {
			$returner = $this->update(
				'UPDATE plugin_settings SET
					setting_value = ?,
					setting_type = ?
					WHERE plugin_name = ? AND setting_name = ? AND conference_id = ? AND sched_conf_id = ?',
				array($value, $type, $pluginName, $name, $conferenceId, $schedConfId)
			);
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Delete a plugin setting.
	 * @param $conferenceId int
	 * @param $schedConfIf int
	 * @param $schedConfId int
	 * @param $pluginName int
	 * @param $name string
	 */
	function deleteSetting($conferenceId, $schedConfId, $pluginName, $name) {
		$cache =& $this->_getCache($conferenceId, $schedConfId, $pluginName);
		$cache->setCache($name, null);

		return $this->update(
			'DELETE FROM plugin_settings WHERE plugin_name = ? AND setting_name = ? AND conference_id = ? AND sched_conf_id = ?',
			array($pluginName, $name, $conferenceId, $schedConfId)
		);
	}

	/**
	 * Delete all settings for a plugin.
	 * @param $pluginName string
	 */
	function deleteSettingsByPlugin($pluginName, $conferenceId = null, $schedConfId = null) {
		if ( $conferenceId && $schedConfId) { 
			$cache =& $this->_getCache($conferenceId, $schedConfId, $pluginName);
			$cache->flush();

			return $this->update(
					'DELETE FROM plugin_settings WHERE plugin_name = ? AND conference_id = ? AND sched_conf_id = ?', 
					array($pluginName, $conferenceId, $schedConfId)
			);
		} else {
			$cacheManager =& CacheManager::getManager();
			// NB: this actually deletes all plugins' settings cache			
			$cacheManager->flush('pluginSettings');
			
			$params = array($pluginName);
			if ($conferenceId) $params[] = $conferenceId;

			return $this->update(
				'DELETE FROM plugin_settings WHERE plugin_name = ?' . (($conferenceId)?' AND conference_id = ?':''), 
				$params
			);
		}		
	}

	/**
	 * Delete all settings for a conference.
	 * @param $conferenceId int
	 */
	function deleteSettingsByConferenceId($conferenceId) {
		return $this->update(
				'DELETE FROM plugin_settings WHERE conference_id = ?', $conferenceId
		);
	}

	/**
	 * Used internally by installSettings to perform variable and translation replacements.
	 * @param $rawInput string contains text including variable and/or translate replacements.
	 * @param $paramArray array contains variables for replacement
	 * @returns string
	 */
	function _performReplacement($rawInput, $paramArray = array()) {
		$value = preg_replace_callback('{{translate key="([^"]+)"}}', '_installer_plugin_regexp_callback', $rawInput);
		foreach ($paramArray as $pKey => $pValue) {
			$value = str_replace('{$' . $pKey . '}', $pValue, $value);
		}
		return $value;
	}

	/**
	 * Used internally by installSettings to recursively build nested arrays.
	 * Deals with translation and variable replacement calls.
	 * @param $node object XMLNode <array> tag
	 * @param $paramArray array Parameters to be replaced in key/value contents
	 */
	function &_buildObject (&$node, $paramArray = array()) {
		$value = array();
		foreach ($node->getChildren() as $element) {
			$key = $element->getAttribute('key');
			$childArray =& $element->getChildByName('array');
			if (isset($childArray)) {
				$content = $this->_buildObject($childArray, $paramArray);
			} else {
				$content = $this->_performReplacement($element->getValue(), $paramArray);
			}
			if (!empty($key)) {
				$key = $this->_performReplacement($key, $paramArray);
				$value[$key] = $content;
			} else $value[] = $content;
		}
		return $value;
	}

	/**
	 * Install plugin settings from an XML file.
	 * @param $conferenceId int ID of conference, if applicable
	 * @param $schedConfId int ID of scheduled conference, if applicable
	 * @param $pluginName name of plugin for settings to apply to
	 * @param $filename string Name of XML file to parse and install
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 */
	function installSettings($conferenceId, $schedConfId, $pluginName, $filename, $paramArray = array()) {
		$xmlParser = new XMLParser();
		$tree = $xmlParser->parse($filename);

		if (!$tree) {
			$xmlParser->destroy();
			return false;
		}

		foreach ($tree->getChildren() as $setting) {
			$nameNode =& $setting->getChildByName('name');
			$valueNode =& $setting->getChildByName('value');

			if (isset($nameNode) && isset($valueNode)) {
				$type = $setting->getAttribute('type');
				$name =& $nameNode->getValue();

				if ($type == 'object') {
					$arrayNode =& $valueNode->getChildByName('array');
					$value = $this->_buildObject($arrayNode, $paramArray);
				} else {
					$value = $this->_performReplacement($valueNode->getValue(), $paramArray);
				}

				// Replace translate calls with translated content
				$this->updateSetting($conferenceId, $schedConfId, $pluginName, $name, $value, $type);
			}
		}

		$xmlParser->destroy();

	}
}

/**
 * Used internally by plugin setting installation code to perform translation function.
 */
function _installer_plugin_regexp_callback($matches) {
	return __($matches[1]);
}

?>
