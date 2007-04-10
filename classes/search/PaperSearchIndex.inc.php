<?php

/**
 * PaperSearchIndex.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 *
 * Class to add content to the paper search index.
 *
 * $Id$
 */

import('search.SearchFileParser');
import('search.SearchHTMLParser');
import('search.SearchHelperParser');
import('search.PaperSearch');

define('SEARCH_STOPWORDS_FILE', 'registry/stopwords.txt');

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
		$searchDao = &DAORegistry::getDAO('PaperSearchDAO');
		$keywords = &PaperSearchIndex::filterKeywords($text);
		for ($i = 0, $count = count($keywords); $i < $count; $i++) {
			$searchDao->insertObjectKeyword($objectId, $keywords[$i], $position);
			$position += 1;
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
			$searchDao = &DAORegistry::getDAO('PaperSearchDAO');
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
		$fileMgr = &new PaperFileManager($paperId);
		$file = &$fileMgr->getFile($fileId);
		
		if (isset($file)) {
			$parser = &SearchFileParser::fromFile($file);
		}
			
		if (isset($parser)) {
			if ($parser->open()) {
				$searchDao = &DAORegistry::getDAO('PaperSearchDAO');
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
		$searchDao = &DAORegistry::getDAO('PaperSearchDAO');
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
		$stopwords = &PaperSearchIndex::loadStopwords();
		
		// Remove punctuation
		if (is_array($text)) {
			$text = join("\n", $text);
		}
		
		$cleanText = preg_replace('/[!"\#\$%\'\(\)\.\?@\[\]\^`\{\}~]/', '', $text);
		$cleanText = preg_replace('/[\+,:;&\/<=>\|\\\]/', ' ', $cleanText);
		$cleanText = preg_replace('/[\*]/', $allowWildcards ? '%' : ' ', $cleanText);
		$cleanText = String::strtolower($cleanText);
		
		// Split into words
		$words = preg_split('/\s+/', $cleanText);
		
		// FIXME Do not perform further filtering for some fields, e.g., presenter names?
		
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
			$searchStopwords = array_count_values(array_filter(file(SEARCH_STOPWORDS_FILE), create_function('&$a', 'return ($a = trim($a)) && !empty($a) && $a[0] != \'#\';')));
			$searchStopwords[''] = 1;
		}
		
		return $searchStopwords;
	}
	
	/**
	 * Index paper metadata.
	 * @param $paper Paper
	 */
	function indexPaperMetadata(&$paper) {
		// Build presenter keywords
		$presenterText = array();
		$presenters = $paper->getPresenters();
		for ($i=0, $count=count($presenters); $i < $count; $i++) {
			$presenter = &$presenters[$i];
			array_push($presenterText, $presenter->getFirstName());
			array_push($presenterText, $presenter->getMiddleName());
			array_push($presenterText, $presenter->getLastName());
			array_push($presenterText, $presenter->getAffiliation());
			array_push($presenterText, strip_tags($presenter->getBiography()));
		}
		
		// Update search index
		$paperId = $paper->getPaperId();
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_PRESENTER, $presenterText);
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_TITLE, array($paper->getTitle(), $paper->getTitleAlt1(), $paper->getTitleAlt2()));

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack($paper->getTrackId());
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_ABSTRACT, array($paper->getAbstract(), $paper->getAbstractAlt1(), $paper->getAbstractAlt2()));
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_DISCIPLINE, $paper->getDiscipline());
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_SUBJECT, array($paper->getSubjectClass(), $paper->getSubject()));
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_TYPE, $paper->getType());
		PaperSearchIndex::updateTextIndex($paperId, PAPER_SEARCH_COVERAGE, array($paper->getCoverageGeo(), $paper->getCoverageChron(), $paper->getCoverageSample()));
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
			array(
				$suppFile->getTitle(),
				$suppFile->getCreator(),
				$suppFile->getSubject(),
				$suppFile->getTypeOther(),
				$suppFile->getDescription(),
				$suppFile->getSource()
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
		$fileDao = &DAORegistry::getDAO('SuppFileDAO');
		$files = &$fileDao->getSuppFilesByPaper($paper->getPaperId());
		foreach ($files as $file) {
			if ($file->getFileId()) {
				PaperSearchIndex::updateFileIndex($paper->getPaperId(), PAPER_SEARCH_SUPPLEMENTARY_FILE, $file->getFileId());
			}
			PaperSearchIndex::indexSuppFileMetadata($file);
		}
		unset($files);
		
		// Index galley files
		$fileDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$files = &$fileDao->getGalleysByPaper($paper->getPaperId());
		foreach ($files as $file) {
			if ($file->getFileId()) {
				PaperSearchIndex::updateFileIndex($paper->getPaperId(), PAPER_SEARCH_GALLEY_FILE, $file->getFileId());
			}
		}
	}
	
	/**
	 * Rebuild the search index for all conferences.
	 */
	function rebuildIndex($log = false) {
		// Clear index
		if ($log) echo 'Clearing index ... ';
		$searchDao = &DAORegistry::getDAO('PaperSearchDAO');
		// FIXME Abstract into PaperSearchDAO?
		$searchDao->update('DELETE FROM paper_search_object_keywords');
		$searchDao->update('DELETE FROM paper_search_objects');
		$searchDao->update('DELETE FROM paper_search_keyword_list');
		$searchDao->setCacheDir(Config::getVar('files', 'files_dir') . '/_db');
		$searchDao->_dataSource->CacheFlush();
		if ($log) echo "done\n";
		
		// Build index
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		
		$conferences = &$conferenceDao->getConferences();
		while (!$conferences->eof()) {
			$conference = &$conferences->next();
			$numIndexed = 0;
			
			if ($log) echo "Indexing \"", $conference->getTitle(), "\" ... ";
			
			$papers = &$paperDao->getPapersByConferenceId($conference->getConferenceId());
			while (!$papers->eof()) {
				$paper = &$papers->next();
				if ($paper->getDateSubmitted()) {
					PaperSearchIndex::indexPaperMetadata($paper);
					PaperSearchIndex::indexPaperFiles($paper);
					$numIndexed++;
				}
				unset($paper);
			}
			
			if ($log) echo $numIndexed, " papers indexed\n";
			unset($conference);
		}
	}
	
}

?>
