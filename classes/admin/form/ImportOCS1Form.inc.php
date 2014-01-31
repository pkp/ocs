<?php

/**
 * @file ImportOCS1Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ImportOCS1Form
 * @ingroup admin_form
 *
 * @brief Form for site administrator to migrate data from an OCS 1.x system.
 */

//$Id$

import('site.ImportOCS1');
import('form.Form');

class ImportOCS1Form extends Form {

	/** @var $importer ImportOCS1 */
	var $importer;

	/**
	 * Constructor.
	 * @param $conferenceId omit for a new conference
	 */
	function ImportOCS1Form() {
		parent::Form('admin/importOCS1.tpl');
		$this->importer = new ImportOCS1();

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'conferencePath', 'required', 'admin.conferences.form.pathRequired'));
		$this->addCheck(new FormValidator($this, 'importPath', 'required', 'admin.conferences.form.importPathRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('importError', $this->importer->error());
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('conferencePath', 'importPath', 'options'));
	}

	/**
	 * Import content.
	 * @return boolean/int false or conference ID
	 */
	function execute() {
		$options = $this->getData('options');
		$conferenceId = $this->importer->import($this->getData('conferencePath'), $this->getData('importPath'), is_array($options) ? $options : array());
		return $conferenceId;
	}

	function getConflicts() {
		return $this->importer->getConflicts();
	}

	function getErrors() {
		return $this->importer->getErrors();
	}
}

?>
