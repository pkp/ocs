<?php

/**
 * @file NLMExportDom.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NLMExportDom
 * @ingroup plugins_importexport_nlm
 * @see NLMExportPlugin
 *
 * @brief NLM XML export plugin DOM functions
 */

//$Id$

import('xml.XMLCustomWriter');

define('NLM_DTD_URL', 'http://www.nlm.nih.gov/databases/dtd/nlmmeetingabstractinput_060101.dtd');
define('NLM_DTD_ID', '-//NLM/DTD MeetingAbstract, 1st  January, 2006//EN');

class NLMExportDom {

	/**
	 * Build paper XML using DOM elements
	 * @param $args Parameters to the plugin
	 *
	 * The DOM for this XML was developed according to the NLM 
	 * Standard Publisher Data Format: 
	 * http://www.ncbi.nlm.nih.gov/entrez/query/static/spec.html
	 */ 

	function &generateNLMDom() {
		// create the output XML document in DOM with a root node
		$doc =& XMLCustomWriter::createDocument('InputMeetingAbstractSet', NLM_DTD_ID, NLM_DTD_URL);

		return $doc;
	}

	function &generatePaperSetDom(&$doc) {
		$root =& XMLCustomWriter::createElement($doc, 'InputMeetingAbstractSet');
		XMLCustomWriter::appendChild($doc, $root);

		return $root;
	}

	function &generatePaperDom(&$doc, &$conference, &$track, &$paper) {

		// register the editor submission DAO for use later
//		$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');

		/* --- MeetingAbstract --- */
		$root =& XMLCustomWriter::createElement($doc, 'MeetingAbstract');
		XMLCustomWriter::setAttribute($root, 'Status', 'Completed');

		/* --- DateCreated --- */
		$dateNode =& XMLCustomWriter::createElement($doc, 'DateCreated');
		XMLCustomWriter::appendChild($root, $dateNode);

		XMLCustomWriter::createChildWithText($doc, $dateNode, 'Year', date('Y') );
		XMLCustomWriter::createChildWithText($doc, $dateNode, 'Month', date('m') );
		XMLCustomWriter::createChildWithText($doc, $dateNode, 'Day', date('d') );

		/* --- Article/Paper --- */
		$articleNode =& XMLCustomWriter::createElement($doc, 'Article');
		XMLCustomWriter::setAttribute($articleNode, 'PubModel', 'Electronic');
		XMLCustomWriter::appendChild($root, $articleNode);

		/* --- Journal/Book --- */
		// FIXME: at the moment this is a null element required by NLM
		$journalNode =& XMLCustomWriter::createChildWithText($doc, $articleNode, 'Journal', null);

		$journalIssueNode =& XMLCustomWriter::createElement($doc, 'JournalIssue');
		XMLCustomWriter::setAttribute($journalIssueNode, 'CitedMedium', 'Internet');
		XMLCustomWriter::appendChild($journalNode, $journalIssueNode);

		$journalDateNode =& XMLCustomWriter::createElement($doc, 'PubDate');
		XMLCustomWriter::appendChild($journalIssueNode, $journalDateNode);
		XMLCustomWriter::createChildWithText($doc, $journalDateNode, 'MedlineDate', date('Y') );

		/* --- ArticleTitle --- */
		// NLM requires english titles for PaperTitle
		XMLCustomWriter::createChildWithText($doc, $articleNode, 'ArticleTitle', $paper->getLocalizedTitle());

		/* --- Pagination --- */
		// If there is no page number, then use abstract number
		$paginationNode =& XMLCustomWriter::createElement($doc, 'Pagination');
		XMLCustomWriter::appendChild($articleNode, $paginationNode);

		$pages = $paper->getPages();

		if (preg_match("/([0-9]+)\s*-\s*([0-9]+)/i", $pages, $matches)) {
			// simple pagination (eg. "pp. 3- 		8")
			XMLCustomWriter::createChildWithText($doc, $paginationNode, 'MedlinePgn', $matches[1].'-'.$matches[2]);			
		} elseif (preg_match("/(e[0-9]+)/i", $pages, $matches)) {
			// elocation-id (eg. "e12")
			XMLCustomWriter::createChildWithText($doc, $paginationNode, 'MedlinePgn', $matches[1]);
		} else {
			// we need to insert something, so use the best ID possible
			XMLCustomWriter::createChildWithText($doc, $paginationNode, 'MedlinePgn', $paper->getBestPaperId($conference));			
		}

		/* --- Abstract --- */
		$abstractNode =& XMLCustomWriter::createElement($doc, 'Abstract');
		XMLCustomWriter::appendChild($articleNode, $abstractNode);

		XMLCustomWriter::createChildWithText($doc, $abstractNode, 'AbstractText', strip_tags($paper->getLocalizedAbstract()), false);

		/* --- Affiliation --- */
		$sponsor = $paper->getLocalizedSponsor();

		if ($sponsor != '') {
			XMLCustomWriter::createChildWithText($doc, $articleNode, 'Affiliation', $sponsor);
		}

		/* --- AuthorList --- */
		$authorListNode =& XMLCustomWriter::createElement($doc, 'AuthorList');
		XMLCustomWriter::setAttribute($authorListNode, 'CompleteYN', 'Y');
		XMLCustomWriter::appendChild($articleNode, $authorListNode);

		foreach ($paper->getAuthors() as $author) {
			$authorNode =& NLMExportDom::generateAuthorDom($doc, $author);
			XMLCustomWriter::appendChild($authorListNode, $authorNode);
		}

		/* --- Conference --- */ 
		$conferenceNode =& XMLCustomWriter::createElement($doc, 'Author');
		XMLCustomWriter::appendChild($authorListNode, $conferenceNode);

		XMLCustomWriter::createChildWithText($doc, $conferenceNode, 'CollectiveName', $conference->getConferenceTitle());

		// OtherInformation element goes here with location for current conference

		/* --- Language --- */
		XMLCustomWriter::createChildWithText($doc, $articleNode, 'Language', strtolower($paper->getLanguage()), false);

		/* --- MedlineJournalInfo--- */
		// FIXME: at the moment this is a null element required by NLM
		$journalInfoNode =& XMLCustomWriter::createChildWithText($doc, $root, 'MedlineJournalInfo', null);
		XMLCustomWriter::createChildWithText($doc, $journalInfoNode, 'MedlineTA', null);

		return $root;
	}

	function &generateAuthorDom(&$doc, &$author) {
		$root =& XMLCustomWriter::createElement($doc, 'Author');

		$foreName = trim($author->getFirstName() .' '.$author->getMiddleName());

		XMLCustomWriter::createChildWithText($doc, $root, 'LastName', ucfirst($author->getLastName()));
		XMLCustomWriter::createChildWithText($doc, $root, 'ForeName', ucwords($foreName));

		return $root;
	}

}

?>
