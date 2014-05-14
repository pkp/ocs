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

//$Id$

define('PAPER_ACCESS_OPEN',			0x00000000);
define('PAPER_ACCESS_ACCOUNT_REQUIRED',		0x00000001);
define('PAPER_ACCESS_REGISTRATION_REQUIRED',	0x00000002);

class Conference extends DataObject {
	//
	// Conference functions: the following do not operate on data from the
	// ConferenceSettings table.
	//

	/**
	 * Constructor.
	 */
	function Conference() {
		parent::DataObject();
	}

	/**
	 * Get the base URL to the conference.
	 * @return string
	 */
	function getUrl() {
		return Request::url($this->getPath());
	}

	/**
	 * Get the localized title of the conference.
	 * @return string
	 */
	function getConferenceTitle() {
		return $this->getLocalizedSetting('title');
	}

	/**
	 * Get title of conference
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getSetting('title', $locale);
	}

	/**
	 * Get the localized description of the conference.
	 * @return string
	 */
	function getConferenceDescription() {
		return $this->getLocalizedSetting('description');
	}

	/**
	 * Get description of conference
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getSetting('description', $locale);
	}

	/**
	 * Get enabled flag of conference
	 * @return int
	 */
	function getEnabled() {
		return $this->getData('enabled');
	}

	/**
	 * Set enabled flag of conference
	 * @param $enabled int
	 */
	function setEnabled($enabled) {
		return $this->setData('enabled',$enabled);
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
	 * Get path to conference (in URL).
	 * @return string
	 */
	function getPath() {
		return $this->getData('path');
	}

	/**
	 * Set path to conference (in URL).
	 * @param $path string
	 */
	function setPath($path) {
		return $this->setData('path', $path);
	}

	/**
	 * Get sequence of conference in site table of contents.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of conference in site table of contents.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	//
	// ConferenceSettings functions: the following make use of data in the
	// ConferenceSettings table.
	//

	/**
	 * Retrieve array of conference settings.
	 * @return array
	 */
	function &getSettings() {
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$settings =& $conferenceSettingsDao->getConferenceSettings($this->getId());
		return $settings;
	}

	function &getLocalizedSetting($name) {
		$returner = $this->getSetting($name, AppLocale::getLocale());
		if ($returner === null) {
			unset($returner);
			$returner = $this->getSetting($name, AppLocale::getPrimaryLocale());
		}
		return $returner;
	}

	/**
	 * Retrieve a conference setting value.
	 * @param $name
	 * @param $locale string
	 * @return mixed
	 */
	function &getSetting($name, $locale = null) {
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$setting =& $conferenceSettingsDao->getSetting($this->getId(), $name, $locale);
		return $setting;
	}

	/**
	 * Update a conference setting value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string optional
	 * @param $isLocalized boolean optional
	 */
	function updateSetting($name, $value, $type = null, $isLocalized = false) {
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		return $conferenceSettingsDao->updateSetting($this->getConferenceId(), $name, $value, $type, $isLocalized);
	}

	/**
	 * Return the primary locale of this conference.
	 * @return string
	 */
	function getPrimaryLocale() {
		return $this->getData('primaryLocale');
	}

	/**
	 * Set the primary locale of this conference.
	 * @param $primaryLocale string
	 */
	function setPrimaryLocale($primaryLocale) {
		$this->setData('primaryLocale', $primaryLocale);
	}

	/**
	 * Return associative array of all locales supported by the site.
	 * These locales are used to provide a language toggle on the main site pages.
	 * @return array
	 */
	function &getSupportedLocaleNames() {
		static $supportedLocales;

		if (!isset($supportedLocales)) {
			$supportedLocales = array();
			$localeNames =& AppLocale::getAllLocales();

			$locales = $this->getSetting('supportedLocales');
			if (!isset($locales) || !is_array($locales)) {
				$locales = array();
			}

			foreach ($locales as $localeKey) {
				$supportedLocales[$localeKey] = $localeNames[$localeKey];
			}
		}

		return $supportedLocales;
	}

	/**
	 * Return associative array of all locales supported by forms on the site.
	 * These locales are used to provide a language toggle on the main site pages.
	 * @return array
	 */
	function &getSupportedFormLocaleNames() {
		$supportedLocales =& $this->getData('supportedFormLocales');

		if (!isset($supportedLocales)) {
			$supportedLocales = array();
			$localeNames =& AppLocale::getAllLocales();

			$locales = $this->getSetting('supportedFormLocales');
			if (!isset($locales) || !is_array($locales)) {
				$locales = array();
			}

			foreach ($locales as $localeKey) {
				$supportedLocales[$localeKey] = $localeNames[$localeKey];
			}
		}

		return $supportedLocales;
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
}

?>
