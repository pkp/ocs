<?php

/**
 * @file CustomBlockEditForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customBlockManager
 * @class CustomBlockEditForm
 *
 * Form for conference managers to create and modify sidebar blocks
 * 
 */

import('form.Form');

class CustomBlockEditForm extends Form {
	/** @var $conferenceId int */
	var $conferenceId;

	/** @var $plugin object */
	var $plugin;
	
	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $conferenceId int
	 */
	function CustomBlockEditForm(&$plugin, $conferenceId) {

		parent::Form($plugin->getTemplatePath() . 'editCustomBlockForm.tpl');

		$this->conferenceId = $conferenceId;
		$this->plugin =& $plugin;

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidator($this, 'blockContent', 'required', 'plugins.generic.customBlock.contentRequired'));

	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;

		// add the tiny MCE script 
		$this->addTinyMCE();
		$this->setData('blockContent', $plugin->getSetting($conferenceId, 0, 'blockContent'));
	}

	/**
	 * Add the tinyMCE script for editing sidebar blocks with a WYSIWYG editor
	 */
	function addTinyMCE() {
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;
		$templateMgr =& TemplateManager::getManager();

		// Enable TinyMCE with specific params
		$additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');

		import('file.ConferenceFileManager');
		$publicFileManager = new PublicFileManager();
		$tinyMCE_script = '
		<script language="javascript" type="text/javascript" src="'.Request::getBaseUrl().'/'.TINYMCE_JS_PATH.'/tiny_mce.js"></script>
		<script language="javascript" type="text/javascript">
			tinyMCE.init({
			mode : "textareas",
			plugins : "style,paste",
			theme : "advanced",
			theme_advanced_buttons1 : "formatselect,fontselect,fontsizeselect",
			theme_advanced_buttons2 : "bold,italic,underline,separator,strikethrough,justifyleft,justifycenter,justifyright, justifyfull,bullist,numlist,undo,redo,link,unlink",
			theme_advanced_buttons3 : "cut,copy,paste,pastetext,pasteword,|,cleanup,help,code,",
			theme_advanced_toolbar_location : "bottom",
			theme_advanced_toolbar_align : "left",
			content_css : "' . Request::getBaseUrl() . '/styles/common.css", 
			relative_urls : false, 		
			document_base_url : "'. Request::getBaseUrl() .'/'.$publicFileManager->getConferenceFilesPath($conferenceId) .'/", 
			extended_valid_elements : "span[*], div[*]"
			});
		</script>';

		$templateMgr->assign('additionalHeadData', $additionalHeadData."\n".$tinyMCE_script);

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('blockContent'));
	}

	/**
	 * Save page into DB
	 */	 
	function save() {
		$plugin =& $this->plugin;
		$conferenceId = $this->conferenceId;
		$plugin->updateSetting($conferenceId, 0, 'blockContent', $this->getData('blockContent'));		
	}

}
?>
