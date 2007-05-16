<?php

/**
 * SchedConfHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.schedConf
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
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$templateMgr->assign('schedConfOverview', $schedConf->getSetting('schedConfOverview'));

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
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
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
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);
		
		$templateMgr->assign('cfpMessage', $schedConf->getSetting('cfpMessage', false));

		$submissionsOpenDate = $schedConf->getSetting('submissionsOpenDate', false);
		$submissionsCloseDate = $schedConf->getSetting('submissionsCloseDate', false);

		if(!$submissionsOpenDate || !$submissionsCloseDate || time() < $submissionsOpenDate) {
			// Too soon
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('presenter.submit.notAcceptingYet');
		} elseif (time() > $submissionsCloseDate) {
			// Too late
			$acceptingSubmissions = false;
			$notAcceptingSubmissionsMessage = Locale::translate('presenter.submit.submissionDeadlinePassed', array('closedDate' => Date('Y-m-d', $submissionsCloseDate)));
		} else {
			$acceptingSubmissions = true;
		}
				
		$templateMgr->assign('acceptingSubmissions', $acceptingSubmissions);
		if (!$acceptingSubmissions) $templateMgr->assign('notAcceptingSubmissionsMessage', $notAcceptingSubmissionsMessage);
		$templateMgr->assign('helpTopicId', 'schedConf.cfp');
		$templateMgr->display('schedConf/cfp.tpl');
	}

	/**
	 * Display conference program page
	 */
	function registration() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		$paymentManager =& OCSPaymentManager::getManager();
		if (!$paymentManager->isConfigured()) Request::redirect(null, null, 'index');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		SchedConfHandler::setupSchedConfTemplate($conference,$schedConf);

		$user =& Request::getUser();
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		if ($user && ($registrationId = $registrationDao->getRegistrationIdByUser($user->getUserId(), $schedConf->getSchedConfId()))) {
			// This user has already registered.
			$registration =& $registrationDao->getRegistration($registrationId);

			if ($registration && $registration->getDatePaid()) $templateMgr->assign('message', 'schedConf.registration.alreadyRegisteredAndPaid');
			else $templateMgr->assign('message', 'schedConf.registration.alreadyRegistered');

			$templateMgr->assign('backLinkLabel', 'common.back');
			$templateMgr->assign('backLink', Request::url(null, null, 'index'));
			$templateMgr->display('common/message.tpl');
		} else {
			import('registration.form.UserRegistrationForm');

			$form =& new UserRegistrationForm();
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
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
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
	 * Display the presentations
	 */
	function presentations() {
		list($conference, $schedConf) = SchedConfHandler::validate(true, true);

		import('schedConf.SchedConfAction');

		$mayViewProceedings = SchedConfAction::mayViewProceedings($schedConf);
		$mayViewPapers = SchedConfAction::mayViewPapers($schedConf, $conference);

		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('pageHierarchy', array(
			array(Request::url(null, 'index', 'index'), $conference->getTitle(), true),
			array(Request::url(null, null, 'index'), $schedConf->getTitle(), true)));
		$templateMgr->assign('helpTopicId', 'FIXME');
		$templateMgr->assign_by_ref('schedConf', $schedConf);

		$templateMgr->assign('mayViewProceedings', $mayViewProceedings);
		$templateMgr->assign('mayViewPapers', $mayViewPapers);
		
		if($mayViewProceedings) {
			$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');

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

			$publishedPapers = &$publishedPaperDao->getPublishedPapersInTracks($schedConf->getSchedConfId(), $searchField, $searchMatch, $search);

			// Set search parameters
			$duplicateParameters = array(
				'searchField', 'searchMatch', 'search', 'searchInitial'
			);
			foreach ($duplicateParameters as $param)
				$templateMgr->assign($param, Request::getUserVar($param));

			$templateMgr->assign('alphaList', explode(' ', Locale::translate('common.alphaList')));
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
