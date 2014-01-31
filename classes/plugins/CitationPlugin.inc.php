<?php

/**
 * @file CitationPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
	function displayCitationHook($hookName, $args) {
		$params =& $args[0];
		$templateMgr =& $args[1];
		$output =& $args[2];

		$output .= $templateMgr->fetch($this->getTemplatePath() . '/citation.tpl');
		return true;
	}

	/**
	 * Display an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $paper object
	 * @param $conference object
	 * @param $schedConf object
	 */
	function displayCitation(&$paper, &$conference, &$schedConf) {
		HookRegistry::register('Template::RT::CaptureCite', array(&$this, 'displayCitationHook'));
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('citationPlugin', $this);
		$templateMgr->assign('paper', $paper);
		$templateMgr->assign('conference', $conference);
		$templateMgr->assign('schedConf', $schedConf);
		$templateMgr->display('rt/captureCite.tpl');
	}

	/**
	 * Return an HTML-formatted citation.
	 * @param $paper object
	 * @param $conference object
	 * @param $schedConf object
	 */
	function fetchCitation(&$paper, &$conference, &$schedConf) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('citationPlugin', $this);
		$templateMgr->assign('paper', $paper);
		$templateMgr->assign('conference', $conference);
		$templateMgr->assign('schedConf', $schedConf);
		return $templateMgr->fetch($this->getTemplatePath() . '/citation.tpl');
	}
}

?>
