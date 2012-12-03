<?php

/**
 * @file classes/conference/ConferenceSettingsDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSettingsDAO
 * @ingroup conference
 *
 * @brief Class for Conference Settings DAO.
 * Operations for retrieving and modifying conference settings.
 */

import('lib.pkp.classes.db.SettingsDAO');

class ConferenceSettingsDAO extends SettingsDAO {
	function &_getCache($conferenceId) {
		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$conferenceId])) {
			$cacheManager = CacheManager::getManager();
			$settingCache[$conferenceId] = $cacheManager->getCache(
				'conferenceSettings', $conferenceId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$conferenceId];
	}

	function _cacheMiss(&$cache, $id) {
		$settings = $this->getSettings($cache->getCacheId());
		if (!isset($settings[$id])) {
			// Make sure that even null values are cached
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Get the settings table name.
	 * @return string
	 */
	protected function _getTableName() {
		return 'conference_settings';
	}

	/**
	 * Get the primary key column name.
	 */
	protected function _getPrimaryKeyColumn() {
		return 'conference_id';
	}
}

?>
