<?php

/**
 * @file pages/gateway/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Handle gateway interaction requests. 
 *
 * @package pages.gateway
 *
 */

// $Id$


switch ($op) {
	case 'index':
	case 'plugin':
		define('HANDLER_CLASS', 'GatewayHandler');
		import('pages.gateway.GatewayHandler');
		break;
}

?>
