<?php

/**
 * @file EmailHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class EmailHandler
 *
 * Handle requests for email management functions. 
 *
 * $Id$
 */

class EmailHandler extends ManagerHandler {

	/**
	 * Display a list of the emails within the current conference.
	 */
	function emails() {
		list($conference, $schedConf) = EmailHandler::validate();
		parent::setupTemplate(true);

		$rangeInfo = Handler::getRangeInfo('emails');

		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplates = &$emailTemplateDao->getEmailTemplates(Locale::getLocale(),
			$conference->getConferenceId(),
			$schedConf ? $schedConf->getSchedConfId() : 0);
		if ($rangeInfo && $rangeInfo->isValid()) {
			$emailTemplates =& new ArrayItemIterator($emailTemplates, $rangeInfo->getPage(), $rangeInfo->getCount());
		} else {
			$emailTemplates =& new ArrayItemIterator($emailTemplates);
		}

		$templateMgr = &TemplateManager::getManager();

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
		$templateMgr->assign('helpTopicId','conference.managementPages.emails');
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
		list($conference, $schedConf) = EmailHandler::validate();
		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array(Request::url(null, null, null, 'emails'), 'manager.emails'));

		$emailKey = !isset($args) || empty($args) ? null : $args[0];

		import('manager.form.EmailTemplateForm');

		$emailTemplateForm = &new EmailTemplateForm($emailKey, $conference, $schedConf);
		$emailTemplateForm->initData();
		$emailTemplateForm->display();
	}

	/**
	 * Save changes to an email.
	 */
	function updateEmail() {
		list($conference, $schedConf) = EmailHandler::validate();

		import('manager.form.EmailTemplateForm');

		$emailKey = Request::getUserVar('emailKey');

		$emailTemplateForm = &new EmailTemplateForm($emailKey, $conference, $schedConf);
		$emailTemplateForm->readInputData();

		if ($emailTemplateForm->validate()) {
			$emailTemplateForm->execute();
			Request::redirect(null, null, null, 'emails');

		} else {
			parent::setupTemplate(true);
			$emailTemplateForm->display();
		}
	}

	/**
	 * Delete a custom email.
	 * @param $args array first parameter is the key of the email to delete
	 */
	function deleteCustomEmail($args) {
		list($conference, $schedConf) = EmailHandler::validate();
		$emailKey = array_shift($args);
		$schedConfId = ($schedConf ? $schedConf->getSchedConfId() : 0);
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		if ($emailTemplateDao->customTemplateExistsByKey($emailKey, $conference->getConferenceId(), $schedConfId)) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $conference->getConferenceId(), $schedConfId);
		}

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * Reset an email to default.
	 * @param $args array first parameter is the key of the email to reset
	 */
	function resetEmail($args) {
		list($conference, $schedConf) = EmailHandler::validate();

		$schedConfId = ($schedConf ? $schedConf->getSchedConfId() : 0);

		if (isset($args) && !empty($args)) {
			$conference = &Request::getConference();

			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplateDao->deleteEmailTemplateByKey($args[0], $conference->getConferenceId(), $schedConfId);
		}

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * resets all email templates associated with the conference.
	 */
	function resetAllEmails() {
		list($conference, $schedConf) = EmailHandler::validate();

		$conference = &Request::getConference();
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');

		if(Request::isConferenceManager()) {
			$emailTemplateDao->deleteEmailTemplatesByConference($conference->getConferenceId());
		} else {
			$emailTemplateDao->deleteEmailTemplatesBySchedConf($schedConf->getSchedConfId());
		}

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * disables an email template.
	 * @param $args array first parameter is the key of the email to disable
	 */
	function disableEmail($args) {
		list($conference, $schedConf) = EmailHandler::validate();

		$schedConfId = ($schedConf ? $schedConf->getSchedConfId() : 0);

		if (isset($args) && !empty($args)) {
			$conference = &Request::getConference();

			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $conference->getConferenceId(), $schedConfId);

			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(0);

					if ($emailTemplate->getConferenceId() == null) {
						$emailTemplate->setConferenceId($conference->getConferenceId());
					}

					if($emailTemplate->getSchedConfId() == null && $schedConf) {
						$emailTemplate->setSchedConfId($schedConfId);
					} else {
						$emailTemplate->setSchedConfId(0);
					}

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
	 * enables an email template.
	 * @param $args array first parameter is the key of the email to enable
	 */
	function enableEmail($args) {
		list($conference, $schedConf) = EmailHandler::validate();

		$schedConfId = ($schedConf ? $schedConf->getSchedConfId() : 0);

		if (isset($args) && !empty($args)) {
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $conference->getConferenceId(), $schedConfId);

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
		if(Validation::isConferenceManager()) {
			list($conference, $schedConf) = parent::validate(false);
		} else {
			list($conference, $schedConf) = parent::validate(true);
		}

		// If the user is a Conference Manager, but has specified a scheduled conference,
		// redirect so no scheduled conference is present (otherwise they would end up managing
		// scheduled conference e-mails.)
		if($schedConf && !Validation::isConferenceManager()) {
			Request::redirect(null, 'index', Request::getRequestedPage(), Request::getRequestedOp());
		}

		return array($conference, $schedConf);
	}
}

?>
