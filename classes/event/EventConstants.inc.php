<?php

/**
 * EventConstants.inc.php
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package event
 *
 * $Id$
 *
 * Constants defining various state variables for events.
 *
 * WARNING: Some templates contain hardcoded numbers corresponding to these
 *          constants. Beware of the following:
 *
 *              * timelineForm.tpl
 *
 */

// All of these are associated with the Event as lowerCamelCase entries
// in the associated EventSettings tables.

define('EVENT_DATE_YEAR_OFFSET_FUTURE',	'+10');

//
// Submission State
//

// Determines whether or not the event is currently accepting submissions.

define('SUBMISSION_STATE_NOTYET', 0);   // not ready yet
define('SUBMISSION_STATE_ACCEPT', 1);   // accept papers
define('SUBMISSION_STATE_CLOSED', 2);   // deadline passed

//
// Publication State
//

// Determines whether or not published papers are available to be viewed on the
// site.

define('PUBLICATION_STATE_NOTYET', 0);       // papers not released yet
define('PUBLICATION_STATE_PARTICIPANTS', 1); // papers released to participants
define('PUBLICATION_STATE_PUBLIC', 2);       // papers released to the public

//
// Registration State
//

// Is the site accepting registration?

define('REGISTRATION_STATE_NOTYET', 0); // not accepting registrants yet
define('PUBLICATION_STATE_ACCEPT', 1);  // accepting registrants
define('PUBLICATION_STATE_CLOSED', 2);  // no longer accepting registrants

?>
