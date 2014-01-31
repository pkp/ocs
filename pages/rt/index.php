<?php

/**
 * @defgroup pages_rt
 */
 
/**
 * @file pages/rt/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle Reading Tools requests.
 *
 * @ingroup pages_rt
 */

//$Id$


switch ($op) {
	case 'bio':
	case 'metadata':
	case 'context':
	case 'captureCite':
	case 'printerFriendly':
	case 'emailColleague':
	case 'emailAuthor':
	case 'suppFiles':
	case 'suppFileMetadata':
	case 'findingReferences':
		define('HANDLER_CLASS', 'RTHandler');
		import('pages.rt.RTHandler');
		break;
}

?>
