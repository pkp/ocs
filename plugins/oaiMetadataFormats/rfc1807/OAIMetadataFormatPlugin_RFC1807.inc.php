<?php

/**
 * @file plugins/OAIMetadata/marc/OAIMetadataFormatPlugin_RFC1807.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_RFC1807
 * @ingroup oai_format
 * @see OAI
 *
 * @brief rfc1807 metadata format plugin for OAI.
 */

// $Id$


import('plugins.OAIMetadataFormatPlugin');

class OAIMetadataFormatPlugin_RFC1807 extends OAIMetadataFormatPlugin {

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OAIFormatPlugin_RFC1807';
	}
	function getDisplayName() {
		return __('plugins.OAIMetadata.rfc1807.displayName');
	}
	function getDescription() {
		return __('plugins.OAIMetadata.rfc1807.description');
	}
	function getFormatClass() {
		return 'OAIMetadataFormat_RFC1807';
	}
	function getMetadataPrefix() {
		return 'rfc1807';
	}
	function getSchema(){
		return 'http://www.openarchives.org/OAI/1.1/rfc1807.xsd';
	}
	function getNamespace(){
		return 'http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt';
	}
}

?>
