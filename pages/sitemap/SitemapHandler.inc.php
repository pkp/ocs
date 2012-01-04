<?php

/**
 * @file SitemapHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SitemapHandler
 * @ingroup pages_sitemap
 *
 * @brief Produce a sitemap in XML format for submitting to search engines. 
 */

// $Id$


import('xml.XMLCustomWriter');
import('handler.Handler');

define('SITEMAP_XSD_URL', 'http://www.sitemaps.org/schemas/sitemap/0.9');

class SitemapHandler extends Handler {
	/**
	 * Generate an XML sitemap for webcrawlers
	 */
	function index() {
		if (Request::getRequestedConferencePath() == 'index') {
			$doc = SitemapHandler::createSitemapIndex();
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: inline; filename=\"sitemap_index.xml\"");
			XMLCustomWriter::printXML($doc);
		} else {
			if(Request::getRequestedSchedConfPath() == 'index') {
				// At conference level
				$doc = SitemapHandler::createConfSitemap();
				header("Content-Type: application/xml");
				header("Cache-Control: private");
				header("Content-Disposition: inline; filename=\"sitemap.xml\"");
				XMLCustomWriter::printXML($doc);
			} else {
				// At scheduled conference level
				$doc = SitemapHandler::createSchedConfSitemap();
				header("Content-Type: application/xml");
				header("Cache-Control: private");
				header("Content-Disposition: inline; filename=\"sitemap.xml\"");
				XMLCustomWriter::printXML($doc);
			}
		}
	}
	
	/**
	 * Construct a sitemap index listing each conference's individual sitemap
	 * @return XMLNode
	 */
	function createSitemapIndex() {
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		
		$doc =& XMLCustomWriter::createDocument();
		$root =& XMLCustomWriter::createElement($doc, 'sitemapindex');
		XMLCustomWriter::setAttribute($root, 'xmlns', SITEMAP_XSD_URL);

		$conferences =& $conferenceDao->getConferences();
		while ($conference =& $conferences->next()) {
			$sitemapUrl = Request::url($conference->getPath(), 'index', 'sitemap');
			$sitemap =& XMLCustomWriter::createElement($doc, 'sitemap');
			XMLCustomWriter::createChildWithText($doc, $sitemap, 'loc', $sitemapUrl, false);
			XMLCustomWriter::appendChild($root, $sitemap);
		
			$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conference->getId());
			while ($schedConf =& $schedConfs->next()) {
				$sitemapUrl = Request::url($conference->getPath(), $schedConf->getPath(), 'sitemap');
				$sitemap =& XMLCustomWriter::createElement($doc, 'sitemap');
				XMLCustomWriter::createChildWithText($doc, $sitemap, 'loc', $sitemapUrl, false);
				XMLCustomWriter::appendChild($root, $sitemap);
				unset($schedConf);
			}
			unset($conference);
		}
		
		XMLCustomWriter::appendChild($doc, $root);
		return $doc;
	}
		
	/**
	 * Construct a sitemap for a conference
	 * @return XMLNode
	 */
	function createConfSitemap() {		
		$conference =& Request::getConference();
		$conferenceId = $conference->getId();
		
		$doc =& XMLCustomWriter::createDocument();
		$root =& XMLCustomWriter::createElement($doc, 'urlset');
		XMLCustomWriter::setAttribute($root, 'xmlns', SITEMAP_XSD_URL);
		
		// Conf. Home 
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), 'index', 'index')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), 'index', 'schedConfs', 'archive')));
		
		XMLCustomWriter::appendChild($doc, $root);
		return $doc;
	}
	
	 /**
	 * Construct a sitemap for a scheduled conference
	 * @return XMLNode
	 */
	function createSchedConfSitemap() {		
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		
		$conference =& Request::getConference();
		$conferenceId = $conference->getId();
		$schedConf = Request::getSchedConf();

		$doc =& XMLCustomWriter::createDocument();
		$root =& XMLCustomWriter::createElement($doc, 'urlset');
		XMLCustomWriter::setAttribute($root, 'xmlns', SITEMAP_XSD_URL);

		// Sched. Conf. home
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath())));
		// About page
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'about')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'about', 'submissions')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'about', 'siteMap')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'about', 'aboutThisPublishingSystem')));
		// Search
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'search')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'search', 'authors')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'search', 'titles')));
		// Conference Information
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'schedConf', 'overview')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'schedConf', 'trackPolicies')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'schedConf', 'presentations')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'schedConf', 'accommodation')));
		XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'schedConf', 'organizingTeam')));
		// Individual Papers
		$publishedPapers =& $publishedPaperDao->getPublishedPapers($schedConf->getId());
		while ($paper =& $publishedPapers->next()) {
			// Abstract
			XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'view', $paper->getId())));
			// Galley files
			$galleys = $galleyDao->getGalleysByPaper($paper->getId());
			foreach ($galleys as $galley) {
				XMLCustomWriter::appendChild($root, SitemapHandler::createUrlTree($doc, Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'view', array($paper->getId(), $galley->getId()))));
			}
		}
			
		XMLCustomWriter::appendChild($doc, $root);
		return $doc;
	}
	
	/**
	 * Create a url entry with children
	 * @param $doc XMLNode Reference to the XML document object
	 * @param $loc string URL of page (required)
	 * @param $lastmod string Last modification date of page (optional)
	 * @param $changefreq Frequency of page modifications (optional)
	 * @param $priority string Subjective priority assesment of page (optional) 
	 * @return XMLNode
	 */
	function createUrlTree(&$doc, $loc, $lastmod = null, $changefreq = null, $priority = null) {		
		$url =& XMLCustomWriter::createElement($doc, 'url');
		
		XMLCustomWriter::createChildWithText($doc, $url, htmlentities('loc'), $loc, false);
		XMLCustomWriter::createChildWithText($doc, $url, 'lastmod', $lastmod, false);
		XMLCustomWriter::createChildWithText($doc, $url, 'changefreq', $changefreq, false);
		XMLCustomWriter::createChildWithText($doc, $url, 'priority', $priority, false);
		
		return $url;
	}
	
}

?>
