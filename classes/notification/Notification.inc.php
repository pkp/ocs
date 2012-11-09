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



/** Notification associative types. */
define('NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT', 	0x1000001);
define('NOTIFICATION_TYPE_GALLEY_MODIFIED', 			0x1000002);
define('NOTIFICATION_TYPE_METADATA_MODIFIED', 			0x1000003);
define('NOTIFICATION_TYPE_PAPER_SUBMITTED', 			0x1000005);
define('NOTIFICATION_TYPE_REVIEWER_COMMENT', 			0x1000006);
define('NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT', 		0x1000007);
define('NOTIFICATION_TYPE_SUBMISSION_COMMENT', 			0x1000008);
define('NOTIFICATION_TYPE_SUPP_FILE_MODIFIED', 			0x1000009);
define('NOTIFICATION_TYPE_USER_COMMENT', 				0x1000010);

import('lib.pkp.classes.notification.PKPNotification');

class Notification extends PKPNotification {

	/**
	 * Constructor.
	 */
	function Notification() {
		parent::PKPNotification();
	}
}
?>
