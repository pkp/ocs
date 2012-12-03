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

import('lib.pkp.classes.db.SettingsDAO');

class SchedConfSettingsDAO extends SettingsDAO {
	function _getCache($schedConfId) {
		static $settingCache;

		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$schedConfId])) {
			$cacheManager = CacheManager::getManager();
			$settingCache[$schedConfId] = $cacheManager->getCache(
				'schedConfSettings', $schedConfId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$schedConfId];
	}

	/**
	 * Get the settings table name.
	 * @return string
	 */
	protected function _getTableName() {
		return 'sched_conf_settings';
	}

	/**
	 * Get the primary key column name.
	 */
	protected function _getPrimaryKeyColumn() {
		return 'sched_conf_id';
	}
}

?>
