<?php

/**
 * @file Author.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Author
 * @ingroup paper
 * @see AuthorDAO
 *
 * @brief Paper author metadata class.
 */

//$Id$


import('submission.PKPAuthor');

class Author extends PKPAuthor {

	/**
	 * Constructor.
	 */
	function Author() {
		parent::PKPAuthor();
	}


	//
	// Get/set methods
	//

	/**
	 * Get ID of paper.
	 * @return int
	 */
	function getPaperId() {
		return $this->getData('paperId');
	}

	/**
	 * Set ID of paper.
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		return $this->setData('paperId', $paperId);
	}
}

?>
