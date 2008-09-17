<?php

/**
 * @file classes/i18n/Locale.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
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

class Locale extends PKPLocale {
	/**
	 * Get all supported locales for the current context.
	 * @return array
	 */
	function getSupportedLocales() {
		static $supportedLocales;
		if (!isset($supportedLocales)) {
			if (defined('SESSION_DISABLE_INIT') || !Config::getVar('general', 'installed')) {
				$supportedLocales = Locale::getAllLocales();
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
				if (empty($locale) || !in_array($locale, array_keys(Locale::getSupportedLocales()))) $locale = Request::getCookieVar('currentLocale');
			} else {
				$sessionManager = &SessionManager::getManager();
				$session = &$sessionManager->getUserSession();
				$locale = $session->getSessionVar('currentLocale');

				$conference = &Request::getConference();
				$site = &Request::getSite();

				if (!isset($locale)) {
					$locale = Request::getCookieVar('currentLocale');
				}

				if (isset($locale)) {
					// Check if user-specified locale is supported
					if ($conference != null) {
						$locales = &$conference->getSupportedLocaleNames();
					} else {
						$locales = &$site->getSupportedLocaleNames();
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

			if (!Locale::isLocaleValid($locale)) {
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
			$localePrecedence = array(Locale::getLocale());

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
		$conference = &Request::getConference();

		if (isset($conference)) {
			$locale = $conference->getPrimaryLocale();
		}

		if (!isset($locale)) {
			$site = &Request::getSite();
			$locale = $site->getPrimaryLocale();
		}

		if (!isset($locale) || !Locale::isLocaleValid($locale)) {
			$locale = LOCALE_DEFAULT;
		}

		return $locale;
	}
}

?>
