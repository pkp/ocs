<?php

/**
 * @file AdminConferenceHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminConferenceHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for conference management in site administration.
 */

//$Id$

import('pages.admin.AdminHandler');

class AdminConferenceHandler extends AdminHandler {
	/**
	 * Constructor
	 **/
	function AdminConferenceHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display a list of the conferences hosted on the site.
	 */
	function conferences() {
		$this->validate();
		$this->setupTemplate(true);

		$rangeInfo = Handler::getRangeInfo('conferences');

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conferences =& $conferenceDao->getConferences($rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('conferences', $conferences);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		$templateMgr->display('admin/conferences.tpl');
	}

	/**
	 * Display form to create a new conference.
	 */
	function createConference() {
		AdminConferenceHandler::editConference();
	}

	/**
	 * Display form to create/edit a conference.
	 * @param $args array optional, if set the first parameter is the ID of the conference to edit
	 */
	function editConference($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		import('admin.form.ConferenceSiteSettingsForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$settingsForm = new ConferenceSiteSettingsForm(!isset($args) || empty($args) ? null : $args[0]);
		} else {
			$settingsForm =& new ConferenceSiteSettingsForm(!isset($args) || empty($args) ? null : $args[0]);
		}
		if ($settingsForm->isLocaleResubmit()) {
			$settingsForm->readInputData();
		} else {
			$settingsForm->initData();
		}
		$settingsForm->display();
	}

	/**
	 * Save changes to a conference's settings.
	 * @param $args array
	 * @param $request object
	 */
	function updateConference($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		import('admin.form.ConferenceSiteSettingsForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$settingsForm = new ConferenceSiteSettingsForm($request->getUserVar('conferenceId'));
		} else {
			$settingsForm =& new ConferenceSiteSettingsForm($request->getUserVar('conferenceId'));
		}
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			PluginRegistry::loadCategory('blocks');
			$settingsForm->execute();
			import('notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification('notification.notification', 'common.changesSaved');
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
			$conference = $conferenceDao->getFreshestConference();
			$conferenceId = $conference->getData('id');
			$conferencePath = $conference->getData('path');

			if ( $settingsForm->getData('scheduleConf') ) {
				$request->redirect($conferencePath, null, 'manager', 'createSchedConf');
			} else {
				$request->redirect(null, null, null, 'conferences');
			}
		} else {
			$settingsForm->display();
		}
	}

	/**
	 * Delete a conference.
	 * @param $args array first parameter is the ID of the conference to delete
	 */
	function deleteConference($args, &$request) {
		$this->validate();

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');

		if (isset($args) && !empty($args) && !empty($args[0])) {
			$conferenceId = $args[0];
			if ($conferenceDao->deleteConferenceById($conferenceId)) {
				// Delete conference file tree
				// FIXME move this somewhere better.
				import('file.FileManager');
				$fileManager = new FileManager();

				$conferencePath = Config::getVar('files', 'files_dir') . '/conferences/' . $conferenceId;
				$fileManager->rmtree($conferencePath);

				import('file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$publicFileManager->rmtree($publicFileManager->getConferenceFilesPath($conferenceId));
			}
		}

		$request->redirect(null, null, null, 'conferences');
	}

	/**
	 * Change the sequence of a conference on the site index page.
	 * @param $args array
	 * @param $request object
	 */
	function moveConference($args, &$request) {
		$this->validate();

		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conference =& $conferenceDao->getConference($request->getUserVar('id'));

		if ($conference != null) {
			$direction = $request->getUserVar('d');
			if ($direction != null) {
				// moving with up or down arrow
				$conference->setSequence($conference->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			} else {
				// Dragging and dropping onto another conference
				$prevId = $request->getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else {
					$prevConference = $conferenceDao->getConference($prevId);
					$prevSeq = $prevConference->getSequence();
				}
				$conference->setSequence($prevSeq + .5);
			}
			$conferenceDao->updateConference($conference);
			$conferenceDao->resequenceConferences();
		}

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			$request->redirect(null, null, null, 'conferences');
		}
	}

	/**
	 * Show form to import data from an OCS 1.x conference.
	 */
	function importOCS1() {
		$this->validate();
		$this->setupTemplate(true);

		import('admin.form.ImportOCS1Form');

		$importForm = new ImportOCS1Form();
		$importForm->initData();
		$importForm->display();
	}

	/**
	 * Import data from an OCS 1.x conference.
	 * @param $args array
	 * @param $request object
	 */
	function doImportOCS1($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		import('admin.form.ImportOCS1Form');

		$importForm = new ImportOCS1Form();
		$importForm->readInputData();

		if ($importForm->validate() && ($conferenceId = $importForm->execute()) !== false) {
			$conflicts = $importForm->getConflicts();
			$errors = $importForm->getErrors();
			if (!empty($conflicts) || !empty($errors)) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('conferenceId', $conferenceId);
				$templateMgr->assign('conflicts', $conflicts);
				$templateMgr->assign('errors', $errors);
				$templateMgr->display('admin/importConflicts.tpl');
			} else {
				$request->redirect(null, null, null, 'editConference', $conferenceId);
			}
		} else {
			$importForm->display();
		}
	}
}

?>
