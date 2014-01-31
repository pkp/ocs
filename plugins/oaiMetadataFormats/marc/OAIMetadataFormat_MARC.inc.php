<?php

/**
 * @file plugins/oaiMetadata/marc/OAIMetadataFormat_MARC.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_MARC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- MARC.
 */

// $Id$


class OAIMetadataFormat_MARC extends OAIMetadataFormat {

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

		// Get supplementary files
		$relation = array();
		foreach ($paper->getSuppFiles() as $suppFile) {
			// FIXME replace with correct URL
			$relation[] = Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'download', array($paper->getId(), $suppFile->getFileId()));
		}

		$response = "<oai_marc status=\"c\" type=\"a\" level=\"m\" encLvl=\"3\" catForm=\"u\"\n" .
			"\txmlns=\"http://www.openarchives.org/OAI/1.1/oai_marc\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/1.1/oai_marc\n" .
			"\thttp://www.openarchives.org/OAI/1.1/oai_marc.xsd\">\n" .
			"\t<fixfield id=\"008\">\"" . date('ymd Y', strtotime($paper->getDatePublished())) . '												eng  "</fixfield>' . "\n" .
			$this->formatElement('042', ' ', ' ', 'a', 'dc') .
			$this->formatElement('245', '0', '0', 'a', $paper->getLocalizedTitle()) .
			$this->formatElement('720', ' ', ' ', 'a', $creators) .
			$this->formatElement('653', ' ', ' ', 'a', $subject) .
			$this->formatElement('520', ' ', ' ', 'a', strip_tags($paper->getLocalizedAbstract())) .
			$this->formatElement('260', ' ', ' ', 'b', $publisher) .
			$this->formatElement('720', ' ', ' ', 'a', $paper->getLocalizedSponsor()) .
			$this->formatElement('260', ' ', ' ', 'c', $paper->getDatePublished()) .
			$this->formatElement('655', ' ', '7', 'a', $type) .
			$this->formatElement('856', ' ', ' ', 'q', $format) .
			$this->formatElement('856', '4', '0', 'u', Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'view', array($paper->getBestPaperId()))) .
			$this->formatElement('786', '0', ' ', 'n', $source) .
			$this->formatElement('546', ' ', ' ', 'a', $paper->getLanguage()) .
			$this->formatElement('787', '0', ' ', 'n', $relation) .
			$this->formatElement('500', ' ', ' ', 'a', array(
				$paper->getLocalizedCoverageGeo(null),
				$paper->getLocalizedCoverageChron(null),
				$paper->getLocalizedCoverageSample(null)
			)) .
			$this->formatElement('540', ' ', ' ', 'a', $conference->getLocalizedSetting('copyrightNotice')) .
			"</oai_marc>\n";

		return $response;
	}

	/**
	 * Format XML for single MARC element.
	 * @param $id string
	 * @param $i1 string
	 * @param $i2 string
	 * @param $label string
	 * @param $value mixed
	 */
	function formatElement($id, $i1, $i2, $label, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}

		$response = '';
		foreach ($value as $v) {
			$response .= "\t<varfield id=\"$id\" i1=\"$i1\" i2=\"$i2\">\n" .
				"\t\t<subfield label=\"$label\">" . OAIUtils::prepOutput($v) . "</subfield>\n" .
				"\t</varfield>\n";
		}
		return $response;
	}
}

?>
