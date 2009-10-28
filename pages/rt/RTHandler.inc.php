<?php

/**
 * @file RTHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTHandler
 * @ingroup pages_rt
 *
 * @brief Handle Reading Tools requests. 
 *
 */

// $Id$


import('rt.RT');

import('rt.ocs.RTDAO');
import('rt.ocs.ConferenceRT');

import('paper.PaperHandler');

class RTHandler extends PaperHandler {
	/**
	 * Constructor
	 **/
	function RTHandler() {
		parent::PaperHandler();
	}
	
	/**
	 * Display a author biography
	 */
	function bio($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);

		if (!$conferenceRt || !$conferenceRt->getAuthorBio()) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('rt/bio.tpl');
	}

	/**
	 * Display the paper metadata
	 */
	function metadata($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;


		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);

		if (!$conferenceRt || !$conferenceRt->getViewMetadata()) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($paper->getTrackId());

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('conferenceRt', $conferenceRt);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign_by_ref('schedConf', $schedConf);
		$templateMgr->assign_by_ref('track', $track);
		if($schedConf)
			$templateMgr->assign_by_ref('conferenceSettings', $schedConf->getSettings());
		else
			$templateMgr->assign_by_ref('conferenceSettings', $conference->getSettings());
		$templateMgr->display('rt/metadata.tpl');
	}

	/**
	 * Display an RT search context
	 */
	function context($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$contextId = Isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);

		$context =& $rtDao->getContext($contextId);
		if ($context) $version =& $rtDao->getVersion($context->getVersionId(), $conference->getId());

		if (!$context || !$version || !$conferenceRt || $conferenceRt->getVersion()==null || $conferenceRt->getVersion() != $context->getVersionId()) {
			Request::redirect(null, null, 'paper', 'view', array($paperId, $galleyId));
		}

		// Deal with the post and URL parameters for each search
		// so that the client browser can properly submit the forms
		// with a minimum of client-side processing.
		$searches = array();
		// Some searches use parameters other than the "default" for
		// the search (i.e. keywords, author name, etc). If additional
		// parameters are used, they should be displayed as part of the
		// form for ALL searches in that context.
		$searchParams = array();
		foreach ($context->getSearches() as $search) {
			$params = array();
			$searchParams += RTHandler::getParameterNames($search->getSearchUrl());
			if ($search->getSearchPost()) {
				$searchParams += RTHandler::getParameterNames($search->getSearchPost());
				$postParams = explode('&', $search->getSearchPost());
				foreach ($postParams as $param) {
					// Split name and value from each parameter
					$nameValue = explode('=', $param);
					if (!isset($nameValue[0])) break;

					$name = trim($nameValue[0]);
					$value = trim(isset($nameValue[1])?$nameValue[1]:'');
					if (!empty($name)) $params[] = array('name' => $name, 'value' => $value);
				}
			}

			$search->postParams = $params;
			$searches[] = $search;
		}

		// Remove duplicate extra form elements and get their values
		$searchParams = array_unique($searchParams);
		$searchValues = array();

		foreach ($searchParams as $key => $param) switch ($param) {
			case 'author':
				$searchValues[$param] = $paper->getAuthorString();
				break;
			case 'coverageGeo':
				$searchValues[$param] = $paper->getLocalizedCoverageGeo();
				break;
			case 'title':
				$searchValues[$param] = $paper->getLocalizedTitle();
				break;
			default:
				// UNKNOWN parameter! Remove it from the list.
				unset($searchParams[$key]);
				break;
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign_by_ref('version', $version);
		$templateMgr->assign_by_ref('context', $context);
		$templateMgr->assign_by_ref('searches', $searches);
		$templateMgr->assign('searchParams', $searchParams);
		$templateMgr->assign('searchValues', $searchValues);
		$templateMgr->assign('defineTerm', Request::getUserVar('defineTerm'));
		$templateMgr->assign('keywords', explode(';', $paper->getLocalizedSubject()));
		$templateMgr->assign('coverageGeo', $paper->getLocalizedCoverageGeo());
		$templateMgr->assign_by_ref('conferenceSettings', $conference->getSettings());
		$templateMgr->display('rt/context.tpl');
	}

	/**
	 * Display citation information
	 */
	function captureCite($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$citeType = isset($args[2]) ? $args[2] : null;

		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);

		if (!$conferenceRt || !$conferenceRt->getCaptureCite()) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('conferenceRt', $conferenceRt);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign_by_ref('schedConf', $schedConf);
		$templateMgr->assign_by_ref('paper', $paper);

		$templateMgr->assign_by_ref('conferenceSettings', $conference->getSettings());

		$citationPlugins =& PluginRegistry::loadCategory('citationFormats');
		uasort($citationPlugins, create_function('$a, $b', 'return strcmp($a->getDisplayName(), $b->getDisplayName());'));
		$templateMgr->assign_by_ref('citationPlugins', $citationPlugins);
		if (isset($citationPlugins[$citeType])) {
			// A citation type has been selected; display citation.
			$citationPlugin =& $citationPlugins[$citeType];
		} else {
			// No citation type chosen; choose a default off the top of the list.
			$citationPlugin = $citationPlugins[array_shift(array_keys($citationPlugins))];
		}
		$citationPlugin->cite($paper);
	}

	/**
	 * Display a printer-friendly version of the paper
	 */
	function printerFriendly($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);

		if (!$conferenceRt || !$conferenceRt->getPrinterFriendly()) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $paperGalleyDao->getGalley($galleyId, $paper->getPaperId());

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($paper->getTrackId());

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign_by_ref('track', $track);
		$templateMgr->assign_by_ref('schedConf', $schedConf);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('rt/printerFriendly.tpl');	
	}

	/**
	 * Display the "Email Colleague" form
	 */
	function emailColleague($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);
		$user =& Request::getUser();

		if (!$conferenceRt || !$conferenceRt->getEmailOthers() || !$user) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		import('mail.MailTemplate');
		$email = new MailTemplate('EMAIL_LINK');

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!Request::getUserVar('continued')) {
				$primaryAuthor = $paper->getAuthors();
				$primaryAuthor = $primaryAuthor[0];

				$email->setSubject('[' . $schedConf->getLocalizedSetting('acronym') . '] ' . strip_tags($paper->getLocalizedTitle()));
				$email->assignParams(array(
					'paperTitle' => strip_tags($paper->getLocalizedTitle()),
					'schedConf' => $schedConf->getSchedConfTitle(),
					'authorName' => $primaryAuthor->getFullName(),
					'paperUrl' => Request::url(null, null, 'paper', 'view', $paper->getBestPaperId())
				));
			}
			$email->displayEditForm(Request::url(null, null, null, 'emailColleague', array($paperId, $galleyId)), null, 'rt/email.tpl', array('op' => 'emailColleague'));
		}
	}

	/**
	 * Display the "email author"
	 */
	function emailAuthor($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);
		$user =& Request::getUser();

		if (!$conferenceRt || !$conferenceRt->getEmailAuthor() || !$user) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		import('mail.MailTemplate');
		$email = new MailTemplate();

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->display('rt/sent.tpl');
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject('[' . $schedConf->getLocalizedSetting('acronym') . '] ' . strip_tags($paper->getLocalizedTitle()));
				$authors =& $paper->getAuthors();
				$author =& $authors[0];
				$email->addRecipient($author->getEmail(), $author->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, null, 'emailAuthor', array($paperId, $galleyId)), null, 'rt/email.tpl', array('op' => 'emailAuthor'));
		}
	}

	/**
	 * Display a list of supplementary files
	 */
	function suppFiles($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);

		if (!$conferenceRt || !$conferenceRt->getSupplementaryFiles()) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('conferenceRt', $conferenceRt);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign_by_ref('conferenceSettings', $conference->getSettings());
		$templateMgr->display('rt/suppFiles.tpl');
	}

	/**
	 * Display the metadata of a supplementary file
	 */
	function suppFileMetadata($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$suppFileId = isset($args[2]) ? (int) $args[2] : 0;
		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $paper->getPaperId());

		if (!$conferenceRt || !$conferenceRt->getSupplementaryFiles() || !$suppFile) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('suppFile', $suppFile);
		$templateMgr->assign_by_ref('conferenceRt', $conferenceRt);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign_by_ref('conferenceSettings', $conference->getSettings());
		$templateMgr->display('rt/suppFileView.tpl');
	}

	/**
	 * Display the "finding references" search engine list
	 */
	function findingReferences($args) {
		$this->setupTemplate();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$this->validate($paperId, $galleyId);
		$conference =& Request::getConference();
		$paper =& $this->paper;
 
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt =& $rtDao->getConferenceRTByConference($conference);
 
		if (!$conferenceRt || !$conferenceRt->getFindingReferences()) {
			Request::redirect(null, null, Request::getRequestedPage());
		}
 
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('conferenceRt', $conferenceRt);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->display('rt/findingReferences.tpl');
	}

	/**
	 * Get parameter values: Used internally for RT searches
	 */
	function getParameterNames($value) {
		$matches = null;
		String::regexp_match_all('/\{\$([a-zA-Z0-9]+)\}/', $value, $matches);
		// Remove the entire string from the matches list
		return $matches[1];
	}
}

?>
