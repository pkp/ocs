<?php

/**
 * @file controllers/grid/settings/schedconf/SchedConfGridRow.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfGridRow
 * @ingroup controllers_grid_settings_schedconf
 *
 * @brief SchedConf grid row definition
 */

import('lib.pkp.controllers.grid.admin.context.ContextGridRow');

class SchedConfGridRow extends ContextGridRow {
	/**
	 * Constructor
	 */
	function SchedConfGridRow() {
		parent::ContextGridRow();
	}


	//
	// Overridden methods from ContextGridRow
	//
	/**
	 * Get the delete context row locale key.
	 * @return string
	 */
	function getConfirmDeleteKey() {
		return 'manager.schedConfs.confirmDelete';
	}
}

?>
