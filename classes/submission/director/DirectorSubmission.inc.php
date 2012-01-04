<?php

/**
 * @file DirectorSubmission.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DirectorSubmission
 * @ingroup submission
 * @see DirectorSubmissionDAO
 *
 * @brief DirectorSubmission class.
 */

//$Id$

import('submission.trackDirector.TrackDirectorSubmission');

class DirectorSubmission extends TrackDirectorSubmission {

	/**
	 * Constructor.
	 */
	function DirectorSubmission() {
		parent::TrackDirectorSubmission();
	}
}

?>
