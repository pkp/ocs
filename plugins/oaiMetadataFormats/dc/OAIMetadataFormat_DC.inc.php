<?php

/**
 * @defgroup oai_format
 */

/**
 * @file plugins/oaiMetadata/dc/OAIMetadataFormat_DC.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- Dublin Core.
 */

// $Id$


class OAIMetadataFormat_DC extends OAIMetadataFormat {
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
		$sources = array($conference->getConferenceTitle() . '; ' . $schedConf->getSchedConfTitle());
		if ($paper->getPages() != '') foreach ($sources as $a => $b) {
			$sources[$a] .= '; ' . $paper->getPages();
		}

		// Get author names
		$creator = array();
		foreach ($paper->getAuthors() as $author) {
			$authorName = $author->getFullName();
			$affiliation = $author->getAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$creator[] = $authorName;
		}

		// Subjects
		$subjects = array_merge_recursive(
			$this->stripAssocArray((array) $paper->getDiscipline(null)),
			$this->stripAssocArray((array) $paper->getSubject(null)),
			$this->stripAssocArray((array) $paper->getSubjectClass(null))
		);

		// Publishers
		$publishers = $this->stripAssocArray((array) $conference->getTitle(null)); // Default
		$publisherInstitution = (array) $conference->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publishers = $publisherInstitution;
		}

		// Types
		AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
		$this->stripAssocArray((array) $track->getIdentifyType(null));
		$types = array_merge_recursive(
			empty($types)?array(AppLocale::getLocale() => __('rt.metadata.pkp.peerReviewed')):$types,
			$this->stripAssocArray((array) $paper->getType(null))
		);

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

		$response = "<oai_dc:dc\n" .
			"\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n" .
			"\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n" .
			"\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n" .
			$this->formatElement('title', $this->stripAssocArray((array) $paper->getLocalizedTitle(null)), true) .
			$this->formatElement('creator', $creator) .
			$this->formatElement('subject', $subjects, true) .
			$this->formatElement('description', $this->stripAssocArray((array) $paper->getAbstract(null)), true) .
			$this->formatElement('publisher', $publishers, true) .
			$this->formatElement('contributor', $this->stripAssocArray((array) $paper->getSponsor(null)), true) .
			$this->formatElement('date', $paper->getDatePublished()) .
			$this->formatElement('type', $types, true) .
			$this->formatElement('format', $format) .
			$this->formatElement('identifier', Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'view', array($paper->getBestPaperId()))) .
			$this->formatElement('source', $sources, true) .
			$this->formatElement('language', $paper->getLanguage()) .
			$this->formatElement('relation', $relation) .
			$this->formatElement('coverage', array_merge_recursive(
				$this->stripAssocArray((array) $paper->getCoverageGeo(null)),
				$this->stripAssocArray((array) $paper->getCoverageChron(null)),
				$this->stripAssocArray((array) $paper->getCoverageSample(null))
			), true) .
			$this->formatElement('rights', (array) $conference->getSetting('copyrightNotice')) .
			"</oai_dc:dc>\n";

		return $response;
	}

	/**
	 * Format XML for single DC element.
	 * @param $name string
	 * @param $value mixed
	 * @param $multilingual boolean optional
	 */
	function formatElement($name, $value, $multilingual = false) {
		if (!is_array($value)) {
			$value = array($value);
		}

		$response = '';
		foreach ($value as $key => $v) {
			$key = str_replace('_', '-', $key);
			if (!$multilingual) $response .= "\t<dc:$name>" . OAIUtils::prepOutput($v) . "</dc:$name>\n";
			else {
				if (is_array($v)) {
					foreach ($v as $subV) {
						$response .= "\t<dc:$name xml:lang=\"$key\">" . OAIUtils::prepOutput($subV) . "</dc:$name>\n";
					}
				} else {
					$response .= "\t<dc:$name xml:lang=\"$key\">" . OAIUtils::prepOutput($v) . "</dc:$name>\n";
				}
			}
		}
		return $response;
	}
}

?>
