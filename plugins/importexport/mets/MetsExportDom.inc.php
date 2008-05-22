<?php

/**
 * MetsExportDom.inc.php
 *
 * Copyright (c) 2003-2005 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package GatewayPlugin
 *
 * MetsExportDom export plugin DOM functions for export
 *
 * $Id$
 */

import('xml.XMLCustomWriter');

class MetsExportDom {

	/**
	 *  creates the METS:structMap element for a conference with multiple Scheduled Conferences
	 */
	function generateConfstructMapWithSchedConfs(&$doc, &$root, &$conference, &$schedConfs) {
		$structMap = &XMLCustomWriter::createElement($doc, 'METS:structMap');
		XMLCustomWriter::setAttribute($structMap, 'TYPE', 'logical');
		$cDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($cDiv, 'TYPE', 'series');
		XMLCustomWriter::setAttribute($cDiv, 'DMDID', 'CS-'.$conference->getConferenceId());
		XMLCustomWriter::setAttribute($cDiv, 'ADMID', 'A-'.$conference->getConferenceId());
		foreach ($schedConfs as $schedConf) {
			MetsExportDom::generateSchedConfDiv($doc, $cDiv, $schedConf);
		}
		XMLCustomWriter::appendChild($structMap, $cDiv);
		XMLCustomWriter::appendChild($root, $structMap);
	}

	/**
	 *  creates the METS:structMap element for a conference with multiple Scheduled Conferences referenced by their IDs
	 */
	function generateConfstructMapWithSchedConfsIdArray(&$doc, &$root, &$conference, &$schedConfIdArray) {
		$SchedConfDAO = &DAORegistry::getDAO('SchedConfDAO');
		$structMap = &XMLCustomWriter::createElement($doc, 'METS:structMap');
		XMLCustomWriter::setAttribute($structMap, 'TYPE', 'logical');
		$cDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($cDiv, 'TYPE', 'series');
		XMLCustomWriter::setAttribute($cDiv, 'DMDID', 'CS-'.$conference->getConferenceId());
		XMLCustomWriter::setAttribute($cDiv, 'ADMID', 'A-'.$conference->getConferenceId());
		$i = 0;
		while ($i < sizeof($schedConfIdArray)) {
			$schedConf = &$SchedConfDAO->getSchedConf($schedConfIdArray[$i]);
			MetsExportDom::generateSchedConfDiv($doc, $cDiv, $schedConf);
			$i++; 
		}
		XMLCustomWriter::appendChild($structMap, $cDiv);
		XMLCustomWriter::appendChild($root, $structMap);
	}

	/**
	 *  creates the METS:structMap element for a conference with singe Scheduled Conference
	 */
	function generateConfstructMapWithSchedConf(&$doc, &$root, &$conference, &$schedConf) {
		$structMap = &XMLCustomWriter::createElement($doc, 'METS:structMap');
		XMLCustomWriter::setAttribute($structMap, 'TYPE', 'logical');
		$cDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($cDiv, 'TYPE', 'series');
		XMLCustomWriter::setAttribute($cDiv, 'DMDID', 'CS-'.$conference->getConferenceId());
		XMLCustomWriter::setAttribute($cDiv, 'ADMID', 'A-'.$conference->getConferenceId());
		MetsExportDom::generateSchedConfDiv($doc, $cDiv, $schedConf);
		XMLCustomWriter::appendChild($structMap, $cDiv);
		XMLCustomWriter::appendChild($root, $structMap);
	}

	/**
	 *  creates the METS:div element for a Scheduled Conferences
	 */
	function generateSchedConfDiv(&$doc, &$root, &$schedConf) {
		$sDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($sDiv, 'TYPE', 'conference');
		XMLCustomWriter::setAttribute($sDiv, 'DMDID', 'SCHC-'.$schedConf->getSchedConfId());
		MetsExportDom::generateOverViewDiv($doc, $sDiv, $schedConf);
		$PublishedPaperDAO = &DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPapersIterator =& $PublishedPaperDAO->getPublishedPapers($schedConf->getSchedConfId());
		$PublishedPaperArray =& $publishedPapersIterator->toArray();

		$i = 0;
		while ($i < sizeof($PublishedPaperArray)) {
		  MetsExportDom::generatePublishedPaperDiv($doc, $sDiv, $PublishedPaperArray[$i], $schedConf);
		  $i++; 
		}
		XMLCustomWriter::appendChild($root, $sDiv);
	}
	
	/**
	 *  creates the METS:div element for the Over View of the Scheduled Conference
	 */
	function generateOverViewDiv(&$doc, &$root, &$schedConf) {
		$SchedConfSettingsDAO = &DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfOverview = $SchedConfSettingsDAO->getSetting($schedConf->getSchedConfId(), 'schedConfOverview');
		$schedConfIntroduction = $SchedConfSettingsDAO->getSetting($schedConf->getSchedConfId(), 'schedConfIntroduction');
		if($schedConfOverview != '' || $schedConfIntroduction != ''){
			$sDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
			XMLCustomWriter::setAttribute($sDiv, 'TYPE', 'overview');
			XMLCustomWriter::setAttribute($sDiv, 'DMDID', 'OV-'.$schedConf->getSchedConfId());
			XMLCustomWriter::appendChild($root, $sDiv);
		}
	}
	
	/**
	 *  creates the METS:div element for a submission
	 */
	function generatePublishedPaperDiv(&$doc, &$root, &$paper, &$schedConf) {
		$pDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($pDiv, 'TYPE', 'submission');
		XMLCustomWriter::setAttribute($pDiv, 'DMDID', 'P'.$paper->getPaperId());
		$PaperGalleyDAO = &DAORegistry::getDAO('PaperGalleyDAO');
		$i = 0;
		$GalleysArray = &$PaperGalleyDAO->getGalleysByPaper($paper->getPaperId());
		while ($i < sizeof($GalleysArray)) {
			MetsExportDom::generatePaperFileDiv($doc, $pDiv,  $GalleysArray[$i]);
			$i++; 
		}
		$exportSuppFiles = &Request::getUserVar('exportSuppFiles');
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$conferenceRt = &$rtDao->getConferenceRTByConference($schedConf->getConference());
		if($exportSuppFiles == 'on' || $conferenceRt->getEnabled()) {
			$SuppFileDAO = &DAORegistry::getDAO('SuppFileDAO');
			$paperFilesArray = &$SuppFileDAO->getSuppFilesByPaper($paper->getPaperId());
			$i = 0;
			while ($i < sizeof($paperFilesArray)) {
				MetsExportDom::generatePaperSuppFilesDiv($doc, $pDiv, $paperFilesArray[$i]);
				$i++; 
			}
		}
		XMLCustomWriter::appendChild($root, $pDiv);
	}

	/**
	 *  creates the METS:fptr element for a PaperGalley
	 */
	function generatePaperFileDiv(&$doc, &$root, $PaperFile) {
		$fDiv = &XMLCustomWriter::createElement($doc, 'METS:fptr');
		XMLCustomWriter::setAttribute($fDiv, 'FILEID', 'F'.$PaperFile->getFileId().'-P'.$PaperFile->getPaperId());
		XMLCustomWriter::appendChild($root, $fDiv);
	}

	/**
	 *  creates the METS:dmdSec element for the Conference
	 */
	function generateConfDmdSecDom(&$doc, $root, &$conference) {
		$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'CS-'.$conference->getConferenceId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $conference->getConferenceTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'series');
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
	}

	/**
	 *  creates the METS:dmdSec element for a Sheduled Conference
	 */
	function generateSchedConfDmdSecDom(&$doc, &$root, &$schedConf) {
		$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'SCHC-'.$schedConf->getSchedConfId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $schedConf->getSchedConfTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'conference');
		if($schedConf->getStartDate() != '' || $schedConf->getEndDate() != ''){
			$originInfo = &XMLCustomWriter::createElement($doc, 'mods:originInfo');
			if($schedConf->getStartDate() != ''){
				$sDate = XMLCustomWriter::createChildWithText($doc, $originInfo, 'mods:dateOther', $schedConf->getStartDate());
				XMLCustomWriter::setAttribute($sDate, 'point', 'start');
			}
			if($schedConf->getEndDate() != ''){
				$sDate = XMLCustomWriter::createChildWithText($doc, $originInfo, 'mods:dateOther', $schedConf->getEndDate());
				XMLCustomWriter::setAttribute($sDate, 'point', 'end');
			}
			 XMLCustomWriter::appendChild($mods, $originInfo);
		}
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
		MetsExportDom::generateOverViewDmdSecDom($doc, $root, $schedConf);
		$PublishedPaperDAO = &DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPapersIterator =& $PublishedPaperDAO->getPublishedPapers($schedConf->getSchedConfId());
		$PublishedPaperArray =& $publishedPapersIterator->toArray();

		$i = 0;
		while ($i < sizeof($PublishedPaperArray)) {
			MetsExportDom::generatePublishedPaperDmdSecDom($doc, $root, $PublishedPaperArray[$i], $schedConf);
			$i++; 
		}
	}

	/**
	 *  creates the METS:dmdSec element for the OverView if schedConfOverview or schedConfIntroduction present
	 */
	function generateOverViewDmdSecDom(&$doc, $root, &$schedConf) {
		$SchedConfSettingsDAO = &DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfOverview = $SchedConfSettingsDAO->getSetting($schedConf->getSchedConfId(), 'schedConfOverview');
		$schedConfIntroduction = $SchedConfSettingsDAO->getSetting($schedConf->getSchedConfId(), 'schedConfIntroduction');
		if($schedConfOverview != '' || $schedConfIntroduction != ''){
			$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
			XMLCustomWriter::setAttribute($dmdSec, 'ID', 'OV-'.$schedConf->getSchedConfId());
			$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
			$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
			XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
			$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
			XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
			XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
			if($schedConfOverview != ''){
				$overviewNode = XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $schedConfOverview);
				XMLCustomWriter::setAttribute($overviewNode, 'type', 'overview');
			}
			if($schedConfIntroduction != ''){
				$introNode = XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $schedConfIntroduction);
				XMLCustomWriter::setAttribute($introNode, 'type', 'introduction');
			}
			XMLCustomWriter::appendChild($xmlData, $mods);
			XMLCustomWriter::appendChild($dmdSec, $mdWrap);
			XMLCustomWriter::appendChild($mdWrap,$xmlData);
			XMLCustomWriter::appendChild($root, $dmdSec);
		}
	}

	/**
	 *  creates the METS:dmdSec element for a published Paper
	 */
	function generatePublishedPaperDmdSecDom(&$doc, &$root, &$Paper , &$schedConf) {
		if($Paper->getStatus() == 3){
			$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
			XMLCustomWriter::setAttribute($dmdSec, 'ID', 'P'.$Paper->getPaperId());
			$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
			$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
			XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
			$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
			XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
			XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
			$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
			XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $Paper->getPaperTitle());
			XMLCustomWriter::appendChild($mods, $titleInfo);
			if($Paper->getTitleAlt1() != ''){
				$titleInfoAlt1 = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
				XMLCustomWriter::createChildWithText($doc, $titleInfoAlt1, 'mods:title', $Paper->getTitleAlt1());
				XMLCustomWriter::setAttribute($titleInfoAlt1, 'type', 'alternative');
				XMLCustomWriter::appendChild($mods, $titleInfoAlt1);
			}
			if($Paper->getTitleAlt2() != ''){
				$titleInfoAlt2 = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
				XMLCustomWriter::createChildWithText($doc, $titleInfoAlt2, 'mods:title', $Paper->getTitleAlt2());
				XMLCustomWriter::setAttribute($titleInfoAlt2, 'type', 'alternative');
				XMLCustomWriter::appendChild($mods, $titleInfoAlt2);
			}
			if($Paper->getPaperAbstract() != '')
				XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $Paper->getPaperAbstract());
			if($Paper->getAbstractAlt1() != '')
				XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $Paper->getAbstractAlt1());
			if($Paper->getAbstractAlt2() != '')
				XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $Paper->getAbstractAlt2());

			MetsExportDom::generatePresentersDom($doc, $mods, $Paper->getPaperId());
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'submission');
			if($Paper->getDatePublished() != ''){
 				$originInfo = &XMLCustomWriter::createElement($doc, 'mods:originInfo');
				$sDate = XMLCustomWriter::createChildWithText($doc, $originInfo, 'mods:dateIssued', $Paper->getDatePublished());
			 	XMLCustomWriter::appendChild($mods, $originInfo);
			}
			XMLCustomWriter::appendChild($xmlData, $mods);
			XMLCustomWriter::appendChild($dmdSec, $mdWrap);
			XMLCustomWriter::appendChild($mdWrap,$xmlData);
			XMLCustomWriter::appendChild($root, $dmdSec);
			$exportSuppFiles = &Request::getUserVar('exportSuppFiles');
			$rtDao = &DAORegistry::getDAO('RTDAO');
			$conferenceRt = &$rtDao->getConferenceRTByConference($schedConf->getConference());
			if($exportSuppFiles == 'on' || $conferenceRt->getEnabled()) {
				$SuppFileDAO = &DAORegistry::getDAO('SuppFileDAO');
				$paperFilesArray = &$SuppFileDAO->getSuppFilesByPaper($Paper->getPaperId());
				$i = 0;
				while ($i < sizeof($paperFilesArray)) {
					MetsExportDom::generatePaperSuppFilesDmdSecDom($doc, $root, $paperFilesArray[$i]);
					$i++; 
				}
			}
		}
	}

	/**
	 *  creates the METS:dmdSec element for Supplementary Files
	 */
	function generatePaperSuppFilesDmdSecDom(&$doc, &$root, $PaperFile) {
		$dmdSec = &XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'DMD-SF'.$PaperFile->getFileId().'-P'.$PaperFile->getPaperId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods = &XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo = &XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $PaperFile->getTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);
		if($PaperFile->getCreator() != ''){
			$creatorNode = &XMLCustomWriter::createElement($doc, 'mods:name');
			XMLCustomWriter::setAttribute($creatorNode, 'type', 'personal');
			$nameNode =&XMLCustomWriter::createChildWithText($doc, $creatorNode, 'mods:namePart', $PaperFile->getCreator());
			$role = &XMLCustomWriter::createElement($doc, 'mods:role');
			$roleTerm =&XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'creator');
			XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
			XMLCustomWriter::appendChild($creatorNode, $role);
			XMLCustomWriter::appendChild($mods, $creatorNode);
		}
		if($PaperFile->getDescription() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $PaperFile->getDescription());
		if($PaperFile->getDateCreated() != ''){
 			$originInfo = &XMLCustomWriter::createElement($doc, 'mods:originInfo');
			$sDate = XMLCustomWriter::createChildWithText($doc, $originInfo, 'mods:dateCreated', $PaperFile->getDateCreated());
		 	XMLCustomWriter::appendChild($mods, $originInfo);
		}
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'additional material');
		if($PaperFile->getType() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', $PaperFile->getType());
		if($PaperFile->getTypeOther() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', $PaperFile->getTypeOther());
		if($PaperFile->getSubject() != ''){
			$subjNode = &XMLCustomWriter::createElement($doc, 'mods:subject');
			XMLCustomWriter::createChildWithText($doc, $subjNode, 'mods:topic', $PaperFile->getSubject());
			XMLCustomWriter::appendChild($mods, $subjNode);
		}
		if($PaperFile->getLanguage() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:language', $PaperFile->getLanguage());
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
	}

	/**
	 *  creates the METS:div @TYPE=additional_material for the Supp Files
	 */
	function generatePaperSuppFilesDiv(&$doc, &$root, $SuppFile) {
		$sDiv = &XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($sDiv, 'TYPE', 'additional_material');
		XMLCustomWriter::setAttribute($sDiv, 'DMDID', 'DMD-SF'.$SuppFile->getFileId().'-P'.$SuppFile->getPaperId());
		$fDiv = &XMLCustomWriter::createElement($doc, 'METS:fptr');
		XMLCustomWriter::setAttribute($fDiv, 'FILEID', 'SF'.$SuppFile->getFileId().'-P'.$SuppFile->getPaperId());
		XMLCustomWriter::appendChild($sDiv, $fDiv);
		XMLCustomWriter::appendChild($root, $sDiv);
	}

	/**
	 *  finds all files associated with this Scheduled Conference by going through all published Papers
	 *
	*/  
	function generateSchedConfFileSecDom(&$doc, &$root, &$schedConf) {
		$PublishedPaperDAO = &DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPapersIterator =& $PublishedPaperDAO->getPublishedPapers($schedConf->getSchedConfId());
		$PublishedPaperArray =& $publishedPapersIterator->toArray();

		$i = 0;
		while ($i < sizeof($PublishedPaperArray)) {
			MetsExportDom::generatePaperFilesDom($doc, $root, $PublishedPaperArray[$i], $schedConf);
			$i++; 
		}
	}
	
	/**
	 *  finds all files associated with this published Papers and if exportSuppFiles == 'on' in the Config file 
	*/
	function generatePaperFilesDom(&$doc, $root, $paper, &$schedConf) {
		$PaperGalleyDAO = &DAORegistry::getDAO('PaperGalleyDAO');
		$i = 0;
		$GalleysArray = &$PaperGalleyDAO->getGalleysByPaper($paper->getPaperId());
		while ($i < sizeof($GalleysArray)) {
			MetsExportDom::generatePaperFileDom($doc, $root, $paper, $GalleysArray[$i]);
			$i++; 
		}
		$exportSuppFiles = &Request::getUserVar('exportSuppFiles');
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$conferenceRt = &$rtDao->getConferenceRTByConference($schedConf->getConference());
		if($exportSuppFiles == 'on' || $conferenceRt->getEnabled()) {
			$SuppFileDAO = &DAORegistry::getDAO('SuppFileDAO');
			$paperFilesArray = &$SuppFileDAO->getSuppFilesByPaper($paper->getPaperId());
			$i = 0;
			while ($i < sizeof($paperFilesArray)) {
				MetsExportDom::generatePaperSuppFileDom($doc, $root, $paper, $paperFilesArray[$i]);
				$i++; 
			}
		}
	}

	/**
	 *  Creates a METS:file for the paperfile; checks if METS:FContent or METS:FLocat should be used
	*/
	function generatePaperFileDom(&$doc, &$root, $paper, $PaperFile) {
		import('classes.file.PublicFileManager');
		import('classes.file.FileManager');
		$contentWrapper = &Request::getUserVar('contentWrapper');
		$mfile = &XMLCustomWriter::createElement($doc, 'METS:file');
		$filePath  = MetsExportDom::getPublicFilePath($PaperFile , '/public/');
		$chkmd5return = md5_file($filePath);
		XMLCustomWriter::setAttribute($mfile, 'ID', 'F'.$PaperFile->getFileId().'-P'.$PaperFile->getPaperId());
		XMLCustomWriter::setAttribute($mfile, 'SIZE', $PaperFile->getFileSize());
		XMLCustomWriter::setAttribute($mfile, 'MIMETYPE', $PaperFile->getFileType());
		XMLCustomWriter::setAttribute($mfile, 'OWNERID', $PaperFile->getFileName());
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUM', $chkmd5return);
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUMTYPE', 'MD5');
		if($contentWrapper == 'FContent'){
			$FileContent = &FileManager::readFile($filePath);
			$FContent = &XMLCustomWriter::createElement($doc, 'METS:FContent');
			$fNameNode =&XMLCustomWriter::createChildWithText($doc, $FContent, 'METS:binData',base64_encode($FileContent));
			XMLCustomWriter::appendChild($mfile, $FContent);
		}
		else{
			$fLocat = &XMLCustomWriter::createElement($doc, 'METS:FLocat');
			$fileUrl = MetsExportDom::getPublicFileUrl($PaperFile);
			XMLCustomWriter::setAttribute($fLocat, 'xlink:href', $fileUrl);
			XMLCustomWriter::setAttribute($fLocat, 'LOCTYPE', 'URL');
			XMLCustomWriter::appendChild($mfile, $fLocat);
		}
		XMLCustomWriter::appendChild($root, $mfile);
	}

	/**
	 *  Creates a METS:file for the Supplementary File; checks if METS:FContent or METS:FLocat should be used
	*/
	function generatePaperSuppFileDom(&$doc, &$root, $paper, $PaperFile) {
		import('classes.file.PublicFileManager');
		import('classes.file.FileManager');
		$contentWrapper = &Request::getUserVar('contentWrapper');
		$mfile = &XMLCustomWriter::createElement($doc, 'METS:file');
		$filePath  = MetsExportDom::getPublicFilePath($PaperFile , '/supp/');;
		$chkmd5return = md5_file($filePath);
		XMLCustomWriter::setAttribute($mfile, 'ID', 'SF'.$PaperFile->getFileId().'-P'.$PaperFile->getPaperId());
		XMLCustomWriter::setAttribute($mfile, 'SIZE', $PaperFile->getFileSize());
		XMLCustomWriter::setAttribute($mfile, 'MIMETYPE', $PaperFile->getFileType());
		XMLCustomWriter::setAttribute($mfile, 'OWNERID', $PaperFile->getFileName());
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUM', $chkmd5return);
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUMTYPE', 'MD5');
		if($contentWrapper == 'FContent'){
			$FileContent = &FileManager::readFile($filePath);
			$FContent = &XMLCustomWriter::createElement($doc, 'METS:FContent');
			$fNameNode =&XMLCustomWriter::createChildWithText($doc, $FContent, 'METS:binData',base64_encode($FileContent));
			XMLCustomWriter::appendChild($mfile, $FContent);
		}
		else{
			$fLocat = &XMLCustomWriter::createElement($doc, 'METS:FLocat');
			$fileUrl = MetsExportDom::getPublicSuppFileUrl($PaperFile);
			XMLCustomWriter::setAttribute($fLocat, 'xlink:href', $fileUrl);
			XMLCustomWriter::setAttribute($fLocat, 'LOCTYPE', 'URL');
			XMLCustomWriter::appendChild($mfile, $fLocat);
		}
		XMLCustomWriter::appendChild($root, $mfile);
	}

	/**
	 *  Process All presenters for the Given Paper
	*/
	function generatePresentersDom(&$doc, &$root, $paperID) {
		$PresenterDAO = &DAORegistry::getDAO('PresenterDAO');
		$i = 0;
		$PresentersArray = &$PresenterDAO->getPresentersByPaper($paperID);
		while ($i < sizeof($PresentersArray)) {
			$PresenterNode =  &MetsExportDom::generatePresenterDom($doc, $PresentersArray[$i]);
			XMLCustomWriter::appendChild($root, $PresenterNode);
			$i++; 
		}
	}

	/**
	 *  Create mods:name for a presenter
	*/	
	function &generatePresenterDom(&$doc, $Presenter) {
		$PresenterNode = &XMLCustomWriter::createElement($doc, 'mods:name');
		XMLCustomWriter::setAttribute($PresenterNode, 'type', 'personal');
		$fNameNode =&XMLCustomWriter::createChildWithText($doc, $PresenterNode, 'mods:namePart', $Presenter->getFirstName().' '.$Presenter->getMiddleName());
		XMLCustomWriter::setAttribute($fNameNode, 'type', 'given');
		$lNameNode =&XMLCustomWriter::createChildWithText($doc, $PresenterNode, 'mods:namePart', $Presenter->getLastName());
		XMLCustomWriter::setAttribute($lNameNode, 'type', 'family');
		$role = &XMLCustomWriter::createElement($doc, 'mods:role');
		$roleTerm =&XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'author');
		XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
		XMLCustomWriter::appendChild($PresenterNode, $role);
		return $PresenterNode;
	}

	/**
	 *  Create METS:amdSec for the Conference
	*/
	function createmetsamdSec($doc, &$root, &$conference) {
		$amdSec = &XMLCustomWriter::createElement($doc, 'METS:amdSec');
		$techMD = &XMLCustomWriter::createElement($doc, 'METS:techMD');
		XMLCustomWriter::setAttribute($techMD, 'ID', 'A-'.$conference->getConferenceId());
		$mdWrap = &XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'PREMIS');
		$xmlData = &XMLCustomWriter::createElement($doc, 'METS:xmlData');
		$pObject = &XMLCustomWriter::createElement($doc, 'premis:object');
		XMLCustomWriter::setAttribute($pObject, 'xmlns:premis', 'http://www.loc.gov/standards/premis/v1');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/standards/premis/v1 http://www.loc.gov/standards/premis/v1/PREMIS-v1-1.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/standards/premis/v1 http://www.loc.gov/standards/premis/v1/PREMIS-v1-1.xsd');
		$objectIdentifier = &XMLCustomWriter::createElement($doc, 'premis:objectIdentifier');
		XMLCustomWriter::createChildWithText($doc, $objectIdentifier, 'premis:objectIdentifierType', 'internal');
		XMLCustomWriter::createChildWithText($doc, $objectIdentifier, 'premis:objectIdentifierValue', 'C-'.$conference->getConferenceId());
		XMLCustomWriter::appendChild($pObject, $objectIdentifier);
		$preservationLevel = &Request::getUserVar('preservationLevel');
		if($preservationLevel == ''){
			$preservationLevel = '1';
		}
		XMLCustomWriter::createChildWithText($doc, $pObject, 'premis:preservationLevel', 'level '.$preservationLevel);
 		XMLCustomWriter::createChildWithText($doc, $pObject, 'premis:objectCategory', 'Representation');

		XMLCustomWriter::appendChild($xmlData, $pObject);
		XMLCustomWriter::appendChild($mdWrap, $xmlData);
		XMLCustomWriter::appendChild($techMD ,$mdWrap);
		XMLCustomWriter::appendChild($amdSec, $techMD);		
		return $amdSec;
	}

	/**
	 *  Create METS:metsHdr for export
	*/
	function createmetsHdr($doc) {
		$root = &XMLCustomWriter::createElement($doc, 'METS:metsHdr');
		XMLCustomWriter::setAttribute($root, 'CREATEDATE', date('c'));
		XMLCustomWriter::setAttribute($root, 'LASTMODDATE', date('c'));
		$agentNode = &XMLCustomWriter::createElement($doc, 'METS:agent');
		XMLCustomWriter::setAttribute($agentNode, 'ROLE', 'DISSEMINATOR');
		XMLCustomWriter::setAttribute($agentNode, 'TYPE', 'ORGANIZATION');
		$organization = &Request::getUserVar('organization');
		if($organization == ''){
		  $siteDao = &DAORegistry::getDAO('SiteDAO');
		  $site = $siteDao->getSite();
		  $organization = $site->getSiteTitle();
		}
		XMLCustomWriter::createChildWithText($doc, $agentNode, 'METS:name', $organization, false);
		XMLCustomWriter::appendChild($root, $agentNode);
		$agentNode2 = &XMLCustomWriter::createElement($doc, 'METS:agent');
		XMLCustomWriter::setAttribute($agentNode2, 'ROLE', 'CREATOR');
		XMLCustomWriter::setAttribute($agentNode2, 'TYPE', 'OTHER');
		XMLCustomWriter::createChildWithText($doc, $agentNode2, 'METS:name', MetsExportDom::getCreatorString(), false);
		XMLCustomWriter::appendChild($root, $agentNode2);
		return $root;
	}

	/**
	 *  Creator is the OCS Sysytem
	*/
	function getCreatorString() {
		$versionDAO = &DAORegistry::getDAO('VersionDAO');
		$cVersion = $versionDAO->getCurrentVersion();
		return sprintf('Open Conference Systems v%d.%d.%d build %d', $cVersion->getMajor(), $cVersion->getMinor(), $cVersion->getRevision(), $cVersion->getBuild());
	}

	/**
	 *  getPublicFilePath had to be added due to problems in the current  $PaperFile->getFilePath(); for Galley Files
	*/
	function getPublicFilePath(&$PaperFile, $pathComponent) {
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paper = &$paperDao->getPaper($PaperFile->getPaperId());
		$paperId = $paper->getSchedConfId();
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($paperId);
		return Config::getVar('files', 'files_dir') . '/conferences/' . $schedConf->getConferenceId() . '/schedConfs/' . $paperId .
		'/papers/' . $PaperFile->getPaperId() . $pathComponent . $PaperFile->getFileName();
	}

	/**
	 *  getPublicFileUrl !!!! must be a better way....
	*/
	 function getPublicFileUrl(&$PaperFile) {
		import('classes.config.Config');
		$base_url = &Config::getVar('general','base_url');
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paper = &$paperDao->getPaper($PaperFile->getPaperId());
		$SchedConf = $paper->getSchedConfId();
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConf = &$schedConfDao->getSchedConf($SchedConf);
		$conference = &$schedConf->getConference();
		$url = $base_url.'/index.php/'.$conference->getPath().'/'.$schedConf->getPath().'/paper/download/'.$PaperFile->getPaperId().'/'.$PaperFile->getGalleyId();
		return $url;
	}
	
	/**
	 *  getPublicSuppFileUrl !!!! must be a better way....
	*/
	function getPublicSuppFileUrl(&$PaperFile) {
		import('classes.config.Config');
		$base_url = &Config::getVar('general','base_url');
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paper = &$paperDao->getPaper($PaperFile->getPaperId());
		$SchedConf = $paper->getSchedConfId();
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConf = &$schedConfDao->getSchedConf($SchedConf);
		$conference = &$schedConf->getConference();
		$url = $base_url.'/index.php/'.$conference->getPath().'/'.$schedConf->getPath().'/paper/downloadSuppFile/'.$PaperFile->getPaperId().'/'.$PaperFile->getSuppFileId();
	  return $url;
	}

}

?>