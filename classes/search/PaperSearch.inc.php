<?php

/**
 * @defgroup search
 */
 
/**
 * @file PaperSearch.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperSearch
 * @ingroup search
 * @see PaperSeachDAO, PaperSearchIndex
 *
 * @brief Class for retrieving paper search results.
 *
 * FIXME: NEAR; precedence w/o parens?; stemming; weighted counting
 */

//$Id$

// Search types
define('PAPER_SEARCH_AUTHOR',		0x00000001);
define('PAPER_SEARCH_TITLE',			0x00000002);
define('PAPER_SEARCH_ABSTRACT',			0x00000004);
define('PAPER_SEARCH_DISCIPLINE',		0x00000008);
define('PAPER_SEARCH_SUBJECT',			0x00000010);
define('PAPER_SEARCH_TYPE',			0x00000020);
define('PAPER_SEARCH_COVERAGE',			0x00000040);
define('PAPER_SEARCH_GALLEY_FILE',		0x00000080);
define('PAPER_SEARCH_SUPPLEMENTARY_FILE',	0x00000100);
define('PAPER_SEARCH_INDEX_TERMS',		0x00000078);

import('search.PaperSearchIndex');

class PaperSearch {

	/**
	 * Parses a search query string.
	 * Supports +/-, AND/OR, parens
	 * @param $query
	 * @return array of the form ('+' => <required>, '' => <optional>, '-' => excluded)
	 */
	function parseQuery($query) {
		$count = preg_match_all('/(\+|\-|)("[^"]+"|\(|\)|[^\s\)]+)/', $query, $matches);
		$pos = 0;
		$keywords = PaperSearch::_parseQuery($matches[1], $matches[2], $pos, $count);
		return $keywords;
	}

	/**
	 * Query parsing helper routine.
	 * Returned structure is based on that used by the Search::QueryParser Perl module.
	 */
	function _parseQuery($signTokens, $tokens, &$pos, $total) {
		$return = array('+' => array(), '' => array(), '-' => array());
		$postBool = $preBool = '';

		$notOperator = String::strtolower(__('search.operator.not'));
		$andOperator = String::strtolower(__('search.operator.and'));
		$orOperator = String::strtolower(__('search.operator.or'));
		while ($pos < $total) {
			if (!empty($signTokens[$pos])) $sign = $signTokens[$pos];
			else if (empty($sign)) $sign = '+';
			$token = String::strtolower($tokens[$pos++]);
			switch ($token) {
				case $notOperator:
					$sign = '-';
					break;
				case ')':
					return $return;
				case '(':
					$token = PaperSearch::_parseQuery($signTokens, $tokens, $pos, $total);
				default:
					$postBool = '';
					if ($pos < $total) {
						$peek = String::strtolower($tokens[$pos]);
						if ($peek == $orOperator) {
							$postBool = 'or';
							$pos++;
						} else if ($peek == $andOperator) {
							$postBool = 'and';
							$pos++;
						}
					}
					$bool = empty($postBool) ? $preBool : $postBool;
					$preBool = $postBool;
					if ($bool == 'or') $sign = '';
					if (is_array($token)) $k = $token;
					else $k = PaperSearchIndex::filterKeywords($token, true);
					if (!empty($k)) $return[$sign][] = $k;
					$sign = '';
					break;
			}
		}
		return $return;
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 */
	function &_getMergedArray(&$conference, &$keywords, $publishedFrom, $publishedTo, &$resultCount) {
		$resultsPerKeyword = Config::getVar('search', 'results_per_keyword');
		$resultCacheHours = Config::getVar('search', 'result_cache_hours');
		if (!is_numeric($resultsPerKeyword)) $resultsPerKeyword = 100;
		if (!is_numeric($resultCacheHours)) $resultCacheHours = 24;

		$mergedKeywords = array('+' => array(), '' => array(), '-' => array());
		foreach ($keywords as $type => $keyword) {
			if (!empty($keyword['+']))
				$mergedKeywords['+'][] = array('type' => $type, '+' => $keyword['+'], '' => array(), '-' => array());
			if (!empty($keyword['']))
				$mergedKeywords[''][] = array('type' => $type, '+' => array(), '' => $keyword[''], '-' => array());
			if (!empty($keyword['-']))
				$mergedKeywords['-'][] = array('type' => $type, '+' => array(), '' => $keyword['-'], '-' => array());
		}
		$mergedResults =& PaperSearch::_getMergedKeywordResults($conference, $mergedKeywords, null, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);

		$resultCount = count($mergedResults);
		return $mergedResults;
	}

	/**
	 * Recursive helper for _getMergedArray.
	 */
	function &_getMergedKeywordResults(&$conference, &$keyword, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours) {
		$mergedResults = null;

		if (isset($keyword['type'])) {
			$type = $keyword['type'];
		}

		foreach ($keyword['+'] as $phrase) {
			$results =& PaperSearch::_getMergedPhraseResults($conference, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
			if ($mergedResults == null) {
				$mergedResults = $results;
			} else {
				foreach ($mergedResults as $paperId => $count) {
					if (isset($results[$paperId])) {
						$mergedResults[$paperId] += $results[$paperId];
					} else {
						unset($mergedResults[$paperId]);
					}
				}
			}
		}

		if ($mergedResults == null) {
			$mergedResults = array();
		}

		if (!empty($mergedResults) || empty($keyword['+'])) {
			foreach ($keyword[''] as $phrase) {
				$results =& PaperSearch::_getMergedPhraseResults($conference, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
				foreach ($results as $paperId => $count) {
					if (isset($mergedResults[$paperId])) {
						$mergedResults[$paperId] += $count;
					} else if (empty($keyword['+'])) {
						$mergedResults[$paperId] = $count;
					}
				}
			}

			foreach ($keyword['-'] as $phrase) {
				$results =& PaperSearch::_getMergedPhraseResults($conference, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
				foreach ($results as $paperId => $count) {
					if (isset($mergedResults[$paperId])) {
						unset($mergedResults[$paperId]);
					}
				}
			}
		}

		return $mergedResults;
	}

	/**
	 * Recursive helper for _getMergedArray.
	 */
	function &_getMergedPhraseResults(&$conference, &$phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours) {
		if (isset($phrase['+'])) {
			$mergedResults =& PaperSearch::_getMergedKeywordResults($conference, $phrase, $type, $publishedFrom, $publishedTo, $resultsPerKeyword, $resultCacheHours);
			return $mergedResults;
		}

		$mergedResults = array();
		$paperSearchDao =& DAORegistry::getDAO('PaperSearchDAO');
		$results =& $paperSearchDao->getPhraseResults(
			$conference,
			$phrase,
			$publishedFrom,
			$publishedTo,
			$type,
			$resultsPerKeyword,
			$resultCacheHours
		);
		while (!$results->eof()) {
			$result =& $results->next();
			$paperId = $result['paper_id'];
			if (!isset($mergedResults[$paperId])) {
				$mergedResults[$paperId] = $result['count'];
			} else {
				$mergedResults[$paperId] += $result['count'];
			}
		}
		return $mergedResults;
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 */
	function &_getSparseArray(&$mergedResults, $resultCount) {
		$results = array();
		$i = 0;
		foreach ($mergedResults as $paperId => $count) {
				$frequencyIndicator = ($resultCount * $count) + $i++;
				$results[$frequencyIndicator] = $paperId;
		}
		krsort($results);
		return $results;
	}

	/**
	 * See implementation of retrieveResults for a description of this
	 * function.
	 * Note that this function is also called externally to fetch
	 * results for the title index, and possibly elsewhere.
	 */
	function &formatResults(&$results) {
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$trackDao =& DAORegistry::getDAO('TrackDAO');

		$publishedPaperCache = array();
		$paperCache = array();
		$schedConfCache = array();
		$schedConfAvailabilityCache = array();
		$conferenceCache = array();
		$trackCache = array();

		$returner = array();
		foreach ($results as $paperId) {
			// Get the paper, storing in cache if necessary.
			if (!isset($paperCache[$paperId])) {
				$publishedPaperCache[$paperId] =& $publishedPaperDao->getPublishedPaperByPaperId($paperId);
				$paperCache[$paperId] =& $paperDao->getPaper($paperId);
			}
			unset($paper, $publishedPaper);
			$paper =& $paperCache[$paperId];
			$publishedPaper =& $publishedPaperCache[$paperId];

			if ($publishedPaper && $paper) {
				$trackId = $paper->getTrackId();
				if (!isset($trackCache[$trackId])) {
					$trackCache[$trackId] =& $trackDao->getTrack($trackId);
				}

				// Get the conference, storing in cache if necessary.
				$schedConfId = $publishedPaper->getSchedConfId();
				$schedConf =& $schedConfDao->getSchedConf($schedConfId);
				$conferenceId = $schedConf->getConferenceId();
				if (!isset($conferenceCache[$conferenceId])) {
					$conferenceCache[$conferenceId] = $conferenceDao->getConference($conferenceId);
				}

				// Get the scheduled conference, storing in cache if necessary.
				if (!isset($schedConfCache[$schedConfId])) {
					$schedConfCache[$schedConfId] =& $schedConf;
					import('schedConf.SchedConfAction');
					$schedConfAvailabilityCache[$schedConfId] = SchedConfAction::mayViewProceedings($schedConf);
				}

				// Store the retrieved objects in the result array.
				if($schedConfAvailabilityCache[$schedConfId]) {
					$returner[] = array(
						'paper' => &$paper,
						'publishedPaper' => &$publishedPaperCache[$paperId],
						'schedConf' => &$schedConfCache[$schedConfId],
						'conference' => &$conferenceCache[$conferenceId],
						'schedConfAvailable' => $schedConfAvailabilityCache[$schedConfId],
						'track' => &$trackCache[$trackId]
					);
				}
			}
		}
		return $returner;
	}

	/**
	 * Return an array of search results matching the supplied
	 * keyword IDs in decreasing order of match quality.
	 * Keywords are supplied in an array of the following format:
	 * $keywords[PAPER_SEARCH_AUTHOR] = array('John', 'Doe');
	 * $keywords[PAPER_SEARCH_...] = array(...);
	 * $keywords[null] = array('Matches', 'All', 'Fields');
	 * @param $conference object The conference to search
	 * @param $keywords array List of keywords
	 * @param $publishedFrom object Search-from date
	 * @param $publishedTo object Search-to date
	 * @param $rangeInfo Information on the range of results to return
	 */
	function &retrieveResults(&$conference, &$keywords, $publishedFrom = null, $publishedTo = null, $rangeInfo = null) {
		// Fetch all the results from all the keywords into one array
		// (mergedResults), where mergedResults[paper_id]
		// = sum of all the occurences for all keywords associated with
		// that paper ID.
		// resultCount contains the sum of result counts for all keywords.
		$mergedResults =& PaperSearch::_getMergedArray($conference, $keywords, $publishedFrom, $publishedTo, $resultCount);

		// Convert mergedResults into an array (frequencyIndicator =>
		// $paperId).
		// The frequencyIndicator is a synthetically-generated number,
		// where higher is better, indicating the quality of the match.
		// It is generated here in such a manner that matches with
		// identical frequency do not collide.
		$results =& PaperSearch::_getSparseArray($mergedResults, $resultCount);

		$totalResults = count($results);

		// Use only the results for the specified page, if specified.
		if ($rangeInfo && $rangeInfo->isValid()) {
			$results = array_slice(
				$results,
				$rangeInfo->getCount() * ($rangeInfo->getPage()-1),
				$rangeInfo->getCount()
			);
			$page = $rangeInfo->getPage();
			$itemsPerPage = $rangeInfo->getCount();
		} else {
			$page = 1;
			$itemsPerPage = max($totalResults, 1);
		}

		// Take the range of results and retrieve the Paper, Conference,
		// and associated objects.
		$results =& PaperSearch::formatResults($results);

		// Return the appropriate iterator.
		import('core.VirtualArrayIterator');
		$returner = new VirtualArrayIterator($results, $totalResults, $page, $itemsPerPage);
		return $returner;
	}
}

?>
