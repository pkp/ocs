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
	function timeline($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.TimelineForm');

		$timelineForm = new TimelineForm($request->getUserVar('overrideDates'));
		$timelineForm->initData();
		$timelineForm->display();

	}

	function updateTimeline($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.manager.form.TimelineForm');

		$timelineForm = new TimelineForm($request->getUserVar('overrideDates'));
		$timelineForm->readInputData();

		if ($timelineForm->validate()) {
			$timelineForm->execute();
			$request->redirect(null, null, null, 'index');
		} else {
			$timelineForm->setData('errorsExist', true);
			$timelineForm->display();
		}
	}
}

?>
