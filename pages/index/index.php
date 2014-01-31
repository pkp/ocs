<?php

/**
 * @defgroup pages_index
 */
 
/**
 * @file pages/index/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle site index requests.
 *
 * @ingroup pages_index
 */

//$Id$


switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'IndexHandler');
		import('pages.index.IndexHandler');
		break;
}

?>
