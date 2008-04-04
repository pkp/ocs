<?php

/**
 * @file DirectorSubmission.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 * @class DirectorSubmission
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
