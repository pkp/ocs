<?php

/**
 * @defgroup manager_form_schedConfSetup
 */
 
/**
 * @file SchedConfSetupStep1Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSetupStep1Form
 * @ingroup manager_form_schedConfSetup
 *
 * @brief Form for Step 1 of scheduled conference setup.
 */

//$Id$

import("manager.form.schedConfSetup.SchedConfSetupForm");

class SchedConfSetupStep1Form extends SchedConfSetupForm {

	function SchedConfSetupStep1Form() {
		parent::SchedConfSetupForm(
			1,
			array(
				'introduction' => 'string',
				'overview' => 'string',
				'locationName' => 'string',
				'locationAddress' => 'string',
				'locationCity' => 'string',
				'locationCountry' => 'string',
				'contactName' => 'string',
				'contactTitle' => 'string',
				'contactAffiliation' => 'string',
				'contactEmail' => 'string',
				'contactPhone' => 'string',
				'contactFax' => 'string',
				'contactMailingAddress' => 'string',
				'supportName' => 'string',
				'supportEmail' => 'string',
				'supportPhone' => 'string',
				'emailSignature' => 'string',
				'envelopeSender' => 'string',
				'sponsorNote' => 'string',
				'sponsors' => 'object',
				'contributorNote' => 'string',
				'contributors' => 'object'
			)
		);

		$this->addCheck(new FormValidator($this, 'contactName', 'required', 'manager.schedConfSetup.details.contactNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'contactEmail', 'required', 'manager.schedConfSetup.details.contactEmailRequired'));
		$this->addCheck(new FormValidator($this, 'supportName', 'required', 'manager.schedConfSetup.details.supportNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'supportEmail', 'required', 'manager.schedConfSetup.details.supportEmailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('introduction', 'contactAffiliation', 'overview', 'emailSignature', 'sponsorNote', 'contributorNote');
	}

	/**
	 * Execute the form, but first:
	 * Make sure we're not saving an empty entry for sponsors. (This would
	 * result in a possibly empty heading for the Sponsors section in About.)
	 */
	function execute() {
		foreach (array('sponsors', 'contributors') as $element) {
			$elementValue = (array) $this->getData($element);
			foreach (array_keys($elementValue) as $key) {
				$values = array_values((array) $elementValue[$key]);
				$isEmpty = true;
				foreach ($values as $value) {
					if (!empty($value)) $isEmpty = false;
				}
				if ($isEmpty) unset($elementValue[$key]);
			}
			$this->setData($element, $elementValue);

		}

		return parent::execute();
	}

	function display() {
		$templateMgr =& TemplateManager::getManager();
		if (Config::getVar('email', 'allow_envelope_sender'))
			$templateMgr->assign('envelopeSenderEnabled', true);

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();
		$templateMgr->assign_by_ref('countries', $countries);

		parent::display();
	}
}

?>
