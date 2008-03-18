<?php

/**
 * @file SchedConfHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.schedConf
 * @class SchedConfHandler
 *
 * Handle requests for scheduled conference functions.
 *
 * $Id$
 */

import ('schedConf.SchedConfAction');
import('payment.ocs.OCSPaymentManager');

class SchedConfHandler extends Handler {

	/**
	 * Display scheduled conference view page.
	 */
	function index($args) {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		SchedConfHandler::setupSchedConfTemplate($conference, $schedConf);
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true)));
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
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$trackDirectorsDao = &DAORegistry::getDAO('TrackDirectorsDAO');
		$tracks = array();
		$tracks = &$trackDao->getSchedConfTracks($schedConf->getSchedConfId());
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
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('overview', $schedConf->getLocalizedSetting('overview'));

		$templateMgr->assign('helpTopicId', 'schedConf.overview');
		$templateMgr->display('schedConf/overview.tpl');
	}

	/**
	 * Display read-only timeline
	 */
	function timeline() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);
		import('manager.form.TimelineForm');
		$timelineForm =& new TimelineForm(false, true);
		$timelineForm->initData();
		$timelineForm->display();
	}

	/**
	 * Display conference CFP page
	 */
	function cfp() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('cfpMessage', $schedConf->getLocalizedSetting('cfpMessage'));

		$submissionsOpenDate = $schedConf->getSetting('submissionsOpenDate');
		$submissionsCloseDate = $schedConf->getSetting('submissionsCloseDate');

		if(!$submissionsOpenDate || !$submissionsCloseDate || time() < $submissionsOpenDate) {
			// Too soon
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('presenter.submit.notAcceptingYet');
		} elseif (time() > $submissionsCloseDate) {
			// Too late
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('presenter.submit.submissionDeadlinePassed', array('closedDate' => strftime(Config::getVar('general', 'date_format_short'), $submissionsCloseDate)));
		} else {
			$acceptingSubmissions = true;
		}

		$templateMgr->assign('acceptingSubmissions', $acceptingSubmissions);
		if (!$acceptingSubmissions) $templateMgr->assign('notAcceptingSubmissionsMessage', $notAcceptingSubmissionsMessage);
		$templateMgr->assign('helpTopicId', 'schedConf.cfp');
		$templateMgr->display('schedConf/cfp.tpl');
	}

	/**
	 * Display conference registration page
	 */
	function registration() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$paymentManager =& OCSPaymentManager::getManager();
		if (!$paymentManager->isConfigured()) Request::redirect(null, null, 'index');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$user =& Request::getUser();
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		if ($user && ($registrationId = $registrationDao->getRegistrationIdByUser($user->getUserId(), $schedConf->getSchedConfId()))) {
			// This user has already registered.
			$registration =& $registrationDao->getRegistration($registrationId);

			import('payment.ocs.OCSPaymentManager');
			$paymentManager =& OCSPaymentManager::getManager();

			if (!$paymentManager->isConfigured() || !$registration || $registration->getDatePaid()) {
				// If the system isn't fully configured or the registration is already paid,
				// display a message and block the user from going further.
				$templateMgr->assign('message', 'schedConf.registration.alreadyRegisteredAndPaid');
				$templateMgr->assign('backLinkLabel', 'common.back');
				$templateMgr->assign('backLink', Request::url(null, null, 'index'));
				return $templateMgr->display('common/message.tpl');
			} else {
				// Otherwise, allow them to try to pay again.
				$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
				$registrationType =& $registrationTypeDao->getRegistrationType($registration->getTypeId());
				$queuedPayment =& $paymentManager->createQueuedPayment($schedConf->getConferenceId(), $schedConf->getSchedConfId(), QUEUED_PAYMENT_TYPE_REGISTRATION, $user->getUserId(), $registrationId, $registrationType->getCost(), $registrationType->getCurrencyCodeAlpha());
				$queuedPaymentId = $paymentManager->queuePayment($queuedPayment, time() + (60 * 60 * 24 * 30)); // 30 days to complete

				$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
			}
		} else {
			import('registration.form.UserRegistrationForm');

			$form =& new UserRegistrationForm();
			if ($form->isLocaleResubmit()) {
				$form->readInputData();
			} else {
				$form->initData();
			}
			$form->display();
		}
	}

	/**
	 * Handle submission of the user registration form
	 */
	function register() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$paymentManager =& OCSPaymentManager::getManager();
		if (!$paymentManager->isConfigured()) Request::redirect(null, null, 'index');

		$user =& Request::getUser();
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		if ($user && ($registrationId = $registrationDao->getRegistrationIdByUser($user->getUserId(), $schedConf->getSchedConfId()))) {
			// User is already registered. Redirect to a message explaining.
			Request::redirect(null, null, null, 'registration');
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		import('registration.form.UserRegistrationForm');
		$form =& new UserRegistrationForm();
		$form->readInputData();
		if ($form->validate()) {
			if (!$form->execute()) {
				// Automatic payment failed; display a generic
				// "you will be contacted" message.
				$templateMgr->assign('message', 'schedConf.registration.noPaymentMethodAvailable');
				$templateMgr->assign('backLinkLabel', 'common.back');
				$templateMgr->assign('backLink', Request::url(null, null, 'index'));
				$templateMgr->display('common/message.tpl');
			}
			// Otherwise, payment is handled for us.
		} else {
			$form->display();
		}
	}

	/**
	 * Display conference schedule
	 */
	function schedule() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$schedConfId = $schedConf->getSchedConfId();

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$scheduledPresentations =& $publishedPaperDao->getPublishedPapers($schedConfId, true);
		$scheduledPresentations =& $scheduledPresentations->toAssociativeArray('paperId');
		$templateMgr->assign_by_ref('scheduledPresentations', $scheduledPresentations);

		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		$scheduledEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConfId, true);
		$scheduledEvents =& $scheduledEvents->toAssociativeArray('specialEventId');
		$templateMgr->assign_by_ref('scheduledEvents', $scheduledEvents);

		$timeBlockDao =& DAORegistry::getDAO('TimeBlockDAO');
		$timeBlocks =& $timeBlockDao->getTimeBlocksBySchedConfId($schedConfId);
		$timeBlocks =& $timeBlocks->toAssociativeArray('timeBlockId');
		$templateMgr->assign_by_ref('timeBlocks', $timeBlocks);

		$baseDates = array(); // Array of columns representing dates
		$boundaryTimes = array(); // Array of rows representing start times
		$timeBlockGrid = array();

		foreach (array_keys($timeBlocks) as $timeBlockKey) { // By ref
			$timeBlock =& $timeBlocks[$timeBlockKey];

			$startDate = strtotime($timeBlock->getStartTime());
			$endDate = strtotime($timeBlock->getEndTime());
			list($startDay, $startMonth, $startYear) = array(strftime('%d', $startDate), strftime('%m', $startDate), strftime('%Y', $startDate));
			$baseDate = mktime(0, 0, 1, $startMonth, $startDay, $startYear);
			$startTime = $startDate - $baseDate;
			$endTime = $endDate - $baseDate;

			$baseDates[] = $baseDate;
			$boundaryTimes[] = $startTime;
			$boundaryTimes[] = $endTime;

			$timeBlockGrid[$baseDate][$startTime]['timeBlockStarts'] =& $timeBlock;
			$timeBlockGrid[$baseDate][$endTime]['timeBlockEnds'] =& $timeBlock;
			unset($timeBlock);
		}

		// Knock out duplicates and sort the results.
		$boundaryTimes = array_unique($boundaryTimes);
		$baseDates = array_unique($baseDates);
		sort($boundaryTimes);
		sort($baseDates);

		$gridSlotUsed = array();
		// For each block, find out how long it lasts
		foreach ($baseDates as $baseDate) {
			foreach ($boundaryTimes as $boundaryTimeIndex => $boundaryTime) {
				if (!isset($timeBlockGrid[$baseDate][$boundaryTime]['timeBlockStarts'])) continue;
				$gridSlotUsed[$baseDate][$boundaryTime] = 1;
				// Establish the number of rows spanned ($i); track used grid slots
				for ($i=1; (isset($boundaryTimes[$i+$boundaryTimeIndex]) && !isset($timeBlockGrid[$baseDate][$boundaryTimes[$i+$boundaryTimeIndex]]['timeBlockEnds'])); $i++) {
					$gridSlotUsed[$baseDate][$boundaryTimes[$i+$boundaryTimeIndex]] = 1;
				}
				$timeBlockGrid[$baseDate][$boundaryTime]['rowspan'] = $i;
			}
		}

		$templateMgr->assign_by_ref('baseDates', $baseDates);
		$templateMgr->assign_by_ref('boundaryTimes', $boundaryTimes);
		$templateMgr->assign_by_ref('timeBlockGrid', $timeBlockGrid);
		$templateMgr->assign_by_ref('gridSlotUsed', $gridSlotUsed);

		$scheduledPresentationsByTimeBlockId = array();
		foreach (array_keys($scheduledPresentations) as $key) { // By ref
			$scheduledPresentation =& $scheduledPresentations[$key];
			$scheduledPresentationsByTimeBlockId[$scheduledPresentation->getTimeBlockId()][] =& $scheduledPresentation;
			unset($scheduledPresentation);
		}
		$templateMgr->assign_by_ref('scheduledPresentationsByTimeBlockId', $scheduledPresentationsByTimeBlockId);

		$scheduledEventsByTimeBlockId = array();
		foreach (array_keys($scheduledEvents) as $key) { // By ref
			$scheduledEvent =& $scheduledEvents[$key];
			$scheduledEventsByTimeBlockId[$scheduledEvent->getTimeBlockId()][] =& $scheduledEvent;
			unset($scheduledEvent);
		}
		$templateMgr->assign_by_ref('scheduledEventsByTimeBlockId', $scheduledEventsByTimeBlockId);

		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$roomDao =& DAORegistry::getDAO('RoomDAO');
		$schedConf =& Request::getSchedConf();
		$buildingIterator =& $buildingDao->getBuildingsBySchedConfId($schedConf->getSchedConfId());
		$buildings = $rooms = array();
		while ($building =& $buildingIterator->next()) {
			$buildingId = $building->getBuildingId();
			$buildings[$buildingId] =& $building;
			$roomIterator =& $roomDao->getRoomsByBuildingId($buildingId);
			while ($room =& $roomIterator->next()) {
				$roomId = $room->getRoomId();
				$rooms[$roomId] =& $room;
				unset($room);
			}
			unset($roomIterator);
			unset($building);
		}
		$templateMgr->assign_by_ref('buildings', $buildings);
		$templateMgr->assign_by_ref('rooms', $rooms);

		$templateMgr->assign('helpTopicId', 'schedConf.schedule');
		$templateMgr->display('schedConf/schedule.tpl');
	}

	/**
	 * Display conference program page
	 */
	function program() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('program', $schedConf->getLocalizedSetting('program'));
		$templateMgr->assign('programFileTitle', $schedConf->getLocalizedSetting('programFileTitle'));
		$templateMgr->assign('programFile', $schedConf->getLocalizedSetting('programFile'));

		$templateMgr->assign('helpTopicId', 'schedConf.program');
		$templateMgr->display('schedConf/program.tpl');
	}

	/**
	 * Display conference accommodation page
	 */
	function accommodation() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('accommodationDescription', $schedConf->getLocalizedSetting('accommodationDescription'));
		$templateMgr->assign('accommodationFiles', $schedConf->getLocalizedSetting('accommodationFiles'));

		$templateMgr->assign('helpTopicId', 'schedConf.accommodation');
		$templateMgr->display('schedConf/accommodation.tpl');
	}

	/**
	 * Display the presentations
	 */
	function presentations() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		import('schedConf.SchedConfAction');

		$mayViewProceedings = SchedConfAction::mayViewProceedings($schedConf);
		$mayViewPapers = SchedConfAction::mayViewPapers($schedConf, $conference);

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		$templateMgr->assign('helpTopicId', 'FIXME');
		$templateMgr->assign_by_ref('schedConf', $schedConf);

		$templateMgr->assign('mayViewProceedings', $mayViewProceedings);
		$templateMgr->assign('mayViewPapers', $mayViewPapers);

		if($mayViewProceedings) {
			$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
			$trackDao =& DAORegistry::getDAO('TrackDAO');

			$tracks = &$trackDao->getTrackTitles($schedConf->getSchedConfId());

			// Get the user's search conditions, if any
			$searchField = Request::getUserVar('searchField');
			$searchMatch = Request::getUserVar('searchMatch');
			$search = Request::getUserVar('search');

			$searchInitial = Request::getUserVar('searchInitial');
			if (isset($searchInitial)) {
				$searchField = SUBMISSION_FIELD_PRESENTER;
				$searchMatch = 'initial';
				$search = $searchInitial;
			}

			$templateMgr->assign('fieldOptions', Array(
				SUBMISSION_FIELD_TITLE => 'paper.title',
				SUBMISSION_FIELD_PRESENTER => 'user.role.presenter'
			));


			$publishedPapers = &$publishedPaperDao->getPublishedPapersInTracks($schedConf->getSchedConfId(), Request::getUserVar('track'), $searchField, $searchMatch, $search);

			// Set search parameters
			$duplicateParameters = array(
				'searchField', 'searchMatch', 'search', 'searchInitial', 'track'
			);
			foreach ($duplicateParameters as $param)
				$templateMgr->assign($param, Request::getUserVar($param));

			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
			$templateMgr->assign('trackOptions', array(0 => Locale::Translate('director.allTracks')) + $tracks);
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

			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));

			$templateMgr->assign_by_ref('schedConf', $schedConf);
			$templateMgr->assign('additionalHomeContent', $conference->getLocalizedSetting('additionalHomeContent'));

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
