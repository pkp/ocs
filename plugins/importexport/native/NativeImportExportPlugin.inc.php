<?php

/**
 * @file NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPlugin
 * @ingroup plugins_importexport_native
 *
 * @brief Native import/export plugin
 */

//$Id$

import('classes.plugins.ImportExportPlugin');

import('xml.XMLCustomWriter');

define('NATIVE_DTD_URL', 'http://pkp.sfu.ca/ocs/dtds/native.dtd');
define('NATIVE_DTD_ID', '-//PKP//OCS Papers XML//EN');

class NativeImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
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
		return 'NativeImportExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.native.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.native.description');
	}

	function display(&$args) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args);

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		switch (array_shift($args)) {
			case 'exportPaper':
				$paperIds = array(array_shift($args));
				$result = array_shift(PaperSearch::formatResults($paperIds));
				$this->exportPaper($schedConf, $result['track'], $result['publishedPaper']);
				break;
			case 'exportPapers':
				$paperIds = Request::getUserVar('paperId');
				if (!isset($paperIds)) $paperIds = array();
				$results =& PaperSearch::formatResults($paperIds);
				$this->exportPapers($results);
				break;
			case 'papers':
				// Display a list of papers for export
				$this->setBreadcrumbs(array(), true);
				$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
				$rangeInfo = Handler::getRangeInfo('papers');
				$paperIds = $publishedPaperDao->getPublishedPaperIdsAlphabetizedBySchedConf($conference->getId(), $schedConf->getId());
				$totalPapers = count($paperIds);
				if ($rangeInfo->isValid()) $paperIds = array_slice($paperIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				import('core.VirtualArrayIterator');
				$iterator = new VirtualArrayIterator(PaperSearch::formatResults($paperIds), $totalPapers, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign_by_ref('papers', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'papers.tpl');
				break;
			case 'import':
				import('file.TemporaryFileManager');
				$trackDao =& DAORegistry::getDAO('TrackDAO');
				$user =& Request::getUser();
				$temporaryFileManager = new TemporaryFileManager();

				if (($existingFileId = Request::getUserVar('temporaryFileId'))) {
					// The user has just entered more context. Fetch an existing file.
					$temporaryFile = TemporaryFileManager::getFile($existingFileId, $user->getId());
				} else {
					$temporaryFile = $temporaryFileManager->handleUpload('importFile', $user->getId());
				}

				$context = array(
					'conference' => $conference,
					'schedConf' => $schedConf,
					'user' => $user
				);

				if (($trackId = Request::getUserVar('trackId'))) {
					$context['track'] = $trackDao->getTrack($trackId);
				}

 				if (!$temporaryFile) {
 					$templateMgr->assign('error', 'plugins.importexport.native.error.uploadFailed');
 					return $templateMgr->display($this->getTemplatePath() . 'importError.tpl');
 				}
 
				$doc =& $this->getDocument($temporaryFile->getFilePath());

				if (substr($this->getRootNodeName($doc), 0, 5) === 'paper') {
					// Ensure the user has supplied enough valid information to
					// import papers within an appropriate context. If not,
					// prompt them for the.
					if (!isset($context['track'])) {
						AppLocale::requireComponents(array(LOCALE_COMPONENT_OCS_AUTHOR));
						$templateMgr->assign('trackOptions', array('0' => __('author.submit.selectTrack')) + $trackDao->getTrackTitles($schedConf->getId(), false));
						$templateMgr->assign('temporaryFileId', $temporaryFile->getId());
						return $templateMgr->display($this->getTemplatePath() . 'paperContext.tpl');
					}
				}

				@set_time_limit(0);

				if ($this->handleImport($context, $doc, $errors, $papers, false)) {
					$templateMgr->assign_by_ref('papers', $papers);
					return $templateMgr->display($this->getTemplatePath() . 'importSuccess.tpl');
				} else {
					$templateMgr->assign_by_ref('errors', $errors);
					return $templateMgr->display($this->getTemplatePath() . 'importError.tpl');
				}
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportPaper(&$schedConf, &$track, &$paper, $outputFile = null) {
		$this->import('NativeExportDom');
		$doc =& XMLCustomWriter::createDocument('paper', NATIVE_DTD_ID, NATIVE_DTD_URL);
		$paperNode =& NativeExportDom::generatePaperDom($doc, $schedConf, $track, $paper);
		XMLCustomWriter::appendChild($doc, $paperNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"paper-" . $paper->getId() . ".xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function exportPapers(&$results, $outputFile = null) {
		$this->import('NativeExportDom');
		$doc =& XMLCustomWriter::createDocument('papers', NATIVE_DTD_ID, NATIVE_DTD_URL);
		$papersNode =& XMLCustomWriter::createElement($doc, 'papers');
		XMLCustomWriter::appendChild($doc, $papersNode);

		foreach ($results as $result) {
			$paper =& $result['publishedPaper'];
			$track =& $result['track'];
			$conference =& $result['conference'];
			$schedConf =& $result['schedConf'];
			$paperNode =& NativeExportDom::generatePaperDom($doc, $schedConf, $track, $paper);
			XMLCustomWriter::appendChild($papersNode, $paperNode);
		}

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"papers.xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function &getDocument($fileName) {
		$parser = new XMLParser();
		$returner =& $parser->parse($fileName);
		return $returner;
	}

	function getRootNodeName(&$doc) {
		return $doc->name;
	}

	function handleImport(&$context, &$doc, &$errors, &$papers, $isCommandLine) {
		$errors = array();
		$papers = array();

		$user =& $context['user'];
		$conference =& $context['conference'];
		$schedConf =& $context['schedConf'];

		$rootNodeName = $this->getRootNodeName($doc);

		$this->import('NativeImportDom');

		switch ($rootNodeName) {
			case 'papers':
				$track =& $context['track'];
				return NativeImportDom::importPapers($conference, $schedConf, $doc->children, $track, $papers, $errors, $user, $isCommandLine);
				break;
			case 'paper':
				$track =& $context['track'];
				$result = NativeImportDom::importPaper($conference, $schedConf, $doc, $track, $paper, $errors, $user, $isCommandLine);
				if ($result) $papers = array($paper);
				return $result;
				break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.unsupportedRoot', array('rootName' => $rootNodeName));
				return false;
				break;
		}
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */ 
	function executeCLI($scriptName, &$args) {
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$conferencePath = array_shift($args);
		$schedConfPath = array_shift($args);

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');

		$conference =& $conferenceDao->getConferenceByPath($conferencePath);
		if ($conference) $schedConf =& $schedConfDao->getSchedConfByPath($schedConfPath, $conference->getId());

		if (!$conference || !$schedConfPath) {
			if ($conferencePath != '') {
				echo __('plugins.importexport.native.cliError') . "\n";
				echo __('plugins.importexport.native.error.unknownConference', array('conferencePath' => $conferencePath, 'schedConfPath' => $schedConfPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		$this->import('NativeImportDom');
		if ($xmlFile && NativeImportDom::isRelativePath($xmlFile)) {
			$xmlFile = PWD . '/' . $xmlFile;
		}

		switch ($command) {
			case 'import':
				$userName = array_shift($args);
				$user =& $userDao->getUserByUsername($userName);

				if (!$user) {
					if ($userName != '') {
						echo __('plugins.importexport.native.cliError') . "\n";
						echo __('plugins.importexport.native.error.unknownUser', array('userName' => $userName)) . "\n\n";
					}
					$this->usage($scriptName);
					return;
				}

				$doc =& $this->getDocument($xmlFile);

				$context = array(
					'user' => $user,
					'conference' => $conference,
					'schedConf' => $schedConf
				);

				switch ($this->getRootNodeName($doc)) {
					case 'paper':
					case 'papers':
						// Determine the extra context information required
						// for importing papers.
						switch (array_shift($args)) {
							case 'track_id':
								$track =& $trackDao->getTrack(($trackIdentifier = array_shift($args)));
								break;
							case 'track_name':
								$track =& $trackDao->getTrackByTitle(($trackIdentifier = array_shift($args)), $schedConf->getId());
								break;
							case 'track_abbrev':
								$track =& $trackDao->getTrackByAbbrev(($trackIdentifier = array_shift($args)), $schedConf->getId());
								break;
							default:
								return $this->usage($scriptName);
						}

						if (!$track) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.trackNotFound', array('trackIdentifier' => $trackIdentifier)) . "\n\n";
							return;
						}
						$context['track'] =& $track;
				}

				$result = $this->handleImport($context, $doc, $errors, $papers, true);
				if ($result) {
					echo __('plugins.importexport.native.import.success.description') . "\n\n";
					if (!empty($papers)) echo __('paper.papers') . ":\n";
					foreach ($papers as $paper) {
						echo "\t" . $paper->getLocalizedTitle() . "\n";
					}
				} else {
					echo __('plugins.importexport.native.cliError') . "\n";
					foreach ($errors as $error) {
						echo "\t" . __($error[0], $error[1]) . "\n";
					}
				}
				return;
				break;
			case 'export':
				if ($xmlFile != '') switch (array_shift($args)) {
					case 'paper':
						$paperId = array_shift($args);
						$publishedPaper =& $publishedPaperDao->getPublishedPaperByBestPaperId($schedConf->getId(), $paperId);
						if ($publishedPaper == null) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.paperNotFound', array('paperId' => $paperId)) . "\n\n";
							return;
						}

						$trackDao =& DAORegistry::getDAO('TrackDAO');
						$track =& $trackDao->getTrack($publishedPaper->getTrackId());

						if (!$this->exportPaper($schedConf, $track, $publishedPaper, $xmlFile)) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
					case 'papers':
						$results =& PaperSearch::formatResults($args);
						if (!$this->exportPapers($results, $xmlFile)) {
							echo __('plugins.importexport.native.cliError') . "\n";
							echo __('plugins.importexport.native.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
						}
						return;
				}
				break;
		}
		$this->usage($scriptName);
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.native.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
