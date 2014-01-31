<?php

/**
 * MetsExportDom.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetsExportDom
 * @ingroup GatewayPlugin
 *
 * @brief MetsExportDom export plugin DOM functions for export
 */

//$Id$

import('xml.XMLCustomWriter');

class MetsExportDom {

	/**
	 *  creates the METS:structMap element for a conference with multiple Scheduled Conferences
	 */
	function generateConfstructMapWithSchedConfs(&$doc, &$root, &$conference, &$schedConfs) {
		$structMap =& XMLCustomWriter::createElement($doc, 'METS:structMap');
		XMLCustomWriter::setAttribute($structMap, 'TYPE', 'logical');
		$cDiv =& XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($cDiv, 'TYPE', 'series');
		XMLCustomWriter::setAttribute($cDiv, 'DMDID', 'CS-'.$conference->getId());
		XMLCustomWriter::setAttribute($cDiv, 'ADMID', 'A-'.$conference->getId());
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
		$schedConfDAO =& DAORegistry::getDAO('SchedConfDAO');
		$structMap =& XMLCustomWriter::createElement($doc, 'METS:structMap');
		XMLCustomWriter::setAttribute($structMap, 'TYPE', 'logical');
		$cDiv =& XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($cDiv, 'TYPE', 'series');
		XMLCustomWriter::setAttribute($cDiv, 'DMDID', 'CS-'.$conference->getId());
		XMLCustomWriter::setAttribute($cDiv, 'ADMID', 'A-'.$conference->getId());
		$i = 0;
		while ($i < sizeof($schedConfIdArray)) {
			$schedConf =& $schedConfDAO->getSchedConf($schedConfIdArray[$i]);
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
		$structMap =& XMLCustomWriter::createElement($doc, 'METS:structMap');
		XMLCustomWriter::setAttribute($structMap, 'TYPE', 'logical');
		$cDiv =& XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($cDiv, 'TYPE', 'series');
		XMLCustomWriter::setAttribute($cDiv, 'DMDID', 'CS-'.$conference->getId());
		XMLCustomWriter::setAttribute($cDiv, 'ADMID', 'A-'.$conference->getId());
		MetsExportDom::generateSchedConfDiv($doc, $cDiv, $schedConf);
		XMLCustomWriter::appendChild($structMap, $cDiv);
		XMLCustomWriter::appendChild($root, $structMap);
	}

	/**
	 *  creates the METS:div element for a Scheduled Conferences
	 */
	function generateSchedConfDiv(&$doc, &$root, &$schedConf) {
		$sDiv =& XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($sDiv, 'TYPE', 'conference');
		XMLCustomWriter::setAttribute($sDiv, 'DMDID', 'SCHC-'.$schedConf->getId());
		MetsExportDom::generateOverViewDiv($doc, $sDiv, $schedConf);
		$publishedPaperDAO =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPapersIterator =& $publishedPaperDAO->getPublishedPapers($schedConf->getId());
		$publishedPaperArray =& $publishedPapersIterator->toArray();

		$i = 0;
		while ($i < sizeof($publishedPaperArray)) {
		  MetsExportDom::generatePublishedPaperDiv($doc, $sDiv, $publishedPaperArray[$i], $schedConf);
		  $i++;
		}
		XMLCustomWriter::appendChild($root, $sDiv);
	}

	/**
	 *  creates the METS:div element for the Over View of the Scheduled Conference
	 */
	function generateOverViewDiv(&$doc, &$root, &$schedConf) {
		$schedConfSettingsDAO =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfOverview = $schedConfSettingsDAO->getSetting($schedConf->getId(), 'schedConfOverview');
		$schedConfIntroduction = $schedConfSettingsDAO->getSetting($schedConf->getId(), 'schedConfIntroduction');
		if($schedConfOverview != '' || $schedConfIntroduction != ''){
			$sDiv =& XMLCustomWriter::createElement($doc, 'METS:div');
			XMLCustomWriter::setAttribute($sDiv, 'TYPE', 'overview');
			XMLCustomWriter::setAttribute($sDiv, 'DMDID', 'OV-'.$schedConf->getId());
			XMLCustomWriter::appendChild($root, $sDiv);
		}
	}

	/**
	 *  creates the METS:div element for a submission
	 */
	function generatePublishedPaperDiv(&$doc, &$root, &$paper, &$schedConf) {
		$pDiv =& XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($pDiv, 'TYPE', 'submission');
		XMLCustomWriter::setAttribute($pDiv, 'DMDID', 'P'.$paper->getId());
		$paperGalleyDAO =& DAORegistry::getDAO('PaperGalleyDAO');
		$i = 0;
		$galleysArray =& $paperGalleyDAO->getGalleysByPaper($paper->getId());
		while ($i < sizeof($galleysArray)) {
			MetsExportDom::generatePaperFileDiv($doc, $pDiv,  $galleysArray[$i]);
			$i++;
		}
		$exportSuppFiles =& Request::getUserVar('exportSuppFiles');
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($schedConf->getConference());
		if($exportSuppFiles == 'on' || $conferenceRt->getEnabled()) {
			$suppFileDAO =& DAORegistry::getDAO('SuppFileDAO');
			$paperFilesArray =& $suppFileDAO->getSuppFilesByPaper($paper->getId());
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
	function generatePaperFileDiv(&$doc, &$root, $paperFile) {
		$fDiv =& XMLCustomWriter::createElement($doc, 'METS:fptr');
		XMLCustomWriter::setAttribute($fDiv, 'FILEID', 'F'.$paperFile->getFileId().'-P'.$paperFile->getPaperId());
		XMLCustomWriter::appendChild($root, $fDiv);
	}

	/**
	 *  creates the METS:dmdSec element for the Conference
	 */
	function generateConfDmdSecDom(&$doc, $root, &$conference) {
		$dmdSec =& XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'CS-'.$conference->getId());
		$mdWrap =& XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData =& XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods =& XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo =& XMLCustomWriter::createElement($doc, 'mods:titleInfo');
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
	function generateSchedConfDmdSecDom(&$doc, &$root, &$conference, &$schedConf) {
		$dmdSec =& XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'SCHC-'.$schedConf->getId());
		$mdWrap =& XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData =& XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods =& XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');
		$titleInfo =& XMLCustomWriter::createElement($doc, 'mods:titleInfo');
		XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $schedConf->getSchedConfTitle());
		XMLCustomWriter::appendChild($mods, $titleInfo);
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'conference');
		if($schedConf->getStartDate() != '' || $schedConf->getEndDate() != ''){
			$originInfo =& XMLCustomWriter::createElement($doc, 'mods:originInfo');
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
		$publishedPaperDAO =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPapersIterator =& $publishedPaperDAO->getPublishedPapers($schedConf->getId());
		$publishedPaperArray =& $publishedPapersIterator->toArray();

		$i = 0;
		while ($i < sizeof($publishedPaperArray)) {
			MetsExportDom::generatePublishedPaperDmdSecDom($doc, $root, $publishedPaperArray[$i], $conference, $schedConf);
			$i++;
		}
	}

	/**
	 *  creates the METS:dmdSec element for the OverView if schedConfOverview or schedConfIntroduction present
	 */
	function generateOverViewDmdSecDom(&$doc, $root, &$schedConf) {
		$schedConfSettingsDAO =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfOverview = $schedConfSettingsDAO->getSetting($schedConf->getId(), 'schedConfOverview');
		$schedConfIntroduction = $schedConfSettingsDAO->getSetting($schedConf->getId(), 'schedConfIntroduction');
		if($schedConfOverview != '' || $schedConfIntroduction != ''){
			$dmdSec =& XMLCustomWriter::createElement($doc, 'METS:dmdSec');
			XMLCustomWriter::setAttribute($dmdSec, 'ID', 'OV-'.$schedConf->getId());
			$mdWrap =& XMLCustomWriter::createElement($doc, 'METS:mdWrap');
			$xmlData =& XMLCustomWriter::createElement($doc, 'METS:xmlData');
			XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
			$mods =& XMLCustomWriter::createElement($doc, 'mods:mods');
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
	function generatePublishedPaperDmdSecDom(&$doc, &$root, &$paper, &$conference, &$schedConf) {
		if($paper->getStatus() == STATUS_PUBLISHED){
			$dmdSec =& XMLCustomWriter::createElement($doc, 'METS:dmdSec');
			XMLCustomWriter::setAttribute($dmdSec, 'ID', 'P'.$paper->getId());
			$mdWrap =& XMLCustomWriter::createElement($doc, 'METS:mdWrap');
			$xmlData =& XMLCustomWriter::createElement($doc, 'METS:xmlData');
			XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
			$mods =& XMLCustomWriter::createElement($doc, 'mods:mods');
			XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
			XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');

			$primaryLocale = $conference->getPrimaryLocale();
			foreach ($paper->getTitle(null) as $locale => $title) {
				$titleInfo =& XMLCustomWriter::createElement($doc, 'mods:titleInfo');
				XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $title);
				if ($locale != $primaryLocale) XMLCustomWriter::setAttribute($titleInfo, 'type', 'alternative');
				XMLCustomWriter::appendChild($mods, $titleInfo);
				unset($titleInfo);
			}

			foreach ($paper->getAbstract(null) as $locale => $abstract) {
				XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $abstract);
			}

			MetsExportDom::generateAuthorsDom($doc, $mods, $paper->getId());
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'submission');
			if($paper->getDatePublished() != ''){
 				$originInfo =& XMLCustomWriter::createElement($doc, 'mods:originInfo');
				$sDate = XMLCustomWriter::createChildWithText($doc, $originInfo, 'mods:dateIssued', $paper->getDatePublished());
			 	XMLCustomWriter::appendChild($mods, $originInfo);
			}
			XMLCustomWriter::appendChild($xmlData, $mods);
			XMLCustomWriter::appendChild($dmdSec, $mdWrap);
			XMLCustomWriter::appendChild($mdWrap,$xmlData);
			XMLCustomWriter::appendChild($root, $dmdSec);
			$exportSuppFiles =& Request::getUserVar('exportSuppFiles');
			$rtDao =& DAORegistry::getDAO('RTDAO');
			$conferenceRt =& $rtDao->getConferenceRTByConference($schedConf->getConference());
			if($exportSuppFiles == 'on' || $conferenceRt->getEnabled()) {
				$suppFileDAO =& DAORegistry::getDAO('SuppFileDAO');
				$paperFilesArray =& $suppFileDAO->getSuppFilesByPaper($paper->getId());
				$i = 0;
				while ($i < sizeof($paperFilesArray)) {
					MetsExportDom::generatePaperSuppFilesDmdSecDom($doc, $root, $paperFilesArray[$i], $conference, $schedConf);
					$i++;
				}
			}
		}
	}

	/**
	 *  creates the METS:dmdSec element for Supplementary Files
	 */
	function generatePaperSuppFilesDmdSecDom(&$doc, &$root, &$paperFile, &$conference, &$schedConf) {
		$dmdSec =& XMLCustomWriter::createElement($doc, 'METS:dmdSec');
		XMLCustomWriter::setAttribute($dmdSec, 'ID', 'DMD-SF'.$paperFile->getFileId().'-P'.$paperFile->getPaperId());
		$mdWrap =& XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		$xmlData =& XMLCustomWriter::createElement($doc, 'METS:xmlData');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'MODS');
		$mods =& XMLCustomWriter::createElement($doc, 'mods:mods');
		XMLCustomWriter::setAttribute($mods, 'xmlns:mods', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd');

		$primaryLocale = $conference->getPrimaryLocale();
		foreach ($paperFile->getTitle(null) as $locale => $title) {
			$titleInfo =& XMLCustomWriter::createElement($doc, 'mods:titleInfo');
			XMLCustomWriter::createChildWithText($doc, $titleInfo, 'mods:title', $title);
			if ($locale != $primaryLocale) XMLCustomWriter::setAttribute($titleInfo, 'type', 'alternative');
			XMLCustomWriter::appendChild($mods, $titleInfo);
			unset($titleInfo);
		}

		foreach ($paperFile->getCreator(null) as $locale => $creator) {
			$creatorNode =& XMLCustomWriter::createElement($doc, 'mods:name');
			XMLCustomWriter::setAttribute($creatorNode, 'type', 'personal');
			$nameNode =& XMLCustomWriter::createChildWithText($doc, $creatorNode, 'mods:namePart', $creator);
			$role =& XMLCustomWriter::createElement($doc, 'mods:role');
			$roleTerm =& XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'creator');
			XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
			XMLCustomWriter::appendChild($creatorNode, $role);
			XMLCustomWriter::appendChild($mods, $creatorNode);
			unset($creatorNode, $nameNode, $role, $roleTerm);
		}
		foreach ($paperFile->getDescription(null) as $locale => $description) {
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:abstract', $description);
		}
		if($paperFile->getDateCreated() != ''){
 			$originInfo =& XMLCustomWriter::createElement($doc, 'mods:originInfo');
			$sDate = XMLCustomWriter::createChildWithText($doc, $originInfo, 'mods:dateCreated', $paperFile->getDateCreated());
		 	XMLCustomWriter::appendChild($mods, $originInfo);
			unset($originInfo);
		}
		XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', 'additional material');
		if($paperFile->getType() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', $paperFile->getType());
		foreach ($paperFile->getTypeOther(null) as $locale => $typeOther) {
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:genre', $typeOther);
		}
		foreach ($paperFile->getSubject(null) as $locale => $subject) {
			$subjNode =& XMLCustomWriter::createElement($doc, 'mods:subject');
			XMLCustomWriter::createChildWithText($doc, $subjNode, 'mods:topic', $subject);
			XMLCustomWriter::appendChild($mods, $subjNode);
			unset($subjNode);
		}
		if($paperFile->getLanguage() != '')
			XMLCustomWriter::createChildWithText($doc, $mods, 'mods:language', $paperFile->getLanguage());
		XMLCustomWriter::appendChild($xmlData, $mods);
		XMLCustomWriter::appendChild($dmdSec, $mdWrap);
		XMLCustomWriter::appendChild($mdWrap,$xmlData);
		XMLCustomWriter::appendChild($root, $dmdSec);
	}

	/**
	 *  creates the METS:div @TYPE=additional_material for the Supp Files
	 */
	function generatePaperSuppFilesDiv(&$doc, &$root, $suppFile) {
		$sDiv =& XMLCustomWriter::createElement($doc, 'METS:div');
		XMLCustomWriter::setAttribute($sDiv, 'TYPE', 'additional_material');
		XMLCustomWriter::setAttribute($sDiv, 'DMDID', 'DMD-SF'.$suppFile->getFileId().'-P'.$suppFile->getPaperId());
		$fDiv =& XMLCustomWriter::createElement($doc, 'METS:fptr');
		XMLCustomWriter::setAttribute($fDiv, 'FILEID', 'SF'.$suppFile->getFileId().'-P'.$suppFile->getPaperId());
		XMLCustomWriter::appendChild($sDiv, $fDiv);
		XMLCustomWriter::appendChild($root, $sDiv);
	}

	/**
	 * finds all files associated with this Scheduled Conference by going through all published Papers
	 *
	 */
	function generateSchedConfFileSecDom(&$doc, &$root, &$conference, &$schedConf) {
		$publishedPaperDAO =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPapersIterator =& $publishedPaperDAO->getPublishedPapers($schedConf->getId());
		$publishedPaperArray =& $publishedPapersIterator->toArray();

		$i = 0;
		while ($i < sizeof($publishedPaperArray)) {
			MetsExportDom::generatePaperFilesDom($doc, $root, $publishedPaperArray[$i], $conference, $schedConf);
			$i++;
		}
	}

	/**
	 *  finds all files associated with this published Papers and if exportSuppFiles == 'on' in the Config file
	 */
	function generatePaperFilesDom(&$doc, &$root, &$paper, &$conference, &$schedConf) {
		$paperGalleyDAO =& DAORegistry::getDAO('PaperGalleyDAO');
		$i = 0;
		$galleysArray =& $paperGalleyDAO->getGalleysByPaper($paper->getId());
		while ($i < sizeof($galleysArray)) {
			MetsExportDom::generatePaperFileDom($doc, $root, $paper, $galleysArray[$i], $conference, $schedConf);
			$i++;
		}
		$exportSuppFiles =& Request::getUserVar('exportSuppFiles');
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($schedConf->getConference());
		if($exportSuppFiles == 'on' || $conferenceRt->getEnabled()) {
			$suppFileDAO =& DAORegistry::getDAO('SuppFileDAO');
			$paperFilesArray =& $suppFileDAO->getSuppFilesByPaper($paper->getId());
			$i = 0;
			while ($i < sizeof($paperFilesArray)) {
				MetsExportDom::generatePaperSuppFileDom($doc, $root, $paper, $paperFilesArray[$i], $conference, $schedConf);
				$i++;
			}
		}
	}

	/**
	 *  Creates a METS:file for the paperfile; checks if METS:FContent or METS:FLocat should be used
	 */
	function generatePaperFileDom(&$doc, &$root, &$paper, &$paperFile, &$conference, &$schedConf) {
		import('classes.file.PublicFileManager');
		import('classes.file.FileManager');
		$contentWrapper =& Request::getUserVar('contentWrapper');
		$mfile =& XMLCustomWriter::createElement($doc, 'METS:file');
		$filePath  = MetsExportDom::getPublicFilePath($paperFile , '/public/');
		$chkmd5return = md5_file($filePath);
		XMLCustomWriter::setAttribute($mfile, 'ID', 'F'.$paperFile->getFileId().'-P'.$paperFile->getPaperId());
		XMLCustomWriter::setAttribute($mfile, 'SIZE', $paperFile->getFileSize());
		XMLCustomWriter::setAttribute($mfile, 'MIMETYPE', $paperFile->getFileType());
		XMLCustomWriter::setAttribute($mfile, 'OWNERID', $paperFile->getFileName());
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUM', $chkmd5return);
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUMTYPE', 'MD5');
		if($contentWrapper == 'FContent'){
			$fileContent =& FileManager::readFile($filePath);
			$fContent =& XMLCustomWriter::createElement($doc, 'METS:FContent');
			$fNameNode =& XMLCustomWriter::createChildWithText($doc, $fContent, 'METS:binData',base64_encode($fileContent));
			XMLCustomWriter::appendChild($mfile, $fContent);
		} else {
			$fLocat =& XMLCustomWriter::createElement($doc, 'METS:FLocat');
			XMLCustomWriter::setAttribute($fLocat, 'xlink:href', Request::url(
				$conference->getPath(), $schedConf->getPath(),
				'paper', 'download',
				array($paperFile->getPaperId(), $paperFile->getGalleyId())
			));
			XMLCustomWriter::setAttribute($fLocat, 'LOCTYPE', 'URL');
			XMLCustomWriter::appendChild($mfile, $fLocat);
		}
		XMLCustomWriter::appendChild($root, $mfile);
	}

	/**
	 *  Creates a METS:file for the Supplementary File; checks if METS:FContent or METS:FLocat should be used
	 */
	function generatePaperSuppFileDom(&$doc, &$root, &$paper, &$paperFile, &$conference, &$schedConf) {
		import('classes.file.PublicFileManager');
		import('classes.file.FileManager');
		$contentWrapper =& Request::getUserVar('contentWrapper');
		$mfile =& XMLCustomWriter::createElement($doc, 'METS:file');
		$filePath  = MetsExportDom::getPublicFilePath($paperFile , '/supp/');;
		$chkmd5return = md5_file($filePath);
		XMLCustomWriter::setAttribute($mfile, 'ID', 'SF'.$paperFile->getFileId().'-P'.$paperFile->getPaperId());
		XMLCustomWriter::setAttribute($mfile, 'SIZE', $paperFile->getFileSize());
		XMLCustomWriter::setAttribute($mfile, 'MIMETYPE', $paperFile->getFileType());
		XMLCustomWriter::setAttribute($mfile, 'OWNERID', $paperFile->getFileName());
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUM', $chkmd5return);
		XMLCustomWriter::setAttribute($mfile, 'CHECKSUMTYPE', 'MD5');
		if($contentWrapper == 'FContent'){
			$fileContent =& FileManager::readFile($filePath);
			$fContent =& XMLCustomWriter::createElement($doc, 'METS:FContent');
			$fNameNode =& XMLCustomWriter::createChildWithText($doc, $fContent, 'METS:binData',base64_encode($fileContent));
			XMLCustomWriter::appendChild($mfile, $fContent);
		} else {
			$fLocat =& XMLCustomWriter::createElement($doc, 'METS:FLocat');
			XMLCustomWriter::setAttribute($fLocat, 'xlink:href', Request::url(
				$conference->getPath(), $schedConf->getPath(),
				'paper', 'downloadSuppFile',
				array($paperFile->getPaperId(), $paperFile->getSuppFileId())
			));
			XMLCustomWriter::setAttribute($fLocat, 'LOCTYPE', 'URL');
			XMLCustomWriter::appendChild($mfile, $fLocat);
		}
		XMLCustomWriter::appendChild($root, $mfile);
	}

	/**
	 *  Process All authors for the Given Paper
	 */
	function generateAuthorsDom(&$doc, &$root, $paperID) {
		$authorDAO =& DAORegistry::getDAO('AuthorDAO');
		$i = 0;
		$authorsArray =& $authorDAO->getAuthorsByPaper($paperID);
		while ($i < sizeof($authorsArray)) {
			$authorNode =  &MetsExportDom::generateAuthorDom($doc, $authorsArray[$i]);
			XMLCustomWriter::appendChild($root, $authorNode);
			$i++;
		}
	}

	/**
	 *  Create mods:name for a author
	 */
	function &generateAuthorDom(&$doc, $author) {
		$authorNode =& XMLCustomWriter::createElement($doc, 'mods:name');
		XMLCustomWriter::setAttribute($authorNode, 'type', 'personal');
		$fNameNode =& XMLCustomWriter::createChildWithText($doc, $authorNode, 'mods:namePart', $author->getFirstName().' '.$author->getMiddleName());
		XMLCustomWriter::setAttribute($fNameNode, 'type', 'given');
		$lNameNode =& XMLCustomWriter::createChildWithText($doc, $authorNode, 'mods:namePart', $author->getLastName());
		XMLCustomWriter::setAttribute($lNameNode, 'type', 'family');
		$role =& XMLCustomWriter::createElement($doc, 'mods:role');
		$roleTerm =& XMLCustomWriter::createChildWithText($doc, $role, 'mods:roleTerm', 'author');
		XMLCustomWriter::setAttribute($roleTerm, 'type', 'text');
		XMLCustomWriter::appendChild($authorNode, $role);
		return $authorNode;
	}

	/**
	 *  Create METS:amdSec for the Conference
	 */
	function createmetsamdSec($doc, &$root, &$conference) {
		$amdSec =& XMLCustomWriter::createElement($doc, 'METS:amdSec');
		$techMD =& XMLCustomWriter::createElement($doc, 'METS:techMD');
		XMLCustomWriter::setAttribute($techMD, 'ID', 'A-'.$conference->getId());
		$mdWrap =& XMLCustomWriter::createElement($doc, 'METS:mdWrap');
		XMLCustomWriter::setAttribute($mdWrap, 'MDTYPE', 'PREMIS');
		$xmlData =& XMLCustomWriter::createElement($doc, 'METS:xmlData');
		$pObject =& XMLCustomWriter::createElement($doc, 'premis:object');
		XMLCustomWriter::setAttribute($pObject, 'xmlns:premis', 'http://www.loc.gov/standards/premis/v1');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', str_replace(' http://www.loc.gov/standards/premis/v1 http://www.loc.gov/standards/premis/v1/PREMIS-v1-1.xsd', '', $root->getAttribute('xsi:schemaLocation')) . ' http://www.loc.gov/standards/premis/v1 http://www.loc.gov/standards/premis/v1/PREMIS-v1-1.xsd');
		$objectIdentifier =& XMLCustomWriter::createElement($doc, 'premis:objectIdentifier');
		XMLCustomWriter::createChildWithText($doc, $objectIdentifier, 'premis:objectIdentifierType', 'internal');
		XMLCustomWriter::createChildWithText($doc, $objectIdentifier, 'premis:objectIdentifierValue', 'C-'.$conference->getId());
		XMLCustomWriter::appendChild($pObject, $objectIdentifier);
		$preservationLevel =& Request::getUserVar('preservationLevel');
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
		$root =& XMLCustomWriter::createElement($doc, 'METS:metsHdr');
		XMLCustomWriter::setAttribute($root, 'CREATEDATE', date('c'));
		XMLCustomWriter::setAttribute($root, 'LASTMODDATE', date('c'));
		$agentNode =& XMLCustomWriter::createElement($doc, 'METS:agent');
		XMLCustomWriter::setAttribute($agentNode, 'ROLE', 'DISSEMINATOR');
		XMLCustomWriter::setAttribute($agentNode, 'TYPE', 'ORGANIZATION');
		$organization =& Request::getUserVar('organization');
		if($organization == ''){
		  $siteDao =& DAORegistry::getDAO('SiteDAO');
		  $site = $siteDao->getSite();
		  $organization = $site->getLocalizedTitle();
		}
		XMLCustomWriter::createChildWithText($doc, $agentNode, 'METS:name', $organization, false);
		XMLCustomWriter::appendChild($root, $agentNode);
		$agentNode2 =& XMLCustomWriter::createElement($doc, 'METS:agent');
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
		$versionDAO =& DAORegistry::getDAO('VersionDAO');
		$cVersion = $versionDAO->getCurrentVersion();
		return sprintf('Open Conference Systems v%d.%d.%d build %d', $cVersion->getMajor(), $cVersion->getMinor(), $cVersion->getRevision(), $cVersion->getBuild());
	}

	/**
	 *  getPublicFilePath had to be added due to problems in the current  $paperFile->getFilePath(); for Galley Files
	 */
	function getPublicFilePath(&$paperFile, $pathComponent) {
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$paper =& $paperDao->getPaper($paperFile->getPaperId());
		$paperId = $paper->getSchedConfId();
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($paperId);
		return Config::getVar('files', 'files_dir') . '/conferences/' . $schedConf->getConferenceId() . '/schedConfs/' . $paperId .
		'/papers/' . $paperFile->getPaperId() . $pathComponent . $paperFile->getFileName();
	}
}

?>
