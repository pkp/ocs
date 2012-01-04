<?php

/**
 * @file ConferenceLanguagesHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceLanguagesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for changing conference language settings. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class ConferenceLanguagesHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function ConferenceLanguagesHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display form to edit language settings.
	 */
	function languages() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}

	/**
	 * Save changes to language settings.
	 * @param $args array
	 * @param $request object
	 */
	function saveLanguageSettings($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			import('notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification('notification.notification', 'common.changesSaved');
			$request->redirect(null, null, null, 'index');
		} else {
			$settingsForm->display();
		}
	}

	/**
	 * Reload the default localized settings for this conference
	 * @param $args array
	 * @param $request object
	 */
	function reloadLocalizedDefaultSettings($args, &$request) {
		// make sure the locale is valid
		$locale = $request->getUserVar('localeToLoad');
		if ( !AppLocale::isLocaleValid($locale) ) {
			$request->redirect(null, null, null, 'languages');
		}

		$this->validate();
		$this->setupTemplate(true);
					
		$conference =& $request->getConference();
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$conferenceSettingsDao->reloadLocalizedDefaultSettings(
			$conference->getId(), 'registry/conferenceSettings.xml',
			array(
				'indexUrl' => $request->getIndexUrl(),
				'conferencePath' => $conference->getData('path'),
				'primaryLocale' => $conference->getPrimaryLocale(),
				'conferenceName' => $conference->getTitle($conference->getPrimaryLocale())
			),
			$locale
		);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array(
			'currentUrl' => $request->url(null, null, null, 'languages'),
			'pageTitle' => 'common.languages',
			'message' => 'common.changesSaved',
			'backLink' => $request->url(null, null, $request->getRequestedPage()),
			'backLinkLabel' => 'manager.conferenceSiteManagement'
		));
		$templateMgr->display('common/message.tpl');
	}

}

?>
