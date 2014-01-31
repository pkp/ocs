<?php

/**
 * @file VersionForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VersionForm
 * @ingroup rt_ocs_form
 *
 * @brief Form to change metadata information for an RT version.
 */

//$Id$

import('form.Form');

class VersionForm extends Form {

	/** @var int the ID of the version */
	var $versionId;

	/** @var int the ID of the conference */
	var $conferenceId;

	/** @var Version current version */
	var $version;

	/**
	 * Constructor.
	 */
	function VersionForm($versionId, $conferenceId) {
		parent::Form('rtadmin/version.tpl');

		$this->addCheck(new FormValidatorPost($this));

		$this->conferenceId = $conferenceId;

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$this->version =& $rtDao->getVersion($versionId, $conferenceId);

		if (isset($this->version)) {
			$this->versionId = $versionId;
		}
	}

	/**
	 * Initialize form data from current version.
	 */
	function initData() {
		if (isset($this->version)) {
			$version =& $this->version;
			$this->_data = array(
				'key' => $version->getKey(),
				'title' => $version->getTitle(),
				'locale' => $version->getLocale(),
				'description' => $version->getDescription()
			);
		} else {
			$this->_data = array();
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();

		if (isset($this->version)) {
			$templateMgr->assign_by_ref('version', $this->version);
			$templateMgr->assign('versionId', $this->versionId);
		}

		$templateMgr->assign('helpTopicId', 'conference.generalManagement.readingTools.versions');
		parent::display();
	}


	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'key',
				'title',
				'locale',
				'description'
			)
		);
	}

	/**
	 * Save changes to version.
	 * @return int the version ID
	 */
	function execute() {
		$rtDao =& DAORegistry::getDAO('RTDAO');

		$version = $this->version;
		if (!isset($version)) {
			$version = new RTVersion();
		}

		$version->setTitle($this->getData('title'));
		$version->setKey($this->getData('key'));
		$version->setLocale($this->getData('locale'));
		$version->setDescription($this->getData('description'));

		if (isset($this->version)) {
			$rtDao->updateVersion($this->conferenceId, $version);
		} else {
			$rtDao->insertVersion($this->conferenceId, $version);
			$this->versionId = $version->getVersionId();
		}

		return $this->versionId;
	}

}

?>
