<?php

/**
 * @defgroup plugins_importexport_users
 */
 
/**
 * @file index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Wrapper for XML user import/export plugin.
 *
 * @ingroup plugins_importexport_users
 */

//$Id$

require_once('UserImportExportPlugin.inc.php');

return new UserImportExportPlugin();

?>
