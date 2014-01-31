<?php

/**
 * @defgroup pages_schedConfs
 */
 
/**
 * @file pages/schedConfs/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle conference index requests.
 *
 * @ingroup pages_schedConfs
 */

//$Id$


switch ($op) {
	case 'current':
	case 'archive':
		define('HANDLER_CLASS', 'SchedConfsHandler');
		import('pages.schedConfs.SchedConfsHandler');
		break;
}

?>
