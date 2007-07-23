<?php

/**
 * @file GenericPlugin.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class GenericPlugin
 *
 * Abstract class for generic plugins
 *
 * $Id$
 */

import('plugins.Plugin');

class GenericPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function GenericPlugin() {
	}

	/**
	 * Called as a plugin is registered to the registry. Subclasses over-
	 * riding this method should call the parent method first.
	 * @param $category String Name of category plugin was registered to
	 * @param $path String The path the plugin was found in
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		return parent::register($category, $path);
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'GenericPlugin';
	}

	/**
	 * Get a description of this plugin.
	 */
	function getDescription() {
		return 'This is the base generic plugin class. It contains no concrete implementation. Its functions must be overridden by subclasses to provide actual functionality.';
	}
}

?>
