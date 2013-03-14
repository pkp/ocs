<?php

/**
 * @file StaticPagesSettingsForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesSettingsForm
 *
 * Form for conference managers to modify Static Page content and title
 *
 */

import('lib.pkp.classes.form.Form');

class StaticPagesSettingsForm extends Form {
	/** @var $conferenceId int */
	var $conferenceId;

	/** @var $plugin object */
	var $plugin;

	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $conferenceId int
	 */
	function StaticPagesSettingsForm(&$plugin, $conferenceId) {

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->conferenceId = $conferenceId;
		$this->plugin =& $plugin;

		$this->addCheck(new FormValidatorPost($this));
	}


	/**
	 * Initialize form data from current group group.
	 * @param $request
	 */
	function initData($request) {
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;

		$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');

		$rangeInfo =& Handler::getRangeInfo($request, 'staticPages');
		$staticPages = $staticPagesDao->getStaticPagesByConferenceId($conferenceId);
		$this->setData('staticPages', $staticPages);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('pages'));
	}

	/**
	 * Save settings/changes
	 */
	function execute() {
		$plugin =& $this->plugin;
		$conferenceId = $this->conferenceId;
	}

}
?>
