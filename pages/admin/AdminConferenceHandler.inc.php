<?php

/**
 * AdminConferenceHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 *
 * Handle requests for conference management in site administration. 
 *
 * $Id$
 */

class AdminConferenceHandler extends AdminHandler {

	/**
	 * Display a list of the conferences hosted on the site.
	 */
	function conferences() {
		parent::validate();
		parent::setupTemplate(true);
		
		$rangeInfo = Handler::getRangeInfo('conferences');

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$conferences = &$conferenceDao->getConferences($rangeInfo);
		
		$templateMgr = &TemplateManager::getManager();
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
		parent::validate();
		parent::setupTemplate(true);
		
		import('admin.form.ConferenceSiteSettingsForm');
		
		$settingsForm = &new ConferenceSiteSettingsForm(!isset($args) || empty($args) ? null : $args[0]);
		$settingsForm->initData();
		$settingsForm->display();
	}
	
	/**
	 * Save changes to a conference's settings.
	 */
	function updateConference() {
		parent::validate();
		
		import('admin.form.ConferenceSiteSettingsForm');
		
		$settingsForm = &new ConferenceSiteSettingsForm(Request::getUserVar('conferenceId'));
		$settingsForm->readInputData();
		
		if ($settingsForm->validate()) {
			$settingsForm->execute();
			Request::redirect(null, null, null, 'conferences');
			
		} else {
			parent::setupTemplate(true);
			$settingsForm->display();
		}
	}
	
	/**
	 * Delete a conference.
	 * @param $args array first parameter is the ID of the conference to delete
	 */
	function deleteConference($args) {
		parent::validate();
		
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		
		if (isset($args) && !empty($args) && !empty($args[0])) {
			$conferenceId = $args[0];
			if ($conferenceDao->deleteConferenceById($conferenceId)) {
				// Delete conference file tree
				// FIXME move this somewhere better.
				import('file.FileManager');
				$fileManager = &new FileManager();

				$conferencePath = Config::getVar('files', 'files_dir') . '/conferences/' . $conferenceId;
				$fileManager->rmtree($conferencePath);

				import('file.PublicFileManager');
				$publicFileManager = &new PublicFileManager();
				$publicFileManager->rmtree($publicFileManager->getConferenceFilesPath($conferenceId));
			}
		}
		
		Request::redirect(null, null, null, 'conferences');
	}
	
	/**
	 * Change the sequence of a conference on the site index page.
	 */
	function moveConference() {
		parent::validate();
		
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$conference = &$conferenceDao->getConference(Request::getUserVar('conferenceId'));
		
		if ($conference != null) {
			$conference->setSequence($conference->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$conferenceDao->updateConference($conference);
			$conferenceDao->resequenceConferences();
		}
		
		Request::redirect(null, null, null, 'conferences');
	}
	
	/**
	 * Show form to import data from an OCS 1.x conference.
	 */
	function importOCS1() {
		parent::validate();
		parent::setupTemplate(true);
		
		import('admin.form.ImportOCS1Form');
		
		$importForm = &new ImportOCS1Form();
		$importForm->initData();
		$importForm->display();
	}
	
	/**
	 * Import data from an OCS 1.x conference.
	 */
	function doImportOCS1() {
		parent::validate();
		
		import('admin.form.ImportOCS1Form');
		
		$importForm = &new ImportOCS1Form();
		$importForm->readInputData();
		
		if ($importForm->validate() && ($conferenceId = $importForm->execute()) !== false) {
			$conflicts = $importForm->getConflicts();
			if (!empty($conflicts)) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('conferenceId', $conferenceId);
				$templateMgr->assign('conflicts', $conflicts);
				$templateMgr->display('admin/importConflicts.tpl');
			} else {
				Request::redirect(null, null, null, 'editConference', $conferenceId);
			}
		} else {
			parent::setupTemplate(true);
			$importForm->display();
		}
	}
	
}

?>
