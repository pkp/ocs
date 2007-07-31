<?php

/**
 * @file PaperHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.paper
 * @class PaperHandler
 *
 * Handle requests for paper functions. 
 *
 * $Id$
 */

import('rt.ocs.RTDAO');
import('rt.ocs.ConferenceRT');

class PaperHandler extends Handler {

	/**
	 * View Paper.
	 */
	function view($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($conference, $schedConf, $paper) = PaperHandler::validate($paperId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$conferenceRt = $rtDao->getConferenceRTByConference($conference);

		$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $paper->getPaperId());

		if (!$conferenceRt->getEnabled()) {
			if (!$galley || $galley->isHtmlGalley()) return PaperHandler::viewPaper($args);
			else if ($galley->isPdfGalley()) return PaperHandler::viewPDFInterstitial($args, $galley);
			else return PaperHandler::viewDownloadInterstitial($args, $galley);
		}

		if (!$paper) {
			Request::redirect(null, null, null, Request::getRequestedPage());
			return;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('paper/view.tpl');
	}

	/**
	 * Paper interstitial page before PDF is shown
	 */
	function viewPDFInterstitial($args, $galley = null) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $paper) = PaperHandler::validate($paperId, $galleyId);

		if (!$galley) {
			$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
			$galley = &$galleyDao->getGalley($galleyId, $paper->getPaperId());
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('paper/pdfInterstitial.tpl');
	}

	/**
	 * Paper interstitial page before a non-PDF, non-HTML galley is
	 * downloaded
	 */
	function viewDownloadInterstitial($args, $galley = null) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		list($conference, $schedConf, $paper) = PaperHandler::validate($paperId, $galleyId);

		if (!$galley) {
			$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
			$galley = &$galleyDao->getGalley($galleyId, $paper->getPaperId());
		}
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('paper/interstitial.tpl');
	}

	/**
	 * Paper view
	 */
	function viewPaper($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($conference, $schedConf, $paper) = PaperHandler::validate($paperId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$conferenceRt = $rtDao->getConferenceRTByConference($conference);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack($paper->getTrackId());

		if ($conferenceRt->getVersion()!=null && $conferenceRt->getDefineTerms()) {
			// Determine the "Define Terms" context ID.
			$version = $rtDao->getVersion($conferenceRt->getVersion(), $conferenceRt->getConferenceId());
			if ($version) foreach ($version->getContexts() as $context) {
				if ($context->getDefineTerms()) {
					$defineTermsContextId = $context->getContextId();
					break;
				}
			}
		}

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$enableComments = $schedConf->getSetting('enableComments', true);
		$commentsRequireRegistration = $schedConf->getSetting('commentsRequireRegistration', true);
		$commentsAllowAnonymous = $schedConf->getSetting('commentsAllowAnonymous', true);

		if ($enableComments) {
			$comments = &$commentDao->getRootCommentsByPaperId($paper->getPaperId());
		}

		$paperGalleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$galley = &$paperGalleyDao->getGalley($galleyId, $paper->getPaperId());

		$templateMgr = &TemplateManager::getManager();

		if (!$galley) {
			// Get the registration status if displaying the abstract;
			// if access is open, we can display links to the full text.
			import('schedConf.SchedConfAction');
			$templateMgr->assign('mayViewPaper', SchedConfAction::mayViewPapers($schedConf, $conference));
			$templateMgr->assign('registeredUser', SchedConfAction::registeredUser($schedConf));
			$templateMgr->assign('registeredDomain', SchedConfAction::registeredDomain($schedConf));

			// Increment the published paper's abstract views count
			$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
			$publishedPaperDao->incrementViewsByPaperId($paper->getPaperId());
		} else {
			// Increment the galley's views count
			$paperGalleyDao->incrementViews($galleyId);

			// Use the paper's CSS file, if set.
			if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
				$templateMgr->addStyleSheet(Request::url(null, 'paper', 'viewFile', array(
					$paper->getPaperId(),
					$galley->getGalleyId(),
					$styleFile->getFileId()
				)));
			}
		}

		$templateMgr->assign_by_ref('schedConf', $schedConf);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('track', $track);
		$templateMgr->assign('paperId', $paperId);

		$closeCommentsDate = $schedConf->getSetting('closeCommentsDate');
		$commentsClosed = $schedConf->getSetting('closeComments')?true:false && (strtotime($closeCommentsDate < time()));
		$templateMgr->assign('closeCommentsDate', $closeCommentsDate);
		$templateMgr->assign('commentsClosed', $commentsClosed);
		$templateMgr->assign('postingAllowed', $enableComments && (!$commentsRequireRegistration || Validation::isLoggedIn()) && !$commentsClosed);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('defineTermsContextId', isset($defineTermsContextId)?$defineTermsContextId:null);
		$templateMgr->assign('comments', isset($comments)?$comments:null);
		$templateMgr->display('paper/paper.tpl');	
	}

	/**
	 * Paper Reading tools
	 */
	function viewRST($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		list($conference, $schedConf, $paper) = PaperHandler::validate($paperId, $galleyId);

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$conferenceRt = $rtDao->getConferenceRTByConference($conference);

		// The RST needs to know whether this galley is HTML or not. Fetch the galley.
		$paperGalleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$galley = &$paperGalleyDao->getGalley($galleyId, $paper->getPaperId());
		
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$track = &$trackDao->getTrack($paper->getTrackId());

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign_by_ref('schedConf', $schedConf);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign_by_ref('track', $track);

		$templateMgr->assign('paperSearchByOptions', array(
			'' => 'search.allFields',
			PAPER_SEARCH_PRESENTER => 'search.presenter',
			PAPER_SEARCH_TITLE => 'paper.title',
			PAPER_SEARCH_ABSTRACT => 'search.abstract',
			PAPER_SEARCH_INDEX_TERMS => 'search.indexTerms',
			PAPER_SEARCH_GALLEY_FILE => 'search.fullText'
		));

		// Bring in comment constants.
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $schedConf->getSetting('enableComments', true);
		$commentsRequireRegistration = $schedConf->getSetting('commentsRequireRegistration', true);
		$commentsAllowAnonymous = $schedConf->getSetting('commentsAllowAnonymous', true);
		$closeCommentsDate = $schedConf->getSetting('closeCommentsDate');
		$commentsClosed = $schedConf->getSetting('closeComments')?true:false && (strtotime($closeCommentsDate < time()));
		$templateMgr->assign('closeCommentsDate', $closeCommentsDate);
		$templateMgr->assign('commentsClosed', $commentsClosed);
		$templateMgr->assign('postingAllowed', $enableComments && (!$commentsRequireRegistration || Validation::isLoggedIn()) && !$commentsClosed);
		$templateMgr->assign('postingDisabled', !$enableComments);

		$templateMgr->assign_by_ref('conferenceRt', $conferenceRt);
		if ($conferenceRt->getEnabled()) {
			$version = $rtDao->getVersion($conferenceRt->getVersion(), $conferenceRt->getConferenceId());
			if ($version) {
				$templateMgr->assign_by_ref('version', $version);
			}
		}

		$templateMgr->display('rt/rt.tpl');	
	}

	/**
	 * View a file (inlines file).
	 * @param $args array ($paperId, $galleyId, $fileId [optional])
	 */
	function viewFile($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $schedConf, $paper) = PaperHandler::validate($paperId, $galleyId);

		$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $paper->getPaperId());

		if (!$galley) Request::redirect(null, null, null, null, 'view', $paperId);

		if (!$fileId) {
			$galleyDao->incrementViews($galleyId);
			$fileId = $galley->getFileId();
		} else {
			if (!$galley->isDependentFile($fileId)) {
				Request::redirect(null, null, null, null, 'view', $paperId);
			}
		}

		// reuse track director's view file function
		import('submission.trackDirector.TrackDirectorAction');
		TrackDirectorAction::viewFile($paper->getPaperId(), $fileId);
	}

	/**
	 * Downloads the document
	 */
	function download($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int)$args[1] : 0;
		list($conference, $schedConf, $paper) = PaperHandler::validate($paperId, $galleyId);

		$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $paper->getPaperId());
		$galleyDao->incrementViews($galleyId);

		if ($paper && $galley) {
			import('file.PaperFileManager');
			$paperFileManager = &new PaperFileManager($paper->getPaperId());
			$paperFileManager->downloadFile($galley->getFileId());
		}
	}

	function downloadSuppFile($args) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$suppId = isset($args[1]) ? $args[1] : 0;
		list($conference, $schedConf, $paper) = PaperHandler::validate($paperId);

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		if ($schedConf->getSetting('enablePublicSuppFileId')) {
			$suppFile = &$suppFileDao->getSuppFileByBestSuppFileId($paper->getPaperId(), $suppId);
		} else {
			$suppFile = &$suppFileDao->getSuppFile((int) $suppId, $paper->getPaperId());
		}

		if ($paper && $suppFile) {
			import('file.PaperFileManager');
			$paperFileManager = &new PaperFileManager($paper->getPaperId());
			if ($suppFile->isInlineable()) {
				$paperFileManager->viewFile($suppFile->getFileId());
			} else {
				$paperFileManager->downloadFile($suppFile->getFileId());
			}
		}
	}

	/**
	 * Validation
	 */
	function validate($paperId, $galleyId = null) {

		list($conference, $schedConf) = parent::validate(true, true);

		$conferenceId = $conference->getConferenceId();
		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');

		if ($schedConf->getSetting('enablePublicPaperId')) {
			$paper = &$publishedPaperDao->getPublishedPaperByBestPaperId($schedConf->getSchedConfId(), $paperId);
		} else {
			$paper = &$publishedPaperDao->getPublishedPaperByPaperId((int) $paperId);
		}

		// if issue or paper do not exist, are not published, or are
		// not parts of the same conference, redirect to index.
		if (isset($schedConf) && isset($paper) && isset($conference) &&
				$paper->getSchedConfId() == $schedConf->getSchedConfId() &&
				$schedConf->getConferenceId() == $conference->getConferenceId()) {

			// Check if login is required for viewing.
			if (!Validation::isLoggedIn() && $schedConf->getSetting('restrictPaperAccess', true)) {
				Validation::redirectLogin();
			}
	
			import('schedConf.SchedConfAction');
			$mayViewPaper = SchedConfAction::mayViewPapers($schedConf, $conference);
			
			// Bar access to paper?
			if ((isset($galleyId) && $galleyId!=0) && !$mayViewPaper) {
				Request::redirect(null, null, null, 'index');	
			}
			
			// Bar access to abstract?
			if ((!isset($galleyId) || $galleyId==0) && !SchedConfAction::mayViewProceedings($schedConf)) {
				Request::redirect(null, null, null, 'index');	
			}
		} else {
			Request::redirect(null, null, null, 'index');
		}
		return array($conference, $schedConf, $paper);
	}

}

?>
