<?php

/**
 * @file classes/install/Install.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Install
 * @ingroup install
 * @see Installer, InstallForm
 *
 * @brief Perform system installation.
 *
 * This script will:
 *  - Create the database (optionally), and install the database tables and initial data.
 *  - Update the config file with installation parameters.
 */

// $Id$


// Default installation data
define('INSTALLER_DEFAULT_SITE_TITLE', 'common.openConferenceSystems');
define('INSTALLER_DEFAULT_MIN_PASSWORD_LENGTH', 6);

import('install.PKPInstall');

class Install extends PKPInstall {

	/**
	 * Constructor.
	 * @see install.form.InstallForm for the expected parameters
	 * @param $params array installer parameters
	 * @param $descriptor string descriptor path
	 * @param $isPlugin boolean true iff a plugin is being installed
	 */
	function Install($params, $descriptor = 'install.xml', $isPlugin = false) {
		parent::PKPInstall($descriptor, $params, $isPlugin);
	}

	//
	// Installer actions
	//

	/**
	 * Get the names of the directories to create.
	 * @return array
	 */
	function getCreateDirectories() {
		$directories = parent::getCreateDirectories();
		$directories[] = 'conferences';
		return $directories;
	}

	/**
	 * Create initial required data.
	 * @return boolean
	 */
	function createData() {
		// Add initial site data
		$locale = $this->getParam('locale');
		$siteDao =& DAORegistry::getDAO('SiteDAO', $this->dbconn);
		$site = new Site();
		$site->setRedirect(0);
		$site->setMinPasswordLength(INSTALLER_DEFAULT_MIN_PASSWORD_LENGTH);
		$site->setPrimaryLocale($locale);
		$site->setInstalledLocales($this->installedLocales);
		$site->setSupportedLocales($this->installedLocales);
		if (!$siteDao->insertSite($site)) {
			$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
			return false;
		}

		$siteSettingsDao =& DAORegistry::getDAO('SiteSettingsDAO');
		$siteSettingsDao->updateSetting('title', array($locale => __(INSTALLER_DEFAULT_SITE_TITLE)), null, true);
		$siteSettingsDao->updateSetting('contactName', array($locale => __(INSTALLER_DEFAULT_SITE_TITLE)), null, true);
		$siteSettingsDao->updateSetting('contactEmail', array($locale => $this->getParam('adminEmail')), null, true);

		// Add initial site administrator user
		$userDao =& DAORegistry::getDAO('UserDAO', $this->dbconn);
		$user = new User();
		$user->setUsername($this->getParam('adminUsername'));
		$user->setPassword(Validation::encryptCredentials($this->getParam('adminUsername'), $this->getParam('adminPassword'), $this->getParam('encryption')));
		$user->setFirstName($user->getUsername());
		$user->setLastName('');
		$user->setEmail($this->getParam('adminEmail'));
		if (!$userDao->insertUser($user)) {
			$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
			return false;
		}

		$roleDao =& DAORegistry::getDao('RoleDAO', $this->dbconn);
		$role = new Role();
		$role->setConferenceId(0);
		$role->setUserId($user->getId());
		$role->setRoleId(ROLE_ID_SITE_ADMIN);
		if (!$roleDao->insertRole($role)) {
			$this->setError(INSTALLER_ERROR_DB, $this->dbconn->errorMsg());
			return false;
		}

		// Install email template list and data for each locale
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->installEmailTemplates($emailTemplateDao->getMainEmailTemplatesFilename());
		foreach ($this->installedLocales as $locale) {
			$emailTemplateDao->installEmailTemplateData($emailTemplateDao->getMainEmailTemplateDataFilename($locale));
		}

		// Add initial plugin data to versions table
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		import('site.VersionCheck');
		$categories = PluginRegistry::getCategories();
		foreach ($categories as $category) {
			PluginRegistry::loadCategory($category, true);
			$plugins = PluginRegistry::getPlugins($category);
			foreach ($plugins as $plugin) {
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
