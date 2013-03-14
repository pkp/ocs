<?php

/**
 * @file controllers/grid/admin/schedConf/SchedConfGridHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfGridHandler
 * @ingroup controllers_grid_admin_schedConf
 *
 * @brief Handle schedConf grid requests.
 */

import('lib.pkp.controllers.grid.admin.context.ContextGridHandler');

import('controllers.grid.settings.schedConf.SchedConfGridRow');
import('controllers.grid.settings.schedConf.form.SchedConfSettingsForm');

class SchedConfGridHandler extends ContextGridHandler {
	/**
	 * Constructor
	 */
	function SchedConfGridHandler() {
		parent::ContextGridHandler();
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request) {
		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_ADMIN,
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_APP_COMMON
		);

		parent::initialize($request);
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return UserGridRow
	 */
	function &getRowInstance() {
		$row = new SchedConfGridRow();
		return $row;
	}

	/**
	 * @see GridHandler::loadData()
	 * @param $request PKPRequest
	 * @return array Grid data.
	 */
	function loadData(&$request) {
		// Get all schedConfs.
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$schedConfs = $schedConfDao->getAll(false, $request->getConference()->getId());

		return $schedConfs->toAssociativeArray();
	}

	/**
	 * @see lib/pkp/classes/controllers/grid/GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence(&$request, $rowId, &$schedConf, $newSequence) {
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO'); /* @var $schedConfDao SchedConfDAO */
		$schedConf->setSequence($newSequence);
		$schedConfDao->updateObject($schedConf);
	}


	//
	// Public grid actions.
	//
	/**
	 * Edit an existing schedConf.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editContext($args, $request) {

		// Identify the schedConf Id.
		$schedConfId = $request->getUserVar('rowId');
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$schedConf = $schedConfDao->getById($schedConfId);
		$conference = $request->getConference();
		if (!$conference || $schedConf && $schedConf->getConferenceId() != $conference->getId()) {
			$json = new JSONMessage(false);
			return $json->getString();
		}

		// Form handling.
		$settingsForm = new SchedConfSettingsForm(!isset($schedConfId) || empty($schedConfId) ? null : $schedConfId);
		$settingsForm->initData();
		$json = new JSONMessage(true, $settingsForm->fetch($args, $request));

		return $json->getString();
	}

	/**
	 * Update an existing schedConf.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateContext($args, $request) {
		// Identify the schedConf Id.
		$schedConfId = $request->getUserVar('contextId');
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$schedConf = $schedConfDao->getById($schedConfId);
		$conference = $request->getConference();
		if (!$conference || $schedConf && $schedConf->getConferenceId() != $conference->getId()) {
			$json = new JSONMessage(false);
			return $json->getString();
		}

		// Form handling.
		$settingsForm = new SchedConfSettingsForm($schedConfId);
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			PluginRegistry::loadCategory('blocks');

			$settingsForm->execute($request);

			// Create the notification.
			$notificationMgr = new NotificationManager();
			$user =& $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			return DAO::getDataChangedEvent($schedConfId);
		}

		$json = new JSONMessage(false);
		return $json->getString();
	}

	/**
	 * Delete a schedConf.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteContext($args, &$request) {
		// Identify the current context.
		$context =& $request->getContext();

		// Identify the schedConf Id.
		$schedConfId = $request->getUserVar('rowId');
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$schedConf = $schedConfDao->getById($schedConfId);
		$conference = $request->getConference();
		if (!$schedConf || !$conference || $schedConf->getConferenceId() != $conference->getId()) {
			$json = new JSONMessage(false);
			return $json->getString();
		}

		if ($schedConf) {
			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$publicFilesPath = $publicFileManager->getSchedConfFilesPath($schedConfId);
			$publicFileManager->rmtree($publicFilesPath);

			// Delete schedConf file tree
			// FIXME move this somewhere better.
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$schedConfPath = Config::getVar('files', 'files_dir') . '/schedConfs/' . $schedConfId;
			$fileManager->rmtree($schedConfPath);

			$schedConfDao->deleteById($schedConfId);

			return DAO::getDataChangedEvent($schedConfId);
		}

		$json = new JSONMessage(false);
		return $json->getString();
	}


	//
	// Private helper methods.
	//
	/**
	 * Get the "add context" locale key
	 * @return string
	 */
	protected function _getAddContextKey() {
		return 'manager.schedConfs.create';
	}

	/**
	 * Get the context name locale key
	 * @return string
	 */
	protected function _getContextNameKey() {
		return 'manager.schedConfs.scheduledConference';
	}
}

?>
