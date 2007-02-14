<?php

/**
 * SchedConfSetupStep1Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.schedConfSetup
 *
 * Form for Step 1 of scheduled conference setup.
 *
 * $Id$
 */

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep1Form extends SchedConfSetupForm {
	
	function SchedConfSetupStep1Form() {
		parent::SchedConfSetupForm(
			1,
			array(
				'schedConfTitle' => 'string',
				'schedConfOverview' => 'string',
				'schedConfIntroduction' => 'string',
				'location' => 'string',
				'sponsorNote' => 'string',
				'sponsors' => 'object',
				'contributorNote' => 'string',
				'contributors' => 'object'
			)
		);
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'schedConfTitle', 'required', 'schedConfManager.setup.form.schedConfTitleRequired'));

		// TODO: Validation checks for scheduled conference start and end date
	}

	function initData() {
		parent::initData();

		$schedConf = &Request::getSchedConf();
		$this->_data['schedConfTitle'] = $schedConf->getTitle();
	}

	function readInputData() {
		parent::readInputData();
		$this->_data['schedConfTitle'] = Request::getUserVar('schedConfTitle');
	}

	function execute() {
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConf = Request::getSchedConf();

		$schedConf->setTitle($this->_data['schedConfTitle']);
		$schedConfDao->updateSchedConf($schedConf);

		parent::execute();
	}
}

?>
