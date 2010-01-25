<?php

/**
 * @file ConferenceLanguagesHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
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
	 */
	function saveLanguageSettings() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.LanguageSettingsForm');

		$settingsForm = new LanguageSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, null, 'languages'),
				'pageTitle' => 'common.languages',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, Request::getRequestedPage()),
				'backLinkLabel' => 'manager.conferenceSiteManagement'
			));
			$templateMgr->display('common/message.tpl');

		} else {
			$settingsForm->display();
		}
	}

	function reloadLocalizedDefaultSettings() {
		// make sure the locale is valid
		$locale = Request::getUserVar('localeToLoad');
		if ( !Locale::isLocaleValid($locale) ) {
			Request::redirect(null, null, null, 'languages');
		}

		$this->validate();
		$this->setupTemplate(true);
					
		$conference =& Request::getConference();
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$conferenceSettingsDao->reloadLocalizedDefaultSettings($conference->getId(), 'registry/conferenceSettings.xml', array(
				'indexUrl' => Request::getIndexUrl(),
				'conferencePath' => $conference->getData('path'),
				'primaryLocale' => $conference->getPrimaryLocale(),
				'conferenceName' => $conference->getTitle($conference->getPrimaryLocale())
			),
			$locale);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array(
			'currentUrl' => Request::url(null, null, null, 'languages'),
			'pageTitle' => 'common.languages',
			'message' => 'common.changesSaved',
			'backLink' => Request::url(null, null, Request::getRequestedPage()),
			'backLinkLabel' => 'manager.conferenceSiteManagement'
		));
		$templateMgr->display('common/message.tpl');
	}

}
?>
