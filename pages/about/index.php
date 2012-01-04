<?php

/**
 * @defgroup pages_about
 */
 
/**
 * @file index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle requests for about the conference functions. 
 *
 * @ingroup pages_about
 */

//$Id$


switch($op) {
	case 'index':
	case 'contact':
	case 'organizingTeam':
	case 'organizingTeamBio':
	case 'editorialPolicies':
	case 'registration':
	case 'submissions':
	case 'siteMap':
	case 'aboutThisPublishingSystem':
	case 'statistics':
		define('HANDLER_CLASS', 'AboutHandler');
		import('pages.about.AboutHandler');
		break;
}

?>
