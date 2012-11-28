<?php

/**
 * @file pages/manager/ManagerSchedConfHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerSchedConfHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for scheduled conference management in site administration.
 */


import('pages.manager.ManagerHandler');

class ManagerSchedConfHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function ManagerSchedConfHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the scheduled conferences hosted on the site.
	 */
	function schedConfs($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$conference =& $request->getConference();

		$rangeInfo = $this->getRangeInfo($request, 'schedConfs', array());

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		while (true) {
			$schedConfs = $schedConfDao->getAll(false, $conference->getId(), $rangeInfo);
			if ($schedConfs->isInBounds()) break;
			unset($rangeInfo);
			$rangeInfo =& $schedConfs->getLastPageRangeInfo();
			unset($schedConfs);
		}

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');
		$templateMgr->assign_by_ref('schedConfs', $schedConfs);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.scheduledConferences');
		$templateMgr->display('manager/schedConfs.tpl');
	}

	/**
	 * Display form to create a new scheduled conference.
	 */
	function createSchedConf($args, &$request) {
		import('classes.schedConf.SchedConf');
		$schedConf = $request->getSchedConf();
		$conference = $request->getConference();

		if($schedConf) {
			$schedConfId = $schedConf->getId();
		} else {
			$schedConfId = null;
		}

		if($conference) {
			$conferenceId = $conference->getId();
		} else {
			$conferenceId = null;
		}

		$this->editSchedConf(array($conferenceId, $schedConfId), $request);
	}

	/**
	 * Display form to create/edit a scheduled conference.
	 * @param $args array optional, if set the first parameter is the ID of the scheduled conference to edit
	 */
	function editSchedConf($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.SchedConfSettingsForm');

		$settingsForm = new SchedConfSettingsForm($args);
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
	function updateSchedConf($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.SchedConfSettingsForm');

		$settingsForm = new SchedConfSettingsForm(
			array($request->getUserVar('conferenceId'), $request->getUserVar('schedConfId'))
		);
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			$request->redirect(null, null, null, 'schedConfs');

		} else {
			$settingsForm->display();
		}
	}

	/**
	 * Delete a scheduled conference.
	 * @param $args array first parameter is the ID of the scheduled conference to delete
	 */
	function deleteSchedConf($args, &$request) {
		$this->validate();

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		if (isset($args) && !empty($args) && !empty($args[0])) {
			$schedConfId = $args[0];
			$schedConf = $schedConfDao->getById($schedConfId);

			// Look up the scheduled conference path before we delete the scheduled conference.
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$schedConfFilesPath = $publicFileManager->getSchedConfFilesPath($schedConfId);

			if ($schedConfDao->deleteId($schedConfId)) {
				// Delete scheduled conference file tree
				// FIXME move this somewhere better.
				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				$schedConfPath = Config::getVar('files', 'files_dir') . '/conferences/' . $schedConf->getConferenceId() . '/schedConfs/' . $schedConfId;
				$fileManager->rmtree($schedConfPath);

				$publicFileManager->rmtree($schedConfFilesPath);
			}
		}

		$request->redirect(null, null, null, 'schedConfs');
	}

	/**
	 * Change the sequence of a schedConf on the site index page.
	 */
	function moveSchedConf($args, &$request) {
		$this->validate();

		$conference =& $request->getConference();

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConf = $schedConfDao->getById($request->getUserVar('id'), $conference->getId());
		$direction = $request->getUserVar('d');

		if ($schedConf != null) {
			if ($direction != null) {
				// moving with up or down arrow
				$schedConf->setSequence($schedConf->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			} else {
				// Dragging and dropping onto another scheduled conference
				$prevId = $request->getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else {
					$prevSchedConf = $schedConfDao->getConference($prevId, $conference->getId());
					$prevSeq = $prefSchedConf->getSequence();
				}
				$schedConf->setSequence($prevSeq + .5);
			}
			$schedConfDao->updateObject($schedConf);
			$schedConfDao->resequence($conference->getId());
		}

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			$request->redirect(null, null, null, 'schedConfs');
		}
	}
}

?>
