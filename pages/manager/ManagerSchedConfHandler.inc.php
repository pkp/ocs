<?php

/**
 * @file ManagerSchedConfHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerSchedConfHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for scheduled conference management in site administration. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class ManagerSchedConfHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function ManagerSchedConfHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the scheduled conferences hosted on the site.
	 */
	function schedConfs() {
		$this->validate();
		$this->setupTemplate(true);

		$conference = &Request::getConference();

		$rangeInfo = Handler::getRangeInfo('schedConfs', array());

		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		while (true) {
			$schedConfs = &$schedConfDao->getSchedConfsByConferenceId($conference->getConferenceId(), $rangeInfo);
			if ($schedConfs->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $schedConfs->getLastPageRangeInfo();
			unset($schedConfs);
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('schedConfs', $schedConfs);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.scheduledConferences');
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
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.SchedConfSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new SchedConfSettingsForm($args);
		if ($settingsForm->isLocaleResubmit()) {
			$settingsForm->readInputData();
		} else {
			$settingsForm->initData();
		}
		$settingsForm->display();
	}

	/**
	 * Save changes to a scheduled conference's settings.
	 */
	function updateSchedConf() {
		$this->validate();

		import('manager.form.SchedConfSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new SchedConfSettingsForm(
			array(Request::getUserVar('conferenceId'), Request::getUserVar('schedConfId')));
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			Request::redirect(null, null, null, 'schedConfs');

		} else {
			$this->setupTemplate(true);
			$settingsForm->display();
		}
	}

	/**
	 * Delete a scheduled conference.
	 * @param $args array first parameter is the ID of the scheduled conference to delete
	 */
	function deleteSchedConf($args) {
		$this->validate();

		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');

		if (isset($args) && !empty($args) && !empty($args[0])) {
			$schedConfId = $args[0];
			$schedConf =& $schedConfDao->getSchedConf($schedConfId);

			// Look up the scheduled conference path before we delete the scheduled conference.
			import('file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$schedConfFilesPath = $publicFileManager->getSchedConfFilesPath($schedConfId);

			if ($schedConfDao->deleteSchedConfById($schedConfId)) {
				// Delete scheduled conference file tree
				// FIXME move this somewhere better.
				import('file.FileManager');
				$fileManager = new FileManager();
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
		$this->validate();

		$conference =& Request::getConference();

		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConf = &$schedConfDao->getSchedConf(Request::getUserVar('schedConfId'), $conference->getConferenceId());

		if ($schedConf != null) {
			$schedConf->setSequence($schedConf->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$schedConfDao->updateSchedConf($schedConf);
			$schedConfDao->resequenceSchedConfs($conference->getConferenceId());
		}

		Request::redirect(null, null, null, 'schedConfs');
	}
}

?>
