<?php

/**
 * @file plugins/oaiMetadata/marc/OAIMetadataFormatPlugin_MARC21.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_MARC21
 * @ingroup oai_format
 * @see OAI
 *
 * @brief marc21 metadata format plugin for OAI.
 */

// $Id$


import('plugins.OAIMetadataFormatPlugin');

class OAIMetadataFormatPlugin_MARC21 extends OAIMetadataFormatPlugin {

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OAIFormatPlugin_MARC21';
	}
	function getDisplayName() {
		return __('plugins.OAIMetadata.marcxml.displayName');
	}
	function getDescription() {
		return __('plugins.OAIMetadata.marcxml.description');
	}
	function getFormatClass() {
		return 'OAIMetadataFormat_MARC21';
	}
	function getMetadataPrefix() {
		return 'marcxml';
	}
	function getSchema(){
		return 'http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd';
	}
	function getNamespace(){
		return 'http://www.loc.gov/MARC21/slim';
	}
}

?>
