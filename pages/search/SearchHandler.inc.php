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

import('classes.search.PaperSearch');
import('classes.handler.Handler');

class SearchHandler extends Handler {
	/**
	 * Constructor
	 */
	function SearchHandler() {
		parent::Handler();
	}

	/**
	 * Show the advanced form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		parent::validate();
		$this->advanced($args, $request);
	}

	/**
	 * Show the advanced form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function search($args, &$request) {
		parent::validate();
		$this->advanced($args, $request);
	}

	/**
	 * Show advanced search form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function advanced($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, false);
		$templateMgr =& TemplateManager::getManager($request);
		$publishedPaperDao = DAORegistry::getDAO('PublishedPaperDAO');

		if ($request->getConference() == null) {
			$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
			$conferences =& $conferenceDao->getNames(true); // Enabled added
			$templateMgr->assign('siteSearch', true);
			$templateMgr->assign('conferenceOptions', array('' => AppLocale::Translate('search.allConferences')) + $conferences);
			$yearRange = $publishedPaperDao->getPaperYearRange(null);
		} else {
			$conference =& $request->getConference();
			$yearRange = $publishedPaperDao->getPaperYearRange($conference->getId());
		}	

		$this->_assignAdvancedSearchParameters($request, $templateMgr, $yearRange);

		$templateMgr->display('search/advancedSearch.tpl');
	}

	/**
	 * Show index of published papers by author.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function authors($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		$authorDao = DAORegistry::getDAO('AuthorDAO');

		if (isset($args[0]) && $args[0] == 'view') {
			// View a specific author
			$firstName = $request->getUserVar('firstName');
			$middleName = $request->getUserVar('middleName');
			$lastName = $request->getUserVar('lastName');
			$affiliation = $request->getUserVar('affiliation');
			$country = $request->getUserVar('country');

			$schedConf =& $request->getSchedConf();
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

			$trackDao = DAORegistry::getDAO('TrackDAO');
			$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
			$conferenceDao = DAORegistry::getDAO('ConferenceDAO');

			foreach ($publishedPapers as $paper) {
				$trackId = $paper->getTrackId();
				$schedConfId = $paper->getSchedConfId();

				if (!isset($schedConfs[$schedConfId])) {
					import('classes.schedConf.SchedConfAction');
					$schedConf = $schedConfDao->getById($schedConfId);
					$schedConfs[$schedConfId] =& $schedConf;
					$schedConfsUnavailable[$schedConfId] = !SchedConfAction::mayViewProceedings($schedConf);
					unset($schedConf);
				}
				if (!isset($tracks[$trackId])) {
					$tracks[$trackId] =& $trackDao->getTrack($trackId);
				}

				$conferenceId = $schedConfs[$schedConfId]->getConferenceId();
				if (!isset($conferences[$conferenceId])) {
					$conferences[$conferenceId] =& $conferenceDao->getById($conferenceId);
				}
			}

			if (empty($publishedPapers)) {
				$request->redirect(null, null, $request->getRequestedPage());
			}

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign_by_ref('publishedPapers', $publishedPapers);
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->assign_by_ref('schedConfs', $schedConfs);
			$templateMgr->assign('schedConfsUnavailable', $schedConfsUnavailable);
			$templateMgr->assign_by_ref('tracks', $tracks);
			$templateMgr->assign('firstName', $firstName);
			$templateMgr->assign('middleName', $middleName);
			$templateMgr->assign('lastName', $lastName);
			$templateMgr->assign('affiliation', $affiliation);

			$countryDao = DAORegistry::getDAO('CountryDAO');
			$country = $countryDao->getCountry($country);
			$templateMgr->assign('country', $country);

			$templateMgr->display('search/authorDetails.tpl');
		} else {
			// Show the author index
			$searchInitial = $request->getUserVar('searchInitial');
			$rangeInfo = $this->getRangeInfo($request, 'authors');

			$schedConf =& $request->getSchedConf();

			$authors =& $authorDao->getAuthorsAlphabetizedBySchedConf(
				isset($schedConf)?$schedConf->getId():null,
				$searchInitial,
				$rangeInfo
			);

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));
			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign_by_ref('authors', $authors);
			$templateMgr->display('search/authorIndex.tpl');
		}
	}

	/**
	 * Show index of published papers by title.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function titles($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		import('classes.schedConf.SchedConfAction');

		$publishedPaperDao = DAORegistry::getDAO('PublishedPaperDAO');
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');

		$rangeInfo = $this->getRangeInfo($request, 'search');

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
				$schedConf = $schedConfDao->getById($schedConfId);
				$schedConfAbstractPermissions[$schedConfId] = SchedConfAction::mayViewProceedings($schedConf);
				$schedConfPaperPermissions[$schedConfId] = SchedConfAction::mayViewPapers($schedConf, $conference);
			}

			if($schedConfAbstractPermissions[$schedConfId]) {
				$paperIds[] = $paperId;
			}
		}

		$totalResults = count($paperIds);
		$paperIds = array_slice($paperIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$results = new VirtualArrayIterator(PaperSearch::formatResults($paperIds), $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->display('search/titleIndex.tpl');
	}

	/**
	 * Show index of published papers by scheduled conference.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function schedConfs($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');

		$conferences =& $conferenceDao->getConferences(true);
		$conferenceIndex = array();
		$schedConfIndex = array();
		while ($conference =& $conferences->next()) {
			$conferenceId = $conference->getId();
			$conferenceIndex[$conferenceId] =& $conference;
			$schedConfsIterator = $schedConfDao->getAll(true, $conferenceId);
			$schedConfIndex[$conferenceId] =& $schedConfsIterator->toArray();
			unset($schedConfsIterator);
			unset($conference);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('conferenceIndex', $conferenceIndex);
		$templateMgr->assign_by_ref('schedConfIndex', $schedConfIndex);
		$templateMgr->display('search/schedConfIndex.tpl');
	}

	/**
	 * Show basic search results.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function results($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		$rangeInfo = $this->getRangeInfo($request, 'search');

		$searchConferenceId = $request->getUserVar('searchConference');
		if (!empty($searchConferenceId)) {
			$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
			$conference =& $conferenceDao->getById($searchConferenceId);
		} else {
			$conference =& $request->getConference();
		}

		$searchType = $request->getUserVar('searchField');
		if (!is_numeric($searchType)) $searchType = null;

		// Load the keywords array with submitted values
		$keywords = array($searchType => PaperSearch::parseQuery($request->getUserVar('query')));

		$results =& PaperSearch::retrieveResults($conference, $keywords, null, null, $rangeInfo);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->assign('basicQuery', $request->getUserVar('query'));
		$templateMgr->assign('searchField', $request->getUserVar('searchField'));
		$templateMgr->display('search/searchResults.tpl');
	}

	/**
	 * Show advanced search results.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function advancedResults($args, &$request) {
		parent::validate();
		$this->setupTemplate($request, true);

		$rangeInfo = $this->getRangeInfo($request, 'search');

		$publishedPaperDao = DAORegistry::getDAO('PublishedPaperDAO');
		$searchConferenceId = $request->getUserVar('searchConference');
		if (!empty($searchConferenceId)) {
			$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
			$conference =& $conferenceDao->getById($searchConferenceId);
			$yearRange = $publishedPaperDao->getPaperYearRange($conference->getId());
		} else {
			$conference =& $request->getConference();
			$yearRange = $publishedPaperDao->getPaperYearRange(null);
		}

		// Load the keywords array with submitted values
		$keywords = array(null => PaperSearch::parseQuery($request->getUserVar('query')));
		$keywords[PAPER_SEARCH_AUTHOR] = PaperSearch::parseQuery($request->getUserVar('author'));
		$keywords[PAPER_SEARCH_TITLE] = PaperSearch::parseQuery($request->getUserVar('title'));
		$keywords[PAPER_SEARCH_DISCIPLINE] = PaperSearch::parseQuery($request->getUserVar('discipline'));
		$keywords[PAPER_SEARCH_SUBJECT] = PaperSearch::parseQuery($request->getUserVar('subject'));
		$keywords[PAPER_SEARCH_TYPE] = PaperSearch::parseQuery($request->getUserVar('type'));
		$keywords[PAPER_SEARCH_COVERAGE] = PaperSearch::parseQuery($request->getUserVar('coverage'));
		$keywords[PAPER_SEARCH_GALLEY_FILE] = PaperSearch::parseQuery($request->getUserVar('fullText'));
		$keywords[PAPER_SEARCH_SUPPLEMENTARY_FILE] = PaperSearch::parseQuery($request->getUserVar('supplementaryFiles'));

		$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$results =& PaperSearch::retrieveResults($conference, $keywords, $fromDate, $toDate, $rangeInfo);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->setCacheability(CACHEABILITY_NO_STORE);
		$templateMgr->assign_by_ref('results', $results);
		$this->_assignAdvancedSearchParameters($request, $templateMgr, $yearRange);

		$templateMgr->display('search/searchResults.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate(&$request, $subclass = false) {
		parent::setupTemplate($request);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('helpTopicId', 'user.searchBrowse');
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array($request->url(null, null, 'search'), 'navigation.search'))
				: array()
		);

		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
	}

	/**
	 * Preserve advanced search parameters for search revisions.
	 * @param $request PKPRequest
	 * @param $templateMgr TemplateManager
	 * @param $yearRange array
	 */
	function _assignAdvancedSearchParameters(&$request, &$templateMgr, $yearRange) {
		$templateMgr->assign('query', $request->getUserVar('query'));
		$templateMgr->assign('searchConference', $request->getUserVar('searchConference'));
		$templateMgr->assign('author', $request->getUserVar('author'));
		$templateMgr->assign('title', $request->getUserVar('title'));
		$templateMgr->assign('fullText', $request->getUserVar('fullText'));
		$templateMgr->assign('supplementaryFiles', $request->getUserVar('supplementaryFiles'));
		$templateMgr->assign('discipline', $request->getUserVar('discipline'));
		$templateMgr->assign('subject', $request->getUserVar('subject'));
		$templateMgr->assign('type', $request->getUserVar('type'));
		$templateMgr->assign('coverage', $request->getUserVar('coverage'));
		$fromMonth = $request->getUserVar('dateFromMonth');
		$fromDay = $request->getUserVar('dateFromDay');
		$fromYear = $request->getUserVar('dateFromYear');
		$templateMgr->assign('dateFromMonth', $fromMonth);
		$templateMgr->assign('dateFromDay', $fromDay);
		$templateMgr->assign('dateFromYear', $fromYear);
		if (!empty($fromYear)) $templateMgr->assign('dateFrom', date('Y-m-d H:i:s',mktime(0,0,0,$fromMonth==null?12:$fromMonth,$fromDay==null?31:$fromDay,$fromYear)));

		$toMonth = $request->getUserVar('dateToMonth');
		$toDay = $request->getUserVar('dateToDay');
		$toYear = $request->getUserVar('dateToYear');
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
