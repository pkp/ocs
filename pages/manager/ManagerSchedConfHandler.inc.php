<?php

/**
 * ManagerSchedConfHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for scheduled conference management in site administration. 
 *
 * $Id$
 */

class ManagerSchedConfHandler extends ManagerHandler {

	/**
	 * Display a list of the scheduled conferences hosted on the site.
	 */
	function schedConfs() {
		parent::validate();
		parent::setupTemplate(true);
		
		$conference = &Request::getConference();
		
		$rangeInfo = Handler::getRangeInfo('schedConfs');

		// TODO: use $rangeInfo here!
		
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConfs = &$schedConfDao->getSchedConfsByConferenceId($conference->getConferenceId());
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('schedConfs', $schedConfs);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		$templateMgr->display('manager/schedConfs.tpl');
	}
	
	/**
	 * Display form to create a new scheduled conference.
	 */
	function createSchedConf() {
		import('schedConf.SchedConf');
		$schedConf = Request::getSchedConf();
		$conference = Request::getConference();
		
		if($schedConf) {
			$schedConfId = $schedConf->getSchedConfId();
		} else {
			$schedConfId = null;
		}

		if($conference) {
			$conferenceId = $conference->getConferenceId();
		} else {
			$conferenceId = null;
		}
				
		ManagerSchedConfHandler::editSchedConf(array($conferenceId, $schedConfId));
	}
	
	/**
	 * Display form to create/edit a scheduled conference.
	 * @param $args array optional, if set the first parameter is the ID of the scheduled conference to edit
	 */
	function editSchedConf($args = array()) {
		parent::validate();
		parent::setupTemplate(true);
		
		import('manager.form.SchedConfSettingsForm');
		
		$settingsForm = &new SchedConfSettingsForm($args);
		$settingsForm->initData();
		$settingsForm->display();
	}
	
	/**
	 * Save changes to a scheduled conference's settings.
	 */
	function updateSchedConf() {
		parent::validate();
		
		import('manager.form.SchedConfSettingsForm');
		
		$settingsForm = &new SchedConfSettingsForm(
			array(Request::getUserVar('conferenceId'), Request::getUserVar('schedConfId')));
		$settingsForm->readInputData();
		
		if ($settingsForm->validate()) {
			$settingsForm->execute();
			Request::redirect(null, null, null, 'schedConfs');
			
		} else {
			parent::setupTemplate(true);
			$settingsForm->display();
		}
	}
	
	/**
	 * Delete a scheduled conference.
	 * @param $args array first parameter is the ID of the scheduled conference to delete
	 */
	function deleteSchedConf($args) {
		parent::validate();
		
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		
		if (isset($args) && !empty($args) && !empty($args[0])) {
			$schedConfId = $args[0];
			$schedConf =& $schedConfDao->getSchedConf($schedConfId);

			// Look up the scheduled conference path before we delete the scheduled conference.
			import('file.PublicFileManager');
			$publicFileManager = &new PublicFileManager();
			$schedConfFilesPath = $publicFileManager->getSchedConfFilesPath($schedConfId);

			if ($schedConfDao->deleteSchedConfById($schedConfId)) {
				// Delete scheduled conference file tree
				// FIXME move this somewhere better.
				import('file.FileManager');
				$fileManager = &new FileManager();
				$schedConfPath = Config::getVar('files', 'files_dir') . '/conferences/' . $schedConf->getConferenceId() . '/schedConfs/' . $schedConfId;
				$fileManager->rmtree($schedConfPath);

				$publicFileManager->rmtree($schedConfFilesPath);
			}
		}
		
		Request::redirect(null, null, null, 'schedConfs');
	}
	
	/**
	 * Change the sequence of a schedConf on the site index page.
	 */
	function moveSchedConf() {
		parent::validate();
		
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConf = &$schedConfDao->getSchedConf(Request::getUserVar('schedConfId'));
		
		if ($schedConf != null) {
			$schedConf->setSequence($schedConf->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$schedConfDao->updateSchedConf($schedConf);
			$schedConfDao->resequenceSchedConfs();
		}
		
		Request::redirect(null, null, null, 'schedConfs');
	}
}

?>
