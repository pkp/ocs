<?php

/**
 * @file METSExportPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubMedExportPlugin
 * @ingroup plugins
 *
 * @brief METS/MODS XML metadata export plugin
 */

//$Id$

import('classes.plugins.ImportExportPlugin');

class METSExportPlugin extends ImportExportPlugin {
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
		return 'METSExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.METSExport.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.METSExport.description');
	}

	function display(&$args) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args);
		$conference =& Request::getConference();
		switch (array_shift($args)) {
			case 'exportschedConf':
				$conferenceDAO =& DAORegistry::getDAO('ConferenceDAO');
				$schedConfDAO =& DAORegistry::getDAO('SchedConfDAO');
				$schedConfId = array_shift($args);
				if ($schedConfId) {
					$schedConf =& $schedConfDAO->getSchedConf($schedConfId);
					$this->exportSchedConf($conference, $schedConf);
					return true;
				} else {
					$schedConfIds = Request::getUserVar('SchedConfId');
					$this->exportSchedConfs($conference, $schedConfIds);
					return true;
				}
				break;
			case 'schedConfs':
				// Display a list of Scheduled Conferences for export
				$this->setBreadcrumbs(array(), true);
				$templateMgr =& TemplateManager::getManager();

				$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
				$currentSchedConfs =& $schedConfDao->getCurrentSchedConfs($conference->getId());

				$siteDao =& DAORegistry::getDAO('SiteDAO');
				$site = $siteDao->getSite();
				$organization = $site->getLocalizedTitle();

				$templateMgr->assign_by_ref('organization', $organization);
				$templateMgr->assign_by_ref('schedConfs', $currentSchedConfs);
				$templateMgr->display($this->getTemplatePath().'schedConfs.tpl');
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportSchedConf(&$conference, &$schedConf) {
		$this->import('MetsExportDom');
		$doc =& XMLCustomWriter::createDocument();
		$root =& XMLCustomWriter::createElement($doc, 'METS:mets');
		XMLCustomWriter::setAttribute($root, 'xmlns:METS', 'http://www.loc.gov/METS/');
		XMLCustomWriter::setAttribute($root, 'xmlns:xlink', 'http://www.w3.org/TR/xlink');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($root, 'PROFILE', 'Australian METS Profile 1.0');
		XMLCustomWriter::setAttribute($root, 'TYPE', 'conference');
		XMLCustomWriter::setAttribute($root, 'OBJID', 'C-'.$conference->getId());
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', 'http://www.loc.gov/METS/ http://www.loc.gov/mets/mets.xsd');
		$headerNode =& MetsExportDom::createmetsHdr($doc);
		XMLCustomWriter::appendChild($root, $headerNode);
		MetsExportDom::generateConfDmdSecDom($doc, $root, $conference);
		MetsExportDom::generateSchedConfDmdSecDom($doc, $root, $conference, $schedConf);
		$amdSec =& MetsExportDom::createmetsamdSec($doc, $root, $conference);
		XMLCustomWriter::appendChild($root, $amdSec);
		$fileSec =& XMLCustomWriter::createElement($doc, 'METS:fileSec');
		$fileGrp =& XMLCustomWriter::createElement($doc, 'METS:fileGrp');
		XMLCustomWriter::setAttribute($fileGrp, 'USE', 'original');
		MetsExportDom::generateSchedConfFileSecDom($doc, $fileGrp, $conference, $schedConf);
		XMLCustomWriter::appendChild($fileSec, $fileGrp);
		XMLCustomWriter::appendChild($root, $fileSec);
		MetsExportDom::generateConfstructMapWithSchedConf($doc, $root, $conference, $schedConf);
		XMLCustomWriter::appendChild($doc, $root);
		header("Content-Type: application/xml");
		header("Cache-Control: private");
		header("Content-Disposition: attachment; filename=\"".$conference->getPath()."_".$schedConf->getPath()."-mets.xml\"");
		XMLCustomWriter::printXML($doc);
		return true;
	}

	function exportSchedConfs(&$conference, &$schedConfIdArray) {
		$this->import('MetsExportDom');
		$schedConfDAO =& DAORegistry::getDAO('SchedConfDAO');
		$doc =& XMLCustomWriter::createDocument();
		$root =& XMLCustomWriter::createElement($doc, 'METS:mets');
		XMLCustomWriter::setAttribute($root, 'xmlns:METS', 'http://www.loc.gov/METS/');
		XMLCustomWriter::setAttribute($root, 'xmlns:xlink', 'http://www.w3.org/TR/xlink');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($root, 'PROFILE', 'Australian METS Profile 1.0');
		XMLCustomWriter::setAttribute($root, 'TYPE', 'conference');
		XMLCustomWriter::setAttribute($root, 'OBJID', 'C-'.$conference->getId());
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', 'http://www.loc.gov/METS/ http://www.loc.gov/mets/mets.xsd');
		$headerNode =& MetsExportDom::createmetsHdr($doc);
		XMLCustomWriter::appendChild($root, $headerNode);
		MetsExportDom::generateConfDmdSecDom($doc, $root, $conference);
		$fileSec =& XMLCustomWriter::createElement($doc, 'METS:fileSec');
		$fileGrp =& XMLCustomWriter::createElement($doc, 'METS:fileGrp');
		XMLCustomWriter::setAttribute($fileGrp, 'USE', 'original');
		$i = 0;
		while ($i < sizeof($schedConfIdArray)) {
			$schedConf =& $schedConfDAO->getSchedConf($schedConfIdArray[$i]);
			MetsExportDom::generateSchedConfDmdSecDom($doc, $root, $conference, $schedConf);
			MetsExportDom::generateSchedConfFileSecDom($doc, $fileGrp, $conference, $schedConf);
			$i++;
		}
		$amdSec =& MetsExportDom::createmetsamdSec($doc, $root, $conference);
		XMLCustomWriter::appendChild($root, $amdSec);
		XMLCustomWriter::appendChild($fileSec, $fileGrp);
		XMLCustomWriter::appendChild($root, $fileSec);
		MetsExportDom::generateConfstructMapWithSchedConfsIdArray($doc, $root, $conference, $schedConfIdArray);
		XMLCustomWriter::appendChild($doc, $root);
		header("Content-Type: application/xml");
		header("Cache-Control: private");
		header("Content-Disposition: attachment; filename=\"".$conference->getPath()."-mets.xml\"");
		XMLCustomWriter::printXML($doc);
		return true;
	}
}

?>
