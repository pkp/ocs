<?php

/**
 * EmailHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.eventDirector
 *
 * Handle requests for email management functions. 
 *
 * $Id$
 */

class EmailHandler extends EventDirectorHandler {

	/**
	 * Display a list of the emails within the current conference.
	 */
	function emails() {
		list($conference, $event) = EmailHandler::validate();
		parent::setupTemplate(true);

		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplates = &$emailTemplateDao->getEmailTemplates(Locale::getLocale(),
			$conference->getConferenceId(),
			$event ? $event->getEventId() : 0);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, null), 'director.conferenceManagement')));
		$templateMgr->assign_by_ref('emailTemplates', $emailTemplates);
		$templateMgr->assign('helpTopicId','conference.managementPages.emails');
		$templateMgr->display('eventDirector/emails/emails.tpl');
	}

	function createEmail($args = array()) {
		EmailHandler::editEmail($args);
	}

	/**
	 * Display form to create/edit an email.
	 * @param $args array optional, if set the first parameter is the key of the email template to edit
	 */
	function editEmail($args = array()) {
		list($conference, $event) = EmailHandler::validate();
		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array(Request::url(null, null, null, 'emails'), 'director.emails'));
		
		$emailKey = !isset($args) || empty($args) ? null : $args[0];

		import('eventDirector.form.EmailTemplateForm');

		$emailTemplateForm = &new EmailTemplateForm($emailKey, $conference, $event);
		$emailTemplateForm->initData();
		$emailTemplateForm->display();
	}
	
	/**
	 * Save changes to an email.
	 */
	function updateEmail() {
		list($conference, $event) = EmailHandler::validate();
		
		import('eventDirector.form.EmailTemplateForm');
		
		$emailKey = Request::getUserVar('emailKey');

		$emailTemplateForm = &new EmailTemplateForm($emailKey, $conference, $event);
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
		list($conference, $event) = EmailHandler::validate();
		$emailKey = array_shift($args);
		$eventId = ($event ? $event->getEventId() : 0);
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		if ($emailTemplateDao->customTemplateExistsByKey($emailKey, $conference->getConferenceId(), $eventId)) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $conference->getConferenceId(), $eventId);
		}

		Request::redirect(null, null, null, 'emails');
	}

	/**
	 * Reset an email to default.
	 * @param $args array first parameter is the key of the email to reset
	 */
	function resetEmail($args) {
		list($conference, $event) = EmailHandler::validate();

		$eventId = ($event ? $event->getEventId() : 0);
		
		if (isset($args) && !empty($args)) {
			$conference = &Request::getConference();
		
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplateDao->deleteEmailTemplateByKey($args[0], $conference->getConferenceId(), $eventId);
		}
		
		Request::redirect(null, null, null, 'emails');
	}
	
	/**
	 * resets all email templates associated with the conference.
	 */
	function resetAllEmails() {
		list($conference, $event) = EmailHandler::validate();
		
		$conference = &Request::getConference();
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		
		if(Request::isConferenceDirector()) {
			$emailTemplateDao->deleteEmailTemplatesByConference($conference->getConferenceId());
		} else {
			$emailTemplateDao->deleteEmailTemplatesByEvent($event->getEventId());
		}
		
		Request::redirect(null, null, null, 'emails');
	}
	
	/**
	 * disables an email template.
	 * @param $args array first parameter is the key of the email to disable
	 */
	function disableEmail($args) {
		list($conference, $event) = EmailHandler::validate();

		$eventId = ($event ? $event->getEventId() : 0);
				
		if (isset($args) && !empty($args)) {
			$conference = &Request::getConference();
		
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $conference->getConferenceId(), $eventId);
			
			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(0);
					
					if ($emailTemplate->getConferenceId() == null) {
						$emailTemplate->setConferenceId($conference->getConferenceId());
					}
					
					if($emailTemplate->getEventId() == null && $event) {
						$emailTemplate->setEventId($eventId);
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
		list($conference, $event) = EmailHandler::validate();
		
		$eventId = ($event ? $event->getEventId() : 0);

		if (isset($args) && !empty($args)) {
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $conference->getConferenceId(), $eventId);
			
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
		if(Validation::isConferenceDirector()) {
			list($conference, $event) = parent::validate(false);
		} else {
			list($conference, $event) = parent::validate(true);
		}
		
		// If the user is a Conference Director, but has specified an Event,
		// redirect so no event is present (otherwise they would end up managing
		// event e-mails.)
		if($event && !Validation::isEventDirector()) {
			Request::redirect(null, 'index', Request::getRequestedPage(), Request::getRequestedOp());
		}
		
		return array($conference, $event);
	}
}

?>
