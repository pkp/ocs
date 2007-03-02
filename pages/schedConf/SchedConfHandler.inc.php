<?php

/**
 * SchedConfHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.schedConf
 *
 * Handle requests for scheduled conference functions.
 *
 * $Id$
 */

import ('schedConf.SchedConfAction');

class SchedConfHandler extends Handler {

	/**
	 * Display scheduled conference view page.
	 */
	function index($args) {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		SchedConfHandler::setupSchedConfTemplate($conference, $schedConf);
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true)));
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		$templateMgr->display('schedConf/index.tpl');

	}

	/**
	 * Display track policies
	 */
	function trackPolicies() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$trackDirectorsDao = &DAORegistry::getDAO('TrackDirectorsDAO');
		$tracks = array();
		$tracks = &$trackDao->getSchedConfTracks($schedConf->getConferenceId());
		$tracks = &$tracks->toArray();
		$templateMgr->assign_by_ref('tracks', $tracks);
		$trackDirectors = array();
		foreach ($tracks as $track) {
			$trackDirectors[$track->getTrackId()] = &$trackDirectorsDao->getDirectorsByTrackId($conference->getConferenceId(), $track->getTrackId());
		}
		$templateMgr->assign_by_ref('trackDirectors', $trackDirectors);

		$templateMgr->assign('helpTopicId', 'schedConf.trackPolicies');
		$templateMgr->display('schedConf/trackPolicies.tpl');
	}

	/**
	 * Display conference overview page
	 */
	function overview() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('schedConfOverview', $schedConf->getSetting('schedConfOverview'));

		$templateMgr->assign('helpTopicId', 'schedConf.overview');
		$templateMgr->display('schedConf/overview.tpl');
	}

	/**
	 * Display conference CFP page
	 */
	function cfp() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);
		
		$templateMgr->assign('cfpMessage', $schedConf->getSetting('cfpMessage', false));

		$templateMgr->assign('helpTopicId', 'schedConf.cfp');
		$templateMgr->display('schedConf/cfp.tpl');
	}

	/**
	 * Display conference program page
	 */
	function program() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('program', $schedConf->getSetting('program'));
		$templateMgr->assign('programFileTitle', $schedConf->getSetting('programFileTitle'));
		$templateMgr->assign('programFile', $schedConf->getSetting('programFile'));

		$templateMgr->assign('helpTopicId', 'schedConf.program');
		$templateMgr->display('schedConf/program.tpl');
	}

	/**
	 * Display the proceedings
	 */
	function proceedings() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		import('schedConf.SchedConfAction');

		$mayViewProceedings = SchedConfAction::mayViewProceedings($schedConf);
		$mayViewPapers = SchedConfAction::mayViewPapers($schedConf);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, null, 'proceedings'), 'schedConf.proceedings')));
		$templateMgr->assign('helpTopicId', 'FIXME');
		$templateMgr->assign_by_ref('schedConf', $schedConf);

		$templateMgr->assign('mayViewProceedings', $mayViewProceedings);
		$templateMgr->assign('mayViewPapers', $mayViewPapers);
		
		if($mayViewProceedings) {
			$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
			$rangeInfo = Handler::getRangeInfo('publishedPapers');

			$publishedPapers = &$publishedPaperDao->getPublishedPapersInTracks($schedConf->getSchedConfId(), true);

			$templateMgr->assign_by_ref('publishedPapers', $publishedPapers);
		}

		$templateMgr->display('schedConf/papers.tpl');
	}

	/**
	 * Given a scheduled conference, set up the template with all the
	 * required variables for schedConf/view.tpl to function properly.
	 * @param $schedConf object The scheduled conference to display
	 * 	the cover page will be displayed. Otherwise table of contents
	 * 	will be displayed.
	 */
	function setupSchedConfTemplate(&$conference, &$schedConf) {

		$templateMgr = &TemplateManager::getManager();

		// Ensure the user is entitled to view the scheduled conference...
		if (isset($schedConf) && ($conference->getEnabled() || (
				Validation::isDirector($conference->getConferenceId()) ||
				Validation::isConferenceManager($conference->getConferenceId())))) {

			$schedConfTitle = $schedConf->getTitle();

			$openDate = $schedConf->getSetting('proposalsOpenDate');
			$closeDate = $schedConf->getSetting('proposalsCloseDate');
			$showCFPDate = $schedConf->getSetting('showCFPDate');
			
			if($showCFPDate && $closeDate &&
					(time() > $showCFPDate) && (time() < $closeDate)) {

				$templateMgr->assign('showCFP', true);
			}
			
			if((time() > $schedConf->getSetting('proposalsOpenDate') &&
					(time() < $schedConf->getSetting('proposalsCloseDate')))) {

				$templateMgr->assign('showSubmissionLink', true);
			}
			
			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));
					
			$templateMgr->assign('submissionOpenDate', $openDate);
			$templateMgr->assign('submissionCloseDate', $closeDate);
			
			$templateMgr->assign_by_ref('schedConf', $schedConf);
			$templateMgr->assign('additionalHomeContent', $conference->getSetting('additionalHomeContent'));

			$enableAnnouncements = $schedConf->getSetting('enableAnnouncements', true);
			if ($enableAnnouncements) {
				$enableAnnouncementsHomepage = $schedConf->getSetting('enableAnnouncementsHomepage', true);
				if ($enableAnnouncementsHomepage) {
					$numAnnouncementsHomepage = $schedConf->getSetting('numAnnouncementsHomepage', true);
					$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
					$announcements = &$announcementDao->getNumAnnouncementsNotExpiredByConferenceId($conference->getConferenceId(), $schedConf->getSchedConfId(), $numAnnouncementsHomepage);
					$templateMgr->assign('announcements', $announcements);
					$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
				}
			} 
		} else {
			Request::redirect(null, 'index');
		}

		if ($styleFileName = $schedConf->getStyleFileName()) {
			import('file.PublicFileManager');
			$publicFileManager = &new PublicFileManager();
			$templateMgr->addStyleSheet(
				Request::getBaseUrl() . '/' . $publicFileManager->getConferenceFilesPath($conference->getConferenceId()) . '/' . $styleFileName
			);
		}

		$templateMgr->assign('schedConfTitle', $schedConfTitle);
	}

	function validate() {
		list($conference, $schedConf) = parent::validate(true, true);

		if(!SchedConfAction::mayViewSchedConf($schedConf)) {
			Request::redirect(null, 'index');
		}

		return array($conference, $schedConf);
	}
}

?>
