<?php

/**
 * @file PaperTypeEntry.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperTypeEntry
 * @ingroup paper
 * @see PaperTypeEntryDAO
 *
 * @brief Basic class describing a paper type.
 */

//$Id$


import('controlledVocab.ControlledVocabEntry');

class PaperTypeEntry extends ControlledVocabEntry {
	//
	// Get/set methods
	//

	/**
	 * Get the localized description.
	 * @return string
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get the description of the paper type entry.
	 * @param $locale string
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set the description of the paper type entry.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description, $locale);
	}

	/**
	 * Get the paper type abstract length word limit
	 * @return int
	 */
	function getAbstractLength() {
		return $this->getData('abstractLength');
	}

	/**
	 * Set the paper type abstract length word limit
	 * @param $abstractLength
	 */
	function setAbstractLength($abstractLength) {
		$this->setData('abstractLength', $abstractLength);
	}

	/**
	 * Get the length in minutes
	 * @return int
	 */
	function getLength() {
		return $this->getData('length');
	}

	/**
	 * Set the length in minutes
	 * @param $length
	 */
	function setLength($length) {
		$this->setData('length', $length);
	}
}

?>
