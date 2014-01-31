<?php

/**
 * @file classes/i18n/AppLocale.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Locale
 * @ingroup i18n
 *
 * @brief Provides methods for loading locale data and translating strings identified by unique keys
 *
 */

// $Id$


import('i18n.PKPLocale');

define('LOCALE_COMPONENT_APPLICATION_COMMON',	0x00000101);
define('LOCALE_COMPONENT_OCS_AUTHOR',		0x00000102);
define('LOCALE_COMPONENT_OCS_DIRECTOR',		0x00000103);
define('LOCALE_COMPONENT_OCS_MANAGER',		0x00000104);
define('LOCALE_COMPONENT_OCS_ADMIN',		0x00000105);
define('LOCALE_COMPONENT_OCS_DEFAULT',		0x00000106);

class AppLocale extends PKPLocale {
	/**
	 * Get all supported UI locales for the current context.
	 * @return array
	 */
	function getSupportedLocales() {
		static $supportedLocales;
		if (!isset($supportedLocales)) {
			if (defined('SESSION_DISABLE_INIT') || !Config::getVar('general', 'installed')) {
				$supportedLocales = AppLocale::getAllLocales();
			} elseif (($conference =& Request::getConference())) {
				$supportedLocales = $conference->getSupportedLocaleNames();
			} else {
				$site =& Request::getSite();
				$supportedLocales = $site->getSupportedLocaleNames();
			}
		}
		return $supportedLocales;
	}

	/**
	 * Get all supported form locales for the current context.
	 * @return array
	 */
	function getSupportedFormLocales() {
		static $supportedFormLocales;
		if (!isset($supportedFormLocales)) {
			if (defined('SESSION_DISABLE_INIT') || !Config::getVar('general', 'installed')) {
				$supportedFormLocales = AppLocale::getAllLocales();
			} elseif (($conference =& Request::getConference())) {
				$supportedFormLocales = $conference->getSupportedFormLocaleNames();
			} else {
				$site =& Request::getSite();
				$supportedFormLocales = $site->getSupportedLocaleNames();
			}
		}
		return $supportedFormLocales;
	}

	/**
	 * Return the key name of the user's currently selected locale (default
	 * is "en_US" for U.S. English).
	 * @return string 
	 */
	function getLocale() {
		static $currentLocale;
		if (!isset($currentLocale)) {
			if (defined('SESSION_DISABLE_INIT') || !Config::getVar('general', 'installed')) {
				// If the locale is specified in the URL, allow
				// it to override. (Necessary when locale is
				// being set, as cookie will not yet be re-set)
				$locale = Request::getUserVar('setLocale');
				if (empty($locale) || !in_array($locale, array_keys(AppLocale::getSupportedLocales()))) $locale = Request::getCookieVar('currentLocale');
			} else {
				$sessionManager =& SessionManager::getManager();
				$session =& $sessionManager->getUserSession();
				$locale = $session->getSessionVar('currentLocale');

				$conference =& Request::getConference();
				$site =& Request::getSite();

				if (!isset($locale)) {
					$locale = Request::getCookieVar('currentLocale');
				}

				if (isset($locale)) {
					// Check if user-specified locale is supported
					if ($conference != null) {
						$locales =& $conference->getSupportedLocaleNames();
					} else {
						$locales =& $site->getSupportedLocaleNames();
					}

					if (!in_array($locale, array_keys($locales))) {
						unset($locale);
					}
				}

				if (!isset($locale)) {
					// Use conference/site default
					if ($conference != null) {
						$locale = $conference->getPrimaryLocale();
					}

					if (!isset($locale)) {
						$locale = $site->getPrimaryLocale();
					}
				}
			}

			if (!AppLocale::isLocaleValid($locale)) {
				$locale = LOCALE_DEFAULT;
			}

			$currentLocale = $locale;
		}
		return $currentLocale;
	}

	/**
	 * Get the stack of "important" locales, most important first.
	 * @return array
	 */
	function getLocalePrecedence() {
		static $localePrecedence;
		if (!isset($localePrecedence)) {
			$localePrecedence = array(AppLocale::getLocale());

			$conference =& Request::getConference();
			if ($conference && !in_array($conference->getPrimaryLocale(), $localePrecedence)) $localePrecedence[] = $conference->getPrimaryLocale();

			$site =& Request::getSite();
			if ($site && !in_array($site->getPrimaryLocale(), $localePrecedence)) $localePrecedence[] = $site->getPrimaryLocale();
		}
		return $localePrecedence;
	}

	/**
	 * Retrieve the primary locale of the current context.
	 * @return string
	 */
	function getPrimaryLocale() {
		$conference =& Request::getConference();

		if (isset($conference)) {
			$locale = $conference->getPrimaryLocale();
		}

		if (!isset($locale)) {
			$site =& Request::getSite();
			$locale = $site->getPrimaryLocale();
		}

		if (!isset($locale) || !AppLocale::isLocaleValid($locale)) {
			$locale = LOCALE_DEFAULT;
		}

		return $locale;
	}

	/**
	 * Make a map of components to their respective files.
	 * @param $locale string
	 * @return array
	 */
	function makeComponentMap($locale) {
		$componentMap = parent::makeComponentMap($locale);
		$baseDir = "locale/$locale/";
		$componentMap[LOCALE_COMPONENT_APPLICATION_COMMON] = $baseDir . 'locale.xml';
		$componentMap[LOCALE_COMPONENT_OCS_AUTHOR] = $baseDir . 'author.xml';
		$componentMap[LOCALE_COMPONENT_OCS_DIRECTOR] = $baseDir . 'director.xml';
		$componentMap[LOCALE_COMPONENT_OCS_MANAGER] = $baseDir . 'manager.xml';
		$componentMap[LOCALE_COMPONENT_OCS_ADMIN] = $baseDir . 'admin.xml';
		$componentMap[LOCALE_COMPONENT_OCS_DEFAULT] = $baseDir . 'default.xml';
		return $componentMap;
	}
}

if (!class_exists('Locale')) {
	class Locale extends AppLocale {
		// This is used for backwards compatibility (bug #5240)
	}
}

?>
