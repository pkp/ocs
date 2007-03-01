<?php

/**
 * ImportOCS1.inc.php
 *
 * Copyright (c) 2005-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 *
 * Class to import data from an OCS 1.x installation.
 *
 * $Id$
 */

define('OCS1_MIN_VERSION', '1.1.5');
define('OCS1_MIN_VERSION_SUBSCRIPTIONS', '1.1.8');

import('user.User');
import('conference.Conference');
import('conference.Track');
import('security.Role');
import('registration.Registration');
import('registration.RegistrationType');
import('registration.Currency');
import('paper.Paper');
import('paper.PaperComment');
import('paper.PaperFile');
import('paper.PaperGalley');
import('paper.PaperHTMLGalley');
import('paper.PaperNote');
import('paper.Presenter');
import('paper.PublishedPaper');
import('paper.SuppFile');
import('submission.common/Action');
import('submission.presenter.PresenterSubmission');
import('submission.reviewer.ReviewerSubmission');
import('issue.Issue');
import('submission.copyAssignment.CopyAssignment');
import('submission.editAssignment.EditAssignment');
import('submission.proofAssignment.ProofAssignment');
import('submission.reviewAssignment.ReviewAssignment');
import('comment.Comment');
import('file.PaperFileManager');
import('file.PublicFileManager');
import('search.PaperSearchIndex');

	
class ImportOCS1 {

	//
	// Private variables
	//

	var $importPath;
	var $importVersion;
	var $conferencePath;
	var $conferenceId = 0;
	
	var $userMap = array();
	var $issueMap = array();
	var $trackMap = array();
	var $paperMap = array();
	var $fileMap = array();
	
	var $importDBConn;
	var $importDao;
	
	var $indexUrl;
	var $importConference;
	var $conferenceConfigInfo;
	var $conferenceInfo;
	var $issueLabelFormat;
	
	var $userCount = 0;
	var $issueCount = 0;
	var $paperCount = 0;
	
	var $options;
	var $error;

	/** @var $transcoder object The transcoder to use, if desired */
	var $transcoder;

	/** @var $conflicts array List of conflicting user accounts */
	var $conflicts;

	/**
	 * Constructor.
	 */
	function ImportOCS1() {
		// Note: generally Request's auto-detection won't work correctly
		// when run via CLI so use config setting if available
		$this->indexUrl = Config::getVar('general', 'base_url');
		if ($this->indexUrl)
			$this->indexUrl .= '/' . INDEX_SCRIPTNAME;
		else
			$this->indexUrl = Request::getIndexUrl();

		$this->conflicts = array();
	}

	/**
	 * Transcode a string as necessary.
	 */
	function trans($string) {
		if (isset($this->transcoder)) {
			return $this->transcoder->trans($string);
		}
		// No transcoder configured -- do nothing.
		return $string;
	}

	/**
	 * Record error message.
	 * @return string;
	 */
	function error($message = null) {
		if (isset($message)) {
			$this->error = $message;
		}
		return $this->error;
	}
	
	/**
	 * Check if an option is enabled.
	 * @param $option string
	 * @return boolean
	 */
	function hasOption($option) {
		return in_array($option, $this->options);
	}
	
	/**
	 * Execute import of an OCS 1 conference.
	 * If an existing conference path is specified, only content is imported;
	 * otherwise, a new conference is created and all conference settings are also imported.
	 * @param $conferencePath string conference URL path
	 * @param $importPath string local filesystem path to the base OCS 1 directory
	 * @param $options array supported: 'importRegistrations'
	 * @return boolean/int false or conference ID
	 */
	function import($conferencePath, $importPath, $options = array()) {
		@set_time_limit(0);
		$this->conferencePath = $conferencePath;
		$this->importPath = $importPath;
		$this->options = $options;

		if ($this->hasOption('transcode')) {
			$clientCharset = Config::getVar('i18n', 'client_charset');
			import('core.Transcoder');
			$this->transcoder =& new Transcoder('ISO-8859-1', $clientCharset);
		}

		// Force a new database connection
		$dbconn = &DBConnection::getInstance();
		$dbconn->reconnect(true);
		
		// Create a connection to the old database
		if (!@include($this->importPath . '/include/db.php')) { // Suppress E_NOTICE messages
			$this->error('Failed to load ' . $this->importPath . '/include/db.php');
			return false;
		}
		
		// Assumes no character set (not supported by OCS 1.x)
		// Forces open a new connection
		$this->importDBConn = &new DBConnection($db_config['type'], $db_config['host'], $db_config['uname'], $db_config['password'], $db_config['name'], false, false, true, false, true);
		$dbconn = &$this->importDBConn->getDBConn();
		
		if (!$this->importDBConn->isConnected()) {
			$this->error('Database connection error: ' . $dbconn->errorMsg());
			return false;
		}
		
		$this->importDao = &new DAO($dbconn);
		
		if (!$this->loadConferenceConfig()) {
			$this->error('Unsupported or unrecognized OCS version');
			return false;
		}
		
		// Determine if conference already exists
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$conference = &$conferenceDao->getConferenceByPath($this->conferencePath);
		$this->importConference = ($conference == null);
		
		// Import data
		if ($this->importConference) {
			$this->importConference();
			$this->importReadingTools();
		} else {
			$this->conferenceId = $conference->getConferenceId();
		}
		$this->importUsers();
		if ($this->hasOption('importRegistrations') && version_compare($this->importVersion, OCS1_MIN_VERSION_SUBSCRIPTIONS) >= 0) {
			// Registrations requires OCS >= 1.1.8
			$this->importRegistrations();
		}
		$this->importTracks();
		$this->importIssues();
		$this->importPapers();
		
		// Rebuild search index
		$this->rebuildSearchIndex();
		
		return $this->conferenceId;
	}
	
	/**
	 * Load OCS 1 conference configuration and settings data.
	 * @return boolean
	 */
	function loadConferenceConfig() {
		// Load conference config
		$result = &$this->importDao->retrieve('SELECT * FROM tblconferenceconfig');
		$this->conferenceConfigInfo = &$result->fields;
		$result->Close();
		
		if (!isset($this->conferenceConfigInfo['chOCSVersion'])) {
			return false;
		}
		$this->importVersion = $this->conferenceConfigInfo['chOCSVersion'];
		if (version_compare($this->importVersion, OCS1_MIN_VERSION) < 0) {
			return false;
		}

		// Load conference settings
		$result = &$this->importDao->retrieve('SELECT * FROM tblconference');
		$this->conferenceInfo = &$result->fields;
		$result->Close();
		
		return true;
	}
	
	
	//
	// Conference
	//
	
	/**
	 * Import conference and conference settings.
	 */
	function importConference() {
		if ($this->hasOption('verbose')) {
			printf("Importing conference\n");
		}
		
		// Create conference
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$conference = &new Conference();
		$conference->setTitle($this->conferenceInfo['chTitle']);
		$conference->setPath($this->conferencePath);
		$conference->setEnabled(1);
		$this->conferenceId = $conferenceDao->insertConference($conference);
		$conferenceDao->resequenceConferences();
		
		// Add conference manager role for site administrator(s)
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$admins = $roleDao->getUsersByRoleId(ROLE_ID_SITE_ADMIN);
		foreach ($admins->toArray() as $admin) {
			$role = &new Role();
			$role->setConferenceId($this->conferenceId);
			$role->setUserId($admin->getUserId());
			$role->setRoleId(ROLE_ID_CONFERENCE_MANAGER);
			$roleDao->insertRole($role);
		}
		
		// Install the default RT versions.
		import('rt.ocs.ConferenceRTAdmin');
		$conferenceRtAdmin = &new ConferenceRTAdmin($this->conferenceId);
		$conferenceRtAdmin->restoreVersions(false);
		
		// Publishers, sponsors, and contributors
		$publisher = array();
		$sponsors = array();
		$result = &$this->importDao->retrieve('SELECT * FROM tblsponsors ORDER BY nSponsorID');
		while (!$result->EOF) {
			$row = &$result->fields;
			$sponsors[] = array('institution' => $this->trans($row['chName']), 'url' => $this->trans($row['chWebpage']));
			if (empty($publisher)) {
				$publisher = array('institution' => $this->trans($row['chName']), 'url' => $this->trans($row['chWebpage']));
			}
			$result->MoveNext();
		}
		$result->Close();
		
		$contributors = array();
		$result = &$this->importDao->retrieve('SELECT * FROM tblcontributors ORDER BY nContributorID');
		while (!$result->EOF) {
			$row = &$result->fields;
			$contributors[] = array('name' => $this->trans($row['chName']), 'url' => $this->trans($row['chWebpage']));
			$result->MoveNext();
		}
		$result->Close();
		
		// Submission checklist
		$submissionChecklist = array();
		$result = &$this->importDao->retrieve('SELECT * FROM tblsubmissionchecklist ORDER BY nOrder');
		while (!$result->EOF) {
			$row = &$result->fields;
			$submissionChecklist[] = array('order' => $row['nOrder'], 'content' => $this->trans($row['chCheck']));
			$result->MoveNext();
		}
		$result->Close();
		
		// Additional about items
		$customAboutItems = array();
		$result = &$this->importDao->retrieve('SELECT * FROM tblaboutconference ORDER BY nItemID');
		while (!$result->EOF) {
			$row = &$result->fields;
			$customAboutItems[] = array('title' => $this->trans($row['chTitle']), 'content' => $this->trans($row['chContent']));
			$result->MoveNext();
		}
		$result->Close();
		
		// Navigation items
		$navItems = array();
		if ($this->conferenceInfo['bDiscussion'] && !empty($this->conferenceInfo['chDiscussionURL'])) {
			$navItems[] = array('name' => $this->trans('Forum'), 'url' => $this->trans($this->conferenceInfo['chDiscussionURL']), 'isLiteral' => '1', 'isAbsolute' => '1');
		}
		
		$publicationFormat = ISSUE_LABEL_NUM_VOL_YEAR;
		if ($this->conferenceInfo['nSchedulingType'] == 1 && !$this->conferenceInfo['bPubUseNum']) {
			$publicationFormat = ISSUE_LABEL_VOL_YEAR;
		} else if($this->conferenceInfo['nSchedulingType'] == 2) {
			$publicationFormat = ISSUE_LABEL_YEAR;
		}
		
		// Conference images
		$homeHeaderLogoImage = $this->copyConferenceImage('chSmallHomeLogo', 'homeHeaderLogoImage');
		$homeHeaderTitleImage = $this->copyConferenceImage('chLargeHomeLogo', 'homeHeaderTitleImage');
		$pageHeaderLogoImage = $this->copyConferenceImage('chSmallLogo', 'pageHeaderLogoImage');
		$pageHeaderTitleImage = $this->copyConferenceImage('chLargeLogo', 'pageHeaderTitleImage');
		$homepageImage = $this->copyConferenceImage('chTableOfContentImage', 'homepageImage');
		
		$translateParams = array('indexUrl' => $this->indexUrl, 'conferencePath' => $this->conferencePath, 'conferenceName' => $this->trans($this->conferenceInfo['chTitle']));

		// Conference settings
		// NOTE: Commented out settings do not have an equivalent in OCS 1.x
		$conferenceSettings = array(
			'conferenceAcronym' => array('string', $this->trans($this->conferenceInfo['chAbbrev'])),
			'issn' => array('string', $this->trans($this->conferenceInfo['chISSN'])),
			'mailingAddress' => array('string', $this->trans($this->conferenceInfo['chMailAddr'])),
			'useEditorialBoard' => array('bool', $this->conferenceInfo['bRevBoard']),
			'contactName' => array('string', $this->trans($this->conferenceInfo['chContactName'])),
			'contactTitle' => array('string', $this->trans($this->conferenceInfo['chContactTitle'])),
			'contactAffiliation' => array('string', $this->trans($this->conferenceInfo['chContactAffiliation'])),
			'contactEmail' => array('string', $this->trans($this->conferenceInfo['chContactEmail'])),
			'contactPhone' => array('string', $this->trans($this->conferenceInfo['chContactPhone'])),
			'contactFax' => array('string', $this->trans($this->conferenceInfo['chContactFax'])),
			'contactMailingAddress' => array('string', $this->trans($this->conferenceInfo['chContactMailAddr'])),
			'supportName' => array('string', $this->trans($this->conferenceInfo['chSupportName'])),
			'supportEmail' => array('string', $this->trans($this->conferenceInfo['chSupportEmail'])),
			'supportPhone' => array('string', $this->trans($this->conferenceInfo['chSupportPhone'])),
			'sponsorNote' => array('string', $this->trans($this->conferenceInfo['chSponsorNote'])),
			'sponsors' => array('object', $sponsors),
			'publisher' => array('object', $publisher),
			'contributorNote' => array('string', $this->trans($this->conferenceInfo['chContribNote'])),
			'contributors' => array('object', $contributors),
			'searchDescription' => array('string', $this->trans($this->conferenceInfo['chMetaDescription'])),
			'searchKeywords' => array('string', $this->trans($this->conferenceInfo['chMetaKeywords'])),
		//	'customHeaders' => array('string', ''),
			
			'focusScopeDesc' => array('string', $this->trans($this->conferenceInfo['chFocusScope'])),
			'numWeeksPerReview' => array('int', $this->conferenceInfo['nReviewDueWeeks']),
		//	'remindForInvite' => array('int', ''),
		//	'remindForSubmit' => array('int', ''),
		//	'numDaysBeforeInviteReminder' => array('int', ''),
		//	'numDaysBeforeSubmitReminder' => array('int', ''),
		//	'rateReviewerOnQuality' => array('int', ''),
			'restrictReviewerFileAccess' => array('int', isset($this->conferenceInfo['bReviewerSubmissionRestrict']) ? $this->conferenceInfo['bReviewerSubmissionRestrict'] : 0),
			'reviewPolicy' => array('string', $this->trans($this->conferenceInfo['chReviewProcess'])),
			'mailSubmissionsToReviewers' => array('int', isset($this->conferenceInfo['bReviewerMailSubmission']) ? $this->conferenceInfo['bReviewerMailSubmission'] : 0),
			'reviewGuidelines' => array('string', $this->trans($this->conferenceInfo['chReviewerGuideline'])),
			'presenterSelectsDirector' => array('int', isset($this->conferenceInfo['bPresenterSelectEditor']) ? $this->conferenceInfo['bPresenterSelectEditor'] : 0),
			'privacyStatement' => array('string', $this->trans($this->conferenceInfo['chPrivacyStatement'])),
			'openAccessPolicy' => array('string', $this->trans($this->conferenceInfo['chOpenAccess'])),
		//	'envelopeSender' => array('string', ''),
		//	'disableUserReg' => array('bool', ''),
		//	'allowRegReader' => array('bool', ''),
		//	'allowRegPresenter' => array('bool', ''),
		//	'allowRegReviewer' => array('bool', ''),
		//	'restrictSiteAccess' => array('bool', ''),
		//	'restrictPaperAccess' => array('bool', ''),
		//	'paperEventLog' => array('bool', ''),
		//	'paperEmailLog' => array('bool', ''),
			'customAboutItems' => array('object', $customAboutItems),
		//	'enableComments' => array('int', $this->conferenceInfo['bComments'] ? COMMENTS_UNAUTHENTICATED : COMMENTS_DISABLED),
			'enableLockss' => array('bool', isset($this->conferenceInfo['bEnableLOCKSS']) ? $this->conferenceInfo['bEnableLOCKSS'] : 0),
			'lockssLicense' => array('string', isset($this->conferenceInfo['chLOCKSSLicense']) ? $this->trans($this->conferenceInfo['chLOCKSSLicense']) : Locale::translate('default.conferenceSettings.lockssLicense')),
			
			'presenterGuidelines' => array('string', $this->trans($this->conferenceInfo['chPresenterGuideline'])),
			'submissionChecklist' => array('object', $submissionChecklist),
			'copyrightNotice' => array('string', $this->trans($this->conferenceInfo['chCopyrightNotice'])),
			'metaDiscipline' => array('bool', $this->conferenceInfo['bMetaDiscipline']),
			'metaDisciplineExamples' => array('string', $this->trans($this->conferenceInfo['chDisciplineExamples'])),
			'metaSubjectClass' => array('bool', $this->conferenceInfo['bMetaSubjectClass']),
			'metaSubjectClassTitle' => array('string', $this->trans($this->conferenceInfo['chSubjectClassTitle'])),
			'metaSubjectClassUrl' => array('string', $this->trans($this->conferenceInfo['chSubjectClassURL'])),
			'metaSubject' => array('bool', $this->conferenceInfo['bMetaSubject']),
			'metaSubjectExamples' => array('string', $this->conferenceInfo['chSubjectExamples']),
			'metaCoverage' => array('bool', $this->conferenceInfo['bMetaCoverage']),
			'metaCoverageGeoExamples' => array('string', $this->trans($this->conferenceInfo['chCovGeoExamples'])),
			'metaCoverageChronExamples' => array('string', $this->trans($this->conferenceInfo['chCovChronExamples'])),
			'metaCoverageResearchSampleExamples' => array('string', $this->trans($this->conferenceInfo['chCovSampleExamples'])),
			'metaType' => array('bool', $this->conferenceInfo['bMetaType']),
			'metaTypeExamples' => array('string', $this->trans($this->conferenceInfo['chDisciplineExamples'])),
			
			'publicationFormat' => array('int', $publicationFormat),
			'initialVolume' => array('int', $this->conferenceInfo['nInitVol']),
			'initialNumber' => array('int', $this->conferenceInfo['nInitNum']),
			'initialYear' => array('int', $this->conferenceInfo['nInitYear']),
			'pubFreqPolicy' => array('string', $this->trans($this->conferenceInfo['chFreqPublication'])),
			'useCopyeditors' => array('bool', $this->conferenceInfo['bCopyEditor']),
			'copyeditInstructions' => array('string', $this->trans($this->conferenceInfo['chCopyeditInstructions'])),
			'useProofreaders' => array('bool', $this->conferenceInfo['bProofReader']),
			'enableRegistration' => array('bool', isset($this->conferenceInfo['bRegistrations']) ? $this->conferenceInfo['bRegistrations'] : 0),
			'registrationName' => array('string', $this->trans($this->conferenceInfo['chContactName'])),
			'registrationEmail' => array('string', $this->trans($this->conferenceInfo['chContactEmail'])),
			'registrationPhone' => array('string', $this->trans($this->conferenceInfo['chContactPhone'])),
			'registrationFax' => array('string', $this->trans($this->conferenceInfo['chContactFax'])),
			'registrationMailingAddress' => array('string', $this->trans($this->conferenceInfo['chContactMailAddr'])),
		//	'registrationAdditionalInformation' => array('string', ''),
		//	'volumePerYear' => array('int', ''),
		//	'issuePerVolume' => array('int', ''),
		//	'enablePublicIssueId' => array('bool', ''),
		//	'enablePublicPaperId' => array('bool', ''),
		//	'enablePageNumber' => array('bool', ''),
		
			'homeHeaderTitleType' => array('int', isset($homeHeaderTitleImage) ? 1 : 0),
			'homeHeaderTitle' => array('string', $this->trans($this->conferenceInfo['chTitle'])),
		//	'homeHeaderTitleTypeAlt1' => array('int', 0),
		//	'homeHeaderTitleAlt1' => array('string', ''),
		//	'homeHeaderTitleTypeAlt2' => array('int', 0),
		//	'homeHeaderTitleAlt2' => array('string', ''),
			'pageHeaderTitleType' => array('int', isset($pageHeaderTitleImage) ? 1 : 0),
			'pageHeaderTitle' => array('string', $this->conferenceInfo['chTitle']),
		//	'pageHeaderTitleTypeAlt1' => array('int', 0),
		//	'pageHeaderTitleAlt1' => array('string', ''),
		//	'pageHeaderTitleTypeAlt2' => array('int', 0),
		//	'pageHeaderTitleAlt2' => array('string', ''),
			'homeHeaderLogoImage' => array('object', $homeHeaderLogoImage),
			'homeHeaderTitleImage' => array('object', $homeHeaderTitleImage),
			'pageHeaderLogoImage' => array('object', $pageHeaderLogoImage),
			'pageHeaderTitleImage' => array('object', $pageHeaderTitleImage),
			'homepageImage' => array('object', $homepageImage),
			'readerInformation' => array('string', Locale::translate('default.conferenceSettings.forReaders', $translateParams)),
			'presenterInformation' => array('string', Locale::translate('default.conferenceSettings.forPresenters', $translateParams)),
			'conferencePageHeader' => array('string', $this->trans($this->conferenceInfo['chHeader'])),
			'conferencePageFooter' => array('string', $this->trans($this->conferenceInfo['chFooter'])),
			'additionalHomeContent' => array('string', $this->trans($this->conferenceInfo['chTableOfContentText'])),
			'conferenceDescription' => array('string', $this->trans($this->conferenceInfo['chHomepageIntro'])),
			'navItems' => array('object', $navItems),
			'itemsPerPage' => array('int', 25),
			'numPageLinks' => array('int', 10),
		);
		
		$settingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		
		foreach ($conferenceSettings as $settingName => $settingInfo) {
			list($settingType, $settingValue) = $settingInfo;
			$settingsDao->updateSetting($this->conferenceId, $settingName, $settingValue, $settingType);
		}
	}
		
	/**
	 * Import reading tools (nee RST) settings.
	 */
	function importReadingTools() {
		if ($this->hasOption('verbose')) {
			printf("Importing RT settings\n");
		}
		
		$rtDao = &DAORegistry::getDAO('RTDAO');
		
		$versionId = 0;
		
		// Try to map to new version
		$result = &$this->importDao->retrieve('SELECT chTitle FROM tblrstversions WHERE bDefault = 1');
		if ($result->RecordCount() != 0) {
			$result = &$rtDao->retrieve('SELECT version_id FROM rt_versions WHERE conference_id = ? AND title = ?', array($this->conferenceId, $result->fields[0]));
			if ($result->RecordCount() != 0) {
				$versionId = $result->fields[0];
			}
		}
		$result->Close();
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblrst');
		$row = &$result->fields;
		
		import('rt.ocs.ConferenceRT');
		$rt = &new ConferenceRT($this->conferenceId);
		$rt->setVersion($versionId);
		$rt->setCaptureCite($row['bCaptureCite']);
		$rt->setViewMetadata($row['bViewMetadata']);
		$rt->setSupplementaryFiles($row['bSuppFiles']);
		$rt->setPrinterFriendly($row['bPrintVersion']);
		$rt->setPresenterBio($row['bPresenterBios']);
		$rt->setDefineTerms($row['bDefineTerms']);
		$rt->setAddComment($row['bAddComment']);
		$rt->setEmailPresenter($row['bEmailPresenter']);
		$rt->setEmailOthers($row['bEmailOthers']);
		$rt->setBibFormat($this->conferenceInfo['chCitationStyle']);

		$result->Close();

		$rtDao->insertConferenceRT($rt);
	}
	
	
	//
	// Users
	//
	
	/**
	 * Import users and roles.
	 */
	function importUsers() {
		if ($this->hasOption('verbose')) {
			printf("Importing users\n");
		}
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$notifyDao = &DAORegistry::getDAO('NotificationStatusDAO');
		
		$result = &$this->importDao->retrieve('SELECT *, DECODE(chPassword, ?) AS chPassword FROM tblusers ORDER BY nUserID', $this->conferenceConfigInfo['chPasswordSalt']);
		while (!$result->EOF) {
			$row = &$result->fields;

			$chFirstName = $this->trans($row['chFirstName']);
			$chMiddleInitial = $this->trans($row['chMiddleInitial']);
			$chSurname = $this->trans($row['chSurname']);

			$initials = substr($chFirstName, 0, 1) . (empty($chMiddleInitial) ? '' : substr($chMiddleInitial, 0, 1)) . substr($chSurname, 0, 1);
			$interests = '';
		
			if ($row['fkEditorID']) {
				$tmpResult = &$this->importDao->retrieve('SELECT chInitials, nEditorRole FROM tbleditors WHERE nEditorID = ?', $row['fkEditorID']);
				$initials = $this->trans($tmpResult->fields[0]);
				$directorRole = $this->trans($tmpResult->fields[1]);
				$tmpResult->Close();
			}
			
			if ($row['fkReviewerID']) {
				$tmpResult = &$this->importDao->retrieve('SELECT chInterests FROM tblreviewers WHERE nReviewerID = ?', $row['fkReviewerID']);
				$interests = $this->trans($tmpResult->fields[0]);
				$tmpResult->Close();
			}
			
			// Check for existing user with this username
			$user = $userDao->getUserByUsername($this->trans($row['chUsername']));
			$existingUser = ($user != null);
			
			if (!isset($user)) {
				// Create new user
				$user = &new User();
				$user->setUsername($this->trans($row['chUsername']));
				$user->setPassword(Validation::encryptCredentials($this->trans($row['chUsername']), $this->trans($row['chPassword'])));
				$user->setFirstName($this->trans($row['chFirstName']));
				$user->setMiddleName($this->trans($row['chMiddleInitial']));
				$user->setInitials($this->trans($initials));
				$user->setLastName($this->trans($row['chSurname']));
				$user->setAffiliation($this->trans($row['chAffiliation']));
				$user->setEmail($this->trans($row['chEmail']));
				$user->setPhone($this->trans($row['chPhone']));
				$user->setFax($this->trans($row['chFax']));
				$user->setMailingAddress($this->trans($row['chMailAddr']));
				$user->setBiography($this->trans($row['chBiography']));
				$user->setInterests($this->trans($interests));
				$user->setLocales(array());
				$user->setDateRegistered($row['dtDateSignedUp']);
				$user->setDateLastLogin($row['dtDateSignedUp']);
				$user->setMustChangePassword(0);
				$user->setDisabled(0);

				$otherUser =& $userDao->getUserByEmail($this->trans($row['chEmail']));
				if ($otherUser !== null) {
					// User exists with this email -- munge it to make unique
					$user->setEmail('ocs-' . $this->trans($row['chUsername']) . '+' . $this->trans($row['chEmail']));
					$this->conflicts[] = array(&$otherUser, &$user);
				}
				unset($otherUser);
				
				$userDao->insertUser($user);
			}
			$userId = $user->getUserId();
			
			if ($row['bNotify']) {
				if ($existingUser) {
					// Just in case
					$notifyDao->setConferenceNotifications($this->conferenceId, $userId, 0);
				}
				$notifyDao->setConferenceNotifications($this->conferenceId, $userId, 1);
			}
			
			if ($row['fkEditorID']) {
				$role = &new Role();
				$role->setConferenceId($this->conferenceId);
				$role->setUserId($userId);
				switch ($directorRole) {
					case 0:
						$role->setRoleId(ROLE_ID_DIRECTOR);
						break;
					case 1:
						$role->setRoleId(ROLE_ID_TRACK_DIRECTOR);
						break;
					case 2:
						$role->setRoleId(ROLE_ID_CONFERENCE_MANAGER);
						break;
				}
				
				if (!$existingUser || !$roleDao->roleExists($role->getConferenceId(), $role->getSchedConfId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkPresenterID']) {
				$role = &new Role();
				$role->setConferenceId($this->conferenceId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_PRESENTER);
				if (!$existingUser || !$roleDao->roleExists($role->getConferenceId(), $role->getSchedConfId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkReviewerID']) {
				$role = &new Role();
				$role->setConferenceId($this->conferenceId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_REVIEWER);
				if (!$existingUser || !$roleDao->roleExists($role->getConferenceId(), $role->getSchedConfId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkCopyEdID']) {
				$role = &new Role();
				$role->setConferenceId($this->conferenceId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_COPYEDITOR);
				if (!$existingUser || !$roleDao->roleExists($role->getConferenceId(), $role->getSchedConfId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkProofID']) {
				$role = &new Role();
				$role->setConferenceId($this->conferenceId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_PROOFREADER);
				if (!$existingUser || !$roleDao->roleExists($role->getConferenceId(), $role->getSchedConfId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			if ($row['fkReaderID']) {
				$role = &new Role();
				$role->setConferenceId($this->conferenceId);
				$role->setUserId($userId);
				$role->setRoleId(ROLE_ID_READER);
				if (!$existingUser || !$roleDao->roleExists($role->getConferenceId(), $role->getSchedConfId(), $role->getUserId(), $role->getRoleId())) {
					$roleDao->insertRole($role);
				}
			}
			
			$this->userMap[$row['nUserID']] = $userId;
			$this->userCount++;
			$result->MoveNext();
			unset($user);
		}
		$result->Close();
	}
	
	/**
	 * Import registrations and registration types.
	 */
	function importRegistrations() {
		if ($this->hasOption('verbose')) {
			printf("Importing registrations\n");
		}
		
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
		
		$registrationTypeMap = array();
		
		$registrationFormatMap = array(
			1 => SUBSCRIPTION_TYPE_FORMAT_PRINT,
			2 => SUBSCRIPTION_TYPE_FORMAT_ONLINE,
			3 => SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE
		);
		
		$currencyMap = array(
			1 => 22,	// CDN
			2 => 160	// USD
		);
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblregistrationtype ORDER BY nOrder');
		$count = 0;
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$registrationType = &new RegistrationType();
			$registrationType->setConferenceId($this->conferenceId);
			$registrationType->setTypeName($this->trans($row['chRegistrationType']));
			$registrationType->setDescription($this->trans($row['chRegistrationTypeDesc']));
			$registrationType->setCost($row['fCost']);
			$registrationType->setCurrencyId(isset($currencyMap[$row['fkCurrencyID']]) ? $currencyMap[$row['fkCurrencyID']] : 160);
			$registrationType->setDuration(12); // No equivalent in OCS 1.x
			$registrationType->setFormat(isset($registrationFormatMap[$row['fkRegistrationFormatID']]) ? $registrationFormatMap[$row['fkRegistrationFormatID']] : SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE);
			$registrationType->setInstitutional($row['bInstitutional']);
			$registrationType->setMembership($row['bMembership']);
			$registrationType->setPublic(1); // No equivalent in OCS 1.x
			$registrationType->setSequence(++$count);
			
			$registrationTypeDao->insertRegistrationType($registrationType);
			$registrationTypeMap[$row['nRegistrationTypeID']] = $registrationType->getTypeId();
			$result->MoveNext();
		}
		$result->Close();
		
		$result = &$this->importDao->retrieve('SELECT tblsubscribers.*, nUserID FROM tblsubscribers LEFT JOIN tblusers ON nSubscriberID = fkSubscriberID ORDER BY nSubscriberID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$registration = &new Registration();
			$registration->setConferenceId($this->conferenceId);
			$registration->setUserId(isset($this->userMap[$row['nUserID']]) ? $this->userMap[$row['nUserID']] : 0);
			$registration->setTypeId(isset($registrationTypeMap[$row['fkRegistrationTypeID']]) ? $registrationTypeMap[$row['fkRegistrationTypeID']] : 0);
			$registration->setDateStart($row['dtDateStart']);
			$registration->setDateEnd($row['dtDateEnd']);
			$registration->setMembership($this->trans($row['chMembership']));
			$registration->setDomain($this->trans($row['chDomain']));
			$registration->setIPRange('');
			
			$registrationDao->insertRegistration($registration);
			$result->MoveNext();
		}
		$result->Close();
	}
	
	
	//
	// Issues
	//
	
	/**
	 * Import issues.
	 */
	function importIssues() {
		if ($this->hasOption('verbose')) {
			printf("Importing issues\n");
		}
		
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		
		$this->issueLabelFormat = ISSUE_LABEL_NUM_VOL_YEAR;
		if ($this->conferenceInfo['nSchedulingType'] == 1 && !$this->conferenceInfo['bPubUseNum']) {
			$this->issueLabelFormat = ISSUE_LABEL_VOL_YEAR;
		} else if($this->conferenceInfo['nSchedulingType'] == 2) {
			$this->issueLabelFormat = ISSUE_LABEL_YEAR;
		}
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblissues ORDER BY bPublished DESC, bLive ASC, nYear ASC, nVolume ASC, nNumber ASC');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$issue = &new Issue();
			$issue->setConferenceId($this->conferenceId);
			$issue->setTitle($this->trans($row['chIssueTitle']));
			$issue->setVolume($row['nVolume']);
			$issue->setNumber($row['nNumber']);
			$issue->setYear($row['nYear']);
			$issue->setPublished($row['bPublished']);
			$issue->setCurrent($row['bLive']);
			$issue->setDatePublished($row['dtDatePublished']);
			$issue->setAccessStatus(OPEN_ACCESS);
			$issue->setOpenAccessDate(isset($row['dtDateOpenAccess']) ? $row['dtDateOpenAccess'] : null);
			$issue->setLabelFormat($this->issueLabelFormat);
			$issue->setDescription('');
			$issue->setShowCoverPage(0);
			
			$issueId = $issueDao->insertIssue($issue);
			$this->issueMap[$row['nIssueID']] = $issueId;
			$this->issueCount++;
			$result->MoveNext();
		}
		$result->Close();
		
		if ($this->issueLabelFormat == ISSUE_LABEL_YEAR) {
			// Insert issues for each year in "publish by year" mode
			$result = &$this->importDao->retrieve('SELECT DISTINCT(DATE_FORMAT(dtDatePublished, \'%Y\')) FROM tblpapers WHERE bPublished = 1 ORDER BY year');
			while (!$result->EOF) {
				list($year) = $result->fields;
				
				$issue = &new Issue();
				$issue->setConferenceId($this->conferenceId);
				$issue->setTitle('');
				$issue->setVolume('');
				$issue->setNumber('');
				$issue->setYear($year);
				$issue->setPublished(1);
				$issue->setDatePublished($year . '-01-01');
				$issue->setAccessStatus(OPEN_ACCESS);
				$issue->setOpenAccessDate(null);
				$issue->setLabelFormat($this->issueLabelFormat);
				
				$result->MoveNext();
			
				$issue->setCurrent($result->EOF ? 1 : 0);	
				$issueId = $issueDao->insertIssue($issue);
				$this->issueMap['YEAR' . $year] = $issueId;
				$this->issueCount++;
			}
			$result->Close();			
		}

		// Import custom track ordering
		$count = 0;
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$result = &$this->importDao->retrieve('SELECT * FROM tblissuestotracks ORDER BY nTrackRank');
		while (!$result->EOF) {
			$row = &$result->fields;

			$trackId = isset($this->trackMap[$row['fkTrackID']])?$this->trackMap[$row['fkTrackID']]:null;
			$issueId = isset($this->issueMap[$row['fkIssueID']])?$this->issueMap[$row['fkIssueID']]:null;

			if (isset($trackId) && isset($issueId)) {
				$trackDao->_insertCustomTrackOrder($issueId, $trackId, $count);
			}
			$result->MoveNext();
		}
		$result->Close();
	}
	
	/**
	 * Import tracks.
	 */
	function importTracks() {
		if ($this->hasOption('verbose')) {
			printf("Importing tracks\n");
		}
		
		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$trackDirectorDao = &DAORegistry::getDAO('TrackDirectorsDAO');
		
		$result = &$this->importDao->retrieve('SELECT * FROM tbltracks ORDER BY nRank');
		$count = 0;
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$track = &new Track();
			$track->setConferenceId($this->conferenceId);
			$track->setTitle($this->trans($row['chTitle']));
			$track->setAbbrev($this->trans($row['chAbbrev']));
			$track->setSequence(++$count);
			$track->setMetaIndexed($row['bMetaIndex']);
			$track->setDirectorRestricted($row['bAcceptSubmissions'] ? 0 : 1);
			$track->setPolicy($this->trans($row['chPolicies']));
			
			$trackId = $trackDao->insertTrack($track);
			$this->trackMap[$row['nTrackID']] = $trackId;
			$result->MoveNext();
		}
		$result->Close();

		// Note: ignores board members (not supported in OCS 1.x)
		$result = &$this->importDao->retrieve('SELECT nUserID, fkTrackID FROM tblusers, tbleditorsections WHERE tblusers.fkEditorID = tbleditorsections.fkEditorID AND fkTrackID IS NOT NULL AND fkTrackID != -1 ORDER BY nUserID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			if (isset($this->trackMap[$row['fkTrackID']]) && isset($this->userMap[$row['nUserID']])) {
				$trackDirectorDao->insertDirector($this->conferenceId, $this->trackMap[$row['fkTrackID']], $this->userMap[$row['nUserID']]);
			}
			
			$result->MoveNext();
		}
		$result->Close();
	}
	
	/**
	 * Import papers (including metadata and files).
	 */
	function importPapers() {
		if ($this->hasOption('verbose')) {
			printf("Importing papers\n");
		}
		
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$copyAssignmentDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		
		$paperUsers = array();
		
		$reviewRecommendations = array(
			0 => null,
			1 => null,
			2 => SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
			3 => SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS,
			4 => SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE,
			5 => SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE,
			6 => SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE,
			7 => SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS
		);
		
		// Import papers
		$result = &$this->importDao->retrieve('SELECT tblpapers.*, editor.nUserID AS nEditorUserID FROM tblpapers LEFT JOIN tblusers AS editor ON (tblpapers.fkEditorId = editor.fkEditorID) ORDER by nPaperID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$status = STATUS_QUEUED;
			if ($row['nStatus'] !== null) {
				if ($row['nStatus'] == 3) {
					$status = STATUS_DECLINED;
				} else if ($row['bArchive']) {
					$status = STATUS_ARCHIVED;
				} else if($row['bPublished']) {
					$status = STATUS_PUBLISHED;
				} else if($row['bSchedule']) {
					$status = STATUS_SCHEDULED;
				}
			}
			
			$paper = &new Paper();
			$paper->setUserId(1);
			$paper->setConferenceId($this->conferenceId);
			$paper->setTrackId(isset($this->trackMap[$row['fkTrackID']]) ? $this->trackMap[$row['fkTrackID']] : 0);
			$paper->setTitle($this->trans($row['chMetaTitle']));
			$paper->setTitleAlt1('');
			$paper->setTitleAlt2('');
			$paper->setAbstract($this->trans($row['chMetaAbstract']));
			$paper->setAbstractAlt1('');
			$paper->setAbstractAlt2('');
			$paper->setDiscipline($this->trans($row['chMetaDiscipline']));
			$paper->setSubjectClass($this->trans($row['chMetaSubjectClass']));
			$paper->setSubject($this->trans($row['chMetaSubject']));
			$paper->setCoverageGeo($this->trans($row['chMetaCoverageGeo']));
			$paper->setCoverageChron($this->trans($row['chMetaCoverageChron']));
			$paper->setCoverageSample($this->trans($row['chMetaCoverageSample']));
			$paper->setType($this->trans($row['chMetaType_Presenter']));
			$paper->setLanguage($this->trans($row['chMetaLanguage']));
			$paper->setSponsor($this->trans($row['chMetaSponsor_Presenter']));
			$paper->setCommentsToDirector($this->trans($row['chNotesToEditor']));
			$paper->setDateSubmitted($row['dtDateSubmitted']);
			$paper->setDateStatusModified($row['dtDateSubmitted']);
			$paper->setLastModified($row['dtDateSubmitted']);
			$paper->setStatus($status);
			$paper->setSubmissionProgress($row['dtDateSubmitted'] ? 0 : $row['nSubmissionProgress']);
			$paper->setPages('');
			
			// Add paper presenters
			$presenterResult = &$this->importDao->retrieve('SELECT nUserID, tblmetapresenters.* FROM tblmetapresenters LEFT JOIN tblusers ON tblmetapresenters.fkPresenterID = tblusers.fkPresenterID WHERE fkPaperID = ? ORDER BY nRank', $row['nPaperID']);
			while (!$presenterResult->EOF) {
				$presenterRow = &$presenterResult->fields;
				
				$presenter = &new Presenter();
				$presenter->setFirstName($this->trans($presenterRow['chFirstName']));
				$presenter->setMiddleName($this->trans($presenterRow['chMiddleInitial']));
				$presenter->setLastName($this->trans($presenterRow['chSurname']));
				$presenter->setAffiliation($this->trans($presenterRow['chAffiliation']));
				$presenter->setEmail($this->trans($presenterRow['chEmail']));
				$presenter->setBiography($this->trans($presenterRow['chBiography']));
				$presenter->setPrimaryContact($presenterRow['bPrimaryContact']);
				
				if ($presenterRow['bPrimaryContact'] && isset($this->userMap[$presenterRow['nUserID']])) {
					$paper->setUserId($this->userMap[$presenterRow['nUserID']]);
				}
				
				$paper->addPresenter($presenter);
				$presenterResult->MoveNext();
			}
			$presenterResult->Close();
			
			$paperDao->insertPaper($paper);
			$paperId = $paper->getPaperId();
			$this->paperMap[$row['nPaperID']] = $paperId;
			$this->paperCount++;
			
			$paperUsers[$paperId] = array(
				'presenterId' => $paper->getUserId(),
				'directorId' => isset($this->userMap[$row['nEditorUserID']]) ? $this->userMap[$row['nEditorUserID']] : $paper->getUserId(),
				'proofId' => 0,
				'reviewerId' => array(),
				'reviewId' => array()
			);
			
			if (empty($row['fkIssueID']) && $row['bPublished'] && $row['dtDatePublished'] && $this->issueLabelFormat == ISSUE_LABEL_YEAR) {
				$row['fkIssueID'] = 'YEAR' . date('Y', strtotime($row['dtDatePublished']));
			}
			
			if ($row['fkIssueID']) {
				$publishedPaper = &new PublishedPaper();
				$publishedPaper->setPaperId($paperId);
				$publishedPaper->setIssueId($this->issueMap[$row['fkIssueID']]);
				$publishedPaper->setDatePublished($row['dtDatePublished'] ? $row['dtDatePublished'] : $row['dtDateSubmitted']);
				$publishedPaper->setSeq((int)$row['nOrder']);
				$publishedPaper->setViews($row['nHitCounter']);
				$publishedPaper->setTrackId(isset($this->trackMap[$row['fkTrackID']]) ? $this->trackMap[$row['fkTrackID']] : 0);
				$publishedPaper->setAccessStatus(isset($row['fkPublishStatusID']) && $row['fkPublishStatusID'] == 2 ? SUBSCRIPTION : OPEN_ACCESS);
				
				$publishedPaperDao->insertPublishedPaper($publishedPaper);
			}
			
			// Paper files
			if ($row['fkFileOriginalID']) {
				$fileId = $this->addPaperFile($paperId, $row['fkFileOriginalID'], PAPER_FILE_SUBMISSION);
				$paper->setSubmissionFileId($fileId);
			}
			if ($row['fkFileRevisionsID']) {
				$fileId = $this->addPaperFile($paperId, $row['fkFileRevisionsID'], PAPER_FILE_DIRECTOR);
				$paper->setRevisedFileId($fileId);
			}
			if ($row['fkFileEditorID']) {
				$fileId = $this->addPaperFile($paperId, $row['fkFileEditorID'], PAPER_FILE_DIRECTOR);
				$paper->setDirectorFileId($fileId);
			}
			
			if ($row['dtDateSubmitted']) {
				$fileManager = &new PaperFileManager($paperId);
				
				if ($paper->getSubmissionFileId()) {
					// Copy submission file to review version (not separate in OCS 1.x)
					$fileId = $fileManager->copyToReviewFile($paper->getSubmissionFileId());
					$paper->setReviewFileId($fileId);
					if (!$paper->getDirectorFileId()) {
						$fileId = $fileManager->copyToDirectorFile($fileId);
						$paper->setDirectorFileId($fileId);
					}
				}
				
				// Add director decision and review stage (only one stage in OCS 1.x)
				if ($row['dtDateEdDec']) {
					$paperDao->update('INSERT INTO edit_decisions
							(paper_id, stage, director_id, decision, date_decided)
							VALUES (?, ?, ?, ?, ?)',
							array($paperId, 1, isset($this->userMap[$row['nEditorUserID']]) ? $this->userMap[$row['nEditorUserID']] : 0, $row['nStatus'] == 3 ? SUBMISSION_DIRECTOR_DECISION_DECLINE : SUBMISSION_DIRECTOR_DECISION_ACCEPT, $paperDao->datetimeToDB($row['dtDateEdDec'])));
				}
				
				$paperDao->update('INSERT INTO review_stages
					(paper_id, stage, review_revision)
					VALUES
					(?, ?, ?)',
					array($paperId, 1, 1)
				);
				
				// Paper galleys
				if ($row['fkFileHTMLID']) {
					$fileId = $this->addPaperFile($paperId, $row['fkFileHTMLID'], PAPER_FILE_PUBLIC);
					$galley = &new PaperHTMLGalley();
					$galley->setPaperId($paperId);
					$galley->setFileId($fileId);
					$galley->setLabel('HTML');
					$galley->setSequence(1);
					if ($row['fkFileStyleID']) {
						$fileId = $this->addPaperFile($paperId, $row['fkFileStyleID'], PAPER_FILE_PUBLIC);
						$galley->setStyleFile($fileId);
					}
					$galleyDao->insertGalley($galley);
					$this->copyHTMLGalleyImages($galley, $row['chLongID']);
				}
				if ($row['fkFilePDFID']) {
					$fileId = $this->addPaperFile($paperId, $row['fkFilePDFID'], PAPER_FILE_PUBLIC);
					$galley = &new PaperGalley();
					$galley->setPaperId($paperId);
					$galley->setFileId($fileId);
					$galley->setLabel('PDF');
					$galley->setSequence(2);
					$galleyDao->insertGalley($galley);
				}
				if ($row['fkFilePostScriptID']) {
					$fileId = $this->addPaperFile($paperId, $row['fkFilePostScriptID'], PAPER_FILE_PUBLIC);
					$galley = &new PaperGalley();
					$galley->setPaperId($paperId);
					$galley->setFileId($fileId);
					$galley->setLabel('PostScript');
					$galley->setSequence(3);
					$galleyDao->insertGalley($galley);
				}
			
				// Create submission management assignment records
				if ($row['nEditorUserID']) {
					// Director assignment
					$editAssignment = &new EditAssignment();
					$editAssignment->setPaperId($paperId);
					$editAssignment->setDirectorId($this->userMap[$row['nEditorUserID']]);
					$editAssignment->setCanEdit(1);
					$editAssignment->setCanReview(1);
					$editAssignment->setDateNotified($row['dtDateEditorNotified']);
					$editAssignment->setDateUnderway($row['dtDateEditorNotified']);
					$editAssignmentDao->insertEditAssignment($editAssignment);
				}
				
				// Copyediting assignment
				$copyAssignment = &new CopyeditorSubmission();
				$copyAssignment->setPaperId($paperId);
				$copyResult = &$this->importDao->retrieve('SELECT tblcopyedit.*, nUserID FROM tblcopyedit, tblpapersassigned, tblusers WHERE tblcopyedit.fkPaperID = tblpapersassigned.fkPaperID AND tblusers.fkCopyEdID = tblpapersassigned.fkCopyEdID AND bReplaced = 0 AND bDeclined = 0 AND tblcopyedit.fkPaperID = ?', $row['nPaperID']);
				if ($copyResult->RecordCount() != 0) {
					$copyRow = &$copyResult->fields;
					
					if ($copyRow['fkFileCopyEdID']) {
						$fileId = $this->addPaperFile($paperId, $copyRow['fkFileCopyEdID'], PAPER_FILE_COPYEDIT);
						$paper->setCopyeditFileId($fileId);
					}
					
					$copyAssignment->setCopyeditorId($this->userMap[$copyRow['nUserID']]);
					$copyAssignment->setDateNotified($copyRow['dtDateNotified_CEd']);
					$copyAssignment->setDateUnderway($copyRow['dtDateNotified_CEd']);
					$copyAssignment->setDateCompleted($copyRow['dtDateCompleted_CEd']);
					$copyAssignment->setDateAcknowledged($copyRow['dtDateAcknowledged_CEd']);
					$copyAssignment->setDatePresenterNotified($copyRow['dtDateNotified_Presenter']);
					$copyAssignment->setDatePresenterUnderway($copyRow['dtDateNotified_Presenter']);
					$copyAssignment->setDatePresenterCompleted($copyRow['dtDateCompleted_Presenter']);
					$copyAssignment->setDatePresenterAcknowledged($copyRow['dtDateAcknowledged_Presenter']);
					$copyAssignment->setDateFinalNotified($copyRow['dtDateNotified_Final']);
					$copyAssignment->setDateFinalUnderway($copyRow['dtDateNotified_Final']);
					$copyAssignment->setDateFinalCompleted($copyRow['dtDateCompleted_Final']);
					$copyAssignment->setDateFinalAcknowledged($copyRow['dtDateAcknowledged_Final']);
					$copyAssignment->setInitialRevision(1);
					$copyAssignment->setDirectorPresenterRevision(1);
					$copyAssignment->setFinalRevision(1);
				} else {
					$copyAssignment->setCopyeditorId(0);
				}
				$copyResult->Close();
				$copyAssignmentDao->insertCopyeditorSubmission($copyAssignment);
				
				// Proofreading assignment
				$proofAssignment = &new ProofAssignment();
				$proofAssignment->setPaperId($paperId);
				$proofResult = &$this->importDao->retrieve('SELECT tblproofread.*, nUserID, dtDateSchedule FROM tblproofread, tblpapersassigned, tblusers, tblpapers WHERE tblproofread.fkPaperID = tblpapers.nPaperID AND tblproofread.fkPaperID = tblpapersassigned.fkPaperID AND tblusers.fkProofID = tblpapersassigned.fkProofID AND bReplaced = 0 AND bDeclined = 0 AND tblproofread.fkPaperID = ?', $row['nPaperID']);
				if ($proofResult->RecordCount() != 0) {
					$proofRow = &$proofResult->fields;
					
					if ($proofRow['fkFileProofID']) {
						// Treat proofreader file as layout file
						$fileId = $this->addPaperFile($paperId, $proofRow['fkFileProofID'], PAPER_FILE_LAYOUT);
					}
					
					$proofAssignment->setProofreaderId($this->userMap[$proofRow['nUserID']]);
					$proofAssignment->setDateSchedulingQueue($proofRow['dtDateSchedule']);
					$proofAssignment->setDatePresenterNotified($proofRow['dtDateNotified_Presenter']);
					$proofAssignment->setDatePresenterUnderway($proofRow['dtDateNotified_Presenter']);
					$proofAssignment->setDatePresenterCompleted($proofRow['dtDateCompleted_Presenter']);
					$proofAssignment->setDatePresenterAcknowledged($proofRow['dtDateAcknowledged_Presenter']);
					$proofAssignment->setDateProofreaderNotified($proofRow['dtDateNotified_Proof']);
					$proofAssignment->setDateProofreaderUnderway($proofRow['dtDateNotified_Proof']);
					$proofAssignment->setDateProofreaderCompleted($proofRow['dtDateCompleted_Proof']);
					$proofAssignment->setDateProofreaderAcknowledged($proofRow['dtDateAcknowledged_Proof']);
				} else {
					$proofAssignment->setProofreaderId(0);
				}
				$proofResult->Close();
				$proofAssignmentDao->insertProofAssignment($proofAssignment);
				
				$reviewerOrder = 1;
				$reviewResult = &$this->importDao->retrieve('SELECT tblreviews.*, tblpapersassigned.*, nUserID FROM tblreviews, tblpapersassigned, tblusers, tblpapers WHERE tblreviews.fkPaperID = tblpapers.nPaperID AND tblreviews.fkPaperID = tblpapersassigned.fkPaperID AND tblusers.fkReviewerID = tblpapersassigned.fkReviewerID AND tblreviews.fkReviewerID = tblpapersassigned.fkReviewerID AND tblpapersassigned.nOrder IS NOT NULL AND tblreviews.fkPaperID = ? ORDER BY nOrder', $row['nPaperID']);
				while (!$reviewResult->EOF) {
					$reviewRow = &$reviewResult->fields;
					
					$reviewAssignment = &new ReviewAssignment();
					
					if ($reviewRow['fkFileRevCopyID']) {
						$fileId = $this->addPaperFile($paperId, $reviewRow['fkFileRevCopyID'], PAPER_FILE_REVIEW);
						$reviewAssignment->setReviewFileId($fileId);
					}
					
					$reviewAssignment->setPaperId($paperId);
					$reviewAssignment->setReviewerId($this->userMap[$reviewRow['nUserID']]);
					$reviewAssignment->setRecommendation($reviewRecommendations[(int)$reviewRow['nRecommendation']]);
					$reviewAssignment->setDateAssigned($reviewRow['dtDateAssigned']);
					$reviewAssignment->setDateNotified($reviewRow['dtDateNotified']);
					$reviewAssignment->setDateConfirmed($reviewRow['dtDateConfirmedDeclined']);
					$reviewAssignment->setDateCompleted($reviewRow['dtDateReviewed']);
					$reviewAssignment->setDateAcknowledged($reviewRow['dtDateAcknowledged']);
					$reviewAssignment->setDateDue($reviewRow['dtDateRequestedBy']);
					$reviewAssignment->setLastModified(isset($reviewRow['dtDateReviewed']) ? $reviewRow['dtDateReviewed'] : (isset($reviewRow['dtDateConfirmedDeclined']) ? $reviewRow['dtDateConfirmedDeclined'] : $reviewRow['dtDateAssigned']));
					$reviewAssignment->setDeclined($reviewRow['bDeclined']);
					$reviewAssignment->setReplaced($reviewRow['bReplaced']);
					$reviewAssignment->setCancelled($reviewRow['bReplaced']);
					$reviewAssignment->setQuality(null);
					$reviewAssignment->setDateRated(null);
					$reviewAssignment->setDateReminded($reviewRow['dtDateReminded']);
					$reviewAssignment->setReminderWasAutomatic(0);
					$reviewAssignment->setStage(1);
					
					$reviewAssignmentDao->insertReviewAssignment($reviewAssignment);
					
					if (!$reviewRow['bReplaced']) {
						$paperUsers[$paperId]['reviewerId'][$reviewerOrder] = $reviewAssignment->getReviewerId();
						$paperUsers[$paperId]['reviewId'][$reviewerOrder] = $reviewAssignment->getReviewId();
						$reviewerOrder++;
					}
					
					$reviewResult->MoveNext();
				}
				$reviewResult->Close();
			}
			
			// Update paper with file IDs, etc.
			$paperDao->updatePaper($paper);
			
			$result->MoveNext();
		}
		$result->Close();
		
		
		// Supplementary files
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblsupplementaryfiles ORDER BY nSupFileID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			$fileId = $this->addPaperFile($this->paperMap[$row['fkPaperID']], $row['fkFileID'], PAPER_FILE_SUPP);
			
			$suppFile = &new SuppFile();
			$suppFile->setFileId($fileId);
			$suppFile->setPaperId($this->paperMap[$row['fkPaperID']]);
			$suppFile->setTitle($this->trans($row['chTitle']));
			$suppFile->setCreator($this->trans($row['chCreator']));
			$suppFile->setSubject($this->trans($row['chSubject']));
			$suppFile->setType($this->trans($row['chType']));
			$suppFile->setTypeOther($this->trans($row['chTypeOther']));
			$suppFile->setDescription($this->trans($row['chDescription']));
			$suppFile->setPublisher($this->trans($row['chPublisher']));
			$suppFile->setSponsor($this->trans($row['chSponsor']));
			$suppFile->setDateCreated($row['dtDateCreated']);
			$suppFile->setSource($this->trans($row['chSource']));
			$suppFile->setLanguage($this->trans($row['chLanguage']));
			$suppFile->setShowReviewers($row['bShowReviewer']);
			$suppFile->setDateSubmitted($row['dtDateCreated']);
			
			$suppFileDao->insertSuppFile($suppFile);
			$result->MoveNext();
		}
		$result->Close();
		
		
		// Paper (public) comments
		$commentDao = &DAORegistry::getDAO('CommentDAO');
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblcomments ORDER BY nCommentID');
		while (!$result->EOF) {
			$row = &$result->fields;
			
			if (!empty($row['chAffiliation'])) {
				$row['chPresenter'] .= ', ' . $this->trans($row['chAffiliation']);
			}
			
			$comment = &new Comment();
			$comment->setPaperId($this->paperMap[$row['fkPaperID']]);
			$comment->setPosterIP('');
			$comment->setPosterName($this->trans($row['chPresenter']));
			$comment->setPosterEmail($this->trans($row['chEmail']));
			$comment->setTitle($this->trans($row['chCommentTitle']));
			$comment->setBody($this->trans($row['chComments']));
			$comment->setDatePosted($row['dtDate']);
			$comment->setDateModified($row['dtDate']);
			$comment->setChildCommentCount(0);
			
			$commentDao->insertComment($comment);
			$result->MoveNext();
		}
		$result->Close();

		
		// Submission comments
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		
		$commentTypes = array(
			'reviewer' => COMMENT_TYPE_PEER_REVIEW,
			'editorrev' => COMMENT_TYPE_DIRECTOR_DECISION,
			'proof' => COMMENT_TYPE_PROOFREAD
		);
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblsubmissioncomments ORDER BY nCommentID');
		while (!$result->EOF) {
			$row = &$result->fields;
			$assocId = $this->paperMap[$row['fkPaperID']];
			
			// Stupidly these strings are localized so this won't necessarily work if using non-English or modified localization
			switch ($row['chFrom']) {
				case 'Presenter':
					$authorId = $paperUsers[$this->paperMap[$row['fkPaperID']]]['presenterId'];
					$roleId = ROLE_ID_PRESENTER;
					break;
				case 'Proofreader':
					$authorId = $paperUsers[$this->paperMap[$row['fkPaperID']]]['proofId'];
					$roleId = ROLE_ID_PROOFREADER;
					break;
				case 'Reviewer':
					$authorId = @$paperUsers[$this->paperMap[$row['fkPaperID']]]['reviewerId'][$row['nOrder']];
					$roleId = ROLE_ID_REVIEWER;
					$assocId = @$paperUsers[$this->paperMap[$row['fkPaperID']]]['reviewId'][$row['nOrder']];
					if (!isset($assocId)) $assocId = $this->paperMap[$row['fkPaperID']];
					break;
				case 'Editor':
				default:
					$authorId = $paperUsers[$this->paperMap[$row['fkPaperID']]]['directorId'];
					$roleId = ROLE_ID_DIRECTOR;
					break;
			}
			
			if (!isset($authorId)) {
				// Assume "Editor" by default
				$authorId = $paperUsers[$this->paperMap[$row['fkPaperID']]]['directorId'];
				$roleId = ROLE_ID_DIRECTOR;
			}
			
			$paperComment = &new PaperComment();
			$paperComment->setCommentType($commentTypes[$row['chType']]);
			$paperComment->setRoleId($roleId);
			$paperComment->setPaperId($this->paperMap[$row['fkPaperID']]);
			$paperComment->setAssocId($assocId);
			$paperComment->setAuthorId($authorId);
			$paperComment->setCommentTitle(''); // Not applicable to 1.x
			$paperComment->setComments($this->trans($row['chComment']));
			$paperComment->setDatePosted($row['dtDateCreated']);
			$paperComment->setDateModified($row['dtDateCreated']);
			$paperComment->setViewable(0);
			
			$paperCommentDao->insertPaperComment($paperComment);
			$result->MoveNext();
		}
		$result->Close();
	}
	
	
	//
	// Helper functions
	//
	
	/**
	 * Rebuild the paper search index.
	 * Note: Rebuilds index for _all_ conferences (non-optimal, but shouldn't be a problem)
	 * Based on code from tools/rebuildSearchIndex.php
	 */
	function rebuildSearchIndex() {
		if ($this->hasOption('verbose')) {
			printf("Rebuilding search index\n");
		}
		
		PaperSearchIndex::rebuildIndex();
	}
	
	/**
	 * Copy a conference title/logo image.
	 * @param $oldName string old setting name
	 * @param $newName string new setting name
	 * @return array image info
	 */
	function copyConferenceImage($oldName, $newName) {
		if (empty($this->conferenceInfo[$oldName])) {
			return null;
		}
		
		$oldPath = $this->importPath . '/images/custom/' . $this->trans($this->conferenceInfo[$oldName]);
		if (!file_exists($oldPath)) {
			return null;
		}
		
		list($width, $height) = getimagesize($oldPath);
		
		$fileManager = &new PublicFileManager();
		$extension = $fileManager->getExtension($this->trans($this->conferenceInfo[$oldName]));
				
		$uploadName = $newName . '.' . $extension;
		if (!$fileManager->copyConferenceFile($this->conferenceId, $oldPath, $uploadName)) {
			printf("Failed to copy file %s\n", $oldPath);
			return null; // This should never happen
		}
		
		return array(
			'name' => $this->trans($this->conferenceInfo[$oldName]),
			'uploadName' => $uploadName,
			'width' => $width,
			'height' => $height,
			'dateUploaded' => Core::getCurrentDate()
		);
	}
	
	/**
	 * Copy a paper file.
	 * @param $paperId int
	 * @param $oldFileId int
	 * @param $fileType string
	 */
	function addPaperFile($paperId, $oldFileId, $fileType) {
		if (!$oldFileId) {
			return 0;
		}
		
		$result = &$this->importDao->retrieve('SELECT * FROM tblfiles WHERE nFileID = ?', $oldFileId);

		if ($result->RecordCount() == 0) {
			$result->Close();
			return 0;
		}
		
		$row = &$result->fields;
		$oldPath = $this->trans($this->conferenceConfigInfo['chFilePath']) . $this->trans($row['chFilePath']);
		
		$fileManager = &new PaperFileManager($paperId);
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		
		$paperFile = &new PaperFile();
		$paperFile->setPaperId($paperId);
		$paperFile->setFileName('temp');
		$paperFile->setOriginalFileName($this->trans($row['chOldFileName']));
		$paperFile->setFileType($this->trans($row['chFileType']));
		$paperFile->setFileSize(filesize($oldPath));
		$paperFile->setType($fileManager->typeToPath($fileType));
		$paperFile->setStatus('');
		$paperFile->setDateUploaded($row['dtDateUploaded']);
		$paperFile->setDateModified($row['dtDateUploaded']);
		$paperFile->setStage(1);
		$paperFile->setRevision(1);
		
		$fileId = $paperFileDao->insertPaperFile($paperFile);
		
		$newFileName = $fileManager->generateFilename($paperFile, $fileType, $row['chOldFileName']);
		if (!$fileManager->copyFile($oldPath, $fileManager->filesDir . $fileManager->typeToPath($fileType) . '/' . $newFileName)) {
			$paperFileDao->deletePaperFileById($paperFile->getFileId());
			printf("Failed to copy file %s\n", $oldPath);
			$result->Close();
			return 0; // This should never happen
		}
		
		$paperFileDao->updatePaperFile($paperFile);
		$this->fileMap[$oldFileId] = $fileId;

		$result->Close();

		return $fileId;
	}
	
	/**
	 * Copy all image files for a paper's HTML galley.
	 * @param $galley PaperHTMLGalley
	 * @param $prefix string image file prefix, e.g. "<abbrev>-<year>-<id>"
	 */
	function copyHTMLGalleyImages($galley, $prefix) {
		$dir = opendir($this->importPath . '/images/paperimages');
		if (!$dir) {
			printf("Failed to open directory %s\n", $this->importPath . '/images/paperimages');
			return; // This should never happen
		}
		
		while(($file = readdir($dir)) !== false) {
			if (!strstr($file, $prefix . '-')) {
				continue;
			}
			
			if (!isset($fileManager)) {
				$fileManager = &new PaperFileManager($galley->getPaperId());
				$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
				$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
			}
			
			$fileType = PAPER_FILE_PUBLIC;
			$oldPath = $this->importPath . '/images/paperimages/' . $file;
			
			$mimeType = String::mime_content_type($oldPath);
			if (empty($mimeType)) {
				$extension = $fileManager->getExtension($file);
				if ($extension == 'jpg') {
					$mimeType = 'image/jpeg';
				} else {
					$mimeType = 'image/' . $extension;
				}
			}
		
			$paperFile = &new PaperFile();
			$paperFile->setPaperId($galley->getPaperId());
			$paperFile->setFileName('temp');
			$paperFile->setOriginalFileName($file);
			$paperFile->setFileType($mimeType);
			$paperFile->setFileSize(filesize($oldPath));
			$paperFile->setType($fileManager->typeToPath($fileType));
			$paperFile->setStatus('');
			$paperFile->setDateUploaded(date('Y-m-d', filemtime($oldPath)));
			$paperFile->setDateModified($paperFile->getDateUploaded());
			$paperFile->setStage(1);
			$paperFile->setRevision(1);
			
			$fileId = $paperFileDao->insertPaperFile($paperFile);
			
			$newFileName = $fileManager->generateFilename($paperFile, $fileType, $file);
			if (!$fileManager->copyFile($oldPath, $fileManager->filesDir . $fileManager->typeToPath($fileType) . '/' . $newFileName)) {
				$paperFileDao->deletePaperFileById($paperFile->getFileId());
				printf("Failed to copy file %s\n", $oldPath);
				// This should never happen
			} else {
				$paperFileDao->updatePaperFile($paperFile);
				$galleyDao->insertGalleyImage($galley->getGalleyId(), $fileId);
			}
		}
		
		closedir($dir);
	}

	/**
	 * Get the list of conflicting user accounts.
	 */
	function getConflicts() {
		return $this->conflicts;
	}
}

?>
