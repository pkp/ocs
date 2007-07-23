<?php

/**
 * @file ConferenceRT.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package rt.ocs
 * @class ConferenceRT
 *
 * OCS-specific Reading Tools end-user interface.
 *
 * $Id$
 */

import('rt.RT');
import('rt.ocs.RTDAO');

class ConferenceRT extends RT {
	var $conferenceId;
	var $enabled;

	function ConferenceRT($conferenceId) {
		$this->setConferenceId($conferenceId);
	}

	// Getter/setter methods

	function getConferenceId() {
		return $this->conferenceId;
	}

	function setConferenceId($conferenceId) {
		$this->conferenceId = $conferenceId;
	}
}

?>
