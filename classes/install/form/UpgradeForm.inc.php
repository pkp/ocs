<?php

/**
 * @file UpgradeForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UpgradeForm
 * @ingroup install_form
 *
 * @brief Form for system upgrades.
 */

//$Id$

import('install.Upgrade');
import('form.Form');

class UpgradeForm extends Form {

	/**
	 * Constructor.
	 */
	function UpgradeForm() {
		parent::Form('install/upgrade.tpl');
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('version', VersionCheck::getCurrentCodeVersion());

		parent::display();
	}

	/**
	 * Perform installation.
	 */
	function execute() {
		$templateMgr =& TemplateManager::getManager();
		$installer = new Upgrade($this->_data);

		// FIXME Use logger?

		// FIXME Mostly common with InstallForm

		if ($installer->execute()) {
			if (!$installer->wroteConfig()) {
				// Display config file contents for manual replacement
				$templateMgr->assign(array('writeConfigFailed' => true, 'configFileContents' => $installer->getConfigContents()));
			}

			$templateMgr->assign('notes', $installer->getNotes());
			$templateMgr->assign_by_ref('newVersion', $installer->getNewVersion());
			$templateMgr->display('install/upgradeComplete.tpl');

		} else {
			switch ($installer->getErrorType()) {
				case INSTALLER_ERROR_DB:
					$this->dbInstallError($installer->getErrorMsg());
					break;
				default:
					$this->installError($installer->getErrorMsg());
					break;
			}
		}

		$installer->destroy();
	}

	// FIXME Common with InstallForm

	/**
	 * Fail with a generic installation error.
	 * @param $errorMsg string
	 */
	function installError($errorMsg) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array('isInstallError' => true, 'errorMsg' => $errorMsg));
		$this->display();
	}

	/**
	 * Fail with a database installation error.
	 * @param $errorMsg string
	 */
	function dbInstallError($errorMsg) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign(array('isInstallError' => true, 'dbErrorMsg' => empty($errorMsg) ? __('common.error.databaseErrorUnknown') : $errorMsg));
		$this->display();
	}

}

?>
