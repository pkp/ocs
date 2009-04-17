<?php

/**
 * @file classes/core/Handler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Handler
 * @ingroup core
 *
 * @brief Base request handler application class
 */


import('handler.PKPHandler');
import('handler.validation.HandlerValidatorConference');
import('handler.validation.HandlerValidatorSchedConf');
import('handler.validation.HandlerValidatorRoles');

class Handler extends PKPHandler {
	function Handler() {
		parent::PKPHandler();

		$conference =& Request::getConference();
		$page = Request::getRequestedPage();
		if ( $conference && $conference->getSetting('restrictSiteAccess')) { 
			$this->addCheck(new HandlerValidatorCustom(&$this, create_function('$page', 'if (!Validation::isLoggedIn() && !in_array($page, Handler::getLoginExemptions())) return false; else return true;'), array($page)));
		}
	}
}

?>
