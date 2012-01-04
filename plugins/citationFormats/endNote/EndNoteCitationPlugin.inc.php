<?php

/**
 * @file EndNoteCitationPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EndNoteCitationPlugin
 * @ingroup plugins_citationFormats_endNote
 *
 * @brief EndNote citation format plugin
 */

//$Id$

import('classes.plugins.CitationPlugin');

class EndNoteCitationPlugin extends CitationPlugin {
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
		return 'EndNoteCitationPlugin';
	}

	function getDisplayName() {
		return __('plugins.citationFormats.endNote.displayName');
	}

	function getCitationFormatName() {
		return __('plugins.citationFormats.endNote.citationFormatName');
	}

	function getDescription() {
		return __('plugins.citationFormats.endNote.description');
	}

	/**
	 * Display a custom-formatted citation.
	 * @param $paper object
	 * @param $conference object
	 * @param $schedConf object
	 */
	function displayCitation(&$paper, &$conference, &$schedConf) {
		header('Content-Disposition: attachment; filename="' . $paper->getId() . '-endNote.enw"');
		header('Content-Type: application/x-endnote-refer');
		echo parent::fetchCitation($paper, $conference, $schedConf);
	}
}

?>
