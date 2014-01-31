<?php

/**
 * @file classes/mail/EmailTemplateDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailTemplateDAO
 * @ingroup mail
 * @see EmailTemplate
 *
 * @brief Operations for retrieving and modifying Email Template objects.
 */

// $Id$


import('mail.PKPEmailTemplateDAO');
import('mail.EmailTemplate');

class EmailTemplateDAO extends PKPEmailTemplateDAO {
	/**
	 * Retrieve a base email template by key.
	 * @param $emailKey string
	 * @param $conferenceId int
	 * @return BaseEmailTemplate
	 */
	function &getBaseEmailTemplate($emailKey, $conferenceId) {
		return parent::getBaseEmailTemplate($emailKey, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Retrieve localized email template by key.
	 * @param $emailKey string
	 * @param $conferenceId int
	 * @return LocaleEmailTemplate
	 */
	function &getLocaleEmailTemplate($emailKey, $conferenceId) {
		return parent::getLocaleEmailTemplate($emailKey, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Retrieve an email template by key.
	 * @param $emailKey string
	 * @param $locale string
	 * @param $conferenceId int
	 * @return EmailTemplate
	 */
	function &getEmailTemplate($emailKey, $locale, $conferenceId) {
		return parent::getEmailTemplate($emailKey, $locale, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Delete an email template by key.
	 * @param $emailKey string
	 * @param $conferenceId int
	 */
	function deleteEmailTemplateByKey($emailKey, $conferenceId) {
		return parent::deleteEmailTemplateByKey($emailKey, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Retrieve all email templates.
	 * @param $locale string
	 * @param $conferenceId int
	 * @param $rangeInfo object optional
	 * @return array Email templates
	 */
	function &getEmailTemplates($locale, $conferenceId, $rangeInfo = null) {
		return parent::getEmailTemplates($locale, ASSOC_TYPE_CONFERENCE, $conferenceId, $rangeInfo);
	}

	/**
	 * Delete all email templates for a specific conference.
	 * @param $conferenceId int
	 */
	function deleteEmailTemplatesByConference($conferenceId) {
		return parent::deleteEmailTemplatesByAssoc(ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Check if a template exists with the given email key for a conference.
	 * @param $emailKey string
	 * @param $conferenceId int
	 * @return boolean
	 */
	function templateExistsByKey($emailKey, $conferenceId) {
		return parent::templateExistsByKey($emailKey, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}

	/**
	 * Check if a custom template exists with the given email key for a conference.
	 * @param $emailKey string
	 * @param $conferenceId int
	 * @return boolean
	 */
	function customTemplateExistsByKey($emailKey, $conferenceId) {
		return parent::customTemplateExistsByKey($emailKey, ASSOC_TYPE_CONFERENCE, $conferenceId);
	}
}

?>
