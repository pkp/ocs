<?php

/**
 * @file RefManCitationPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RefManCitationPlugin
 * @ingroup plugins_citationFormats_refMan
 *
 * @brief Reference Manager citation format plugin
 */

//$Id$

import('classes.plugins.CitationPlugin');

class RefManCitationPlugin extends CitationPlugin {
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
		return 'RefManCitationPlugin';
	}

	function getDisplayName() {
		return __('plugins.citationFormats.refMan.displayName');
	}

	function getCitationFormatName() {
		return __('plugins.citationFormats.refMan.citationFormatName');
	}

	function getDescription() {
		return __('plugins.citationFormats.refMan.description');
	}

	/**
	 * Display a custom-formatted citation.
	 * @param $paper object
	 * @param $conference object
	 * @param $schedConf object
	 */
	function displayCitation(&$paper, $conference, $schedConf) {
		header('Content-Disposition: attachment; filename="' . $paper->getId() . '-refMan.ris"');
		header('Content-Type: application/x-Research-Info-Systems');
		echo parent::fetchCitation($paper, $conference, $schedConf);
	}
}

?>
