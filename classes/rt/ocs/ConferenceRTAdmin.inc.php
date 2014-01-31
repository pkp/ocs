<?php

/**
 * @file ConferenceRTAdmin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceRTAdmin
 * @ingroup rt_ocs
 *
 * @brief OCS-specific Reading Tools administration interface.
 */

//$Id$

import('rt.RTAdmin');
import('rt.ocs.RTDAO');

define('RT_DIRECTORY', 'rt');
define('DEFAULT_RT_LOCALE', 'en_US');

class ConferenceRTAdmin extends RTAdmin {

	/** @var $conferenceId int */
	var $conferenceId;

	/** @var $dao DAO */
	var $dao;


	function ConferenceRTAdmin($conferenceId) {
		$this->conferenceId = $conferenceId;
		$this->dao =& DAORegistry::getDAO('RTDAO');
	}

	function restoreVersions($deleteBeforeLoad = true) {
		import('rt.RTXMLParser');
		$parser = new RTXMLParser();

		if ($deleteBeforeLoad) $this->dao->deleteVersionsByConferenceId($this->conferenceId);

		$localeFilesLocation = RT_DIRECTORY . DIRECTORY_SEPARATOR . AppLocale::getLocale();
		if (!file_exists($localeFilesLocation)) {
			// If no reading tools exist for the given locale, use the default set
			$localeFilesLocation = RT_DIRECTORY . DIRECTORY_SEPARATOR . DEFAULT_RT_LOCALE;
			$overrideLocale = true;
		} else {
			$overrideLocale = false;
		}

		$versions = $parser->parseAll($localeFilesLocation);
		foreach ($versions as $version) {
			if ($overrideLocale) {
				$version->setLocale(AppLocale::getLocale());
			}
			$this->dao->insertVersion($this->conferenceId, $version);
		}
	}

	function importVersion($filename) {
		import ('rt.RTXMLParser');
		$parser = new RTXMLParser();

		$version =& $parser->parse($filename);
		$this->dao->insertVersion($this->conferenceId, $version);
	}
}

?>
