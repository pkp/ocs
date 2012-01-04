<?php

/**
 * @file TimelineHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimelineHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for scheduled conference timeline management functions. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class TimelineHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function TimelineHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the tracks within the current conference.
	 */
	function timeline($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.TimelineForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$timelineForm = new TimelineForm(Request::getUserVar('overrideDates'));
		} else {
			$timelineForm =& new TimelineForm(Request::getUserVar('overrideDates'));
		}
		$timelineForm->initData();
		$timelineForm->display();

	}

	function updateTimeline($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('manager.form.TimelineForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$timelineForm = new TimelineForm(Request::getUserVar('overrideDates'));
		} else {
			$timelineForm =& new TimelineForm(Request::getUserVar('overrideDates'));
		}
		$timelineForm->readInputData();

		if ($timelineForm->validate()) {
			$timelineForm->execute();
			Request::redirect(null, null, null, 'index');
		} else {
			$timelineForm->setData('errorsExist', true);
			$timelineForm->display();
		}
	}

}
?>
