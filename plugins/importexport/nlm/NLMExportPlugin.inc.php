<?php

/**
 * @file NLMExportPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class NLMExportPlugin
 * @ingroup plugins_importexport_nlm
 * @see NLMExportDom
 *
 * @brief NLM XML metadata export plugin
 */

//$Id$

import('classes.plugins.ImportExportPlugin');

class NLMExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
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
		return 'NLMExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.nlm.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.nlm.description');
	}

	function display(&$args) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args);

		$conference =& Request::getConference();

		switch (array_shift($args)) {
			case 'exportPaper':
				$paperIds = array(array_shift($args));
//				$result = array_shift(PaperSearch::formatResults($paperIds));
//				$this->exportPaper($conference, $result['track'], $result['section'], $result['publishedPaper']);
				$result = PaperSearch::formatResults($paperIds);
				$this->exportPapers($result);
				break;
			case 'exportPapers':
				$paperIds = Request::getUserVar('paperId');
				if (!isset($paperIds)) $paperIds = array();
				else array_pop($paperIds);
				$results =& PaperSearch::formatResults($paperIds);
				$this->exportPapers($results);
				break;
			case 'papers':
				// Display a list of papers for export
				$this->setBreadcrumbs(array(), true);
				$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
				$rangeInfo = Handler::getRangeInfo('papers');
				$paperIds = $publishedPaperDao->getPublishedPaperIdsAlphabetizedBySchedConf($conference->getId());
				$totalPapers = count($paperIds);
				if ($rangeInfo->isValid()) $paperIds = array_slice($paperIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				import('core.VirtualArrayIterator');
				$iterator = new VirtualArrayIterator(PaperSearch::formatResults($paperIds), $totalPapers, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign_by_ref('papers', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'papers.tpl');
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportPapers(&$results, $outputFile = null) {
		$this->import('NLMExportDom');
		$doc =& NLMExportDom::generateNLMDom();
		$paperSetNode =& NLMExportDom::generatePaperSetDom($doc);

		foreach ($results as $result) {
			$conference =& $result['conference'];
			$track =& $result['track'];
			$paper =& $result['publishedPaper'];

			$paperNode =& NLMExportDom::generatePaperDom($doc, $conference, $track, $paper);
			XMLCustomWriter::appendChild($paperSetNode, $paperNode);
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"nlm.xml\"");
			XMLCustomWriter::printXML($doc);
//echo '<pre>'.htmlentities(preg_replace('/></', ">\n<", XMLCustomWriter::getXML($doc))).'</pre>';
		}
		return true;
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
//		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$conferencePath = array_shift($args);

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');

		$conference =& $conferenceDao->getConferenceByPath($conferencePath);

		if (!$conference) {
			if ($conferencePath != '') {
				echo __('plugins.importexport.nlm.cliError') . "\n";
				echo __('plugins.importexport.nlm.export.error.unknownConference', array('conferencePath' => $conferencePath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile != '') switch (array_shift($args)) {
			case 'papers':
				$results =& PaperSearch::formatResults($args);
				if (!$this->exportPapers($results, $xmlFile)) {
					echo __('plugins.importexport.nlm.cliError') . "\n";
					echo __('plugins.importexport.nlm.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
		}
		$this->usage($scriptName);

	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.nlm.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
