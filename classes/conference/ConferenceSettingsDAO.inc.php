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
	/**
	 * Constructor
	 */
	function ConferenceSettingsDAO() {
		parent::SettingsDAO();
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

	/**
	 * Get the cache name.
	 */
	protected function _getCacheName() {
		return 'conferenceSettings';
	}
}

?>
