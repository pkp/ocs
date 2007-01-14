<?php

/**
 * TimelineHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for event timeline management functions. 
 *
 * $Id$
 */

class TimelineHandler extends TrackEditorHandler {

	/**
	 * Display a list of the tracks within the current conference.
	 */
	function timeline($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		import('editor.form.TimelineForm');
		
		$timelineForm = &new TimelineForm();
		$timelineForm->initData();
		$timelineForm->display();

	}

	function updateTimeline($args) {
		parent::validate();
		
		import('editor.form.TimelineForm');
		
		$timelineForm = &new TimelineForm();
		$timelineForm->readInputData();
		
		if ($timelineForm->validate()) {
			$timelineForm->execute();
			Request::redirect(null, null, null, 'timeline');
		} else {
			parent::setupTemplate(true);
			$timelineForm->display();
		}
	}
	
}
?>
