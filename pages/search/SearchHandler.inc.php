<?php

/**
 * @file SearchHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SearchHandler
 * @ingroup pages_search
 *
 * @brief Handle site index requests. 
 *
 */

// $Id$

import('search.PaperSearch');
import('handler.Handler');

class SearchHandler extends Handler {
	/**
	 * Constructor
	 **/
	function SearchHandler() {
		parent::Handler();
	}

	/**
	 * Show the advanced form
	 */
	function index() {
		parent::validate();
		SearchHandler::advanced();
	}

	/**
	 * Show the advanced form
	 */
	function search() {
		parent::validate();
		SearchHandler::advanced();
	}

	/**
	 * Show advanced search form.
	 */
	function advanced() {
		parent::validate();
		SearchHandler::setupTemplate(false);
		$templateMgr =& TemplateManager::getManager();
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');

		if (Request::getConference() == null) {
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
			$conferences =& $conferenceDao->getEnabledConferenceTitles();  //Enabled added
			$templateMgr->assign('siteSearch', true);
			$templateMgr->assign('conferenceOptions', array('' => AppLocale::Translate('search.allConferences')) + $conferences);
			$yearRange = $publishedPaperDao->getPaperYearRange(null);
		} else {
			$conference =& Request::getConference();
			$yearRange = $publishedPaperDao->getPaperYearRange($conference->getId());
		}	

		SearchHandler::assignAdvancedSearchParameters($templateMgr, $yearRange);

		$templateMgr->display('search/advancedSearch.tpl');
	}

	/**
	 * Show index of published papers by author.
	 */
	function authors($args) {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$authorDao =& DAORegistry::getDAO('AuthorDAO');

		if (isset($args[0]) && $args[0] == 'view') {
			// View a specific author
			$firstName = Request::getUserVar('firstName');
			$middleName = Request::getUserVar('middleName');
			$lastName = Request::getUserVar('lastName');
			$affiliation = Request::getUserVar('affiliation');
			$country = Request::getUserVar('country');

			$schedConf =& Request::getSchedConf();
			$publishedPapers = $authorDao->getPublishedPapersForAuthor(
				$schedConf?$schedConf->getId():null,
				$firstName,
				$middleName,
				$lastName,
				$affiliation,
				$country
			);
			unset($schedConf);

			// Load information associated with each paper.
			$conferences = array();
			$schedConfs = array();
			$tracks = array();
			$schedConfsUnavailable = array();

			$trackDao =& DAORegistry::getDAO('TrackDAO');
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');

			foreach ($publishedPapers as $paper) {
				$trackId = $paper->getTrackId();
				$schedConfId = $paper->getSchedConfId();

				if (!isset($schedConfs[$schedConfId])) {
					import('schedConf.SchedConfAction');
					$schedConf =& $schedConfDao->getSchedConf($schedConfId);
					$schedConfs[$schedConfId] =& $schedConf;
					$schedConfsUnavailable[$schedConfId] = !SchedConfAction::mayViewProceedings($schedConf);
					unset($schedConf);
				}
				if (!isset($tracks[$trackId])) {
					$tracks[$trackId] =& $trackDao->getTrack($trackId);
				}

				$conferenceId = $schedConfs[$schedConfId]->getConferenceId();
				if (!isset($conferences[$conferenceId])) {
					$conferences[$conferenceId] =& $conferenceDao->getConference($conferenceId);
				}
			}

			if (empty($publishedPapers)) {
				Request::redirect(null, null, Request::getRequestedPage());
			}

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign_by_ref('publishedPapers', $publishedPapers);
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->assign_by_ref('schedConfs', $schedConfs);
			$templateMgr->assign('schedConfsUnavailable', $schedConfsUnavailable);
			$templateMgr->assign_by_ref('tracks', $tracks);
			$templateMgr->assign('firstName', $firstName);
			$templateMgr->assign('middleName', $middleName);
			$templateMgr->assign('lastName', $lastName);
			$templateMgr->assign('affiliation', $affiliation);

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$country = $countryDao->getCountry($country);
			$templateMgr->assign('country', $country);

			$templateMgr->display('search/authorDetails.tpl');
		} else {
			// Show the author index
			$searchInitial = Request::getUserVar('searchInitial');
			$rangeInfo = Handler::getRangeInfo('authors');

			$schedConf =& Request::getSchedConf();

			$authors =& $authorDao->getAuthorsAlphabetizedBySchedConf(
				isset($schedConf)?$schedConf->getId():null,
				$searchInitial,
				$rangeInfo
			);

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('searchInitial', Request::getUserVar('searchInitial'));
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign_by_ref('authors', $authors);
			$templateMgr->display('search/authorIndex.tpl');
		}
	}

	/**
	 * Show index of published papers by title.
	 */
	function titles($args) {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		import('schedConf.SchedConfAction');

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		$rangeInfo = Handler::getRangeInfo('search');

		$allPaperIds =& $publishedPaperDao->getPublishedPaperIdsAlphabetizedByTitle(
			$conference? $conference->getId():null,
			$schedConf?$schedConf->getId():null,
			$rangeInfo);

		// FIXME: this is horribly inefficient.
		$paperIds = array();
		$schedConfAbstractPermissions = array();
		$schedConfPaperPermissions = array();
		foreach($allPaperIds as $paperId) {
			$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($paperId);
			if (!$publishedPaper) continue; // Bonehead check (e.g. cached IDs)
			$schedConfId = $publishedPaper->getSchedConfId();

			if(!isset($schedConfAbstractPermissions[$schedConfId])) {
				unset($schedConf);
				$schedConf =& $schedConfDao->getSchedConf($schedConfId);
				$schedConfAbstractPermissions[$schedConfId] = SchedConfAction::mayViewProceedings($schedConf);
				$schedConfPaperPermissions[$schedConfId] = SchedConfAction::mayViewPapers($schedConf, $conference);
			}

			if($schedConfAbstractPermissions[$schedConfId]) {
				$paperIds[] = $paperId;
			}
		}

		$totalResults = count($paperIds);
		$paperIds = array_slice($paperIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		import('core.VirtualArrayIterator');
		$results = new VirtualArrayIterator(PaperSearch::formatResults($paperIds), $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->display('search/titleIndex.tpl');
	}

	/**
	 * Show index of published papers by scheduled conference.
	 */
	function schedConfs($args) {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		$conferences =& $conferenceDao->getEnabledConferences();
		$conferenceIndex = array();
		$schedConfIndex = array();
		while ($conference =& $conferences->next()) {
			$conferenceId = $conference->getId();
			$conferenceIndex[$conferenceId] =& $conference;
			$schedConfsIterator =& $schedConfDao->getSchedConfsByConferenceId($conferenceId);
			$schedConfIndex[$conferenceId] =& $schedConfsIterator->toArray();
			unset($schedConfsIterator);
			unset($conference);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('conferenceIndex', $conferenceIndex);
		$templateMgr->assign_by_ref('schedConfIndex', $schedConfIndex);
		$templateMgr->display('search/schedConfIndex.tpl');
	}

	/**
	 * Show basic search results.
	 */
	function results() {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$rangeInfo = Handler::getRangeInfo('search');

		$searchConferenceId = Request::getUserVar('searchConference');
		if (!empty($searchConferenceId)) {
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
			$conference =& $conferenceDao->getConference($searchConferenceId);
		} else {
			$conference =& Request::getConference();
		}

		$searchType = Request::getUserVar('searchField');
		if (!is_numeric($searchType)) $searchType = null;

		// Load the keywords array with submitted values
		$keywords = array($searchType => PaperSearch::parseQuery(Request::getUserVar('query')));

		$results =& PaperSearch::retrieveResults($conference, $keywords, null, null, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->assign('basicQuery', Request::getUserVar('query'));
		$templateMgr->assign('searchField', Request::getUserVar('searchField'));
		$templateMgr->display('search/searchResults.tpl');
	}

	/**
	 * Show advanced search results.
	 */
	function advancedResults() {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$rangeInfo = Handler::getRangeInfo('search');

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$searchConferenceId = Request::getUserVar('searchConference');
		if (!empty($searchConferenceId)) {
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
			$conference =& $conferenceDao->getConference($searchConferenceId);
			$yearRange = $publishedPaperDao->getPaperYearRange($conference->getId());
		} else {
			$conference =& Request::getConference();
			$yearRange = $publishedPaperDao->getPaperYearRange(null);
		}

		// Load the keywords array with submitted values
		$keywords = array(null => PaperSearch::parseQuery(Request::getUserVar('query')));
		$keywords[PAPER_SEARCH_AUTHOR] = PaperSearch::parseQuery(Request::getUserVar('author'));
		$keywords[PAPER_SEARCH_TITLE] = PaperSearch::parseQuery(Request::getUserVar('title'));
		$keywords[PAPER_SEARCH_DISCIPLINE] = PaperSearch::parseQuery(Request::getUserVar('discipline'));
		$keywords[PAPER_SEARCH_SUBJECT] = PaperSearch::parseQuery(Request::getUserVar('subject'));
		$keywords[PAPER_SEARCH_TYPE] = PaperSearch::parseQuery(Request::getUserVar('type'));
		$keywords[PAPER_SEARCH_COVERAGE] = PaperSearch::parseQuery(Request::getUserVar('coverage'));
		$keywords[PAPER_SEARCH_GALLEY_FILE] = PaperSearch::parseQuery(Request::getUserVar('fullText'));
		$keywords[PAPER_SEARCH_SUPPLEMENTARY_FILE] = PaperSearch::parseQuery(Request::getUserVar('supplementaryFiles'));

		$fromDate = Request::getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = Request::getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$results =& PaperSearch::retrieveResults($conference, $keywords, $fromDate, $toDate, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_NO_STORE);
		$templateMgr->assign_by_ref('results', $results);
		SearchHandler::assignAdvancedSearchParameters($templateMgr, $yearRange);

		$templateMgr->display('search/searchResults.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'user.searchBrowse');
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, null, 'search'), 'navigation.search'))
				: array()
		);

		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
	}

	function assignAdvancedSearchParameters(&$templateMgr, $yearRange) {
		$templateMgr->assign('query', Request::getUserVar('query'));
		$templateMgr->assign('searchConference', Request::getUserVar('searchConference'));
		$templateMgr->assign('author', Request::getUserVar('author'));
		$templateMgr->assign('title', Request::getUserVar('title'));
		$templateMgr->assign('fullText', Request::getUserVar('fullText'));
		$templateMgr->assign('supplementaryFiles', Request::getUserVar('supplementaryFiles'));
		$templateMgr->assign('discipline', Request::getUserVar('discipline'));
		$templateMgr->assign('subject', Request::getUserVar('subject'));
		$templateMgr->assign('type', Request::getUserVar('type'));
		$templateMgr->assign('coverage', Request::getUserVar('coverage'));
		$fromMonth = Request::getUserVar('dateFromMonth');
		$fromDay = Request::getUserVar('dateFromDay');
		$fromYear = Request::getUserVar('dateFromYear');
		$templateMgr->assign('dateFromMonth', $fromMonth);
		$templateMgr->assign('dateFromDay', $fromDay);
		$templateMgr->assign('dateFromYear', $fromYear);
		if (!empty($fromYear)) $templateMgr->assign('dateFrom', date('Y-m-d H:i:s',mktime(0,0,0,$fromMonth==null?12:$fromMonth,$fromDay==null?31:$fromDay,$fromYear)));

		$toMonth = Request::getUserVar('dateToMonth');
		$toDay = Request::getUserVar('dateToDay');
		$toYear = Request::getUserVar('dateToYear');
		$templateMgr->assign('dateToMonth', $toMonth);
		$templateMgr->assign('dateToDay', $toDay);
		$templateMgr->assign('dateToYear', $toYear);
		if (!empty($toYear)) $templateMgr->assign('dateTo', date('Y-m-d H:i:s',mktime(0,0,0,$toMonth==null?12:$toMonth,$toDay==null?31:$toDay,$toYear)));
	
		$startYear = substr($yearRange[1], 0, 4);
		$endYear = substr($yearRange[0], 0, 4);
		$templateMgr->assign('endYear', $endYear);
		$templateMgr->assign('startYear', $startYear);
	}
}

?>
