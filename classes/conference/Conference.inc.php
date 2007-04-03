<?php

/**
 * Conference.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package conference
 *
 * Conference class.
 * Describes basic conference properties.
 *
 * $Id$
 */

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
	 * Get title of conference
	 * @return string
	 */
	 function getTitle() {
	 	return $this->getData('title');
	}
	
	/**
	* Set title of conference
	* @param $title string
	*/
	function setTitle($title) {
		return $this->setData('title',$title);
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
		return $this->getData('conferenceId');
	}
	
	/**
	 * Set ID of conference.
	 * @param $conferenceId int
	 */
	function setConferenceId($conferenceId) {
		return $this->setData('conferenceId', $conferenceId);
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
		$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		$settings = &$conferenceSettingsDao->getConferenceSettings($this->getData('conferenceId'));
		return $settings;
	}
	
	/**
	 * Retrieve a conference setting value.
	 * @param $name
	 * @return mixed
	 */
	function &getSetting($name) {
		$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
		$setting = &$conferenceSettingsDao->getSetting($this->getData('conferenceId'), $name);
		return $setting;
	}

	/**
	 * Update a conference setting value.
	 */
	function updateSetting($name, $value, $type = null) {
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		return $conferenceSettingsDao->updateSetting($this->getConferenceId(), $name, $value, $type);
	}

	/**
	 * Return the primary locale of this conference.
	 * @return string
	 */
	function getLocale() {
		return $this->getSetting('primaryLocale');
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
			$localeNames = &Locale::getAllLocales();

			$locales = $this->getSetting('supportedLocales');
			if (!isset($locales) || !is_array($locales)) {
				$locales = array();
			}
						
			foreach ($locales as $localeKey) {
				$supportedLocales[$localeKey] = $localeNames[$localeKey];
			}
		
			asort($supportedLocales);
		}
		
		return $supportedLocales;
	}
	
	/**
	 * Get "localized" conference page title (if applicable).
	 * param $home boolean get homepage title
	 * @return string
	 */
	function getPageHeaderTitle($home = false) {
		// FIXME this is evil
		$alternateLocaleNum = Locale::isAlternateConferenceLocale($this->getData('conferenceId'));
		$prefix = $home ? 'home' : 'page';
		switch ($alternateLocaleNum) {
			case 1:
				$type = $this->getSetting($prefix . 'HeaderTitleTypeAlt1');
				if ($type) {
					$title = $this->getSetting($prefix . 'HeaderTitleImageAlt1');
				}
				if (!isset($title)) {
					$title = $this->getSetting($prefix . 'HeaderTitleAlt1');
				}
				break;
			case 2:
				$type = $this->getSetting($prefix . 'HeaderTitleTypeAlt2');
				if ($type) {
					$title = $this->getSetting($prefix . 'HeaderTitleImageAlt2');
				}
				if (!isset($title)) {
					$title = $this->getSetting($prefix . 'HeaderTitleAlt2');
				}
				break;
		}
		
		if (isset($title) && !empty($title)) {
			return $title;
			
		} else {
			$type = $this->getSetting($prefix . 'HeaderTitleType');
			if ($type) {
				$title = $this->getSetting($prefix . 'HeaderTitleImage');
			}
			if (!isset($title)) {
				$title = $this->getSetting($prefix . 'HeaderTitle');
			}
			
			return $title;
		}
	}
	
	/**
	 * Get "localized" conference page logo (if applicable).
	 * param $home boolean get homepage logo
	 * @return string
	 */
	function getPageHeaderLogo($home = false) {
		// FIXME this is evil
		$alternateLocaleNum = Locale::isAlternateConferenceLocale($this->getData('conferenceId'));
		$prefix = $home ? 'home' : 'page';
		switch ($alternateLocaleNum) {
			case 1:
				$logo = $this->getSetting($prefix . 'HeaderLogoImageAlt1');
				break;
			case 2:
				$logo = $this->getSetting($prefix . 'HeaderLogoImageAlt2');
				break;
		}
		
		if (isset($logo) && !empty($logo)) {
			return $logo;
			
		} else {
			return $this->getSetting($prefix . 'HeaderLogoImage');
		}
	}
}

?>
