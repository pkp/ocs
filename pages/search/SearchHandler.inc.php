<?php

/**
 * SearchHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.search
 *
 * Handle site index requests. 
 *
 * $Id$
 */

import('search.PaperSearch');

class SearchHandler extends Handler {

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
		$templateMgr = &TemplateManager::getManager();
		
		if (Request::getConference() == null) {
			$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
			$conferences = &$conferenceDao->getEnabledConferenceTitles();  //Enabled added
			$templateMgr->assign('siteSearch', true);
			$templateMgr->assign('conferenceOptions', array('' => Locale::Translate('search.allConferences')) + $conferences);
		}
		
		SearchHandler::assignAdvancedSearchParameters($templateMgr);

		$templateMgr->display('search/advancedSearch.tpl');
	}
	
	/**
	 * Show index of published papers by author.
	 */
	function authors($args) {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$conference =& Request::getConference();
		$event =& Request::getEvent();

		$authorDao = &DAORegistry::getDAO('AuthorDAO');

		if (isset($args[0]) && $args[0] == 'view') {
			// View a specific author
			$firstName = Request::getUserVar('firstName');
			$middleName = Request::getUserVar('middleName');
			$lastName = Request::getUserVar('lastName');
			$affiliation = Request::getUserVar('affiliation');

			$publishedPapers = $authorDao->getPublishedPapersForAuthor($event?$event->getEventId():null, $firstName, $middleName, $lastName, $affiliation);

			// Load information associated with each paper.
			$events = array();
			$tracks = array();
			$eventsUnavailable = array();

			$trackDao = &DAORegistry::getDAO('TrackDAO');
			$eventDao = &DAORegistry::getDAO('EventDAO');

			foreach ($publishedPapers as $paper) {
				$trackId = $paper->getTrackId();
				$eventId = $paper->getEventId();
				
				if (!isset($events[$eventId])) {
					import('event.EventAction');
					$event = &$eventDao->getEvent($eventId);
					$events[$eventId] = &$event;
					$eventsUnavailable[$eventId] = EventAction::registrationRequired($event) && (!EventAction::registeredUser($event) && !EventAction::registeredDomain($event));
				}
				if (!isset($tracks[$trackId])) $tracks[$trackId] = &$trackDao->getTrack($trackId);
			}

			if (empty($publishedPapers)) {
				Request::redirect(null, null, Request::getRequestedPage());
			}

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign_by_ref('publishedPapers', $publishedPapers);
			$templateMgr->assign_by_ref('events', $events);
			$templateMgr->assign('eventsUnavailable', $eventsUnavailable);
			$templateMgr->assign_by_ref('tracks', $tracks);
			$templateMgr->assign('firstName', $firstName);
			$templateMgr->assign('middleName', $middleName);
			$templateMgr->assign('lastName', $lastName);
			$templateMgr->assign('affiliation', $affiliation);
			$templateMgr->display('search/authorDetails.tpl');
		} else {
			// Show the author index
			$searchInitial = Request::getUserVar('searchInitial');
			$rangeInfo = Handler::getRangeInfo('authors');

			$authors = &$authorDao->getAuthorsAlphabetizedByEvent(
				isset($event)?$event->getEventId():null,
				$searchInitial,
				$rangeInfo
			);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('searchInitial', $searchInitial);
			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
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

		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');

		$rangeInfo = Handler::getRangeInfo('search');

		$paperIds = &$publishedPaperDao->getPublishedPaperIdsAlphabetizedByTitle(isset($event)?$event->getConferenceId():null, $rangeInfo);
		$totalResults = count($paperIds);
		$paperIds = array_slice($paperIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		$results = &new VirtualArrayIterator(PaperSearch::formatResults($paperIds), $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->display('search/titleIndex.tpl');
	}

	/**
	 * Show index of published papers by event.
	 */
	function events($args) {
		parent::validate();
		SearchHandler::setupTemplate(true);

		$conference =& Request::getConference();
		$event =& Request::getEvent();

		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');

		$rangeInfo = Handler::getRangeInfo('search');

		$paperIds = &$publishedPaperDao->getPublishedPaperIdsAlphabetizedByEvent(isset($event)?$event->getEventId():null, $rangeInfo);
		$totalResults = count($paperIds);
		$paperIds = array_slice($paperIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		$results = &new VirtualArrayIterator(PaperSearch::formatResults($paperIds), $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		$templateMgr->display('search/eventIndex.tpl');
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
			$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
			$conference = &$conferenceDao->getConference($searchConferenceId);
		} else {
			$conference =& Request::getConference();
		}

		$searchType = Request::getUserVar('searchField');
		if (!is_numeric($searchType)) $searchType = null;

		// Load the keywords array with submitted values
		$keywords = array($searchType => PaperSearch::parseQuery(Request::getUserVar('query')));

		$results = &PaperSearch::retrieveResults($conference, $keywords, null, null, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
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

		$searchConferenceId = Request::getUserVar('searchConference');
		if (!empty($searchConferenceId)) {
			$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
			$conference = &$conferenceDao->getConference($searchConferenceId);
		} else {
			$conference =& Request::getConference();
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

		$results = &PaperSearch::retrieveResults($conference, $keywords, $fromDate, $toDate, $rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('results', $results);
		SearchHandler::assignAdvancedSearchParameters($templateMgr);

		$templateMgr->display('search/searchResults.tpl');
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::validate();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'user.searchAndBrowse');
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array(Request::url(null, null, 'search'), 'navigation.search'))
				: array()
		);
	}

	function assignAdvancedSearchParameters(&$templateMgr) {
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
	}
}

?>
