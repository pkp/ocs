<?php

/**
 * DirectorSubmission.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * DirectorSubmission class.
 *
 * $Id$
 */

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
