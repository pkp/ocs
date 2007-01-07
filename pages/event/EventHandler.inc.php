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
		list($conference, $event) = parent::validate(true, true);

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
		list($conference, $event) = parent::validate(true, true);

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
		list($conference, $event) = parent::validate(true, true);

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
		list($conference, $event) = parent::validate(true, true);

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
		list($conference, $event) = parent::validate(true, true);

		// Determine whether this event's proceedings are visible for this user
		$publicationState = $event->getPublicationState();
		$allowed = false;
		$releasedToParticipants = false;
		$releasedToPublic = false;

		import('event.EventAction');

		switch($publicationState) {

			case PUBLICATION_STATE_NOTYET:
				// Require an appropriate role.
				$allowed = EventAction::entitledUser($event);
				break;

			case PUBLICATION_STATE_PARTICIPANTS:
				// Require a valid registration.
				$allowed = EventAction::registeredUser($event);
				$releasedToParticipants = true;
				break;

			case PUBLICATION_STATE_PUBLIC:
				$allowed = true;
				$releasedToParticipants = true;
				$releasedToPublic = true;
				break;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, null, 'proceedings'), 'event.proceedings')));
		$templateMgr->assign('helpTopicId', 'FIXME');
		$templateMgr->assign_by_ref('event', $event);

		$templateMgr->assign('releasedToParticipants', $releasedToParticipants);
		$templateMgr->assign('releasedToPublic', $releasedToPublic);
	
		if($allowed) {
			$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
			$rangeInfo = Handler::getRangeInfo('publishedPapers');

			$publishedPapers = &$publishedPaperDao->getPublishedPapersInTracks($event->getEventId(), true);

			$templateMgr->assign_by_ref('publishedPapers', $publishedPapers);

		} else {
			$templateMgr->assign('notPermitted', true);
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
		if (isset($event) &&
				($event->getEnabled() ||
				 Validation::isEditor($conference->getConferenceId()) ||
				 Validation::isConferenceDirector($conference->getConferenceId())) {

			$eventTitle = $event->getTitle();

			$submissionState = $event->getSubmissionState();

			$openDate = $event->getAcceptSubmissionsDate();
			$closeDate = $event->getAbstractDueDate();

			if( ($event->getAutoShowCFP() && time() > $event->getShowCFPDate() && $submissionState == SUBMISSION_STATE_NOTYET) ||
					($submissionState == SUBMISSION_STATE_ACCEPT))
				$showCFP = true;
			else
				$showCFP = false;
			
			if($event->getAutoShowCFP() && time() > $event->getShowCFPDate() && $submissionState == SUBMISSION_STATE_CLOSED)
				$showCFPExpired = true;
			else
				$showCFPExpired = false;
			
			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $event->getPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $event->getPageHeaderLogo(true));
			$templateMgr->assign('displayConferencePageHeaderTitle', $conference->getPageHeaderTitle(true));
			$templateMgr->assign('displayConferencePageHeaderLogo', $conference->getPageHeaderLogo(true));

			$templateMgr->assign('showCFP', $showCFP);
			$templateMgr->assign('showCFPExpired', $showCFPExpired);
			$templateMgr->assign('showSubmissionLink', ($submissionState == SUBMISSION_STATE_ACCEPT));
					
			$templateMgr->assign('submissionOpenDate', TimeZone::formatLocalTime(null, $openDate, null));
			$templateMgr->assign('submissionCloseDate', TimeZone::formatLocalTime(null, $closeDate, null));
			$templateMgr->assign('cfpDate', TimeZone::formatLocalTime(TZ_DATE_FORMAT_DATEONLY, $event->getSetting('showCFPDate'), null));
			$templateMgr->assign('cfpExpireDate', TimeZone::formatLocalTime(TZ_DATE_FORMAT_DATEONLY, $closeDate, null));
			
			$templateMgr->assign_by_ref('event', $event);
			$templateMgr->assign('additionalHomeContent', $event->getSetting('additionalHomeContent', true));

			$enableAnnouncements = $event->getSetting('enableAnnouncements', true);
			if ($enableAnnouncements) {
				$enableAnnouncementsHomepage = $event->getSetting('enableAnnouncementsHomepage', true);
				if ($enableAnnouncementsHomepage) {
					$numAnnouncementsHomepage = $event->getSetting('numAnnouncementsHomepage', true);
					$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
					$announcements = &$announcementDao->getNumAnnouncementsNotExpiredByConferenceId($conference->getConferenceId(), 	$numAnnouncementsHomepage);
					$templateMgr->assign('announcements', $announcements);
					$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
				}
			} 

			// Registration Access
			import('event.EventAction');
			$templateMgr->assign('registrationRequired', EventAction::registrationRequired($event));
			$templateMgr->assign('registeredUser', EventAction::registeredUser($event));
			$templateMgr->assign('registeredDomain', EventAction::registeredDomain($event));

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

}

?>
