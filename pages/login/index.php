<?php

/**
 * @defgroup pages_login
 */
 
/**
 * @file pages/login/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle login/logout requests.
 *
 * @ingroup pages_login
 */

//$Id$


switch ($op) {
	case 'signInAsUser':
	case 'signOutAsUser':
	case 'index':
	case 'implicitAuthLogin':
	case 'implicitAuthReturn':
	case 'signIn':
	case 'signOut':
	case 'lostPassword':
	case 'requestResetPassword':
	case 'resetPassword':
	case 'changePassword':
	case 'savePassword':
		define('HANDLER_CLASS', 'LoginHandler');
		import('pages.login.LoginHandler');
		break;
}

?>
