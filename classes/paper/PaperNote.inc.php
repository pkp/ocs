<?php

/**
 * @file classes/paper/PaperNote.inc.php
 *
 * Copyright (c) 2005-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperNote
 * @ingroup paper
 * @see PaperNoteDAO
 *
 * @brief Class for PaperNote.
 */



import('classes.note.Note');

class PaperNote extends Note {
	/**
	 * Constructor.
	 */
	function PaperNote() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated class PaperNote. Use Note instead');
		parent::Note();
	}
}

?>
