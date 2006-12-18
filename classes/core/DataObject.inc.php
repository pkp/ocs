<?php

/**
 * DataObject.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 *
 * Abstract class for data objects. 
 * Any class with an associated DAO should extend this class.
 *
 * $Id$
 */

class DataObject {

	/** Array of object data */
	var $_data;
	
	/**
	 * Constructor.
	 */
	function DataObject($callHooks = true) {
		$this->_data = array();
	}
	
	/**
	 * Get the value of a data variable.
	 * @param $key string
	 * @return mixed
	 */
	function &getData($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		}
		$nullVar = null;
		return $nullVar;
	}
	
	/**
	 * Set the value of a new or existing data variable.
	 * @param $key string
	 * @param $value mixed
	 */
	function setData($key, $value) {
		$this->_data[$key] = $value;
	}
}

?>
