<?php

/**
 * @file controllers/grid/admin/conference/ConferenceGridHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceGridHandler
 * @ingroup controllers_grid_admin_conference
 *
 * @brief Handle conference grid requests.
 */

import('lib.pkp.controllers.grid.admin.context.ContextGridHandler');

import('controllers.grid.admin.conference.ConferenceGridRow');
import('controllers.grid.admin.conference.form.ConferenceSiteSettingsForm');

class ConferenceGridHandler extends ContextGridHandler {
	/**
	 * Constructor
	 */
	function ConferenceGridHandler() {
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

		// Basic grid configuration.
		$this->setTitle('conference.conferences');
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return UserGridRow
	 */
	function &getRowInstance() {
		$row = new ConferenceGridRow();
		return $row;
	}

	/**
	 * @see GridHandler::loadData()
	 * @param $request PKPRequest
	 * @return array Grid data.
	 */
	function loadData(&$request) {
		// Get all conferences.
		$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
		$conferences = $conferenceDao->getAll();

		return $conferences->toAssociativeArray();
	}

	/**
	 * @see lib/pkp/classes/controllers/grid/GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence(&$request, $rowId, &$conference, $newSequence) {
		$conferenceDao = DAORegistry::getDAO('ConferenceDAO'); /* @var $conferenceDao ConferenceDAO */
		$conference->setSequence($newSequence);
		$conferenceDao->updateObject($conference);
	}


	//
	// Public grid actions.
	//
	/**
	 * Edit an existing conference.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editContext($args, &$request) {

		// Identify the conference Id.
		$conferenceId = $request->getUserVar('rowId');

		// Form handling.
		$settingsForm = new ConferenceSiteSettingsForm(!isset($conferenceId) || empty($conferenceId) ? null : $conferenceId);
		$settingsForm->initData();
		$json = new JSONMessage(true, $settingsForm->fetch($args, $request));

		return $json->getString();
	}

	/**
	 * Update an existing conference.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateContext($args, $request) {
		// Identify the conference Id.
		$conferenceId = $request->getUserVar('contextId');

		// Form handling.
		$settingsForm = new ConferenceSiteSettingsForm($conferenceId);
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			PluginRegistry::loadCategory('blocks');

			$settingsForm->execute($request);

			// Create the notification.
			$notificationMgr = new NotificationManager();
			$user =& $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			return DAO::getDataChangedEvent($conferenceId);
		}

		$json = new JSONMessage(false);
		return $json->getString();
	}

	/**
	 * Delete a conference.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteContext($args, &$request) {
		// Identify the current context.
		$context =& $request->getContext();

		// Identify the conference Id.
		$conferenceId = $request->getUserVar('rowId');
		$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
		$conference =& $conferenceDao->getById($conferenceId);

		if ($conference) {
			$conferenceDao->deleteById($conferenceId);

			// Delete conference file tree
			// FIXME move this somewhere better.
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager($conferenceId);
			$conferencePath = Config::getVar('files', 'files_dir') . '/conferences/' . $conferenceId;
			$fileManager->rmtree($conferencePath);

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$publicFileManager->rmtree($publicFileManager->getConferenceFilesPath($conferenceId));

			return DAO::getDataChangedEvent($conferenceId);
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
		return 'admin.conferences.create';
	}

	/**
	 * Get the context name locale key
	 * @return string
	 */
	protected function _getContextNameKey() {
		return 'manager.setup.conferenceTitle';
	}
}

?>
