<?php

/**
 * @file EmailTemplateForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
	function EmailTemplateForm($emailKey, &$conference) {
		parent::Form('manager/emails/emailTemplateForm.tpl');

		$this->conference =& $conference;
		$this->emailKey = $emailKey;

		// Validation checks for this form
		$this->addCheck(new FormValidatorArray($this, 'subject', 'required', 'manager.emails.form.subjectRequired'));
		$this->addCheck(new FormValidatorArray($this, 'body', 'required', 'manager.emails.form.bodyRequired'));
		$this->addCheck(new FormValidator($this, 'emailKey', 'required', 'manager.emails.form.emailKeyRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$conferenceId = $this->conference->getId();

		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate =& $emailTemplateDao->getBaseEmailTemplate($this->emailKey, $conferenceId);
		$templateMgr->assign('canDisable', $emailTemplate?$emailTemplate->getCanDisable():false);
		$templateMgr->assign('supportedLocales', $this->conference->getSupportedLocaleNames());
		$templateMgr->assign('helpTopicId','conference.generalManagement.emails');
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$conferenceId = $this->conference->getId();
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');

		$emailTemplate =& $emailTemplateDao->getLocaleEmailTemplate($this->emailKey, $conferenceId);

		$thisLocale = AppLocale::getLocale();

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
		$this->readUserVars(array('emailId', 'subject', 'body', 'enabled', 'conferenceId', 'emailKey'));

		$conferenceId = $this->conference->getId();
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate =& $emailTemplateDao->getLocaleEmailTemplate($this->emailKey, $conferenceId);
		if (!$emailTemplate) $this->_data['isNewTemplate'] = true;
	}

	/**
	 * Save email template.
	 */
	function execute() {
		$conferenceId = $this->conference->getId();

		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate =& $emailTemplateDao->getLocaleEmailTemplate($this->emailKey, $conferenceId);

		if (!$emailTemplate) {
			$emailTemplate = new LocaleEmailTemplate();
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

		$emailTemplate->setAssocType(ASSOC_TYPE_CONFERENCE);
		$emailTemplate->setAssocId($conferenceId);

		$supportedLocales = $this->conference->getSupportedLocaleNames();
		if (!empty($supportedLocales)) {
			foreach ($supportedLocales as $localeKey => $localeName) {
				$emailTemplate->setSubject($localeKey, $this->_data['subject'][$localeKey]);
				$emailTemplate->setBody($localeKey, $this->_data['body'][$localeKey]);
			}
		} else {
			$localeKey = AppLocale::getLocale();
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
