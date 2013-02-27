<?php

/**
 * @defgroup conference
 */

/**
 * @file Conference.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Conference
 * @ingroup conference
 * @see ConferenceDAO
 *
 * @brief Describes basic conference properties.
 */

define('PAPER_ACCESS_OPEN',			0x00000000);
define('PAPER_ACCESS_ACCOUNT_REQUIRED',		0x00000001);
define('PAPER_ACCESS_REGISTRATION_REQUIRED',	0x00000002);

import('lib.pkp.classes.context.Context');

class Conference extends Context {
	/**
	 * Constructor.
	 */
	function Conference() {
		parent::Context();
	}

	/**
	 * Get ID of conference.
	 * @return int
	 */
	function getConferenceId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of conference.
	 * @param $conferenceId int
	 */
	function setConferenceId($conferenceId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($conferenceId);
	}

	/**
	 * Get "localized" conference page title (if applicable).
	 * param $home boolean get homepage title
	 * @return string
	 */
	function getPageHeaderTitle($home = false) {
		$prefix = $home ? 'home' : 'page';
		$typeArray = $this->getSetting($prefix . 'HeaderTitleType');
		$imageArray = $this->getSetting($prefix . 'HeaderTitleImage');
		$titleArray = $this->getSetting($prefix . 'HeaderTitle');

		$title = null;

		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($typeArray[$locale]) && $typeArray[$locale]) {
				if (isset($imageArray[$locale])) $title = $imageArray[$locale];
			}
			if (empty($title) && isset($titleArray[$locale])) $title = $titleArray[$locale];
			if (!empty($title)) return $title;
		}
		return null;
	}

	/**
	 * Get "localized" conference page logo (if applicable).
	 * param $home boolean get homepage logo
	 * @return string
	 */
	function getPageHeaderLogo($home = false) {
		$prefix = $home ? 'home' : 'page';
		$logoArray = $this->getSetting($prefix . 'HeaderLogoImage');
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($logoArray[$locale])) return $logoArray[$locale];
		}
		return null;
	}

	/**
	 * Get localized favicon
	 * @return string
	 */
	function getLocalizedFavicon() {
		$faviconArray = $this->getSetting('conferenceFavicon');
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($faviconArray[$locale])) return $faviconArray[$locale];
		}
	}

	/**
	 * Get the association type for this context.
	 * @return int
	 */
	function getAssocType() {
		return ASSOC_TYPE_CONFERENCE;
	}

	/**
	 * Get the settings DAO for this context object.
	 * @return DAO
	 */
	static function getSettingsDAO() {
		return DAORegistry::getDAO('ConferenceSettingsDAO');
	}

	/**
	 * Get the DAO for this context object.
	 * @return DAO
	 */
	static function getDAO() {
		return DAORegistry::getDAO('ConferenceDAO');
	}

}

?>
