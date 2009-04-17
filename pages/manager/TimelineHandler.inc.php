<?php

/**
 * @file TimelineHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimelineHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for scheduled conference timeline management functions. 
 */

//$Id$

class TimelineHandler extends ManagerHandler {

	/**
	 * Display a list of the tracks within the current conference.
	 */
	function timeline($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.TimelineForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$timelineForm =& new TimelineForm(Request::getUserVar('overrideDates'));
		$timelineForm->initData();
		$timelineForm->display();

	}

	function updateTimeline($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.TimelineForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$timelineForm =& new TimelineForm(Request::getUserVar('overrideDates'));
		$timelineForm->readInputData();

		if ($timelineForm->validate()) {
			$timelineForm->execute();
			Request::redirect(null, null, null, 'index');
		} else {
			$this->setupTemplate(true);
			$timelineForm->setData('errorsExist', true);
			$timelineForm->display();
		}
	}

}
?>
