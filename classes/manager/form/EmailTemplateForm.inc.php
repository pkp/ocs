<?php

/**
 * @file EmailTemplateForm.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailTemplateForm
 * @ingroup manager_form
 *
 * @brief Form for creating and modifying email templates.
 */

//$Id$

import('form.Form');

class EmailTemplateForm extends Form {

	/** The key of the email template being edited */
	var $emailKey;

	/** The conference of the email template being edited */
	var $conference;

	/**
	 * Constructor.
	 * @param $emailKey string
	 */
	function EmailTemplateForm($emailKey, $conference) {
		parent::Form('manager/emails/emailTemplateForm.tpl');

		$this->conference =& $conference;
		$this->emailKey = $emailKey;

		// Validation checks for this form
		$this->addCheck(new FormValidatorArray($this, 'subject', 'required', 'manager.emails.form.subjectRequired'));
		$this->addCheck(new FormValidatorArray($this, 'body', 'required', 'manager.emails.form.bodyRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();

		$conferenceId = $this->conference->getConferenceId();
		$schedConfId = 0;

		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = &$emailTemplateDao->getBaseEmailTemplate($this->emailKey, $conferenceId, $schedConfId);
		$templateMgr->assign('canDisable', $emailTemplate?$emailTemplate->getCanDisable():false);
		$templateMgr->assign('supportedLocales', $this->conference->getSupportedLocaleNames());
		$templateMgr->assign('helpTopicId','conference.generalManagement.emails');
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$schedConfId = 0;
		$conferenceId = $this->conference->getConferenceId();

		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');

		// If there's already a scheduled conference-level template, grab it. This will grab
		// the conference-level template if no scheduled conference is specified.
		$emailTemplate = &$emailTemplateDao->getLocaleEmailTemplate($this->emailKey, $conferenceId, $schedConfId, false);

		// If not, initialize with the conference template (if one exists). Note
		// it's necessary to blank the ID field if it exists, since we don't want
		// to overwrite the conference template with a scheduled conference template.
		if(!$emailTemplate && $schedConfId !== 0) {
			$emailTemplate = &$emailTemplateDao->getLocaleEmailTemplate($this->emailKey, $conferenceId, $schedConfId, true);
			if($emailTemplate) {
				$emailTemplate->setEmailId(null);
			}
		}
		$thisLocale = Locale::getLocale();

		if ($emailTemplate) {
			$subject = array();
			$body = array();
			$description = array();
			foreach ($emailTemplate->getLocales() as $locale) {
				$subject[$locale] = $emailTemplate->getSubject($locale);
				$body[$locale] = $emailTemplate->getBody($locale);
				$description[$locale] = $emailTemplate->getDescription($locale);
			}

			if ($emailTemplate != null) {
				$this->_data = array(
					'emailId' => $emailTemplate->getEmailId(),
					'emailKey' => $emailTemplate->getEmailKey(),
					'subject' => $subject,
					'body' => $body,
					'description' => isset($description[$thisLocale])?$description[$thisLocale]:null,
					'enabled' => $emailTemplate->getEnabled()
				);
			}
		} else {
			$this->_data = array('isNewTemplate' => true);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('emailId', 'subject', 'body', 'enabled', 'conferenceId', 'schedConfId', 'emailKey'));
	}

	/**
	 * Save email template.
	 */
	function execute() {
		$schedConfId = 0;
		$conferenceId = $this->conference->getConferenceId();

		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = &$emailTemplateDao->getLocaleEmailTemplate($this->emailKey, $conferenceId, $schedConfId, false);

		if (!$emailTemplate) {
			$emailTemplate = &new LocaleEmailTemplate();
			$emailTemplate->setCustomTemplate(true);
			$emailTemplate->setCanDisable(false);
			$emailTemplate->setEnabled(true);
			$emailTemplate->setEmailKey($this->getData('emailKey'));
		} else {
			$emailTemplate->setEmailId($this->getData('emailId'));
			if ($emailTemplate->getCanDisable()) {
				$emailTemplate->setEnabled($this->getData('enabled'));
			}
		}

		$emailTemplate->setConferenceId($conferenceId);
		$emailTemplate->setSchedConfId($schedConfId);

		$supportedLocales = $this->conference->getSupportedLocaleNames();
		if (!empty($supportedLocales)) {
			foreach ($supportedLocales as $localeKey => $localeName) {
				$emailTemplate->setSubject($localeKey, $this->_data['subject'][$localeKey]);
				$emailTemplate->setBody($localeKey, $this->_data['body'][$localeKey]);
			}
		} else {
			$localeKey = Locale::getLocale();
			$emailTemplate->setSubject($localeKey, $this->_data['subject'][$localeKey]);
			$emailTemplate->setBody($localeKey, $this->_data['body'][$localeKey]);
		}

		if ($emailTemplate->getEmailId() != null) {
			$emailTemplateDao->updateLocaleEmailTemplate($emailTemplate);
		} else {
			$emailTemplateDao->insertLocaleEmailTemplate($emailTemplate);
		}
	}
}

?>
