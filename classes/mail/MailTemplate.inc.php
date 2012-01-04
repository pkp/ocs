<?php

/**
 * @file MailTemplate.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MailTemplate
 * @ingroup plugins_generic_translator
 *
 * @brief Subclass of PKPMailTemplate for mailing a template email.
 */


import('mail.PKPMailTemplate');

class MailTemplate extends PKPMailTemplate {
	/** @var $conference object */
	var $conference;

	/**
	 * Constructor.
	 * @param $emailKey string unique identifier for the template
	 * @param $locale string locale of the template
	 * @param $enableAttachments boolean optional Whether or not to enable paper attachments in the template
	 * @param $conference object optional The conference this message relates to
	 * @param $schedConf object optional The scheduled conference this message relates to
	 * @param $includeSignature boolean optional
	 * @param $ignorePostedData boolean optional
	 */
	function MailTemplate($emailKey = null, $locale = null, $enableAttachments = null, $conference = null, $schedConf = null, $includeSignature = true, $ignorePostedData = false) {
		parent::PKPMailTemplate($emailKey, $locale, $enableAttachments, $includeSignature);

		// If a conference wasn't specified, use the current request.
		if ($conference === null) $conference =& Request::getConference();
		if ($schedConf == null) $schedConf =& Request::getSchedConf();

		if (isset($this->emailKey)) {
			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate =& $emailTemplateDao->getEmailTemplate($this->emailKey, $this->locale, $conference == null ? 0 : $conference->getId());
		}

		$userSig = '';
		$user =& Request::getUser();
		if ($user && $includeSignature) {
			$userSig = $user->getLocalizedSignature();
			if (!empty($userSig)) $userSig = "\n" . $userSig;
		}

		if (isset($emailTemplate) && ($ignorePostedData || (Request::getUserVar('subject')==null && Request::getUserVar('body')==null))) {
			$this->setSubject($emailTemplate->getSubject());
			$this->setBody($emailTemplate->getBody() . $userSig);
			$this->enabled = $emailTemplate->getEnabled();

			if (Request::getUserVar('usePostedAddresses')) {
				$to = Request::getUserVar('to');
				if (is_array($to)) {
					$this->setRecipients($this->processAddresses ($this->getRecipients(), $to));
				}
				$cc = Request::getUserVar('cc');
				if (is_array($cc)) {
					$this->setCcs($this->processAddresses ($this->getCcs(), $cc));
				}
				$bcc = Request::getUserVar('bcc');
				if (is_array($bcc)) {
					$this->setBccs($this->processAddresses ($this->getBccs(), $bcc));
				}
			}
		} else {
			$this->setSubject(Request::getUserVar('subject'));
			$body = Request::getUserVar('body');
			if (empty($body)) $this->setBody($userSig);
			else $this->setBody($body);
			$this->skip = (($tmp = Request::getUserVar('send')) && is_array($tmp) && isset($tmp['skip']));
			$this->enabled = true;

			if (is_array($toEmails = Request::getUserVar('to'))) {
				$this->setRecipients($this->processAddresses ($this->getRecipients(), $toEmails));
			}
			if (is_array($ccEmails = Request::getUserVar('cc'))) {
				$this->setCcs($this->processAddresses ($this->getCcs(), $ccEmails));
			}
			if (is_array($bccEmails = Request::getUserVar('bcc'))) {
				$this->setBccs($this->processAddresses ($this->getBccs(), $bccEmails));
			}
		}

		// Default "From" to user if available, otherwise site/conference principal contact
		$user =& Request::getUser();
		if ($user) {
			$this->setFrom($user->getEmail(), $user->getFullName());
		} elseif ($schedConf) {
			$this->setFrom($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
		} elseif ($conference) {
			$this->setFrom($conference->getSetting('contactEmail'), $conference->getSetting('contactName'));
		} else {
			$site =& Request::getSite();
			$this->setFrom($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
		}

		if ($schedConf && !Request::getUserVar('continued')) {
			$this->setSubject('[' . $schedConf->getLocalizedSetting('acronym') . '] ' . $this->getSubject());
		}

		$this->conference =& $conference;
	}

	/**
	 * Assigns values to e-mail parameters.
	 * @param $paramArray array
	 * @return void
	 */
	function assignParams($paramArray = array()) {
		// Add commonly-used variables to the list
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if ($schedConf) {
			$paramArray['principalContactSignature'] = $schedConf->getSetting('contactName');
		} elseif ($conference) {
			$paramArray['principalContactSignature'] = $conference->getSetting('contactName');
		} else {
			$site =& Request::getSite();
			$paramArray['principalContactSignature'] = $site->getLocalizedContactName();
		}

		if (isset($conference)) {
			// FIXME Include affiliation, title, etc. in signature?
			$paramArray['conferenceName'] = $conference->getConferenceTitle();
		}
		if (!isset($paramArray['conferenceUrl'])) $paramArray['conferenceUrl'] = Request::url(Request::getRequestedConferencePath(), Request::getRequestedSchedConfPath());

		return parent::assignParams($paramArray);
	}

	/**
	 * Displays an edit form to customize the email.
	 * @param $formActionUrl string
	 * @param $hiddenFormParams array
	 * @return void
	 */
	function displayEditForm($formActionUrl, $hiddenFormParams = null, $alternateTemplate = null, $additionalParameters = array()) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.emails');

		parent::displayEditForm($formActionUrl, $hiddenFormParams, $alternateTemplate, $additionalParameters);
	}

	/**
	 * Send the email.
	 * Aside from calling the parent method, this actually attaches
	 * the persistent attachments if they are used.
	 * @param $clearAttachments boolean Whether to delete attachments after
	 */
	function send($clearAttachments = true) {
		$schedConf =& Request::getSchedConf();

		if($schedConf) {
			$envelopeSender = $schedConf->getSetting('envelopeSender');
			$emailSignature = $schedConf->getLocalizedSetting('emailSignature');
		}

		if (isset($emailSignature)) {
			//If {$templateSignature} exists in the body of the
			// message, replace it with the conference signature;
			// otherwise just append it. This is here to
			// accomodate MIME-encoded messages or other cases
			// where the signature cannot just be appended.
			$searchString = '{$templateSignature}';
			if (strstr($this->getBody(), $searchString) === false) {
				$this->setBody($this->getBody() . "\n" . $emailSignature);
			} else {
				$this->setBody(str_replace($searchString, $emailSignature, $this->getBody()));
			}

			if (!empty($envelopeSender) && Config::getVar('email', 'allow_envelope_sender')) $this->setEnvelopeSender($envelopeSender);
		}

		return parent::send($clearAttachments);
	}
}

?>
