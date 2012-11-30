<?php

/**
 * @file controllers/grid/admin/conference/ConferenceGridRow.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceGridRow
 * @ingroup controllers_grid_admin_conference
 *
 * @brief Conference grid row definition
 */

import('lib.pkp.controllers.grid.admin.context.ContextGridRow');

class ConferenceGridRow extends ContextGridRow {
	/**
	 * Constructor
	 */
	function ConferenceGridRow() {
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
		return 'admin.conferences.confirmDelete';
	}
}

?>
