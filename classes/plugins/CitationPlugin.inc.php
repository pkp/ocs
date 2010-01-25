<?php

/**
 * @file CitationPlugin.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CitationPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for citation plugins
 */

//$Id$

class CitationPlugin extends Plugin {
	function CitationPlugin() {
		parent::Plugin();
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		// This should not be used as this is an abstract class
		return 'CitationPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		// This name should never be displayed because child classes
		// will override this method.
		return 'Abstract Citation Plugin';
	}

	/**
	 * Get the citation format name for this plugin.
	 */
	function getCitationFormatName() {
		// Subclasses must override.
		fatalError('ABSTRACT METHOD');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return 'This is the CitationPlugin base class. Its functions can be overridden by subclasses to provide citation support.';
	}

	/**
	 * Used by the cite function to embed an HTML citation in the
	 * templates/rt/captureCite.tpl template, which ships with OCS.
	 */
	function displayCitation($hookName, $args) {
		$params =& $args[0];
		$templateMgr =& $args[1];
		$output =& $args[2];

		$output .= $templateMgr->fetch($this->getTemplatePath() . '/citation.tpl');
		return true;
	}

	/**
	 * Return an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $paper object
	 */
	function cite(&$paper) {
		HookRegistry::register('Template::RT::CaptureCite', array(&$this, 'displayCitation'));
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('citationPlugin', $this);
		$templateMgr->display('rt/captureCite.tpl');
	}
}
?>
