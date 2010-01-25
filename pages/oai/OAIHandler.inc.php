<?php

/**
 * @file OAIHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIHandler
 * @ingroup pages_oai
 *
 * @brief Handle OAI protocol requests. 
 */

//$Id$


define('SESSION_DISABLE_INIT', 1); // FIXME?

import('oai.ocs.ConferenceOAI');
import('handler.Handler');

class OAIHandler extends Handler {
	/**
	 * Constructor
	 **/
	function OAIHandler() {
		parent::Handler();
	}

	function index() {
		$this->validate();
		PluginRegistry::loadCategory('oaiMetadataFormats', true);

		$oai = new ConferenceOAI(new OAIConfig(Request::getRequestUrl(), Config::getVar('oai', 'repository_id')));
		$oai->execute();
	}

	function validate() {
		// Site validation checks not applicable
		//parent::validate();

		if (!Config::getVar('oai', 'oai')) {
			Request::redirect(null, 'index');
		}
	}
}

?>
