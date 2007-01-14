<?php

/**
 * EventHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.event
 *
 * Handle requests for event functions.
 *
 * $Id$
 */

import ('event.EventAction');

class EventHandler extends Handler {

	/**
	 * Display event view page.
	 */
	function index($args) {
		list($conference, $event) = EventHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		EventHandler::setupEventTemplate($conference, $event);
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true)));
		$templateMgr->assign('helpTopicId', 'user.currentAndArchives');
		$templateMgr->display('event/index.tpl');

	}

	/**
	 * Display conference overview page
	 */
	function overview() {
		list($conference, $event) = EventHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $event->getTitle(), true)));
		EventHandler::setupEventTemplate($conference,$event);

		$templateMgr->assign('eventOverview', $event->getSetting('eventOverview'));

		$templateMgr->assign('helpTopicId', 'event.overview');
		$templateMgr->display('event/overview.tpl');
	}

	/**
	 * Display conference CFP page
	 */
	function cfp() {
		list($conference, $event) = EventHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $event->getTitle(), true)));
		EventHandler::setupEventTemplate($conference,$event);
		
		$templateMgr->assign('cfpMessage', $event->getSetting('cfpMessage', false));

		$templateMgr->assign('helpTopicId', 'event.cfp');
		$templateMgr->display('event/cfp.tpl');
	}

	/**
	 * Display conference program page
	 */
	function program() {
		list($conference, $event) = EventHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $event->getTitle(), true)));
		EventHandler::setupEventTemplate($conference,$event);

		$templateMgr->assign('eventProgram', $event->getSetting('eventProgram', false));

		$templateMgr->assign('helpTopicId', 'event.program');
		$templateMgr->display('event/program.tpl');
	}

	/**
	 * Display the proceedings
	 */
	function proceedings() {
		list($conference, $event) = EventHandler::validate(true, true);

		import('event.EventAction');

		$mayViewProceedings = EventAction::mayViewProceedings($event);
		$mayViewPapers = EventAction::mayViewPapers($event);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, null, 'proceedings'), 'event.proceedings')));
		$templateMgr->assign('helpTopicId', 'FIXME');
		$templateMgr->assign_by_ref('event', $event);

		$templateMgr->assign('mayViewProceedings', $mayViewProceedings);
		$templateMgr->assign('mayViewPapers', $mayViewPapers);
		
		if($mayViewProceedings) {
			$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
			$rangeInfo = Handler::getRangeInfo('publishedPapers');

			$publishedPapers = &$publishedPaperDao->getPublishedPapersInTracks($event->getEventId(), true);

			$templateMgr->assign_by_ref('publishedPapers', $publishedPapers);
		}

		$templateMgr->display('event/papers.tpl');
	}

	/**
	 * Given an event, set up the template with all the required variables for
	 * events/view.tpl to function properly.
	 * @param $event object The event to display
	 * 	the cover page will be displayed. Otherwise table of contents
	 * 	will be displayed.
	 */
	function setupEventTemplate(&$conference, &$event) {

		$templateMgr = &TemplateManager::getManager();

		// Ensure the user is entitled to view the event...
		if (isset($event) && ($event->getEnabled() || (
				Validation::isEditor($conference->getConferenceId()) ||
				Validation::isConferenceDirector($conference->getConferenceId())))) {

			$eventTitle = $event->getTitle();

			$openDate = $event->getSetting('proposalsOpenDate');
			$closeDate = $event->getSetting('propsalsCloseDate');
			$showCFPDate = $event->getSetting('showCFPDate');
			
			if($showCFPDate && $closeDate &&
					($time() > $showCFPDate) && (time() < $closeDate)) {

				$templateMgr->assign('showCFP', true);
			}
			
			if((time() > $event->getSetting('proposalsOpenDate') &&
					(time() < $event->getSetting('proposalsCloseDate')))) {

				$templateMgr->assign('showSubmissionLink', true);
			}
			
			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $event->getPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $event->getPageHeaderLogo(true));
			$templateMgr->assign('displayConferencePageHeaderTitle', $conference->getPageHeaderTitle(true));
			$templateMgr->assign('displayConferencePageHeaderLogo', $conference->getPageHeaderLogo(true));
					
			$templateMgr->assign('submissionOpenDate', $openDate);
			$templateMgr->assign('submissionCloseDate', $closeDate);
			
			$templateMgr->assign_by_ref('event', $event);
			$templateMgr->assign('additionalHomeContent', $event->getSetting('additionalHomeContent', true));

			$enableAnnouncements = $event->getSetting('enableAnnouncements', true);
			if ($enableAnnouncements) {
				$enableAnnouncementsHomepage = $event->getSetting('enableAnnouncementsHomepage', true);
				if ($enableAnnouncementsHomepage) {
					$numAnnouncementsHomepage = $event->getSetting('numAnnouncementsHomepage', true);
					$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
					$announcements = &$announcementDao->getNumAnnouncementsNotExpiredByConferenceId($conference->getConferenceId(), $event->getEventId(), $numAnnouncementsHomepage);
					$templateMgr->assign('announcements', $announcements);
					$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
				}
			} 
		} else {
			Request::redirect(null, 'index');
		}

		if ($styleFileName = $event->getStyleFileName()) {
			import('file.PublicFileManager');
			$publicFileManager = &new PublicFileManager();
			$templateMgr->addStyleSheet(
				Request::getBaseUrl() . '/' . $publicFileManager->getConferenceFilesPath($conference->getConferenceId()) . '/' . $styleFileName
			);
		}

		$templateMgr->assign('eventTitle', $eventTitle);
	}

	function validate() {
		list($conference, $event) = parent::validate(true, true);

		if(!EventAction::mayViewEvent($event)) {
			Request::redirect(null, 'index');
		}

		return array($conference, $event);
	}
}

?>
