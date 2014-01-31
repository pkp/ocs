<?php

/**
 * @defgroup pages_oai
 */
 
/**
 * @file pages/oai/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle Open Archives Initiative protocol interaction requests.
 *
 * @ingroup pages_oai
 */

//$Id$


switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'OAIHandler');
		import('pages.oai.OAIHandler');
		break;
}

?>
