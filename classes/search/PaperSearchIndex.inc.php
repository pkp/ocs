<?php

/**
 * @file PaperSearchIndex.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperSearchIndex
 * @ingroup search
 * @see PaperSearch
 *
 * @brief Class to add content to the paper search index.
 */

//$Id$

import('search.SearchFileParser');
import('search.SearchHTMLParser');
import('search.SearchHelperParser');
import('search.PaperSearch');

// Words are truncated to at most this length
define('SEARCH_KEYWORD_MAX_LENGTH', 40);

class PaperSearchIndex {

	/**
	 * Index a block of text for an object.
	 * @param $objectId int
	 * @param $text string
	 * @param $position int
	 */
	function indexObjectKeywords($objectId, $text, &$position) {
		$searchDao =& DAORegistry::getDAO('PaperSearchDAO');
		$keywords =& PaperSearchIndex::filterKeywords($text);
		for ($i = 0, $count = count($keywords); $i < $count; $i++) {
			if ($searchDao->insertObjectKeyword($objectId, $keywords[$i], $position) !== null) {
				$position += 1;
			}
		}
	}

	/**
	 * Add a block of text to the search index.
	 * @param $paperId int
	 * @param $type int
	 * @param $text string
	 * @param $assocId int optional
	 */
	function updateTextIndex($paperId, $type, $text, $assocId = null) {
			$searchDao =& DAORegistry::getDAO('PaperSearchDAO');
			$objectId = $searchDao->insertObject($paperId, $type, $assocId);
			$position = 0;
			PaperSearchIndex::indexObjectKeywords($objectId, $text, $position);
	}

	/**
	 * Add a file to the search index.
	 * @param $paperId int
	 * @param $type int
	 * @param $fileId int
	 */
	function updateFileIndex($paperId, $type, $fileId) {
		import('file.PaperFileManager');
		$fileMgr = new PaperFileManager($paperId);
		$file =& $fileMgr->getFile($fileId);

		if (isset($file)) {
			$parser =& SearchFileParser::fromFile($file);
		}

		if (isset($parser)) {
			if ($parser->open()) {
				$searchDao =& DAORegistry::getDAO('PaperSearchDAO');
				$objectId = $searchDao->insertObject($paperId, $type, $fileId);

				$position = 0;
				while(($text = $parser->read()) !== false) {
					PaperSearchIndex::indexObjectKeywords($objectId, $text, $position);
				}
				$parser->close();
			}
		}
	}

	/**
	 * Delete keywords from the search index.
	 * @param $paperId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	function deleteTextIndex($paperId, $type = null, $assocId = null) {
		$searchDao =& DAORegistry::getDAO('PaperSearchDAO');
		return $searchDao->deletePaperKeywords($paperId, $type, $assocId);
	}

	/**
	 * Split a string into a clean array of keywords
	 * @param $text string
	 * @param $allowWildcards boolean
	 * @return array of keywords
	 */
	function &filterKeywords($text, $allowWildcards = false) {
		$minLength = Config::getVar('search', 'min_word_length');
		$stopwords =& PaperSearchIndex::loadStopwords();

		// Join multiple lines into a single string
		if (is_array($text)) $text = join("\n", $text);

		$cleanText = Core::cleanVar($text);

		// Remove punctuation
		$cleanText = String::regexp_replace('/[!"\#\$%\'\(\)\.\?@\[\]\^`\{\}~]/', '', $cleanText);
		$cleanText = String::regexp_replace('/[\+,:;&\/<=>\|\\\]/', ' ', $cleanText);
		$cleanText = String::regexp_replace('/[\*]/', $allowWildcards ? '%' : ' ', $cleanText);
		$cleanText = String::strtolower($cleanText);

		// Split into words
		$words = preg_split('/\s+/', $cleanText);

		// FIXME Do not perform further filtering for some fields, e.g., author names?

		// Remove stopwords
		$keywords = array();
		foreach ($words as $k) {
			if (!isset($stopwords[$k]) && String::strlen($k) >= $minLength && !is_numeric($k)) {
				$keywords[] = String::substr($k, 0, SEARCH_KEYWORD_MAX_LENGTH);
			}
		}
		return $keywords;
	}

	/**
	 * Return list of stopwords.
	 * FIXME Should this be locale-specific?
	 * @return array with stopwords as keys
	 */
	function &loadStopwords() {
		static $searchStopwords;

		if (!isset($searchStopwords)) {
			// Load stopwords only once per request (FIXME Cache?)
			$searchStopwords = array_count_values(array_filter(file(Config::getVar('general', 'registry_dir') . '/stopwords.txt'), create_function('&$a', 'return ($a = trim($a)) && !empty($a) && $a[0] != \'#\';')));
			$searchStopwords[''] = 1;
		}

		return $searchStopwords;
	}

	/**
	 * Index paper metadata.
	 * @param $paper Paper
	 */
	function indexPaperMetadata(&$paper) {
		// Build author keywords
		$authorText = array();
		$authors = $paper->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			$author =& $authors[$i];
			array_push($authorText, $author->getFirstName());
			array_push($authorText, $author->getMiddleName());
			array_push($authorText, $author->getLastName());
			array_push($authorText, $author->getAffiliation());
			$bios = $author->getBiography(null);
			if (is_array($bios)) foreach ($bios as $bio) { // Localized
				array_push($authorText, strip_tags($bio));
			}
		}

		// Update search index
		$paperId = $paper->getId();
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_AUTHOR, $authorText);
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_TITLE, $paper->getTitle(null));

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($paper->getTrackId());
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_ABSTRACT, $paper->getAbstract(null));
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_DISCIPLINE, $paper->getDiscipline(null));
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_SUBJECT, array_merge(array_values((array) $paper->getSubjectClass(null)), array_values((array) $paper->getSubject(null))));
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_TYPE, $paper->getType(null));
		PaperSearchIndex::updateTextIndex(
			$paperId,
			PAPER_SEARCH_COVERAGE,
			array_merge(
				array_values((array) $paper->getCoverageGeo(null)),
				array_values((array) $paper->getCoverageChron(null)),
				array_values((array) $paper->getCoverageSample(null))
			)
		);
		// FIXME Index sponsors too?
	}

	/**
	 * Index supp file metadata.
	 * @param $suppFile object
	 */
	function indexSuppFileMetadata(&$suppFile) {
		// Update search index
		$paperId = $suppFile->getPaperId();
		PaperSearchIndex::updateTextIndex(
			$paperId,
			PAPER_SEARCH_SUPPLEMENTARY_FILE,
			array_merge(
				array_values((array) $suppFile->getTitle(null)),
				array_values((array) $suppFile->getCreator(null)),
				array_values((array) $suppFile->getSubject(null)),
				array_values((array) $suppFile->getTypeOther(null)),
				array_values((array) $suppFile->getDescription(null)),
				array_values((array) $suppFile->getSource(null))
			),
			$suppFile->getFileId()
		);
	}

	/**
	 * Index all paper files (supplementary and galley).
	 * @param $paper Paper
	 */
	function indexPaperFiles(&$paper) {
		// Index supplementary files
		$fileDao =& DAORegistry::getDAO('SuppFileDAO');
		$files =& $fileDao->getSuppFilesByPaper($paper->getId());
		foreach ($files as $file) {
			if ($file->getFileId()) {
				PaperSearchIndex::updateFileIndex($paper->getId(), PAPER_SEARCH_SUPPLEMENTARY_FILE, $file->getFileId());
			}
			PaperSearchIndex::indexSuppFileMetadata($file);
		}
		unset($files);

		// Index galley files
		$fileDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$files =& $fileDao->getGalleysByPaper($paper->getId());
		foreach ($files as $file) {
			if ($file->getFileId()) {
				PaperSearchIndex::updateFileIndex($paper->getId(), PAPER_SEARCH_GALLEY_FILE, $file->getFileId());
			}
		}
	}

	/**
	 * Rebuild the search index for all conferences.
	 */
	function rebuildIndex($log = false) {
		// Clear index
		if ($log) echo 'Clearing index ... ';
		$searchDao =& DAORegistry::getDAO('PaperSearchDAO');
		// FIXME Abstract into PaperSearchDAO?
		$searchDao->update('DELETE FROM paper_search_object_keywords');
		$searchDao->update('DELETE FROM paper_search_objects');
		$searchDao->update('DELETE FROM paper_search_keyword_list');
		$searchDao->setCacheDir(Config::getVar('files', 'files_dir') . '/_db');
		$searchDao->_dataSource->CacheFlush();
		if ($log) echo "done\n";

		// Build index
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$paperDao =& DAORegistry::getDAO('PaperDAO');

		$schedConfs =& $schedConfDao->getSchedConfs();
		while (!$schedConfs->eof()) {
			$schedConf =& $schedConfs->next();
			$numIndexed = 0;

			if ($log) echo "Indexing \"", $schedConf->getFullTitle(), "\" ... ";

			$papers =& $paperDao->getPapersBySchedConfId($schedConf->getId());
			while (!$papers->eof()) {
				$paper =& $papers->next();
				if ($paper->getDateSubmitted()) {
					PaperSearchIndex::indexPaperMetadata($paper);
					PaperSearchIndex::indexPaperFiles($paper);
					$numIndexed++;
				}
				unset($paper);
			}

			if ($log) echo $numIndexed, " papers indexed\n";
			unset($schedConf);
		}
	}

}

?>
