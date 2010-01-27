<?php

/**
 * @file StaticPage.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPage
 * 
 */

class StaticPage extends DataObject {
	//
	// Get/set methods
	//

	/**
	 * Get conference id 
	 * @return string
	 */
	function getConferenceId(){
		return $this->getData('conferenceId');
	}
	
	/**
	 * Set conference Id
	 * @param $conferenceId int
	 */
	function setConferenceId($conferenceId) {
		return $this->setData('conferenceId', $conferenceId);
	}
	

	/**
	 * Set page title 
	 * @param string string
	 * @param locale
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}	
		
	/**
	 * Get page title 
	 * @param locale
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Get Localized page title 
	 * @return string
	 */
	function getStaticPageTitle() {
		return $this->getLocalizedData('title');
	}	
		
	/**
	 * Set page content
	 * @param $content string
	 * @param locale
	 */
	function setContent($content, $locale) {
		return $this->setData('content', $content, $locale);
	}
	
	/**
	 * Get content
	 * @param locale
	 * @return string
	 */
	function getContent($locale) {
		return $this->getData('content', $locale);
	}
	
	/**
	 * Get "localized" content
	 * @return string
	 */
	function getStaticPageContent() {
		return $this->getLocalizedData('content');
	}	
	
	/**
	 * Get page path string
	 * @return string
	 */
	function getPath() {
		return $this->getData('path');
	}
	 
	 /**
	  * Set page path string
	  * @param $path string
	  */
	function setPath($path) {
		return $this->setData('path', $path);
	}
	
	/**
	 * Get ID of page.
	 * @return int
	 */
	function getStaticPageId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}
	
	/**
	 * Set ID of page.
	 * @param $staticPageId int
	 */
	function setStaticPageId($staticPageId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($staticPageId);
	}
}

?>
