<?php

/**
 * @file TranslatorAction.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TranslatorAction
 * @ingroup plugins_generic_translator
 *
 * @brief Perform various tasks related to translation.
 */

//$Id$

class TranslatorAction {
	/**
	 * Export the locale files to the browser as a tarball.
	 * Requires /bin/tar for operation.
	 */
	function export($locale) {
		// Construct the tar command
		$tarBinary = Config::getVar('cli', 'tar');
		if (empty($tarBinary) || !file_exists($tarBinary)) {
			// We can use fatalError() here as we already have a user
			// friendly way of dealing with the missing tar on the
			// index page.
			fatalError('The tar binary must be configured in config.inc.php\'s cli section to use the export function of this plugin!');
		}
		$command = $tarBinary.' cz';

		$localeFilesList = TranslatorAction::getLocaleFiles($locale);
		$localeFilesList = array_merge($localeFilesList, TranslatorAction::getMiscLocaleFiles($locale));
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$localeFilesList[] = $emailTemplateDao->getMainEmailTemplateDataFilename($locale);
		foreach (array_values(TranslatorAction::getEmailFileMap($locale)) as $emailFile) {
		}

		// Include locale files (main file and plugin files)
		foreach ($localeFilesList as $file) {
			if (file_exists($file)) $command .= ' ' . escapeshellarg($file);
		}

		header('Content-Type: application/x-gtar');
		header("Content-Disposition: attachment; filename=\"$locale.tar.gz\"");
		header('Cache-Control: private'); // Workarounds for IE weirdness
		passthru($command);
	}

	function getLocaleFiles($locale) {
		if (!Locale::isLocaleValid($locale)) return null;

		$localeFiles = Locale::getFilenameComponentMap($locale);
		$plugins =& PluginRegistry::loadAllPlugins();
		foreach (array_keys($plugins) as $key) {
			$plugin =& $plugins[$key];
			$localeFile = $plugin->getLocaleFilename($locale);
			if (!empty($localeFile)) $localeFiles[] = $localeFile;
			unset($plugin);
		}
		return $localeFiles;
	}

	function getMiscLocaleFiles($locale) {
		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$currencyDao =& DAORegistry::getDAO('CurrencyDAO');
		return array(
			$countryDao->getFilename($locale),
			$currencyDao->getCurrencyFilename($locale)
		);
	}

	function getEmailFileMap($locale) {
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$files = array($emailTemplateDao->getMainEmailTemplatesFilename() => $emailTemplateDao->getMainEmailTemplateDataFilename($locale));
		$categories = PluginRegistry::getCategories();
		foreach ($categories as $category) {
			$plugins =& PluginRegistry::loadCategory($category, true);
			foreach (array_keys($plugins) as $name) {
				$plugin =& $plugins[$name];
				$templatesFile = $plugin->getInstallEmailTemplatesFile();
				if ($templatesFile) {
					$files[$templatesFile] = str_replace('{$installedLocale}', $locale, $plugin->getInstallEmailTemplateDataFile());
				}
				unset($plugin);
			}
			unset($plugins);
		}
		return $files;
	}

	function getEmailTemplates($locale) {
		$files = TranslatorAction::getEmailFileMap($locale);
		$returner = array();
		foreach ($files as $templateFile => $templateDataFile) {
			$xmlParser = new XMLParser();
			$data =& $xmlParser->parse($templateDataFile, array('email'));
			if ($data) foreach ($data->getChildren() as $emailNode) {
				$returner[$emailNode->getAttribute('key')] = array(
					'subject' => $emailNode->getChildValue('subject'),
					'body' => $emailNode->getChildValue('body'),
					'description' => $emailNode->getChildValue('description'),
					'templateFile' => $templateFile,
					'templateDataFile' => $templateDataFile
				);
			}
			unset($xmlParser, $data);
		}
		return $returner;
	}

	function isLocaleFile($locale, $filename) {
		if (in_array($filename, TranslatorAction::getLocaleFiles($locale))) return true;
		if (in_array($filename, TranslatorAction::getMiscLocaleFiles($locale))) return true;
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		if ($filename == $emailTemplateDao->getMainEmailTemplateDataFilename($locale)) return true;
		return false;
	}

	function determineReferenceFilename($locale, $filename) {
		// FIXME: This is ugly.
		return str_replace($locale, MASTER_LOCALE, $filename);
	}

	/**
	 * Test all locale files for the supplied locale against the supplied
	 * reference locale, returning an array of errors.
	 * @param $locale string Name of locale to test
	 * @param $referenceLocale string Name of locale to test against
	 * @return array
	 */
	function testLocale($locale, $referenceLocale) {
		$localeFileNames = Locale::getFilenameComponentMap($locale);

		$errors = array();
		foreach ($localeFileNames as $localeFileName) {
			$referenceLocaleFileName = str_replace($locale, $referenceLocale, $localeFileName);
			$localeFile = new LocaleFile($locale, $localeFileName);
			$referenceLocaleFile = new LocaleFile($referenceLocale, $referenceLocaleFileName);
			$errors = array_merge_recursive($errors, $localeFile->testLocale($referenceLocaleFile));
			unset($localeFile);
			unset($referenceLocaleFile);
		}

		$plugins =& PluginRegistry::loadAllPlugins();
		foreach (array_keys($plugins) as $key) {
			$plugin =& $plugins[$key];
			$referenceLocaleFilename = $plugin->getLocaleFilename($referenceLocale);
			if ($referenceLocaleFilename) {
				$localeFile = new LocaleFile($locale, $plugin->getLocaleFilename($locale));
				$referenceLocaleFile = new LocaleFile($referenceLocale, $referenceLocaleFilename);
				$errors = array_merge_recursive($errors, $localeFile->testLocale($referenceLocaleFile));
				unset($localeFile);
				unset($referenceLocaleFile);
			}
			unset($plugin);
		}
		return $errors;
	}

	/**
	 * Test the emails in the supplied locale against those in the supplied
	 * reference locale.
	 * @param $locale string
	 * @param $referenceLocale string
	 * @return array List of errors
	 */
	function testEmails($locale, $referenceLocale) {
		$errors = array(
		);

		$emails = TranslatorAction::getEmailTemplates($locale);
		$referenceEmails = TranslatorAction::getEmailTemplates($referenceLocale);

		// Pass 1: For all translated emails, check that they match
		// against reference translations.
		foreach ($emails as $emailKey => $email) {
			// Check if a matching reference email was found.
			if (!isset($referenceEmails[$emailKey])) {
				$errors[EMAIL_ERROR_EXTRA_EMAIL][] = array(
					'key' => $emailKey
				);
				continue;
			}

			// We've successfully found a matching reference email.
			// Compare it against the translation.
			$bodyParams = Locale::getParameterNames($email['body']);
			$referenceBodyParams = Locale::getParameterNames($referenceEmails[$emailKey]['body']);
			$diff = array_diff($bodyParams, $referenceBodyParams);
			if (!empty($diff)) {
				$errors[EMAIL_ERROR_DIFFERING_PARAMS][] = array(
					'key' => $emailKey,
					'mismatch' => $diff
				);
			}

			$subjectParams = Locale::getParameterNames($email['subject']);
			$referenceSubjectParams = Locale::getParameterNames($referenceEmails[$emailKey]['subject']);

			$diff = array_diff($subjectParams, $referenceSubjectParams);
			if (!empty($diff)) {
				$errors[EMAIL_ERROR_DIFFERING_PARAMS][] = array(
					'key' => $emailKey,
					'mismatch' => $diff
				);
			}

			$matchedReferenceEmails[] = $emailKey;

			unset($email);
			unset($referenceEmail);
		}

		// Pass 2: Make sure that there are no missing translations.
		foreach ($referenceEmails as $emailKey => $email) {
			// Extract the fields from the email to be tested.
			if (!isset($emails[$emailKey])) {
				$errors[EMAIL_ERROR_MISSING_EMAIL][] = array(
					'key' => $emailKey
				);
			}
		}

		return $errors;
	}
}

?>
