<?php

/**
 * @file Upgrade.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
	 * @param $params array installer parameters
	 * @param $installFile string descriptor path
	 * @param $isPlugin boolean true iff a plugin is being installed		 */
	function Upgrade($params, $installFile = 'upgrade.xml', $isPlugin = false) {
		parent::Installer($installFile, $params, $isPlugin);
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
			$conferenceIds[] = $conference->getId();
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
				$defaultText = __('common.default');
				$building = new Building();
				$building->setSchedConfId($schedConfId);
				$building->setName($defaultText, $locale);
				$building->setAbbrev($defaultText, $locale);
				$building->setDescription($defaultText, $locale);
				$buildingId = $buildingDao->insertBuilding($building);
				unset($building);
				$rooms = array();
			}

			if (!isset($rooms[$location])) {
				$room = new Room();
				$room->setBuildingId($buildingId);
				$room->setName($location, $locale);
				$room->setAbbrev($location, $locale);
				$room->setDescription($location, $locale);
				$roomId = $roomDao->insertRoom($room);

				$rooms[$location] =& $room;
				unset($room);
			} else {
				$room =& $rooms[$location];
				$roomId = $room->getId();
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
			'metaCitations' => 'metaCitations',
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
			$papers =& $paperDao->getPapersBySchedConfId($schedConf->getId());
			$reviewMode = $schedConf->getSetting('reviewMode');
			$paperDao->update('UPDATE papers SET review_mode = ? WHERE sched_conf_id = ?', array((int) $reviewMode, $schedConf->getId()));
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
			'plugin_settings', 'roles',
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
	 * For 2.3 update.  Go through all user email templates and change {$presenter to {$author
	 */
	function changePresenterInUserEmailTemplates() {
		$emailTemplateDAO =& DAORegistry::getDAO('EmailTemplateDAO');

		// Reset email templates
		$result =& $emailTemplateDAO->retrieve('SELECT email_key, locale, body, subject FROM email_templates_data');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			$newBody = str_replace('{$presenterName}', '{$authorName}', $row['body']);
			$newBody = str_replace('{$presenterUsername}', '{$authorUsername}', $newBody);
			$newSubject = str_replace('{$presenterName}', '{$authorName}', $row['subject']);
			$newSubject = str_replace('{$presenterUsername}', '{$authorUsername}', $newSubject);

			$emailTemplateDAO->update('UPDATE email_templates_data SET body = ?, subject = ? WHERE email_key = ? AND locale = ?', array($newBody, $newSubject, $row['email_key'], $row['locale']));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		
		// Reset default email templates
		$result =& $emailTemplateDAO->retrieve('SELECT email_key, locale, body, subject FROM email_templates_default_data');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			$newBody = str_replace('{$presenterName}', '{$authorName}', $row['body']);
			$newBody = str_replace('{$presenterUsername}', '{$authorUsername}', $newBody);
			$newSubject = str_replace('{$presenterName}', '{$authorName}', $row['subject']);
			$newSubject = str_replace('{$presenterUsername}', '{$authorUsername}', $newSubject);

			$emailTemplateDAO->update('UPDATE email_templates_default_data SET body = ?, subject = ? WHERE email_key = ? AND locale = ?', array($newBody, $newSubject, $row['email_key'], $row['locale']));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);


		return true;
	}

	/**
	 * For upgrade to 2.3: remove allowIndividualSubmissions and
	 * allowPanelSubmissions settings in favour of controlled vocabulary
	 * structure.
	 */
	function upgradePaperType() {
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$paperTypeDao =& DAORegistry::getDAO('PaperTypeDAO');
		$paperTypeEntryDao =& DAORegistry::getDAO('PaperTypeEntryDAO');

		$conferences =& $conferenceDao->getConferences();

		while ($conference =& $conferences->next()) {
			$locales = array_keys($conference->getSupportedLocaleNames());
			$locales[] = $conference->getPrimaryLocale();
			$locales = array_unique($locales);

			foreach ($locales as $locale) AppLocale::requireComponents(array(LOCALE_COMPONENT_OCS_DEFAULT), $locale);

			$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conference->getId());
			while ($schedConf =& $schedConfs->next()) {
				$allowIndividualSubmissions = $schedConf->getSetting('allowIndividualSubmissions');
				$allowPanelSubmissions = $schedConf->getSetting('allowPanelSubmissions');
				if ($allowIndividualSubmissions || $allowPanelSubmissions) {
					$paperType =& $paperTypeDao->build($schedConf->getId());
				}
				if ($allowIndividualSubmissions) {
					$paperTypeEntry =& $paperTypeEntryDao->newDataObject();
					foreach ($locales as $locale) {
						$paperTypeEntry->setName(__('default.paperType.individual.name', array(), $locale), $locale);
						$paperTypeEntry->setDescription(__('default.paperType.individual.description', array(), $locale), $locale);
					}
					$paperTypeEntry->setControlledVocabId($paperType->getId());
					$paperTypeEntryDao->insertObject($paperTypeEntry);
					$schedConfDao->update(
						'INSERT INTO paper_settings (setting_name, setting_type, paper_id, locale, setting_value) SELECT  ?, ?, paper_id, ?, ? FROM papers WHERE sched_conf_id = ? AND paper_type = ?',
						array(
							'sessionType',
							'int',
							'',
							(int) $paperTypeEntry->getId(),
							(int) $schedConf->getId(),
							0 // SUBMISSION_TYPE_SINGLE (since removed)
						)
					);
					unset($paperTypeEntry);
				}
				if ($allowPanelSubmissions) {
					$paperTypeEntry =& $paperTypeEntryDao->newDataObject();
					foreach ($locales as $locale) {
						$paperTypeEntry->setName(__('default.paperType.panel.name', array(), $locale), $locale);
						$paperTypeEntry->setDescription(__('default.paperType.panel.description', array(), $locale), $locale);
					}
					$paperTypeEntry->setControlledVocabId($paperType->getId());
					$paperTypeEntryDao->insertObject($paperTypeEntry);
					$schedConfDao->update(
						'INSERT INTO paper_settings (setting_name, setting_type, paper_id, locale, setting_value) SELECT  ?, ?, paper_id, ?, ? FROM papers WHERE sched_conf_id = ? AND paper_type = ?',
						array(
							'sessionType',
							'int',
							'',
							(int) $paperTypeEntry->getId(),
							(int) $schedConf->getId(),
							1 // SUBMISSION_TYPE_PANEL (since removed)
						)
					);
					unset($paperTypeEntry);
				}
				unset($schedConf, $paperType);
			}
			unset($schedConfs, $conference);
		}

		return true;
	}

	/**
	 * For 2.1.2 upgrade: add locale data to program settings
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

	/**
	 * For 2.3 upgrade: update default review deadline settings to allow absolute due dates
	 * @return boolean
	 */
	function updateReviewDeadlineSettings() {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');


		$result =& $schedConfDao->retrieve('SELECT scs.sched_conf_id FROM sched_conf_settings scs WHERE scs.setting_name = ?', array('numWeeksPerReview'));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$schedConfSettingsDao->update('UPDATE sched_conf_settings SET setting_name = ? WHERE sched_conf_id = ? AND setting_name = ?',
							array('numWeeksPerReviewRelative', $row['sched_conf_id'], 'numWeeksPerReview'));
			$schedConfDao->update(
				'INSERT INTO sched_conf_settings (sched_conf_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?, ?)',
				array(
					$row['sched_conf_id'],
					'',
					'reviewDeadlineType',
					REVIEW_DEADLINE_TYPE_RELATIVE,
					'int',
				)
			);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return true;
	}
	
	/**
	 * For 2.3 upgrade: Add clean titles for every article title so sorting by title ignores punctuation.
	 * @return boolean
	 */
	function cleanTitles() {
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$punctuation = array ("\"", "\'", ",", ".", "!", "?", "-", "$", "(", ")");

		$result =& $paperDao->retrieve('SELECT paper_id, locale, setting_value FROM paper_settings WHERE setting_name = ?', "title");
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$cleanTitle = str_replace($punctuation, "", $row['setting_value']);
			$paperDao->update('INSERT INTO paper_settings (paper_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?, ?)', array((int) $row['paper_id'], $row['locale'], "cleanTitle", $cleanTitle, "string"));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		
		
		return true;
	}

	/**
	 * For 2.3 upgrade: Move image alts for Conference Setup Step 2/3 from within the image
	 * settings into their own settings. (Improves usability of setup forms and simplifies
	 * the code considerably.)
	 * @return boolean
	 */
	function cleanImageAlts() {
		$imageSettings = array(
			'homeHeaderTitleImage' => 'homeHeaderTitleImageAltText',
			'homeHeaderLogoImage' => 'homeHeaderLogoImageAltText',
			'homepageImage' => 'homepageImageAltText',
			'pageHeaderTitleImage' => 'pageHeaderTitleImageAltText',
			'pageHeaderLogoImage' => 'pageHeaderLogoImageAltText'
		);
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conferences =& $conferenceDao->getConferences();
		while ($conference =& $conferences->next()) {
			foreach ($imageSettings as $imageSettingName => $newSettingName) {
				$imageSetting = $conference->getSetting($imageSettingName);
				$newSetting = array();
				if ($imageSetting) foreach ($imageSetting as $locale => $setting) {
					if (isset($setting['altText'])) $newSetting[$locale] = $setting['altText'];
				}
				if (!empty($newSetting)) {
					$conference->updateSetting($newSettingName, $newSetting, 'string', true);
				}
			}
			unset($conference);
		}
		return true;
	}
	
	/**
	 * For 2.3 upgrade:  Add initial plugin data to versions table
	 * @return boolean
	 */
	function addPluginVersions() {
		$versionDao =& DAORegistry::getDAO('VersionDAO'); 
		import('site.VersionCheck');
		$categories = PluginRegistry::getCategories();
		foreach ($categories as $category) {
			PluginRegistry::loadCategory($category, true);
			$plugins = PluginRegistry::getPlugins($category);
			if (is_array($plugins)) foreach ($plugins as $plugin) {
				$versionFile = $plugin->getPluginPath() . '/version.xml';
				
				if (FileManager::fileExists($versionFile)) {
					$versionInfo =& VersionCheck::parseVersionXML($versionFile);
					$pluginVersion = $versionInfo['version'];		
					$pluginVersion->setCurrent(1);
					$versionDao->insertVersion($pluginVersion);
				}  else {
					$pluginVersion = new Version();
					$pluginVersion->setMajor(1);
					$pluginVersion->setMinor(0);
					$pluginVersion->setRevision(0);
					$pluginVersion->setBuild(0);
					$pluginVersion->setDateInstalled(Core::getCurrentDate());
					$pluginVersion->setCurrent(1);
					$pluginVersion->setProductType('plugins.' . $category);
					$pluginVersion->setProduct(basename($plugin->getPluginPath()));
					$versionDao->insertVersion($pluginVersion);
				}
			}
		}
		
		return true;
	}
}

?>
