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
	/**
	 * Constructor
	 */
	function SchedConfSettingsDAO() {
		parent::SettingsDAO();
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

	/**
	 * Get the cache name.
	 */
	protected function _getCacheName() {
		return 'schedConfSettings';
	}
}

?>
