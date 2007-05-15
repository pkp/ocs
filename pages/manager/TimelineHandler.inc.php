<?php

/**
 * TimelineHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for scheduled conference timeline management functions. 
 *
 * $Id$
 */

class TimelineHandler extends ManagerHandler {

	/**
	 * Display a list of the tracks within the current conference.
	 */
	function timeline($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		import('manager.form.TimelineForm');
		
		$timelineForm = &new TimelineForm(Request::getUserVar('overrideDates'));
		$timelineForm->initData();
		$timelineForm->display();

	}

	function updateTimeline($args) {
		parent::validate();
		
		import('manager.form.TimelineForm');
		
		$timelineForm = &new TimelineForm(Request::getUserVar('overrideDates'));
		$timelineForm->readInputData();
		
		if ($timelineForm->validate()) {
			$timelineForm->execute();
			Request::redirect(null, null, null, 'index');
		} else {
			parent::setupTemplate(true);
			$timelineForm->setData('errorsExist', true);
			$timelineForm->display();
		}
	}
	
}
?>
