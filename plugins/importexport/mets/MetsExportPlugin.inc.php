<?php

/**
 * @file METSExportPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @package plugins
 * @class PubMedExportPlugin
 *
 * METS/MODS XML metadata export plugin
 *
 * $Id$
 */

import('classes.plugins.ImportExportPlugin');

class METSExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
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
		return Locale::translate('plugins.importexport.METSExport.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.importexport.METSExport.description');
	}

	function display(&$args) {
		$templateMgr = &TemplateManager::getManager();
		parent::display($args);
		$Conference = &Request::getConference();
		switch (array_shift($args)) {
			case 'exportschedConf':
				$ConferenceDAO = &DAORegistry::getDAO('ConferenceDAO');
				$SchedConfDAO = &DAORegistry::getDAO('SchedConfDAO');
				$SchedConfId = array_shift($args);
				if ($SchedConfId)
				{
					$schedConf = &$SchedConfDAO->getSchedConf($SchedConfId);
					$this->exportSchedConf($Conference, $schedConf);
					return true;
				}
				else
				{
					$SchedConfIds = Request::getUserVar('SchedConfId');
					$this->exportSchedConfs($Conference, $SchedConfIds);
					return true;
				}
				break;
			case 'schedConfs':
				// Display a list of Scheduled Conferences for export
				$this->setBreadcrumbs(array(), true);
				$templateMgr = &TemplateManager::getManager();

				$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
				$currentSchedConfs = &$schedConfDao->getCurrentSchedConfs($Conference->getConferenceId());

				$siteDao = &DAORegistry::getDAO('SiteDAO');
				$site = $siteDao->getSite();
				$organization = $site->getSiteTitle();

				$templateMgr->assign_by_ref('organization', $organization);
				$templateMgr->assign_by_ref('schedConfs', $currentSchedConfs);
				$templateMgr->display($this->getTemplatePath().'schedConfs.tpl');
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportSchedConf(&$Conference, &$schedConf)
	{
		$this->import('MetsExportDom');
		$doc = &XMLCustomWriter::createDocument('', null);
		$root = &XMLCustomWriter::createElement($doc, 'METS:mets');
		XMLCustomWriter::setAttribute($root, 'xmlns:METS', 'http://www.loc.gov/METS/');
		XMLCustomWriter::setAttribute($root, 'xmlns:xlink', 'http://www.w3.org/TR/xlink');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($root, 'PROFILE', 'Australian METS Profile 1.0');
		XMLCustomWriter::setAttribute($root, 'TYPE', 'conference');
		XMLCustomWriter::setAttribute($root, 'OBJID', 'C-'.$Conference->getConferenceId());
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', 'http://www.loc.gov/METS/ http://www.loc.gov/mets/mets.xsd');
		$HeaderNode = &MetsExportDom::createmetsHdr($doc);
		XMLCustomWriter::appendChild($root, $HeaderNode);
		MetsExportDom::generateConfDmdSecDom($doc, $root, $Conference);
		MetsExportDom::generateSchedConfDmdSecDom($doc, $root, $schedConf);
		$amdSec = &MetsExportDom::createmetsamdSec($doc, $root, $Conference);
		XMLCustomWriter::appendChild($root, $amdSec);
		$fileSec = &XMLCustomWriter::createElement($doc, 'METS:fileSec');
		$fileGrp = &XMLCustomWriter::createElement($doc, 'METS:fileGrp');
		XMLCustomWriter::setAttribute($fileGrp, 'USE', 'original');		
		MetsExportDom::generateSchedConfFileSecDom($doc, $fileGrp, $schedConf);
		XMLCustomWriter::appendChild($fileSec, $fileGrp);
		XMLCustomWriter::appendChild($root, $fileSec);
		MetsExportDom::generateConfstructMapWithSchedConf($doc, $root, $Conference, $schedConf);
		XMLCustomWriter::appendChild($doc, $root);
		header("Content-Type: application/xml");
		header("Cache-Control: private");
		header("Content-Disposition: attachment; filename=\"".$Conference->getPath()."_".$schedConf->getPath()."-mets.xml\"");
		XMLCustomWriter::printXML($doc);
		return true;
	}

	function exportSchedConfs(&$Conference, &$SchedConfIdArray) {
		$this->import('MetsExportDom');
		$SchedConfDAO = &DAORegistry::getDAO('SchedConfDAO');
		$doc = &XMLCustomWriter::createDocument('', null);
		$root = &XMLCustomWriter::createElement($doc, 'METS:mets');
		XMLCustomWriter::setAttribute($root, 'xmlns:METS', 'http://www.loc.gov/METS/');
		XMLCustomWriter::setAttribute($root, 'xmlns:xlink', 'http://www.w3.org/TR/xlink');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($root, 'PROFILE', 'Australian METS Profile 1.0');
		XMLCustomWriter::setAttribute($root, 'TYPE', 'conference');
		XMLCustomWriter::setAttribute($root, 'OBJID', 'C-'.$Conference->getConferenceId());
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', 'http://www.loc.gov/METS/ http://www.loc.gov/mets/mets.xsd');
		$HeaderNode = &MetsExportDom::createmetsHdr($doc);
		XMLCustomWriter::appendChild($root, $HeaderNode);
		MetsExportDom::generateConfDmdSecDom($doc, $root, $Conference);
		$fileSec = &XMLCustomWriter::createElement($doc, 'METS:fileSec');
		$fileGrp = &XMLCustomWriter::createElement($doc, 'METS:fileGrp');
		XMLCustomWriter::setAttribute($fileGrp, 'USE', 'original');	
		$i = 0;
		while ($i < sizeof($SchedConfIdArray)) {
			$schedConf = &$SchedConfDAO->getSchedConf($SchedConfIdArray[$i]);
			MetsExportDom::generateSchedConfDmdSecDom($doc, $root, $schedConf);
			MetsExportDom::generateSchedConfFileSecDom($doc, $fileGrp, $schedConf);
			$i++; 
		}
		$amdSec = &MetsExportDom::createmetsamdSec($doc, $root, $Conference);
		XMLCustomWriter::appendChild($root, $amdSec);
		XMLCustomWriter::appendChild($fileSec, $fileGrp);
		XMLCustomWriter::appendChild($root, $fileSec);
		MetsExportDom::generateConfstructMapWithSchedConfsIdArray($doc, $root, $Conference, $SchedConfIdArray);
		XMLCustomWriter::appendChild($doc, $root);
		header("Content-Type: application/xml");
		header("Cache-Control: private");
		header("Content-Disposition: attachment; filename=\"".$Conference->getPath()."-mets.xml\"");
		XMLCustomWriter::printXML($doc);
		return true;
	}
}

?>