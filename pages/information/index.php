<?php

/**
 * @defgroup pages_information
 */
 
/**
 * @file pages/information/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle information requests.
 *
 * @ingroup pages_information
 */

// $Id$


switch ($op) {
	case 'index':
	case 'readers':
	case 'authors':
		define('HANDLER_CLASS', 'InformationHandler');
		import('pages.information.InformationHandler');
		break;
}

?>
