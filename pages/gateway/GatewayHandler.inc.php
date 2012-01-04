<?php

/**
 * @file pages/gateway/GatewayHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GatewayHandler
 * @ingroup pages_gateway
 *
 * @brief Handle external gateway requests. 
 *
 */

//$Id$


import('handler.Handler');

class GatewayHandler extends Handler {
	/**
	 * Constructor
	 **/
	function GatewayHandler() {
		parent::Handler();
	}

	function index() {
		Request::redirect(null, 'index');
	}

	/**
	 * Handle requests for gateway plugins.
	 */
	function plugin($args) {
		$this->validate();
		$pluginName = array_shift($args);

		$plugins =& PluginRegistry::loadCategory('gateways');
		if (isset($pluginName) && isset($plugins[$pluginName])) {
			$plugin =& $plugins[$pluginName];
			if (!$plugin->fetch($args)) {
				Request::redirect(null, 'index');
			}
		}
		else Request::redirect(null, 'index');
	}
}

?>
