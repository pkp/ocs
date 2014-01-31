<?php

/**
 * @file CbeCitationPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CbeCitationPlugin
 * @ingroup plugins_citationFormats_cbe
 *
 * @brief CBE citation format plugin
 */

//$Id$

import('classes.plugins.CitationPlugin');

class CbeCitationPlugin extends CitationPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'CbeCitationPlugin';
	}

	function getDisplayName() {
		return __('plugins.citationFormats.cbe.displayName');
	}

	function getCitationFormatName() {
		return __('plugins.citationFormats.cbe.citationFormatName');
	}

	function getDescription() {
		return __('plugins.citationFormats.cbe.description');
	}

}

?>
