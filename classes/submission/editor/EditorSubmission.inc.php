<?php

/**
 * EditorSubmission.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * EditorSubmission class.
 *
 * $Id$
 */

import('submission.trackEditor.TrackEditorSubmission');

class EditorSubmission extends TrackEditorSubmission {

	/**
	 * Constructor.
	 */
	function EditorSubmission() {
		parent::TrackEditorSubmission();
	}
}

?>
