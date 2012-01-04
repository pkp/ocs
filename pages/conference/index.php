<?php

/**
 * @defgroup pages_conference
 */
 
/**
 * @file index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle conference index requests. 
 *
 * @ingroup pages_conference
 */

//$Id$


switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'ConferenceHandler');
		import('pages.conference.ConferenceHandler');
		break;
}

?>
