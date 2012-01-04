<?php

/**
 * @file ImportOCS1.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ImportOCS1
 * @ingroup site
 *
 * @brief Class to import data from an OCS 1.x installation.
 *
 */

// $Id$


import('user.User');
import('conference.Conference');
import('conference.Track');
import('security.Role');
import('registration.Registration');
import('registration.RegistrationType');
import('currency.Currency');
import('paper.Paper');
import('paper.PaperComment');
import('paper.PaperFile');
import('paper.PaperGalley');
import('paper.PaperHTMLGalley');
import('paper.PaperNote');
import('paper.Author');
import('paper.PublishedPaper');
import('paper.SuppFile');
import('submission.common/Action');
import('submission.author.AuthorSubmission');
import('submission.reviewer.ReviewerSubmission');
import('submission.editAssignment.EditAssignment');
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
	var $conferencePath;
	var $conference;
	var $conferenceId = 0;
	var $conferenceIsNew;

	var $dbtable = array();

	var $schedConfMap = array();
	var $trackMap = array();
	var $paperMap = array();
	var $reviewerMap = array();

	var $importDBConn;
	var $importDao;

	var $indexUrl;
	var $globalConfigInfo;
	var $conferenceInfo = array();

	var $userCount = 0;
	var $paperCount = 0;

	var $options;
	var $error;

	/** @var $conflicts array List of conflicting user accounts */
	var $conflicts;

	/** @var $errors array List of errors */
	var $errors;

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
		$this->errors = array();
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

		// Force a new database connection
		$dbconn =& DBConnection::getInstance();
		$dbconn->reconnect(true);

		// Create a connection to the old database
		if (!@include($this->importPath . '/include/db.inc.php')) { // Suppress E_NOTICE messages
			$this->error('Failed to load ' . $this->importPath . '/include/db.php');
			return false;
		}

		// Assumes no character set (not supported by OCS 1.x)
		// Forces open a new connection
		$this->importDBConn = new DBConnection($db_type, $db_host, $db_login, $db_password, $db_name, false, false, true, false, true);
		$dbconn =& $this->importDBConn->getDBConn();

		if (!$this->importDBConn->isConnected()) {
			$this->error('Database connection error: ' . $dbconn->errorMsg());
			return false;
		}

		$this->dbtable = $dbtable;
		$this->importDao = new DAO($dbconn);

		if (!$this->loadGlobalConfig()) {
			$this->error('Unsupported or unrecognized OCS version');
			return false;
		}

		$this->importConference();
		$this->importSchedConfs();
		$this->importTracks();
		$this->importPapers();
		$this->importReviewers();
		$this->importReviews();

		if ($this->hasOption('importRegistrations')) {
			$this->importRegistrations();
		}

		// Rebuild search index
		$this->rebuildSearchIndex();

		return $this->conferenceId;
	}

	/**
	 * Load OCS 1 configuration and settings data.
	 * @return boolean
	 */
	function loadGlobalConfig() {
		$dbtable = $this->dbtable;
		// Load global config
		$result =& $this->importDao->retrieve("SELECT * FROM $dbtable[conference_global]");
		$this->globalConfigInfo =& $result->fields;
		$result->Close();

		if (!isset($this->globalConfigInfo['admin_login'])) {
			return false;
		}

		return true;
	}


	//
	// Conference
	//

	function importConference() {
		// If necessary, create the conference.
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		if (!$conference =& $conferenceDao->getConferenceByPath($this->conferencePath)) {
			if ($this->hasOption('verbose')) {
				printf("Creating conference\n");
			}
			unset($conference);
			$conference = new Conference();
			$conference->setPath($this->conferencePath);
			$conference->setPrimaryLocale(AppLocale::getLocale());
			$conference->setEnabled(true);
			$this->conferenceId = $conferenceDao->insertConference($conference);
			$conferenceDao->resequenceConferences();
			$conference->updateSetting('title', array(AppLocale::getLocale() => $this->globalConfigInfo['name']), null, true);

			$this->conferenceIsNew = true;
		} else {
			if ($this->hasOption('verbose')) {
				printf("Using existing conference\n");
			}
			$conference->updateSetting('title', array(AppLocale::getLocale() => $this->globalConfigInfo['name']), null, true);
			$this->conferenceId = $conference->getId();
			$this->conferenceIsNew = false;
		}
		$this->conference =& $conference;

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		if ($this->conferenceIsNew) {
			// All site admins should get a manager role by default
			$admins = $roleDao->getUsersByRoleId(ROLE_ID_SITE_ADMIN);
			foreach ($admins->toArray() as $admin) {
				$role = new Role();
				$role->setConferenceId($this->conferenceId);
				$role->setUserId($admin->getId());
				$role->setRoleId(ROLE_ID_CONFERENCE_MANAGER);
				$roleDao->insertRole($role);
			}

			// Install the default RT versions.
			import('rt.ocs.ConferenceRTAdmin');
			$conferenceRtAdmin = new ConferenceRTAdmin($this->conferenceId);
			$conferenceRtAdmin->restoreVersions(false);

			$confSettings = array(
				'itemsPerPage' => array('int', 25),
				'numPageLinks' => array('int', 10),
			);

			foreach ($confSettings as $settingName => $settingInfo) {
				list($settingType, $settingValue) = $settingInfo;
				$this->conference->updateSetting($settingName, $settingValue, $settingType);
			}
		}

	}


	//
	// Scheduled Conference
	//

	function importSchedConfs() {
		if ($this->hasOption('verbose')) {
			printf("Importing scheduled conferences\n");
		}

		$dbtable = $this->dbtable;
		$result =& $this->importDao->retrieve("SELECT id FROM $dbtable[conference] ORDER BY id");
		$conferenceIds = array();
		while (!$result->EOF) {
			$conferenceIds[] =& $result->fields[0];
			$result->MoveNext();
		}
		$result->Close();

		foreach ($conferenceIds as $id) {
			$this->importSchedConf($id);
		}
	}

	/**
	 * Import scheduled conference and related settings.
	 * @param $id int
	 */
	function importSchedConf($id) {
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		if ($this->hasOption('verbose')) {
			printf("Importing OCS 1.x conference ID $id\n");
		}
		$dbtable = $this->dbtable;

		// Load sched conf config
		$result =& $this->importDao->retrieve("SELECT * FROM $dbtable[conference] WHERE id = ?", array($id));
		$this->conferenceInfo[$id] =& $result->fields;
		$result->Close();

		$conferenceInfo =& $this->conferenceInfo[$id];
		// Create/fetch scheduled conference
		if (!$schedConf =& $schedConfDao->getSchedConfByPath($id, $this->conferenceId)) {
			unset($schedConf);
			$schedConf = new SchedConf();
			$schedConf->setConferenceId($this->conferenceId);
			$schedConf->setPath($id);
			$schedConfDao->insertSchedConf($schedConf);
			$schedConfDao->resequenceSchedConfs($this->conferenceId);
			$schedConf->updateSetting('title', array(AppLocale::getLocale() => $conferenceInfo['name']), null, true);
		} else {
			$schedConf->updateSetting('title', array(AppLocale::getLocale() => $conferenceInfo['name']), null, true);
		}

		$this->schedConfMap[$id] =& $schedConf;

		$schedConfSettings = array(
			'contactEmail' => array('string', Core::cleanVar($this->conferenceInfo[$id]['contact_email'])),
			'contactName' => array('string', Core::cleanVar($this->conferenceInfo[$id]['contact_name']))
		);

		foreach ($schedConfSettings as $settingName => $settingInfo) {
			list($settingType, $settingValue) = $settingInfo;
			$schedConf->updateSetting($settingName, $settingValue, $settingType);
		}
	}


	/**
	 * Import registrations and registration types.
	 */
	function importRegistrations() {
		if ($this->hasOption('verbose')) {
			printf("Importing registrations\n");
		}

		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$registrationTypes = array();

		foreach ($this->conferenceInfo as $conferenceId => $conferenceInfo) {
			$levels = array_map('trim', split("\n", Core::cleanVar($conferenceInfo['reg_levels'])));
			$fees = array_map('trim', split("\n", Core::cleanVar($conferenceInfo['reg_fees'])));
			$levelsLate = array_map('trim', split("\n", Core::cleanVar($conferenceInfo['reg_levels_late'])));
			$feesLate = array_map('trim', split("\n", Core::cleanVar($conferenceInfo['reg_fees_late'])));

			$lateDate = Core::cleanVar($conferenceInfo['reg_late_date']);
			$schedConf =& $this->schedConfMap[$conferenceId];

			foreach ($levels as $key => $level) {
				$fee = $fees[$key];
				$registrationType = new RegistrationType();
				$registrationType->setSchedConfId($schedConf->getId());
				$registrationType->setName($level, AppLocale::getLocale());
				$registrationType->setCost($fee);
				$registrationType->setCurrencyCodeAlpha('USD'); // FIXME?
				$registrationType->setOpeningDate(Core::cleanVar($conferenceInfo['accept_deadline']));
				$registrationType->setClosingDate($lateDate);
				$registrationType->setAccess(REGISTRATION_TYPE_ACCESS_ONLINE);
				$registrationType->setPublic(0);
				$registrationType->setInstitutional(0);
				$registrationType->setMembership(0);
				$registrationType->setSequence($key);
				$registrationTypeDao->insertRegistrationType($registrationType);
				$registrationTypes[substr($level, 0, 60)] =& $registrationType; // Truncated in 1.x DB
				unset($registrationType);
			}

			foreach ($levelsLate as $key => $level) {
				$fee = $feesLate[$key];
				$registrationType = new RegistrationType();
				$registrationType->setSchedConfId($schedConf->getId());
				$registrationType->setName($level . ' (Late)', AppLocale::getLocale());
				$registrationType->setCost($fee);
				$registrationType->setCurrencyCodeAlpha('USD'); // FIXME?
				$registrationType->setOpeningDate($lateDate);
				$registrationType->setClosingDate(Core::cleanVar($conferenceInfo['start_date']));
				$registrationType->setAccess(REGISTRATION_TYPE_ACCESS_ONLINE);
				$registrationType->setPublic(0);
				$registrationType->setInstitutional(0);
				$registrationType->setMembership(0);
				$registrationType->setSequence($key);
				$registrationTypeDao->insertRegistrationType($registrationType);
				$registrationTypes[substr($level, 0, 60) . ' (Late)'] =& $registrationType; // Truncated in 1.x DB
				unset($registrationType);
			}
		}

		$result =& $this->importDao->retrieve('SELECT * FROM registrants ORDER BY cf, id');
		while (!$result->EOF) {
			$row =& $result->fields;
			$schedConf =& $this->schedConfMap[$row['cf']];

			$email = Core::cleanVar($row['email']);

			if (!$user =& $userDao->getUserByEmail($email)) {
				// The user doesn't exist by email; create one.
				$name = Core::cleanVar($row['name']);
				$nameParts = split(' ', $name);

				$lastName = array_pop($nameParts);
				$firstName = join(' ', $nameParts);

				$user = new User();
				$user->setEmail($email);
				$user->setFirstName($firstName);
				$user->setLastName($lastName);
				$user->setPhone(Core::cleanVar($row['phone']));
				$user->setFax(Core::cleanVar($row['fax']));
				$user->setMailingAddress(Core::cleanVar($row['address']));

				$i = "";
				while ($userDao->userExistsByUsername($lastName . $i)) $i++;
				$user->setUsername($lastName . $i);

				$user->setDateRegistered($row['date_registered']);
				$user->setDateLastLogin(null);
				$user->setMustChangePassword(1);

				$password = Validation::generatePassword();
				$user->setPassword(Validation::encryptCredentials($user->getUsername(), $password));

				$userDao->insertUser($user);

				if ($this->hasOption('emailUsers')) {
					import('mail.MailTemplate');
					$mail = new MailTemplate('USER_REGISTER');

					$mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
					$mail->assignParams(array('username' => $user->getUsername(), 'password' => $password, 'conferenceName' => $schedConf->getFullTitle()));
					$mail->addRecipient($user->getEmail(), $user->getFullName());
					$mail->send();

				}
			}

			$regLevel = trim(Core::cleanVar($row['reg_level']));
			$regFee = Core::cleanVar($row['reg_fee']);
			$conferenceInfo =& $this->conferenceInfo[$row['cf']];
			$seekingRegLevel = $regLevel . (strtotime($row['date_registered']) > strtotime($conferenceInfo['reg_late_date']) ? ' (Late)':'');
			$registrationType =& $registrationTypes[$seekingRegLevel];
			if (!$registrationType || $registrationType->getCost() != $regFee) {
				if (!$registrationType) $this->errors[] = "Registration data inconsistency: Registration type \"$seekingRegLevel\" not found for user with email $email.";
				else {
					$this->errors[] = "Registration data inconsistency: Paid registration fee $regFee does not match registration type cost for \"$seekingRegLevel\" (" . $registrationType->getCost() . ") for user with email $email.";
					unset($registrationType);
				}

				unset($user);
				unset($schedConf);
				$result->MoveNext();
				continue;
			}

			if ($registrationDao->registrationExistsByUser($user->getId(), $schedConf->getId())) {
				$this->errors[] = "A duplicate registration (level \"$seekingRegLevel\") was skipped for user with email $email.";
			} else {
				$registration = new Registration();
				$registration->setSchedConfId($schedConf->getId());
				$registration->setUserId($user->getId());
				$registration->setTypeId($registrationType->getTypeId());
				if ($row['has_paid'] == 'paid') $registration->setDatePaid(Core::cleanVar($row['date_paid']));
				$registration->setSpecialRequests(Core::cleanVar($row['special_requests']));
				$registration->setDateRegistered($row['date_registered']);
				$registrationDao->insertRegistration($registration);
				unset($registration);
			}

			unset($user);
			unset($registrationType);
			unset($conferenceInfo);
			unset($schedConf);

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

		$trackDao =& DAORegistry::getDAO('TrackDAO');

		$result =& $this->importDao->retrieve('SELECT * FROM tracks ORDER BY cf, track_order');
		$oldConferenceId = null;
		while (!$result->EOF) {
			$row =& $result->fields;
			if ($oldConferenceId != $row['cf']) {
				$sequence = 0;
				$oldConferenceId = $row['cf'];
			}

			$track = new Track();
			$schedConf =& $this->schedConfMap[$row['cf']];
			$track->setSchedConfId($schedConf->getId());
			$track->setTitle(Core::cleanVar($row['track']), AppLocale::getLocale());
			$track->setSequence(++$sequence);
			$track->setDirectorRestricted(0);
			$track->setMetaReviewed(1);

			$trackId = $trackDao->insertTrack($track);
			$this->trackMap[$row['id']] = $trackId;
			$result->MoveNext();
			unset($track);
			unset($schedConf);
		}
		$result->Close();
	}

	/**
	 * Import reviewers
	 */
	function importReviewers() {
		if ($this->hasOption('verbose')) {
			printf("Importing reviewers\n");
		}

		import('file.PaperFileManager');
		import('search.PaperSearchIndex');

		$userDao =& DAORegistry::getDAO('UserDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');

		$result =& $this->importDao->retrieve('SELECT * FROM reviewers');
		while (!$result->EOF) {
			$row =& $result->fields;
			$schedConf =& $this->schedConfMap[$row['cf']];
			$schedConfId = $schedConf->getId();

			$user = $userDao->getUserByUsername(Core::cleanVar($row['login']));
			if (!$user) {
				unset($user);
				$user = new User();
				$user->setUsername(Core::cleanVar($row['login']));

				$nameParts = split(' ', Core::cleanVar($row['name']));
				$firstName = array_shift($nameParts);
				$lastName = join(' ', $nameParts);

				$user->setFirstName(empty($firstName)?'(NONE)':$firstName);
				$user->setLastName(empty($lastName)?'(NONE)':$lastName);
				$user->setEmail(Core::cleanVar($row['email']));
				$user->setDateRegistered(Core::getCurrentDate());
				$user->setDateLastLogin(null);
				$user->setMustChangePassword(1);

				$password = Validation::generatePassword();
				$user->setPassword(Validation::encryptCredentials($user->getUsername(), $password));

				if ($this->hasOption('emailUsers')) {
					import('mail.MailTemplate');
					$mail = new MailTemplate('USER_REGISTER');

					$mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
					$mail->assignParams(array('username' => $user->getUsername(), 'password' => $password, 'conferenceName' => $schedConf->getFullTitle()));
					$mail->addRecipient($user->getEmail(), $user->getFullName());
					$mail->send();

				}
				$user->setDisabled(0);

				$otherUser =& $userDao->getUserByEmail(Core::cleanVar($row['email']));
				if ($otherUser !== null) {
					// User exists with this email -- munge it to make unique
					$user->setEmail('ocs-' . Core::cleanVar($row['login']) . '+' . Core::cleanVar($row['email']));
					$this->conflicts[] = array(&$otherUser, &$user);
				}

				unset($otherUser);

				$userDao->insertUser($user);

				// Make this user a author
				$role = new Role();
				$role->setSchedConfId($schedConf->getId());
				$role->setConferenceId($schedConf->getConferenceId());
				$role->setUserId($user->getId());
				$role->setRoleId(ROLE_ID_REVIEWER);
				$roleDao->insertRole($role);
				unset($role);
			}
			$this->reviewerMap[$row['login']] =& $user;

			unset($schedConf);
			unset($user);

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

		import('file.PaperFileManager');
		import('search.PaperSearchIndex');

		$userDao =& DAORegistry::getDAO('UserDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$trackDao =& DAORegistry::getDAO('TrackDAO');
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');

		$unassignedTrackId = null;

		$result =& $this->importDao->retrieve('SELECT * FROM papers ORDER by id');
		while (!$result->EOF) {
			$row =& $result->fields;
			$schedConf =& $this->schedConfMap[$row['cf']];
			$schedConfId = $schedConf->getId();

			// Bring in the primary user for this paper.
			$user = $userDao->getUserByUsername(Core::cleanVar($row['login']));
			if (!$user) {
				unset($user);
				$user = new User();
				$user->setUsername(Core::cleanVar($row['login']));
				$user->setFirstName(Core::cleanVar($row['first_name']));
				$user->setLastName(Core::cleanVar($row['surname']));
				$user->setAffiliation(Core::cleanVar($row['affiliation']));
				$user->setEmail(Core::cleanVar($row['email']));
				$user->setUrl(Core::cleanVar($row['url']));
				$user->setBiography(Core::cleanVar($row['bio']), AppLocale::getLocale());
				$user->setLocales(array());
				$user->setDateRegistered($row['created']);
				$user->setDateLastLogin($row['created']);
				$user->setMustChangePassword(1);

				$password = Validation::generatePassword();
				$user->setPassword(Validation::encryptCredentials($user->getUsername(), $password));

				if ($this->hasOption('emailUsers')) {
					import('mail.MailTemplate');
					$mail = new MailTemplate('USER_REGISTER');

					$mail->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
					$mail->assignParams(array('username' => $user->getUsername(), 'password' => $password, 'conferenceName' => $schedConf->getFullTitle()));
					$mail->addRecipient($user->getEmail(), $user->getFullName());
					$mail->send();

				}
				$user->setDisabled(0);

				$otherUser =& $userDao->getUserByEmail(Core::cleanVar($row['email']));
				if ($otherUser !== null) {
					// User exists with this email -- munge it to make unique
					$user->setEmail('ocs-' . Core::cleanVar($row['login']) . '+' . Core::cleanVar($row['email']));
					$this->conflicts[] = array(&$otherUser, &$user);
				}

				unset($otherUser);

				$userDao->insertUser($user);

				// Make this user a author
				$role = new Role();
				$role->setSchedConfId($schedConf->getId());
				$role->setConferenceId($schedConf->getConferenceId());
				$role->setUserId($user->getId());
				$role->setRoleId(ROLE_ID_AUTHOR);
				$roleDao->insertRole($role);
				unset($role);
			}
			$userId = $user->getId();

			// Bring in the basic entry for the paper
			$paper = new Paper();
			$paper->setUserId($userId);
			$paper->setSchedConfId($schedConfId);

			$oldTrackId = $row['primary_track_id'];
			if (!$oldTrackId || !isset($this->trackMap[$oldTrackId])) {
				$oldTrackId = $row['secondary_track_id'];
			}
			if (!$oldTrackId || !isset($this->trackMap[$oldTrackId])) {
				if (!$unassignedTrackId) {
					// Create an "Unassigned" track to use for submissions
					// that didn't have a track in OCS 1.x.
					$track = new Track();
					$track->setSchedConfId($schedConf->getId());
					$track->setTitle('UNASSIGNED', AppLocale::getLocale());
					$track->setSequence(REALLY_BIG_NUMBER);
					$track->setDirectorRestricted(1);
					$track->setMetaReviewed(1);

					$unassignedTrackId = $trackDao->insertTrack($track);
				}
				$newTrackId = $unassignedTrackId;
			} else {
				$newTrackId = $this->trackMap[$oldTrackId];
			}

			$paper->setTrackId($newTrackId);
			$paper->setTitle(Core::cleanVar($row['title']), AppLocale::getLocale());
			$paper->setAbstract(Core::cleanVar($row['abstract']), AppLocale::getLocale());
			$paper->setDiscipline(Core::cleanVar($row['discipline']), AppLocale::getLocale());
			$paper->setSponsor(Core::cleanVar($row['sponsor']), AppLocale::getLocale());
			$paper->setSubject(Core::cleanVar($row['topic']), AppLocale::getLocale());
			$paper->setLanguage(Core::cleanVar($row['language']));

			$paper->setDateSubmitted($row['created']);
			$paper->setDateStatusModified($row['timestamp']);

			// $paper->setTypeConst($row['present_format'] == 'multiple' ? SUBMISSION_TYPE_PANEL : SUBMISSION_TYPE_SINGLE); FIXME
			$paper->setCurrentStage(REVIEW_STAGE_ABSTRACT);
			$paper->setSubmissionProgress(0);
			$paper->setPages('');

			// Bring in authors
			$firstNames = split("\n", Core::cleanVar($row['first_name'] . "\n" . $row['add_first_names']));
			$lastNames = split("\n", Core::cleanVar($row['surname'] . "\n" . $row['add_surnames']));
			$emails = split("\n", Core::cleanVar($row['email'] . "\n" . $row['add_emails']));
			$affiliations = split("\n", Core::cleanVar($row['affiliation'] . "\n" . $row['add_affiliations']));
			$urls = split("\n", Core::cleanVar($row['url'] . "\n" . $row['add_urls']));
			foreach ($emails as $key => $email) {
				if (empty($email)) continue;

				$author = new Author();

				$author->setEmail($email);
				$author->setFirstName($firstNames[$key]);
				$author->setLastName($lastNames[$key]);
				$author->setAffiliation($affiliations[$key]);
				@$author->setUrl($urls[$key]); // Suppress warnings from inconsistent OCS 1.x data
				$author->setPrimaryContact($key == 0 ? 1 : 0);

				$paper->addAuthor($author);

				unset($author);
			}

			switch ($row['accepted']) {
				case 'true':
					$paper->setStatus(STATUS_PUBLISHED);
					$paperId = $paperDao->insertPaper($paper);
					$publishedPaper = new PublishedPaper();
					$publishedPaper->setPaperId($paperId);
					$publishedPaper->setSchedConfId($schedConfId);
					$publishedPaper->setDatePublished(Core::getCurrentDate());
					$publishedPaper->setSeq(REALLY_BIG_NUMBER);
					$publishedPaper->setViews(0);
					$publishedPaperDao->insertPublishedPaper($publishedPaper);
					$publishedPaperDao->resequencePublishedPapers($paper->getTrackId(), $schedConfId);
					break;
				case 'reject':
					$paper->setStatus(STATUS_DECLINED);
					$paperId = $paperDao->insertPaper($paper);
					break;
				default:
					$paper->setStatus(STATUS_QUEUED);
					$paperId = $paperDao->insertPaper($paper);
			}

			$this->paperMap[$row['id']] =& $paper;

			$paperFileManager = new PaperFileManager($paperId);
			if (!empty($row['paper']) && $row['paper'] != 'PDF') {
				$format = 'text/html';
				$extension = $paperFileManager->getDocumentExtension($format);

				$fileId = $paperFileManager->writeSubmissionFile('migratedFile' . $extension, $row['paper'], $format);
				$paper->setSubmissionFileId($fileId);
				$paperDao->updatePaper($paper);

				$fileId = $paperFileManager->writePublicFile('migratedGalley' . $extension, $row['paper'], $format);
				PaperSearchIndex::updateFileIndex($paperId, PAPER_SEARCH_GALLEY_FILE, $fileId);
				if (strstr($format, 'html')) {
					$galley = new PaperHTMLGalley();
					$galley->setLabel('HTML');
				} else {
					$galley = new PaperGalley();
					switch ($format) {
						case 'application/pdf': $galley->setLabel('PDF'); break;
						case 'application/postscript': $galley->setLabel('PostScript'); break;
						case 'application/msword': $galley->setLabel('Word'); break;
						case 'text/xml': $galley->setLabel('XML'); break;
						case 'application/powerpoint': $galley->setLabel('Slideshow'); break;
						default: $galley->setLabel('Untitled'); break;
					}
				}

				$galley->setLocale(AppLocale::getLocale());
				$galley->setPaperId($paperId);
				$galley->setFileId($fileId);
				$galleyDao->insertGalley($galley);
				unset($galley);
			} elseif ($row['paper'] == 'PDF') {
				$fileId = $paperFileManager->copySubmissionFile($this->importPath . '/papers/' . $row['pdf'], 'application/pdf');
				$paper->setSubmissionFileId($fileId);
				$paperDao->updatePaper($paper);

				$fileId = $paperFileManager->copyPublicFile($this->importPath . '/papers/' . $row['pdf'], 'application/pdf');
				PaperSearchIndex::updateFileIndex($paperId, PAPER_SEARCH_GALLEY_FILE, $fileId);
				$galley = new PaperGalley();
				$galley->setLabel('PDF');
				$galley->setLocale(AppLocale::getLocale());
				$galley->setPaperId($paperId);
				$galley->setFileId($fileId);
				$galleyDao->insertGalley($galley);
				unset($galley);
			}

			// FIXME: The following fields from OCS 1.x are UNUSED:
			// program_insert approach coverage format relation appendix_names appendix_dates
			// appendix appendix_pdf secondary_track_id multiple_* restrict_access paper_email
			// delete_paper comment_email

			unset($user);
			unset($paper);
			unset($schedConf);
			unset($paperFileManager);
			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Import reviews.
	 */
	function importReviews() {
		if ($this->hasOption('verbose')) {
			printf("Importing reviews\n");
		}

		$userDao =& DAORegistry::getDAO('UserDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');

		$unassignedTrackId = null;

		$result =& $this->importDao->retrieve('SELECT * FROM reviews ORDER by timestamp');
		while (!$result->EOF) {
			$row =& $result->fields;

			$schedConf =& $this->schedConfMap[$row['cf']];
			$paper =& $this->paperMap[$row['paper']];
			$reviewer =& $this->reviewerMap[$row['reviewer']];

			if (!$schedConf || !$paper || !$reviewer) {
				// Database inconsistency! Skip this entry.
				if (!$schedConf) $errors[] = "Unknown conference referenced in reviews: $row[cf]";
				else unset($schedConf);
				if (!$paper) $errors[] = "Unknown paper referenced in reviews: $row[paper]";
				else unset($paper);
				if (!$reviewer) $errors[] = "Unknown reviewer referenced in reviews: $row[reviewer]";
				else unset($reviewer);

				$result->MoveNext();
				continue;
			}

			$schedConfId = $schedConf->getId();
			$paperId = $paper->getId();
			$reviewerId = $reviewer->getId();

			$reviewAssignment = new ReviewAssignment();
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setPaperId($paperId);
			$reviewAssignment->setStage(REVIEW_STAGE_ABSTRACT); // Won't always be accurate
			$reviewAssignment->setDateAssigned($row['timestamp']);
			$reviewAssignment->setDateNotified($row['timestamp']);
			$reviewAssignment->setDateConfirmed($row['timestamp']);
			$reviewAssignment->setDeclined(0);
			switch (trim(strtolower(array_shift(split("[\n\.:]", $row['recommendation']))))) {
				case 'accept':
					$reviewAssignment->setRecommendation(SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
					$reviewAssignment->setDateCompleted($row['timestamp']);
					break;
				case 'revise':
				case 'pending revisions':
				case 'accept with revisions':
					$reviewAssignment->setRecommendation(SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS);
					$reviewAssignment->setDateCompleted($row['timestamp']);
					break;
				case 'decline':
				case 'reject':
					$reviewAssignment->setRecommendation(SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE);
					$reviewAssignment->setDateCompleted($row['timestamp']);
					break;
				default:
					// WARNING: We're not setting a recommendation here at all!
					break;
			}

			$reviewId = $reviewAssignmentDao->insertReviewAssignment($reviewAssignment);

			$paperComment = new PaperComment();
			$paperComment->setCommentType(COMMENT_TYPE_PEER_REVIEW);
			$paperComment->setRoleId(ROLE_ID_REVIEWER);
			$paperComment->setPaperId($paperId);
			$paperComment->setAssocId($reviewId);
			$paperComment->setAuthorId($reviewerId);
			$paperComment->setCommentTitle('');
			$paperComment->setComments(Core::cleanVar($row['comments'] . "\n" . $row['recommendation']));
			$paperComment->setDatePosted($row['timestamp']);
			$paperComment->setViewable(0);

			$paperCommentDao->insertPaperComment($paperComment);

			unset($schedConf);
			unset($paper);
			unset($reviewer);
			unset($reviewAssignment);
			unset($paperComment);
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
	 * Get the list of conflicting user accounts.
	 */
	function getConflicts() {
		return $this->conflicts;
	}

	/**
	 * Get the list of errors.
	 */
	function getErrors() {
		return $this->errors;
	}
}

?>
