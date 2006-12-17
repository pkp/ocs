<?php

/**
 * ConferenceSetupStep4Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form.setup
 *
 * Form for Step 4 of conference setup.
 *
 * $Id$
 */

import("director.form.setup.ConferenceSetupForm");

class ConferenceSetupStep4Form extends ConferenceSetupForm {
	
	function ConferenceSetupStep4Form() {
		parent::ConferenceSetupForm(
			4,
			array(
				'searchDescription' => 'string',
				'searchKeywords' => 'string',
				'customHeaders' => 'string',
				'metaDiscipline' => 'bool',
				'metaDisciplineExamples' => 'string',
				'metaSubjectClass' => 'bool',
				'metaSubjectClassTitle' => 'string',
				'metaSubjectClassUrl' => 'string',
				'metaSubject' => 'bool',
				'metaSubjectExamples' => 'string',
				'metaCoverage' => 'bool',
				'metaCoverageGeoExamples' => 'string',
				'metaCoverageChronExamples' => 'string',
				'metaCoverageResearchSampleExamples' => 'string',
				'metaType' => 'bool',
				'metaTypeExamples' => 'string',
				'enablePublicPaperId' => 'bool',
				'enablePublicSuppFileId' => 'bool',
				'enableAnnouncements' => 'bool',
				'enableAnnouncementsHomepage' => 'bool',
				'numAnnouncementsHomepage' => 'int',
				'announcementsIntroduction' => 'string'
			)
		);
	}
}

?>
