<?php

/**
 * @file SchedConfHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfHandler
 * @ingroup pages_schedConf
 *
 * @brief Handle requests for scheduled conference functions.
 *
 */

// $Id$


import ('schedConf.SchedConfAction');
import('payment.ocs.OCSPaymentManager');

class SchedConfHandler extends Handler {

	/**
	 * Display scheduled conference view page.
	 */
	function index($args) {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr =& TemplateManager::getManager();
		SchedConfHandler::setupSchedConfTemplate($conference, $schedConf);
		$enableAnnouncements = $conference->getSetting('enableAnnouncements');

		if ($enableAnnouncements) {
			$enableAnnouncementsHomepage = $conference->getSetting('enableAnnouncementsHomepage');
			if ($enableAnnouncementsHomepage) {
				$numAnnouncementsHomepage = $conference->getSetting('numAnnouncementsHomepage');
				$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
				$announcements =& $announcementDao->getNumAnnouncementsNotExpiredByConferenceId($conference->getConferenceId(), $schedConf->getSchedConfId(), $numAnnouncementsHomepage);
				$templateMgr->assign('announcements', $announcements);
				$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
			}
		} 
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true)));
		$templateMgr->assign('homepageImage', $conference->getLocalizedSetting('homepageImage'));
		$templateMgr->assign('helpTopicId', 'user.currentArchives');
		$templateMgr->display('schedConf/index.tpl');

	}

	/**
	 * Display track policies
	 */
	function trackPolicies() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');
		$tracks = array();
		$tracks =& $trackDao->getSchedConfTracks($schedConf->getSchedConfId());
		$tracks =& $tracks->toArray();
		$templateMgr->assign_by_ref('tracks', $tracks);
		$trackDirectors = array();
		foreach ($tracks as $track) {
			$trackDirectors[$track->getTrackId()] =& $trackDirectorsDao->getDirectorsByTrackId($conference->getConferenceId(), $track->getTrackId());
		}
		$templateMgr->assign_by_ref('trackDirectors', $trackDirectors);

		$templateMgr->assign('helpTopicId', 'conference.currentConferences.tracks');
		$templateMgr->display('schedConf/trackPolicies.tpl');
	}

	/**
	 * Display conference overview page
	 */
	function overview() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('overview', $schedConf->getLocalizedSetting('overview'));

		$templateMgr->assign('helpTopicId', 'user.home');
		$templateMgr->display('schedConf/overview.tpl');
	}

	/**
	 * Display read-only timeline
	 */
	function timeline() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr =& TemplateManager::getManager();
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

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('cfpMessage', $schedConf->getLocalizedSetting('cfpMessage'));
		$templateMgr->assign('presenterGuidelines', $schedConf->getLocalizedSetting('presenterGuidelines'));

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
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.setup.submissions');
		$templateMgr->display('schedConf/cfp.tpl');
	}

	/**
	 * Display conference registration page
	 */
	function registration() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$paymentManager =& OCSPaymentManager::getManager();
		if (!$paymentManager->isConfigured()) Request::redirect(null, null, 'index');

		$templateMgr =& TemplateManager::getManager();
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
			}
		}

		$typeId = (int) Request::getUserVar('registrationTypeId');
		if ($typeId) {
			// A registration type has been chosen
			import('registration.form.UserRegistrationForm');

			$form =& new UserRegistrationForm($typeId);
			if ($form->isLocaleResubmit()) {
				$form->readInputData();
			} else {
				$form->initData();
			}
			$form->display();
		} else {
			// A registration type has not been chosen; prompt for one.
			$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
			$registrationTypes =& $registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getSchedConfId());
			$templateMgr->assign_by_ref('registrationTypes', $registrationTypes);
			return $templateMgr->display('registration/selectRegistrationType.tpl');
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
			// This user has already registered.
			$registration =& $registrationDao->getRegistration($registrationId);
			if ( !$registration || $registration->getDatePaid() ) {
				// And they have already paid. Redirect to a message explaining.
				Request::redirect(null, null, null, 'registration');
			} else {
				// Allow them to resubmit the form to change type or pay again.
				$registrationDao->deleteRegistrationById($registrationId);
			}			
		}
		
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		import('registration.form.UserRegistrationForm');
		$typeId = (int) Request::getUserVar('registrationTypeId');
		$form =& new UserRegistrationForm($typeId);
		$form->readInputData();
		if ($form->validate()) {
			if ($registrationError = $form->execute() != REGISTRATION_SUCCESSFUL) {
				if($registrationError == REGISTRATION_FAILED) {
					// User not created
					$templateMgr->assign('message', 'schedConf.registration.failed');
					$templateMgr->assign('backLinkLabel', 'common.back');
					$templateMgr->assign('backLink', Request::url(null, null, 'index'));
					$templateMgr->display('common/message.tpl');
				} elseif ($registrationError == REGISTRATION_NO_PAYMENT) {				
					// Automatic payment failed; display a generic
					// "you will be contacted" message.
					$templateMgr->assign('message', 'schedConf.registration.noPaymentMethodAvailable');
					$templateMgr->assign('backLinkLabel', 'common.back');
					$templateMgr->assign('backLink', Request::url(null, null, 'index'));
					$templateMgr->display('common/message.tpl');
				}
			}
			// Otherwise, payment is handled for us.
		} else {
			$form->display();
		}
	}

	/**
	 * Display conference program page
	 */
	function program() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('program', $schedConf->getSetting('program', Locale::getLocale()));
		$templateMgr->assign('programFile', $schedConf->getSetting('programFile', Locale::getLocale()));
		$templateMgr->assign('programFileTitle', $schedConf->getSetting('programFileTitle', Locale::getLocale()));
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.program');
		$templateMgr->display('schedConf/program.tpl');
	}

	/**
	 * Display conference schedule page
	 */
	function schedule() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$postScheduleDate = $schedConf->getSetting('postScheduleDate');
		if (!$postScheduleDate || time() < $postScheduleDate || !$schedConf->getSetting('postSchedule')) Request::redirect(null, null, 'schedConf');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$roomDao =& DAORegistry::getDAO('RoomDAO');

		$buildingsAndRooms = $allRooms = array();
		$buildings =& $buildingDao->getBuildingsBySchedConfId($schedConf->getSchedConfId());
		while ($building =& $buildings->next()) {
			$buildingId = $building->getBuildingId();
			$rooms =& $roomDao->getRoomsByBuildingId($buildingId);
			$buildingsAndRooms[$buildingId] = array(
				'building' => &$building
			);
			while ($room =& $rooms->next()) {
				$roomId = $room->getRoomId();
				$buildingsAndRooms[$buildingId]['rooms'][$roomId] =& $room;
				$allRooms[$roomId] =& $room;
				unset($room);
			}
			unset($building);
			unset($rooms);
		}
		$templateMgr->assign_by_ref('buildingsAndRooms', $buildingsAndRooms);
		$templateMgr->assign_by_ref('allRooms', $allRooms);

		// Merge special events and papers into an array by time/date
		$itemsByTime = array();

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPapers =& $publishedPaperDao->getPublishedPapers($schedConf->getSchedConfId(), PAPER_SORT_ORDER_TIME);
		while ($paper =& $publishedPapers->next()) {
			$startTime = $paper->getStartTime();
			if ($startTime) $itemsByTime[$startTime][] =& $paper;
			unset($paper);
		}
		unset($publishedPapers);

		$specialEventDao =& DAORegistry::getDAO('SpecialEventDAO');
		$specialEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConf->getSchedConfId());
		while ($specialEvent =& $specialEvents->next()) {
			$startTime = $specialEvent->getStartTime();
			if ($startTime) $itemsByTime[$startTime][] =& $specialEvent;
			unset($specialEvent);
		}
		unset($specialEvents);

		$templateMgr->assign_by_ref('itemsByTime', $itemsByTime);
		$templateMgr->assign('conference.currentConferences.scheduler');
		$templateMgr->display('schedConf/schedule.tpl');
	}

	/**
	 * Display conference accommodation page
	 */
	function accommodation() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('accommodationDescription', $schedConf->getLocalizedSetting('accommodationDescription'));
		$templateMgr->assign('accommodationFiles', $schedConf->getLocalizedSetting('accommodationFiles'));

		$templateMgr->assign('helpTopicId', 'conference.currentConferences.accommodation');
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

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getSchedConfTitle(), true)));
		$templateMgr->assign('helpTopicId', 'editorial.trackDirectorsRole.presentations');
		$templateMgr->assign_by_ref('schedConf', $schedConf);

		$templateMgr->assign('mayViewProceedings', $mayViewProceedings);
		$templateMgr->assign('mayViewPapers', $mayViewPapers);

		if($mayViewProceedings) {
			$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
			$trackDao =& DAORegistry::getDAO('TrackDAO');

			$tracks =& $trackDao->getTrackTitles($schedConf->getSchedConfId());

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

			$previewAbstracts = (
				$schedConf->getSetting('reviewMode') == REVIEW_MODE_BOTH_SEQUENTIAL &&
				$schedConf->getSetting('previewAbstracts')
			);

			$publishedPapers =& $publishedPaperDao->getPublishedPapersInTracks($schedConf->getSchedConfId(), Request::getUserVar('track'), $searchField, $searchMatch, $search, $previewAbstracts);

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
		$templateMgr =& TemplateManager::getManager();

		// Ensure the user is entitled to view the scheduled conference...
		if (isset($schedConf) && ($conference->getEnabled() || (
				Validation::isDirector($conference->getConferenceId()) ||
				Validation::isConferenceManager($conference->getConferenceId())))) {

			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));

			$templateMgr->assign_by_ref('schedConf', $schedConf);
			$templateMgr->assign('additionalHomeContent', $conference->getLocalizedSetting('additionalHomeContent'));
		} else {
			Request::redirect(null, 'index');
		}

		if ($styleFileName = $schedConf->getStyleFileName()) {
			import('file.PublicFileManager');
			$publicFileManager =& new PublicFileManager();
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
