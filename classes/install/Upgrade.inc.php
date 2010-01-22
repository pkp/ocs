<?php

/**
 * @file Upgrade.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Upgrade
 * @ingroup install
 *
 * @brief Perform system upgrade.
 */

//$Id$

import('install.Installer');

class Upgrade extends Installer {

	/**
	 * Constructor.
	 * @param $params array upgrade parameters
	 */
	function Upgrade($params) {
		parent::Installer('upgrade.xml', $params);
	}


	/**
	 * Returns true iff this is an upgrade process.
	 * @return boolean
	 */
	function isUpgrade() {
		return true;
	}

	//
	// Upgrade actions
	//

	/**
	 * Rebuild the search index.
	 * @return boolean
	 */
	function rebuildSearchIndex() {
		import('search.PaperSearchIndex');
		PaperSearchIndex::rebuildIndex();
		return true;
	}

	/**
	 * For upgrade to 2.1.0: Install default settings for block plugins.
	 * @return boolean
	 */
	function installBlockPlugins() {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conferences =& $conferenceDao->getConferences();

		// Get conference IDs for insertion, including 0 for site-level
		$conferenceIds = array(0);
		while ($conference =& $conferences->next()) {
			$conferenceIds[] = $conference->getConferenceId();
			unset($conference);
		}

		$pluginNames = array(
			'DevelopedByBlockPlugin',
			'HelpBlockPlugin',
			'UserBlockPlugin',
			'RoleBlockPlugin',
			'LanguageToggleBlockPlugin',
			'NavigationBlockPlugin',
			'FontSizeBlockPlugin',
			'InformationBlockPlugin'
		);
		foreach ($conferenceIds as $conferenceId) {
			$i = 0;
			foreach ($pluginNames as $pluginName) {
				$pluginSettingsDao->updateSetting($conferenceId, 0, $pluginName, 'enabled', 'true', 'bool');
				$pluginSettingsDao->updateSetting($conferenceId, 0, $pluginName, 'seq', $i++, 'int');
				$pluginSettingsDao->updateSetting($conferenceId, 0, $pluginName, 'context', BLOCK_CONTEXT_RIGHT_SIDEBAR, 'int');
			}
		}

		return true;
	}

	/**
	 * For upgrade to 2.1.0: Move primary_locale from conference settings
	 * into dedicated column.
	 * @return boolean
	 */
	function setConferencePrimaryLocales() {
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');

		// There was a bug in OCS 2.0 that resulted in a conference
		// primary locale setting of {$primaryLocale} (or truncated to
		// "{$pri"); in this case, the site primary locale should be
		// used as a fallback.
		$result =& $conferenceSettingsDao->retrieve('SELECT primary_locale FROM site');
		$siteLocale = 'en_US';
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$siteLocale = $row['primary_locale'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		$result =& $conferenceSettingsDao->retrieve('SELECT conference_id, setting_value FROM conference_settings WHERE setting_name = ?', array('primaryLocale'));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$newLocale = $row['setting_value'];

			// Fix the bug mentioned above.
			if (empty($newLocale) || strpos($newLocale, '{$pr') === 0) {
				$newLocale = $siteLocale;
			}

			// Set the primary locale value in the conferences table.
			$conferenceDao->update('UPDATE conferences SET primary_locale = ? WHERE conference_id = ?', array($newLocale, $row['conference_id']));
			$result->MoveNext();
		}
		$conferenceDao->update('UPDATE conferences SET primary_locale = ? WHERE primary_locale IS NULL OR primary_locale = ?', array(INSTALLER_DEFAULT_LOCALE, ''));
		$result->Close();
		return true;
	}

	/**
	 * For upgrade to 2.1.0: Migrate paper locations into scheduler
	 * @return boolean
	 */
	function migratePaperLocations() {
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$roomDao =& DAORegistry::getDAO('RoomDAO');

		$lastSchedConfId = null;
		$buildingId = null;

		$result =& $paperDao->retrieve("SELECT p.paper_id, c.primary_locale, sc.sched_conf_id, p.location FROM papers p, published_papers pp, sched_confs sc, conferences c WHERE p.paper_id = pp.paper_id AND p.sched_conf_id = sc.sched_conf_id AND sc.conference_id = c.conference_id AND location IS NOT NULL AND location <> '' ORDER BY sched_conf_id");
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			$paperId = $row['paper_id'];
			$schedConfId = $row['sched_conf_id'];
			$locale = $row['primary_locale'];
			$location = $row['location'];

			if ($schedConfId !== $lastSchedConfId) {
				// Create a default building
				$defaultText = Locale::translate('common.default');
				$building =& new Building();
				$building->setSchedConfId($schedConfId);
				$building->setName($defaultText, $locale);
				$building->setAbbrev($defaultText, $locale);
				$building->setDescription($defaultText, $locale);
				$buildingId = $buildingDao->insertBuilding($building);
				unset($building);
				$rooms = array();
			}

			if (!isset($rooms[$location])) {
				$room =& new Room();
				$room->setBuildingId($buildingId);
				$room->setName($location, $locale);
				$room->setAbbrev($location, $locale);
				$room->setDescription($location, $locale);
				$roomId = $roomDao->insertRoom($room);

				$rooms[$location] =& $room;
				unset($room);
			} else {
				$room =& $rooms[$location];
				$roomId = $room->getRoomId();
				unset($room);
			}

			$paperDao->update('UPDATE published_papers SET room_id = ? WHERE paper_id = ?', array($roomId, $paperId));

			$result->MoveNext();
			$lastSchedConfId = $schedConfId;

		}
		$result->Close();
		unset($result);

		return true;
	}

	/**
	 * Clear the data cache files (needed because of direct tinkering
	 * with settings tables)
	 * @return boolean
	 */
	function clearDataCache() {
		import('cache.CacheManager');
		$cacheManager =& CacheManager::getManager();
		$cacheManager->flush();
		return true;
	}

	/**
	 * For 2.1.0 upgrade: add locale data to existing conference settings
	 * that were not previously localized.
	 * @return boolean
	 */
	function localizeConferenceSettings() {
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');

		$settingNames = array(
			// Setup page 1
			'title' => 'title',
			'conferenceDescription' => 'description',
			'archiveAccessPolicy' => 'archiveAccessPolicy',
			'copyrightNotice' => 'copyrightNotice',
			'privacyStatement' => 'privacyStatement',
			'customAboutItems' => 'customAboutItems',
			// Setup page 2
			'additionalHomeContent' => 'additionalHomeContent',
			'homepageImage' => 'homepageImage',
			'readerInformation' => 'readerInformation',
			'presenterInformation' => 'presenterInformation',
			'announcementsIntroduction' => 'announcementsIntroduction',
			// Setup page 3
			'homeHeaderLogoImage' => 'homeHeaderLogoImage',
			'homeHeaderTitleType' => 'homeHeaderTitleType',
			'homeHeaderTitle' => 'homeHeaderTitle',
			'homeHeaderTitleImage' => 'homeHeaderTitleImage',
			'pageHeaderTitleType' => 'pageHeaderTitleType',
			'pageHeaderTitle' => 'pageHeaderTitle',
			'pageHeaderTitleImage' => 'pageHeaderTitleImage',
			'navItems' => 'navItems',
			'conferencePageHeader' => 'conferencePageHeader',
			'conferencePageFooter' => 'conferencePageFooter',
			// Setup page 4
			// Setup page 5
			// Setup page 6
			'searchDescription' => 'searchDescription',
			'searchKeywords' => 'searchKeywords',
			'customHeaders' => 'customHeaders',
			// Registration policies
			'registrationAdditionalInformation' => 'registrationAdditionalInformation',
			'delayedOpenAccessPolicy' => 'delayedOpenAccessPolicy',
			'presenterSelfArchivePolicy' => 'presenterSelfArchivePolicy'
		);

		foreach ($settingNames as $oldName => $newName) {
			$result =& $conferenceDao->retrieve('SELECT c.conference_id, c.primary_locale FROM conferences c, conference_settings cs WHERE c.conference_id = cs.conference_id AND cs.setting_name = ? AND (cs.locale IS NULL OR cs.locale = ?)', array($oldName, ''));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$conferenceSettingsDao->update('UPDATE conference_settings SET locale = ?, setting_name = ? WHERE conference_id = ? AND setting_name = ? AND (locale IS NULL OR locale = ?)', array($row['primary_locale'], $newName, $row['conference_id'], $oldName, ''));
				$result->MoveNext();
			}
			$result->Close();
			unset($result);
		}

		return true;
	}

	/**
	 * For 2.1.0 upgrade: add locale data to existing sched conf settings
	 * and that were not previously localized.
	 * @return boolean
	 */
	function localizeSchedConfSettings() {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		$settingNames = array(
			// Setup page 1
			'schedConfIntroduction' => 'introduction',
			'schedConfOverview' => 'overview',
			'emailSignature' => 'emailSignature',
			'sponsorNote' => 'sponsorNote',
			'contributorNote' => 'contributorNote',
			// Setup page 2
			'cfpMessage' => 'cfpMessage',
			'presenterGuidelines' => 'presenterGuidelines',
			'submissionChecklist' => 'submissionChecklist',
			'metaDisciplineExamples' => 'metaDisciplineExamples',
			'metaSubjectClassTitle' => 'metaSubjectClassTitle',
			'metaSubjectExamples' => 'metaSubjectExamples',
			'metaCoverageGeoExamples' => 'metaCoverageGeoExamples',
			'metaCoverageChronExamples' => 'metaCoverageChronExamples',
			'metaCoverageResearchSampleExamples' => 'metaCoverageResearchSampleExamples',
			'metaTypeExamples' => 'metaTypeExamples',
			// Setup page 3
			'reviewPolicy' => 'reviewPolicy',
			'reviewGuidelines' => 'reviewGuidelines'
		);

		foreach ($settingNames as $oldName => $newName) {
			$result =& $schedConfDao->retrieve('SELECT sc.sched_conf_id, c.primary_locale FROM sched_confs sc, conferences c, sched_conf_settings scs WHERE c.conference_id = sc.conference_id AND sc.sched_conf_id = scs.sched_conf_id AND scs.setting_name = ? AND (scs.locale IS NULL OR scs.locale = ?)', array($oldName, ''));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$schedConfSettingsDao->update('UPDATE sched_conf_settings SET locale = ?, setting_name = ? WHERE sched_conf_id = ? AND setting_name = ? AND (locale IS NULL OR locale = ?)', array($row['primary_locale'], $newName, $row['sched_conf_id'], $oldName, ''));
				$result->MoveNext();
			}
			$result->Close();
			unset($result);
		}

		return true;
	}

	/**
	 * For 2.1.0 upgrade: Set locales for galleys.
	 * @return boolean
	 */
	function setGalleyLocales() {
		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');

		$result =& $conferenceDao->retrieve('SELECT g.galley_id, c.primary_locale FROM conferences c, sched_confs sc, papers p, paper_galleys g WHERE p.sched_conf_id = sc.sched_conf_id AND sc.conference_id = c.conference_id AND g.paper_id = p.paper_id AND (g.locale IS NULL OR g.locale = ?)', '');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$paperGalleyDao->update('UPDATE paper_galleys SET locale = ? WHERE galley_id = ?', array($row['primary_locale'], $row['galley_id']));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return true;
	}

	/**
	 * For 2.1.0 upgrade: Set review mode for papers.
	 * @return boolean
	 */
	function setReviewMode() {
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$paperDao =& DAORegistry::getDAO('PaperDAO');

		$schedConfs =& $schedConfDao->getSchedConfs();
		while ($schedConf =& $schedConfs->next()) {
			$papers =& $paperDao->getPapersBySchedConfId($schedConf->getSchedConfId());
			$reviewMode = $schedConf->getSetting('reviewMode');
			$paperDao->update('UPDATE papers SET review_mode = ? WHERE sched_conf_id = ?', array((int) $reviewMode, $schedConf->getSchedConfId()));
			unset($schedConf);
		}
		return true;
	}

	/**
	 * For 2.1 upgrade: index handling changed away from using the <KEY />
	 * syntax in schema descriptors in cases where AUTONUM columns were not
	 * used, in favour of specifically-named indexes using the <index ...>
	 * syntax. For this, all indexes (including potentially duplicated
	 * indexes from before) on OCS tables should be dropped prior to the new
	 * schema being applied.
	 * @return boolean
	 */
	function dropAllIndexes() {
		$siteDao =& DAORegistry::getDAO('SiteDAO');
		$dict = NewDataDictionary($siteDao->_dataSource);
		$dropIndexSql = array();

		// This is a list of tables that were used in 2.0 (i.e.
		// before the way indexes were used was changed). All indexes
		// from these tables will be dropped.
		$tables = array(
			'versions', 'site', 'site_settings', 'scheduled_tasks',
			'sessions', 'conference_settings', 'sched_conf_settings',
			'plugin_settings', 'roles', 'notification_status', 
			'track_directors',
			'review_stages', 'paper_html_galley_images',
			'email_templates_default_data', 'email_templates_data',
			'paper_search_object_keywords', 'oai_resumption_tokens',
			'group_memberships'
		);

		// Assemble a list of indexes to be dropped
		foreach ($tables as $tableName) {
			$indexes = $dict->MetaIndexes($tableName);
			if (is_array($indexes)) foreach ($indexes as $indexName => $indexData) {
				$dropIndexSql = array_merge($dropIndexSql, $dict->DropIndexSQL($indexName, $tableName));
			}
		}

		// Execute the DROP INDEX statements.
		foreach ($dropIndexSql as $sql) {
			$siteDao->update($sql);
		}

		// Second run: Only return primary indexes. This is necessary
		// so that primary indexes can be dropped by MySQL.
		foreach ($tables as $tableName) {
			$indexes = $dict->MetaIndexes($tableName, true);
			if (!empty($indexes)) switch(Config::getVar('database', 'driver')) {
				case 'mysql':
					$siteDao->update("ALTER TABLE $tableName DROP PRIMARY KEY");
					break;
			}
		}


		return true;
	}

	/**
	 * The supportedLocales setting may be missing for conferences; ensure
	 * that it is properly set.
	 */
	function ensureSupportedLocales() {
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');
		$result =& $conferenceDao->retrieve(
			'SELECT	c.conference_id,
				c.primary_locale
			FROM	conferences c
				LEFT JOIN conference_settings cs ON (cs.conference_id = c.conference_id AND cs.setting_name = ?)
			WHERE	cs.setting_name IS NULL',
			array('supportedLocales')
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$conferenceSettingsDao->updateSetting(
				$row['conference_id'],
				'supportedLocales',
				array($row['primary_locale']),
				'object',
				false
			);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		return true;
	}
	
	/**
	 * For 2.1.2 upgrade: add locale data to program settings
	 * and that were not previously localized.
	 * @return boolean
	 */
	function localizeProgramSettings() {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		$settings = array('program', 'programFile', 'programFileTitle');

		foreach ($settings as $setting) {
			$result =& $schedConfDao->retrieve('SELECT sc.sched_conf_id, c.primary_locale FROM sched_confs sc, conferences c, sched_conf_settings scs WHERE c.conference_id = sc.conference_id AND sc.sched_conf_id = scs.sched_conf_id AND scs.setting_name = ? AND (scs.locale IS NULL OR scs.locale = ?)', array($setting, ''));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$schedConfSettingsDao->update('UPDATE sched_conf_settings SET locale = ? WHERE sched_conf_id = ? AND setting_name = ? AND (locale IS NULL OR locale = ?)', array($row['primary_locale'], $row['sched_conf_id'], $setting, ''));
				$result->MoveNext();
			}
			$result->Close();
			unset($result);
		}

		return true;
	}
}

?>
