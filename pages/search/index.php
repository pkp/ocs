<?php

/**
 * @defgroup pages_search
 */
 
/**
 * @file pages/search/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle search requests.
 *
 * @ingroup pages_search
 */

//$Id$


switch ($op) {
	case 'index':
	case 'search':
	case 'advanced':
	case 'authors':
	case 'titles':
	case 'schedConfs':
	case 'results':
	case 'advancedResults':
		define('HANDLER_CLASS', 'SearchHandler');
		import('pages.search.SearchHandler');
}

?>
