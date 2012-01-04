<?php

/**
 * @file PaperHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperHandler
 * @ingroup pages_paper
 *
 * Handle requests for paper functions.
 *
 */

// $Id$


import('rt.ocs.RTDAO');
import('rt.ocs.ConferenceRT');
import('handler.Handler');

class PaperHandler extends Handler {
	/** the paper associated with this request **/
	var $paper;

	/**
	 * Constructor
	 * @param $request Request
	 */
	function PaperHandler(&$request) {
		parent::Handler($request);

		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorSchedConf($this));
	}

	/**
	 * View Paper.
	 * @param $args array
	 * @param $request Request
	 */
	function view($args, &$request) {
		$router =& $request->getRouter();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($request, $paperId, $galleyId);
		$conference =& $router->getContext($request, CONTEXT_CONFERENCE);
		$paper =& $this->paper;
		$this->setupTemplate();

		// manager.scheduler.building / manager.scheduler.room
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OCS_MANAGER));

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt = $rtDao->getConferenceRTByConference($conference);

		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $paper->getId());

		if (!$conferenceRt->getEnabled()) {
			if (!$galley || $galley->isHtmlGalley()) return PaperHandler::viewPaper($args, $request);
			else if ($galley->isPdfGalley()) return PaperHandler::viewPDFInterstitial($args, $request, $galley);
			else return PaperHandler::viewDownloadInterstitial($args, $request, $galley);
		}

		if (!$paper) {
			$request->redirect(null, null, null, $router->getRequestedPage($request));
			return;
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('paper/view.tpl');
	}

	/**
	 * Paper interstitial page before PDF is shown
	 * @param $args array
	 * @param $request Request
	 * @param $galley PaperGalley
	 */
	function viewPDFInterstitial($args, &$request, $galley = null) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$this->validate($request, $paperId, $galleyId);
		$paper =& $this->paper;
		$this->setupTemplate();

		if (!$galley) {
			$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
			$galley =& $galleyDao->getGalley($galleyId, $paper->getId());
		}

		if (!$galley) $request->redirect(null, null, 'view', $paperId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('paper/pdfInterstitial.tpl');
	}

	/**
	 * Paper interstitial page before a non-PDF, non-HTML galley is
	 * downloaded
	 * @param $args array
	 * @param $request Request
	 * @param $galley PaperGalley
	 */
	function viewDownloadInterstitial($args, &$request, $galley = null) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($request, $paperId, $galleyId);
		$paper =& $this->paper;
		$this->setupTemplate();

		if (!$galley) {
			$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
			$galley =& $galleyDao->getGalley($galleyId, $paper->getId());
		}

		if (!$galley) $request->redirect(null, null, 'view', $paperId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);

		$templateMgr->display('paper/interstitial.tpl');
	}

	/**
	 * Paper view
	 * @param $args array
	 * @param $request Request
	 */
	function viewPaper($args, &$request) {
		$router =& $request->getRouter();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($request, $paperId, $galleyId);
		$conference =& $router->getContext($request, CONTEXT_CONFERENCE);
		$schedConf =& $router->getContext($request, CONTEXT_SCHED_CONF);
		$paper =& $this->paper;
		$this->setupTemplate();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt = $rtDao->getConferenceRTByConference($conference);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($paper->getTrackId());

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

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		$enableComments = $conference->getSetting('enableComments');
		$commentsRequireRegistration = $conference->getSetting('commentsRequireRegistration');
		$commentsAllowAnonymous = $conference->getSetting('commentsAllowAnonymous');

		if ($enableComments && $paper->getEnableComments()) {
			$comments =& $commentDao->getRootCommentsByPaperId($paper->getId());
		}

		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $paperGalleyDao->getGalley($galleyId, $paper->getId());

		$templateMgr =& TemplateManager::getManager();

		if (!$galley) {
			// Get the registration status if displaying the abstract;
			// if access is open, we can display links to the full text.
			import('schedConf.SchedConfAction');
			$templateMgr->assign('mayViewPaper', SchedConfAction::mayViewPapers($schedConf, $conference));
			$templateMgr->assign('registeredUser', SchedConfAction::registeredUser($schedConf));
			$templateMgr->assign('registeredDomain', SchedConfAction::registeredDomain($schedConf));

			// Increment the published paper's abstract views count
			$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
			$publishedPaperDao->incrementViewsByPaperId($paper->getId());
		} else {
			// Increment the galley's views count
			$paperGalleyDao->incrementViews($galleyId);

			// Use the paper's CSS file, if set.
			if ($galley->isHTMLGalley() && $styleFile =& $galley->getStyleFile()) {
				$templateMgr->addStyleSheet($router->url($request, null, null, 'paper', 'viewFile', array(
					$paper->getId(),
					$galley->getId(),
					$styleFile->getFileId()
				)));
			}
		}

		// Add font sizer js and css if not already in header
		$additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');
		if (strpos(strtolower($additionalHeadData), 'sizer.js') === false) {
			$additionalHeadData .= $templateMgr->fetch('common/sizer.tpl');
			$templateMgr->assign('additionalHeadData', $additionalHeadData);
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
		$templateMgr->assign('postingAllowed', ($enableComments && !$commentsClosed && $paper->getEnableComments()) && (!$commentsRequireRegistration || Validation::isLoggedIn()));
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('defineTermsContextId', isset($defineTermsContextId)?$defineTermsContextId:null);
		$templateMgr->assign('comments', isset($comments)?$comments:null);

		if ($paper->getRoomId()) {
			$roomDao =& DAORegistry::getDAO('RoomDAO');
			$buildingDao =& DAORegistry::getDAO('BuildingDAO');

			$room =& $roomDao->getRoom($paper->getRoomId());
			if (!$room) break;

			$building =& $buildingDao->getBuilding($room->getBuildingId());
			$templateMgr->assign_by_ref('room', $room);
			$templateMgr->assign_by_ref('building', $building);
		}

		$templateMgr->display('paper/paper.tpl');
	}

	/**
	 * Paper Reading tools
	 * @param $args array
	 * @param $request Request
	 */
	function viewRST($args, &$request) {
		$router =& $request->getRouter();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;

		$this->validate($request, $paperId, $galleyId);

		$conference =& $router->getContext($request, CONTEXT_CONFERENCE);
		$schedConf =& $router->getContext($request, CONTEXT_SCHED_CONF);
		$paper =& $this->paper;
		$this->setupTemplate();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conferenceRt = $rtDao->getConferenceRTByConference($conference);

		// The RST needs to know whether this galley is HTML or not. Fetch the galley.
		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $paperGalleyDao->getGalley($galleyId, $paper->getId());

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$track =& $trackDao->getTrack($paper->getTrackId());

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('schedConf', $schedConf);
		$templateMgr->assign_by_ref('paper', $paper);
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign_by_ref('galley', $galley);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign_by_ref('track', $track);

		$templateMgr->assign('paperSearchByOptions', array(
			'' => 'search.allFields',
			PAPER_SEARCH_AUTHOR => 'search.author',
			PAPER_SEARCH_TITLE => 'paper.title',
			PAPER_SEARCH_ABSTRACT => 'search.abstract',
			PAPER_SEARCH_INDEX_TERMS => 'search.indexTerms',
			PAPER_SEARCH_GALLEY_FILE => 'search.fullText'
		));

		// Bring in comment constants.
		$commentDao =& DAORegistry::getDAO('CommentDAO');

		$enableComments = $conference->getSetting('enableComments');
		$commentsRequireRegistration = $conference->getSetting('commentsRequireRegistration');
		$commentsAllowAnonymous = $conference->getSetting('commentsAllowAnonymous');

		$closeCommentsDate = $schedConf->getSetting('closeCommentsDate');
		$commentsClosed = $schedConf->getSetting('closeComments')?true:false && (strtotime($closeCommentsDate < time()));

		$postingAllowed = $enableComments && $paper->getEnableComments() && !$commentsClosed ? true : false;
		$templateMgr->assign('closeCommentsDate', $closeCommentsDate);
		$templateMgr->assign('commentsClosed', $commentsClosed);
		$templateMgr->assign('postingAllowed', $postingAllowed);
		$templateMgr->assign('postingDisabled', $postingAllowed && ($commentsRequireRegistration && !Validation::isLoggedIn()));

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
	 * @param $request Request
	 */
	function viewFile($args, &$request) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? $args[1] : 0;
		$fileId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($request, $paperId, $galleyId);
		$paper =& $this->paper;
		$this->setupTemplate();

		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $paper->getId());

		if (!$galley) $request->redirect(null, null, null, null, 'view', $paperId);

		if (!$fileId) {
			$galleyDao->incrementViews($galleyId);
			$fileId = $galley->getFileId();
		} else {
			if (!$galley->isDependentFile($fileId)) {
				$request->redirect(null, null, null, null, 'view', $paperId);
			}
		}

		// reuse track director's view file function
		import('submission.trackDirector.TrackDirectorAction');
		TrackDirectorAction::viewFile($paper->getId(), $fileId);
	}

	/**
	 * Downloads the document
	 * @param $args array
	 * @param $request Request
	 */
	function download($args, &$request) {
		$paperId = isset($args[0]) ? $args[0] : 0;
		$galleyId = isset($args[1]) ? (int)$args[1] : 0;

		$this->validate($request, $paperId, $galleyId);
		$paper =& $this->paper;

		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $paper->getId());
		$galleyDao->incrementViews($galleyId);

		if ($paper && $galley) {
			import('file.PaperFileManager');
			$paperFileManager = new PaperFileManager($paper->getId());
			$paperFileManager->downloadFile($galley->getFileId());
		}
	}

	/**
	 * Downloads a supplementary file
	 * @param $args array
	 * @param $request Request
	 */
	function downloadSuppFile($args, &$request) {
		$router =& $request->getRouter();
		$paperId = isset($args[0]) ? $args[0] : 0;
		$suppId = isset($args[1]) ? $args[1] : 0;

		$schedConf =& $router->getContext($request, CONTEXT_SCHED_CONF);

		$this->validate($request, $paperId);
		$paper =& $this->paper;

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		if ($schedConf->getSetting('enablePublicSuppFileId')) {
			$suppFile =& $suppFileDao->getSuppFileByBestSuppFileId($paper->getId(), $suppId);
		} else {
			$suppFile =& $suppFileDao->getSuppFile((int) $suppId, $paper->getId());
		}

		if ($paper && $suppFile) {
			import('file.PaperFileManager');
			$paperFileManager = new PaperFileManager($paper->getId());
			if ($suppFile->isInlineable()) {
				$paperFileManager->viewFile($suppFile->getFileId());
			} else {
				$paperFileManager->downloadFile($suppFile->getFileId());
			}
		}
	}

	/**
	 * Validation
	 * @see lib/pkp/classes/handler/PKPHandler#validate()
	 * @param $request Request
	 * @param $paperId integer
	 * @param $galleyId integer
	 */
	function validate(&$request, $paperId, $galleyId = null) {
		$router =& $request->getRouter();
		parent::validate(null, $request);

		$conference =& $router->getContext($request, CONTEXT_CONFERENCE);
		$schedConf =& $router->getContext($request, CONTEXT_SCHED_CONF);

		$conferenceId = $conference->getId();
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');

		if ($schedConf->getSetting('enablePublicPaperId')) {
			$paper =& $publishedPaperDao->getPublishedPaperByBestPaperId(
				$schedConf->getId(),
				$paperId,
				$schedConf->getSetting('previewAbstracts')?true:false
			);
		} else {
			$paper =& $publishedPaperDao->getPublishedPaperByPaperId(
				(int) $paperId,
				$schedConf->getId(),
				$schedConf->getSetting('previewAbstracts')?true:false
			);
		}

		// if paper does not exist, is not published, or is not part of
		// the right conference & sched conf, redirect to index.
		if (isset($schedConf) && isset($paper) && isset($conference) &&
				$paper->getSchedConfId() == $schedConf->getId() &&
				$schedConf->getConferenceId() == $conference->getId()) {

			// Check if login is required for viewing.
			if (!Validation::isLoggedIn() && $schedConf->getSetting('restrictPaperAccess')) {
				Validation::redirectLogin();
			}

			import('schedConf.SchedConfAction');
			$mayViewPaper = SchedConfAction::mayViewPapers($schedConf, $conference);

			if (
				// Bar access to paper?
				((isset($galleyId) && $galleyId!=0) && !$mayViewPaper) ||
				// Bar access to abstract?
				((!isset($galleyId) || $galleyId==0) && !SchedConfAction::mayViewProceedings($schedConf))
			) {
				$this->setupTemplate();				
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('paper', $paper);
				$templateMgr->assign_by_ref('schedConf', $schedConf);
				$templateMgr->assign_by_ref('conference', $conference);
				$templateMgr->display('paper/accessDenied.tpl');
				exit;
			}

		} else {
			$request->redirect(null, null, 'index');
		}

		$this->paper =& $paper;
		return true;
	}

	function setupTemplate() {
		parent::setupTemplate();
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_PKP_SUBMISSION));
	}
}

?>
