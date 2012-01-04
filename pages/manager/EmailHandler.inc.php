<?php

/**
 * @file EmailHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for email management functions. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class EmailHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function EmailHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the emails within the current conference.
	 */
	function emails() {
		$this->validate();
		$this->setupTemplate(true);
		$conference =& Request::getConference();

		$rangeInfo = Handler::getRangeInfo('emails', array());

		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplatesArray =& $emailTemplateDao->getEmailTemplates(
			AppLocale::getLocale(),
			$conference->getId()
		);

		import('core.ArrayItemIterator');
		if ($rangeInfo && $rangeInfo->isValid()) {
			while (true) {
				$emailTemplates = new ArrayItemIterator($emailTemplatesArray, $rangeInfo->getPage(), $rangeInfo->getCount());
				if ($emailTemplates->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $emailTemplates->getLastPageRangeInfo();
				unset($emailTemplates);
			}
		} else {
			$emailTemplates = new ArrayItemIterator($emailTemplatesArray);
		}

		$templateMgr =& TemplateManager::getManager();

		// The bread crumbs depends on whether we're doing scheduled conference or conference
		// management. FIXME: this is going to be a common situation, and this isn't
		// an elegant way of testing for it.
		if(Request::getRequestedPage() === 'manager') {
			$templateMgr->assign('pageHierarchy', array(
				array(Request::url(null, 'index', 'manager'), 'manager.conferenceSiteManagement')
			));
		} else {
			$templateMgr->assign('pageHierarchy', array(
				array(Request::url(null, null, 'manager'), 'manager.schedConfManagement')
			));
		}

		$templateMgr->assign_by_ref('emailTemplates', $emailTemplates);
		$templateMgr->assign('helpTopicId','conference.generalManagement.emails');
		$templateMgr->display('manager/emails/emails.tpl');
	}

	function createEmail($args = array()) {
		EmailHandler::editEmail($args);
	}

	/**
	 * Display form to create/edit an email.
	 * @param $args array optional, if set the first parameter is the key of the email template to edit
	 */
	function editEmail($args = array()) {
		$this->validate();
		$this->setupTemplate(true);
		$conference =& Request::getConference();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array(Request::url(null, null, null, 'emails'), 'manager.emails'));

		$emailKey = !isset($args) || empty($args) ? null : $args[0];

		import('manager.form.EmailTemplateForm');

		$emailTemplateForm = new EmailTemplateForm($emailKey, $conference);
		$emailTemplateForm->initData();
		$emailTemplateForm->display();
	}

	/**
	 * Save changes to an email.
	 */
	function updateEmail() {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.EmailTemplateForm');

		$emailKey = Request::getUserVar('emailKey');

		$conference =& Request::getConference();
		$emailTemplateForm = new EmailTemplateForm($emailKey, $conference);
		$emailTemplateForm->readInputData();

		if ($emailTemplateForm->validate()) {
			$emailTemplateForm->execute();
			Request::redirect(null, null, null, 'emails');
		} else {
			$emailTemplateForm->display();
		}
	}

	/**
	 * Delete a custom email.
	 * @param $args array first parameter is the key of the email to delete
	 */
	function deleteCustomEmail($args) {
		$this->validate();
		$conference =& Request::getConference();

		$emailKey = array_shift($args);
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		if ($emailTemplateDao->customTemplateExistsByKey($emailKey, $conference->getId())) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $conference->getId());
		}

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * Reset an email to default.
	 * @param $args array first parameter is the key of the email to reset
	 */
	function resetEmail($args) {
		$this->validate();
		if (isset($args) && !empty($args)) {
			$conference =& Request::getConference();

			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplateDao->deleteEmailTemplateByKey($args[0], $conference->getId());
		}

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * resets all email templates associated with the conference.
	 */
	function resetAllEmails() {
		$this->validate();

		$conference =& Request::getConference();
		
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByConference($conference->getId());

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * disables an email template.
	 * @param $args array first parameter is the key of the email to disable
	 */
	function disableEmail($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$conference =& Request::getConference();

			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $conference->getId());

			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(0);
					if ($emailTemplate->getEmailId() != null) {
						$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
					} else {
						$emailTemplate->setAssocType(ASSOC_TYPE_CONFERENCE);
						$emailTemplate->setAssocId($conference->getId());
						$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
					}
				}
			}
		}

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * enables an email template.
	 * @param $args array first parameter is the key of the email to enable
	 */
	function enableEmail($args) {
		$this->validate();
		$conference =& Request::getConference();

		if (isset($args) && !empty($args)) {
			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $conference->getId());

			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(1);

					if ($emailTemplate->getEmailId() != null) {
						$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
					} else {
						$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
					}
				}
			}
		}

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * Validate that user has permissions to manage e-mail templates.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		
		$schedConf =& Request::getSchedConf();

		// If the user is a Conference Manager, but has specified a scheduled conference,
		// redirect so no scheduled conference is present (otherwise they would end up managing
		// scheduled conference e-mails.)
		if($schedConf && !Validation::isConferenceManager()) {
			Request::redirect(null, 'index', Request::getRequestedPage(), Request::getRequestedOp());
		}

		return true;
	}
}

?>
