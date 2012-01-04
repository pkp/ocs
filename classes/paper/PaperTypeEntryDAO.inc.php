<?php

/**
 * @file PaperTypeEntryDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperTypeEntryDAO
 * @ingroup paper
 * @see PaperTypeEntry, ControlledVocabEntryDAO
 *
 * @brief Operations for retrieving and modifying PaperTypeEntry objects
 */

//$Id$

import('paper.PaperTypeEntry');
import('controlledVocab.ControlledVocabEntryDAO');

class PaperTypeEntryDAO extends ControlledVocabEntryDAO {
	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return PaperTypeEntry
	 */
	function newDataObject() {
		return new PaperTypeEntry();
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$fieldNames = parent::getLocaleFieldNames();
		$fieldNames[] = 'description';
		return $fieldNames;
	}

	/**
	 * Get the list of non-localized additional fields to store.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array('length', 'abstractLength');
	}
}

?>
