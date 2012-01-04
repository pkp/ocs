<?php

/**
 * @file classes/notification/Notification.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSNotification
 * @ingroup notification
 * @see NotificationDAO
 * @brief OJS subclass for Notifications (defines OJS-specific types and icons).
 */

// $Id$


/** Notification associative types. */
define('NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT', 		0x1000001);
define('NOTIFICATION_TYPE_GALLEY_MODIFIED', 			0x1000002);
define('NOTIFICATION_TYPE_METADATA_MODIFIED', 			0x1000003);
define('NOTIFICATION_TYPE_NEW_ANNOUNCEMENT', 			0x1000004);
define('NOTIFICATION_TYPE_PAPER_SUBMITTED', 			0x1000005);
define('NOTIFICATION_TYPE_REVIEWER_COMMENT', 			0x1000006);
define('NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT', 		0x1000007);
define('NOTIFICATION_TYPE_SUBMISSION_COMMENT', 			0x1000008);
define('NOTIFICATION_TYPE_SUPP_FILE_MODIFIED', 			0x1000009);
define('NOTIFICATION_TYPE_USER_COMMENT', 			0x1000010);

import('notification.PKPNotification');
import('notification.NotificationDAO');

class Notification extends PKPNotification {

	/**
	 * Constructor.
	 */
	function Notification() {
		parent::PKPNotification();
	}

	/**
	 * return the path to the icon for this type
	 * @return string
	 */
	function getIconLocation() {
		$baseUrl = Request::getBaseUrl() . '/lib/pkp/templates/images/icons/';
		switch ($this->getAssocType()) {
			case NOTIFICATION_TYPE_PAPER_SUBMITTED:
				return $baseUrl . 'page_new.gif';
				break;
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
			case NOTIFICATION_TYPE_SUPP_FILE_ADDED:
				return $baseUrl . 'page_attachment.gif';
				break;
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return $baseUrl . 'edit.gif';
				break;
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
			case NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT:
			case NOTIFICATION_TYPE_USER_COMMENT:
				return $baseUrl . 'comment_new.gif';
				break;
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				return $baseUrl . 'note_new.gif';
				break;
			default:
				return $baseUrl . 'page_alert.gif';
		}
	}

	/**
	 * Static function to send an email to a mailing list user regarding signup or a lost password
	 * @param $email string
	 * @param $password string the user's password
	 * @param $template string The mail template to use
	 */
	function sendMailingListEmail($email, $password, $template) {
		import('mail.MailTemplate');
		$conference = Request::getConference();
		$site = Request::getSite();

		$params = array(
			'password' => $password,
			'siteTitle' => $conference->getConferenceTitle(),
			'unsubscribeLink' => Request::url(null, null, 'notification', 'unsubscribeMailList')
		);

		if ($template == 'NOTIFICATION_MAILLIST_WELCOME') {
			$keyHash = md5($password);
			$confirmLink = Request::url(null, null, 'notification', 'confirmMailListSubscription', array($keyHash, $email));
			$params["confirmLink"] = $confirmLink;
		}

		$mail = new MailTemplate($template);
		$mail->setFrom($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
		$mail->assignParams($params);
		$mail->addRecipient($email);
		$mail->send();
	}

	/**
	 * Returns an array of information on the conference's subscription settings
	 * @return array
	 */
	function getSubscriptionSettings() {
		$conference = Request::getConference();
		import('payment.ocs.OCSPaymentManager');
		$paymentManager =& OCSPaymentManager::getManager();

		$settings = array(
			'allowRegReviewer' => $conference->getSetting('allowRegReviewer'),
			'allowRegAuthor' => $conference->getSetting('allowRegAuthor')
		);

		return $settings;
	}
}

?>
