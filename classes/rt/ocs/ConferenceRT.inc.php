<?php

/**
 * @defgroup rt_ocs
 */
 
/**
 * @file ConferenceRT.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceRT
 * @ingroup rt_ocs
 *
 * @brief OCS-specific Reading Tools end-user interface.
 */

//$Id$

import('rt.RT');
import('rt.ocs.RTDAO');

class ConferenceRT extends RT {
	var $conferenceId;
	var $enabled;

	/** @var $addComment boolean */
	var $addComment;

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

	function setAddComment($addComment) {
		$this->addComment = $addComment;
	}

	function getAddComment() {
		return $this->addComment;
	}
}

?>
