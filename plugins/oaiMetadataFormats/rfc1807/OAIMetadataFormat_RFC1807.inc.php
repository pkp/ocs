<?php

/**
 * @file plugins/oaiMetadataFormats/rfc1807/OAIMetadataFormat_RFC1807.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_RFC1807
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- RFC 1807.
 */

// $Id$


class OAIMetadataFormat_RFC1807 extends OAIMetadataFormat {

	/**
	 * @see OAIMetadataFormat#toXml
	 */
	function toXml(&$record, $format = null) {
		$conference =& $record->getData('conference');
		$schedConf =& $record->getData('schedConf');
		$paper =& $record->getData('paper');
		$track =& $record->getData('track');
		$galleys =& $record->getData('galleys');

		// Add page information to sources
		$source = $conference->getConferenceTitle() . '; ' . $schedConf->getSchedConfTitle();
		if ($paper->getPages() != '') {
			$source .= '; ' . $paper->getPages();
		}

		// Get author names
		$creators = array();
		foreach ($paper->getAuthors() as $author) {
			$authorName = $author->getFullName();
			$affiliation = $author->getAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$creators[] = $authorName;
		}

		// Subjects
		$subject = array(
			$paper->getLocalizedDiscipline(null),
			$paper->getLocalizedSubject(null),
			$paper->getLocalizedSubjectClass(null)
		);

		// Publishers
		$publisher = $conference->getConferenceTitle(); // Default
		$publisherInstitution = $conference->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publisher = $publisherInstitution;
		}

		// Types
		AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
		$type = __('rt.metadata.pkp.peerReviewed');

		// Formats
		$format = array();
		foreach ($galleys as $galley) {
			$format[] = $galley->getFileType();
		}

		// Subjects
		$subject = array(
			$paper->getLocalizedDiscipline(null),
			$paper->getLocalizedSubject(null),
			$paper->getLocalizedSubjectClass(null)
		);

		// Get supplementary files
		$relation = array();
		foreach ($paper->getSuppFiles() as $suppFile) {
			// FIXME replace with correct URL
			$relation[] = Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'download', array($paper->getId(), $suppFile->getFileId()));
		}

		$url = Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'view', array($paper->getBestPaperId()));

		$response = "<rfc1807\n" .
			"\txmlns=\"http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt\n" .
			"\thttp://www.openarchives.org/OAI/1.1/rfc1807.xsd\">\n" .
			"\t<bib-version>v2</bib-version>\n" .
			$this->formatElement('id', $url) .
			$this->formatElement('entry', $record->datestamp) .
			$this->formatElement('organization', $publisher) .
			$this->formatElement('organization', $source) .
			$this->formatElement('title', $paper->getLocalizedTitle()) .
			$this->formatElement('type', $type) .
			$this->formatElement('type', $relation) .
			$this->formatElement('author', $creators) .
			$this->formatElement('date', $paper->getDatePublished()) .
			$this->formatElement('copyright', $conference->getLocalizedSetting('copyrightNotice')) .
			$this->formatElement('other_access', "url:$url") .
			$this->formatElement('keyword', $subject) .
			$this->formatElement('period', array(
				$paper->getLocalizedCoverageGeo(null),
				$paper->getLocalizedCoverageChron(null),
				$paper->getLocalizedCoverageSample(null)
			)) .
			$this->formatElement('monitoring', $paper->getLocalizedSponsor()) .
			$this->formatElement('language', $paper->getLanguage()) .
			$this->formatElement('abstract', strip_tags($paper->getLocalizedAbstract())) .
			"</rfc1807>\n";

		return $response;
	}

	/**
	 * Format XML for single RFC 1807 element.
	 * @param $name string
	 * @param $value mixed
	 */
	function formatElement($name, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}

		$response = '';
		foreach ($value as $v) {
			$response .= "\t<$name>" . OAIUtils::prepOutput($v) . "</$name>\n";
		}
		return $response;
	}
}

?>
