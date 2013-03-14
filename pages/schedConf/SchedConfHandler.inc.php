<?php

/**
 * @file SchedConfHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfHandler
 * @ingroup pages_schedConf
 *
 * @brief Handle requests for scheduled conference functions.
 *
 */

import('classes.schedConf.SchedConfAction');
import('classes.payment.ocs.OCSPaymentManager');
import('classes.handler.Handler');

class SchedConfHandler extends Handler {
	/**
	 * Constructor
	 */
	function SchedConfHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorSchedConf($this));
	}

	/**
	 * Display scheduled conference view page.
	 */
	function index($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate($request, $conference, $schedConf);
		$enableAnnouncements = $conference->getSetting('enableAnnouncements');

		if ($enableAnnouncements) {
			$enableAnnouncementsHomepage = $conference->getSetting('enableAnnouncementsHomepage');
			if ($enableAnnouncementsHomepage) {
				$numAnnouncementsHomepage = $conference->getSetting('numAnnouncementsHomepage');
				$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
				$announcements =& $announcementDao->getNumAnnouncementsNotExpiredByConferenceId($conference->getId(), $schedConf->getId(), $numAnnouncementsHomepage);
				$templateMgr->assign('announcements', $announcements);
				$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
			}
		}

		$templateMgr->assign('schedConf', $schedConf);

		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true)));
		$templateMgr->assign('homepageImage', $conference->getLocalizedSetting('homepageImage'));
		$templateMgr->assign('homepageImageAltText', $conference->getLocalizedSetting('homepageImageAltText'));
		$templateMgr->assign('helpTopicId', 'user.currentArchives');
		$templateMgr->display('schedConf/index.tpl');

	}

	/**
	 * Display track policies
	 */
	function trackPolicies($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);

		$trackDao = DAORegistry::getDAO('TrackDAO');
		$trackDirectorsDao = DAORegistry::getDAO('TrackDirectorsDAO');
		$tracks = array();
		$tracks =& $trackDao->getSchedConfTracks($schedConf->getId());
		$tracks =& $tracks->toArray();
		$templateMgr->assign_by_ref('tracks', $tracks);
		$trackDirectors = array();
		foreach ($tracks as $track) {
			$trackDirectors[$track->getId()] =& $trackDirectorsDao->getDirectorsByTrackId($schedConf->getId(), $track->getId());
		}
		$templateMgr->assign_by_ref('trackDirectors', $trackDirectors);

		$templateMgr->assign('helpTopicId', 'conference.currentConferences.tracks');
		$templateMgr->display('schedConf/trackPolicies.tpl');
	}

	/**
	 * Display conference overview page
	 */
	function overview($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);

		$templateMgr->assign('overview', $schedConf->getLocalizedSetting('overview'));

		$templateMgr->assign('helpTopicId', 'user.home');
		$templateMgr->display('schedConf/overview.tpl');
	}

	/**
	 * Display read-only timeline
	 */
	function timeline($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER); // FIXME: For timeline constants
		import('classes.manager.form.TimelineForm');
		$timelineForm = new TimelineForm(false, true);
		$timelineForm->initData();
		$timelineForm->display();
	}

	/**
	 * Display conference CFP page
	 */
	function cfp($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_AUTHOR);

		$templateMgr->assign('cfpMessage', $schedConf->getLocalizedSetting('cfpMessage'));
		$templateMgr->assign('authorGuidelines', $schedConf->getLocalizedSetting('authorGuidelines'));

		$submissionsOpenDate = $schedConf->getSetting('submissionsOpenDate');
		$submissionsCloseDate = $schedConf->getSetting('submissionsCloseDate');

		if(!$submissionsOpenDate || !$submissionsCloseDate || time() < $submissionsOpenDate) {
			// Too soon
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = __('author.submit.notAcceptingYet');
		} elseif (time() > $submissionsCloseDate) {
			// Too late
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = __('author.submit.submissionDeadlinePassed', array('closedDate' => strftime(Config::getVar('general', 'date_format_short'), $submissionsCloseDate)));
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
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function registration($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$paymentManager = new OCSPaymentManager($request);
		if (!$paymentManager->isConfigured()) {
			// If the system isn't fully configured, display a message and block
			// the user from going further.
			$templateMgr->assign('message', 'schedConf.registration.paymentNotConfigured');
			$templateMgr->assign('backLinkLabel', 'common.back');
			$templateMgr->assign('backLink', $request->url(null, null, 'index'));
			AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
			return $templateMgr->display('common/message.tpl');
		}

		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);

		$user =& $request->getUser();
		$registrationDao = DAORegistry::getDAO('RegistrationDAO');
		$registration = null;
		if ($user && ($registrationId = $registrationDao->getRegistrationIdByUser($user->getId(), $schedConf->getId()))) {
			// This user has already registered.
			$registration =& $registrationDao->getRegistration($registrationId);

			import('classes.payment.ocs.OCSPaymentManager');
			$paymentManager = new OCSPaymentManager($request);

			if (!$paymentManager->isConfigured() || !$registration || $registration->getDatePaid()) {
				// If the system isn't fully configured or the registration is already paid,
				// display a message and block the user from going further.
				$templateMgr->assign('message', 'schedConf.registration.alreadyRegisteredAndPaid');
				$templateMgr->assign('backLinkLabel', 'common.back');
				$templateMgr->assign('backLink', $request->url(null, null, 'index'));
				return $templateMgr->display('common/message.tpl');
			}
		}

		$typeId = (int) $request->getUserVar('registrationTypeId');
		if ($typeId) {
			// A registration type has been chosen
			import('classes.registration.form.UserRegistrationForm');

			$form = new UserRegistrationForm($typeId, $registration, $request);
			if ($form->isLocaleResubmit()) {
				$form->readInputData();
			} else {
				$form->initData();
			}
			$form->display();
		} else {
			// A registration type has not been chosen; prompt for one.
			$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
			$registrationTypes =& $registrationTypeDao->getRegistrationTypesBySchedConfId($schedConf->getId());
			$templateMgr->assign_by_ref('registrationTypes', $registrationTypes);
			$templateMgr->assign('registration', $registration);
			return $templateMgr->display('registration/selectRegistrationType.tpl');
		}
	}

	/**
	 * Handle submission of the user registration form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function register($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$paymentManager = new OCSPaymentManager($request);
		if (!$paymentManager->isConfigured()) $request->redirect(null, null, 'index');

		$user =& $request->getUser();
		$registrationDao = DAORegistry::getDAO('RegistrationDAO');
		$registration = null;
		if ($user && ($registrationId = $registrationDao->getRegistrationIdByUser($user->getId(), $schedConf->getId()))) {
			// This user has already registered.
			$registration =& $registrationDao->getRegistration($registrationId);
			if ( !$registration || $registration->getDatePaid() ) {
				// And they have already paid. Redirect to a message explaining.
				$request->redirect(null, null, null, 'registration');
			}
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);

		import('classes.registration.form.UserRegistrationForm');
		$typeId = (int) $request->getUserVar('registrationTypeId');
		$form = new UserRegistrationForm($typeId, $registration, $request);
		$form->readInputData();
		if ($form->validate()) {
			if (($registrationError = $form->execute()) == REGISTRATION_SUCCESSFUL) {
				$registration =& $form->getRegistration();
				$queuedPayment =& $form->getQueuedPayment();

				// Successful: Send an email.
				import('mail.MailTemplate');
				$mail = new MailTemplate('USER_REGISTRATION_NOTIFY');
				$mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
				$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
				$registrationType =& $registrationTypeDao->getRegistrationType($typeId);

				// Determine the registration options for inclusion
				$registrationOptionText = '';
				$totalCost = $registrationType->getCost();
				$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
				$registrationOptionIterator =& $registrationOptionDao->getRegistrationOptionsBySchedConfId($schedConf->getId());
				$registrationOptionCosts = $registrationTypeDao->getRegistrationOptionCosts($typeId);
				$registrationOptionIds = $registrationOptionDao->getRegistrationOptions($registration->getRegistrationId());
				while ($registrationOption =& $registrationOptionIterator->next()) {
					if (in_array($registrationOption->getOptionId(), $registrationOptionIds)) {
						$registrationOptionText .= $registrationOption->getRegistrationOptionName() . ' - ' . sprintf('%.2f', $registrationOptionCosts[$registrationOption->getOptionId()]) . ' ' . $registrationType->getCurrencyCodeAlpha() . "\n";
						$totalCost += $registrationOptionCosts[$registrationOption->getOptionId()];
					}
					unset($registrationOption);
				}

				$mail->assignParams(array(
					'registrantName' => $user->getFullName(),
					'registrationType' => $registrationType->getSummaryString(),
					'registrationOptions' => $registrationOptionText,
					'totalCost' => sprintf('%.2f', $totalCost) . ' ' . $registrationType->getCurrencyCodeAlpha(),
					'username' => $user->getUsername(),
					'specialRequests' => $registration->getSpecialRequests(),
					'invoiceId' => $queuedPayment->getInvoiceId(),
					'registrationContactSignature' => $schedConf->getSetting('registrationName')
				));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send();
			} else {
				// Not successful
				$templateMgr->assign('isUserLoggedIn', Validation::isLoggedIn()); // In case a user was just created, make sure they appear logged in
				if ($registrationError == REGISTRATION_FAILED) {
					// User not created
					$templateMgr->assign('message', 'schedConf.registration.failed');
					$templateMgr->assign('backLinkLabel', 'common.back');
					$templateMgr->assign('backLink', $request->url(null, null, 'index'));
					$templateMgr->display('common/message.tpl');
				} elseif ($registrationError == REGISTRATION_NO_PAYMENT) {
					// Automatic payment failed; display a generic
					// "you will be contacted" message.
					$templateMgr->assign('message', 'schedConf.registration.noPaymentMethodAvailable');
					$templateMgr->assign('backLinkLabel', 'common.back');
					$templateMgr->assign('backLink', $request->url(null, null, 'index'));
					$templateMgr->display('common/message.tpl');
				} elseif ($registrationError == REGISTRATION_FREE) {
					// Registration successful; no payment required (free)
					$templateMgr->assign('message', 'schedConf.registration.free');
					$templateMgr->assign('backLinkLabel', 'common.back');
					$templateMgr->assign('backLink', $request->url(null, null, 'index'));
					$templateMgr->display('common/message.tpl');
				}
			}
			// Otherwise, payment is handled for us.
		} else {
			$templateMgr->assign('isUserLoggedIn', Validation::isLoggedIn()); // In case a user was just created, make sure they appear logged in
			$form->display();
		}
	}

	/**
	 * Display conference program page
	 */
	function program($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);

		$templateMgr->assign('program', $schedConf->getSetting('program', AppLocale::getLocale()));
		$templateMgr->assign('programFile', $schedConf->getSetting('programFile', AppLocale::getLocale()));
		$templateMgr->assign('programFileTitle', $schedConf->getSetting('programFileTitle', AppLocale::getLocale()));
		$templateMgr->assign('helpTopicId', 'conference.currentConferences.program');
		$templateMgr->display('schedConf/program.tpl');
	}

	/**
	 * Display conference schedule page
	 */
	function schedule($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$postScheduleDate = $schedConf->getSetting('postScheduleDate');
		if (!$postScheduleDate || time() < $postScheduleDate || !$schedConf->getSetting('postSchedule')) $request->redirect(null, null, 'schedConf');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);

		$buildingDao = DAORegistry::getDAO('BuildingDAO');
		$roomDao = DAORegistry::getDAO('RoomDAO');

		$buildingsAndRooms = $allRooms = array();
		$buildings =& $buildingDao->getBuildingsBySchedConfId($schedConf->getId());
		while ($building =& $buildings->next()) {
			$buildingId = $building->getId();
			$rooms =& $roomDao->getRoomsByBuildingId($buildingId);
			$buildingsAndRooms[$buildingId] = array(
				'building' => &$building
			);
			while ($room =& $rooms->next()) {
				$roomId = $room->getId();
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

		$publishedPaperDao = DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPapers =& $publishedPaperDao->getPublishedPapers($schedConf->getId(), PAPER_SORT_ORDER_TIME);
		while ($paper =& $publishedPapers->next()) {
			if ($paper->getStartTime()) {
				$startTime = strtotime($paper->getStartTime());
				$itemsByTime[$startTime][] =& $paper;
			}
			unset($paper);
		}
		unset($publishedPapers);

		$specialEventDao = DAORegistry::getDAO('SpecialEventDAO');
		$specialEvents =& $specialEventDao->getSpecialEventsBySchedConfId($schedConf->getId());
		while ($specialEvent =& $specialEvents->next()) {
			$startTime = strtotime($specialEvent->getStartTime());
			if ($startTime) $itemsByTime[$startTime][] =& $specialEvent;
			unset($specialEvent);
		}
		unset($specialEvents);

		// WARNING: $itemsByTime contains both PublishedPapers and
		// SpecialEvents; both implement getStartTime() and
		// getEndTime.
		ksort($itemsByTime);
		foreach ($itemsByTime as $startTime => $junk) {
			uasort($itemsByTime[$startTime], create_function('$a, $b', 'return strtotime($a->getEndTime()) - strtotime($b->getEndTime());'));
		}

		// Read in schedule layout settings
		if ($schedConf->getSetting('mergeSchedules')) {
			ksort($itemsByTime);
		}
		$templateMgr->assign('showEndTime', $schedConf->getSetting('showEndTime'));
		$templateMgr->assign('showAuthors', $schedConf->getSetting('showAuthors'));
		$templateMgr->assign('hideNav', $schedConf->getSetting('hideNav'));
		$templateMgr->assign('hideLocations', $schedConf->getSetting('hideLocations'));

		$templateMgr->assign_by_ref('itemsByTime', $itemsByTime);
		$templateMgr->assign('conference.currentConferences.scheduler');

		if($schedConf->getSetting('layoutType') == SCHEDULE_LAYOUT_COMPACT) {
			$templateMgr->display('schedConf/schedules/compact.tpl');
		} else if($schedConf->getSetting('layoutType') == SCHEDULE_LAYOUT_EXPANDED || !$schedConf->getSetting('layoutType')) {
			$templateMgr->display('schedConf/schedules/expanded.tpl');
		}
	}

	/**
	 * Display conference accommodation page
	 */
	function accommodation($args, &$request) {
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$this->setupTemplate($request, $conference, $schedConf);

		$templateMgr->assign('accommodationDescription', $schedConf->getLocalizedSetting('accommodationDescription'));
		$templateMgr->assign('accommodationFiles', $schedConf->getLocalizedSetting('accommodationFiles'));

		$templateMgr->assign('helpTopicId', 'conference.currentConferences.accommodation');
		$templateMgr->display('schedConf/accommodation.tpl');
	}

	/**
	 * Display the presentations
	 */
	function presentations($args, &$request) {
		$this->validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		import('classes.schedConf.SchedConfAction');

		$mayViewProceedings = SchedConfAction::mayViewProceedings($schedConf);
		$mayViewPapers = SchedConfAction::mayViewPapers($schedConf, $conference);

		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate($request, $conference, $schedConf);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR); // FIXME: director.allTracks

		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true),
			array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true)));
		$templateMgr->assign('helpTopicId', 'editorial.trackDirectorsRole.presentations');
		$templateMgr->assign_by_ref('schedConf', $schedConf);

		$templateMgr->assign('mayViewProceedings', $mayViewProceedings);
		$templateMgr->assign('mayViewPapers', $mayViewPapers);

		if($mayViewProceedings) {
			$publishedPaperDao = DAORegistry::getDAO('PublishedPaperDAO');
			$trackDao = DAORegistry::getDAO('TrackDAO');

			$tracks =& $trackDao->getTrackTitles($schedConf->getId());

			// Get the user's search conditions, if any
			$searchField = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');
			$search = $request->getUserVar('search');

			$searchInitial = $request->getUserVar('searchInitial');
			if (!empty($searchInitial)) {
				$searchField = SUBMISSION_FIELD_AUTHOR;
				$searchMatch = 'initial';
				$search = $searchInitial;
			}

			$templateMgr->assign('fieldOptions', Array(
				SUBMISSION_FIELD_TITLE => 'paper.title',
				SUBMISSION_FIELD_AUTHOR => 'user.role.author'
			));

			$previewAbstracts = (
				$schedConf->getSetting('reviewMode') == REVIEW_MODE_BOTH_SEQUENTIAL &&
				$schedConf->getSetting('previewAbstracts')
			);

			$publishedPapers =& $publishedPaperDao->getPublishedPapersInTracks($schedConf->getId(), $request->getUserVar('track'), $searchField, $searchMatch, $search, $previewAbstracts);

			// Set search parameters
			$duplicateParameters = array(
				'searchField', 'searchMatch', 'search', 'searchInitial', 'track'
			);
			foreach ($duplicateParameters as $param)
				$templateMgr->assign($param, $request->getUserVar($param));

			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
			$templateMgr->assign('trackOptions', array(0 => AppLocale::Translate('director.allTracks')) + $tracks);
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
	function setupTemplate($request, &$conference, &$schedConf) {
		parent::setupTemplate($request);
		$templateMgr =& TemplateManager::getManager();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);

		// Ensure the user is entitled to view the scheduled conference...
		if (isset($schedConf) && ($conference->getEnabled() || (
				Validation::isDirector($conference->getId()) ||
				Validation::isConferenceManager($conference->getId())))) {

			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $conference->getPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $conference->getPageHeaderLogo(true));
			$templateMgr->assign('displayPageHeaderTitleAltText', $conference->getLocalizedSetting('homeHeaderTitleImageAltText'));
			$templateMgr->assign('displayPageHeaderLogoAltText', $conference->getLocalizedSetting('homeHeaderLogoImageAltText'));
			$templateMgr->assign_by_ref('schedConf', $schedConf);
			$templateMgr->assign('additionalHomeContent', $conference->getLocalizedSetting('additionalHomeContent'));
		} else {
			$request->redirect(null, 'index');
		}

		if ($styleFileName = $schedConf->getStyleFileName()) {
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$templateMgr->addStyleSheet(
				$request->getBaseUrl() . '/' . $publicFileManager->getConferenceFilesPath($conference->getId()) . '/' . $styleFileName
			);
		}
	}

	function validate() {
		parent::validate();

		$schedConf =& Request::getSchedConf();
		if(!SchedConfAction::mayViewSchedConf($schedConf)) {
			Request::redirect(null, 'index');
		}

		return true;
	}
}

?>
