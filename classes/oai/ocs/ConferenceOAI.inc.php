<?php

/**
 * @defgroup oai_ocs
 */
 
/**
 * @file ConferenceOAI.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceOAI
 * @ingroup oai_ocs
 *
 * @brief OCS-specific OAI interface.
 * Designed to support site-wide and conference-wide OAI interface
 * (based on where the request is directed).
 */

//$Id$

import('oai.OAI');
import('oai.ocs.OAIDAO');

class ConferenceOAI extends OAI {
	/** @var $site Site associated site object */
	var $site;

	/** @var $conference Conference associated conference object */
	var $conference;

	/** @var $conferenceId int null if no conference */
	var $conferenceId;

	/** @var $dao OAIDAO DAO for retrieving OAI records/tokens from database */
	var $dao;


	/**
	 * @see OAI#OAI
	 */
	function ConferenceOAI($config) {
		parent::OAI($config);

		$this->site =& Request::getSite();
		$this->conference =& Request::getConference();
		$this->conferenceId = isset($this->conference) ? $this->conference->getId() : null;
		$this->dao =& DAORegistry::getDAO('OAIDAO');
		$this->dao->setOAI($this);
	}

	/**
	 * Return a list of ignorable GET parameters.
	 * @return array
	 */
	function getNonPathInfoParams() {
		return array('conference', 'schedConf', 'page');
	}

	/**
	 * Convert paper ID to OAI identifier.
	 * @param $paperId int
	 * @return string
	 */
	function paperIdToIdentifier($paperId) {
		return 'oai:' . $this->config->repositoryId . ':' . 'paper/' . $paperId;
	}

	/**
	 * Convert OAI identifier to paper ID.
	 * @param $identifier string
	 * @return int
	 */
	function identifierToPaperId($identifier) {
		$prefix = 'oai:' . $this->config->repositoryId . ':' . 'paper/';
		if (strstr($identifier, $prefix)) {
			return (int) str_replace($prefix, '', $identifier);
		} else {
			return false;
		}
	}

	/**
	 * Get the conference ID and track ID corresponding to a set specifier.
	 * @return int
	 */	
	function setSpecToTrackId($setSpec, $conferenceId = null) {
		$tmpArray = split(':', $setSpec);
		if (count($tmpArray) == 1) {
			list($conferenceSpec) = $tmpArray;
			$trackSpec = null;
		} else if (count($tmpArray) == 3) {
			list($conferenceSpec, $schedConfSpec, $trackSpec) = $tmpArray;
		} else {
			return array(0, 0);
		}
		return $this->dao->getSetConferenceTrackId($conferenceSpec, $schedConfSpec, $trackSpec, $this->conferenceId);
	}


	//
	// OAI interface functions
	//

	/**
	 * @see OAI#repositoryInfo
	 */
	function &repositoryInfo() {
		$info = new OAIRepository();

		if (isset($this->conference)) {
			$info->repositoryName = $this->conference->getConferenceTitle();
			$info->adminEmail = $this->conference->getSetting('contactEmail');

		} else {
			$info->repositoryName = $this->site->getLocalizedTitle();
			$info->adminEmail = $this->site->getLocalizedContactEmail();
		}

		$info->sampleIdentifier = $this->paperIdToIdentifier(1);
		$info->earliestDatestamp = $this->dao->getEarliestDatestamp($this->conferenceId);

		return $info;
	}

	/**
	 * @see OAI#validIdentifier
	 */
	function validIdentifier($identifier) {
		return $this->identifierToPaperId($identifier) !== false;
	}

	/**
	 * @see OAI#identifierExists
	 */
	function identifierExists($identifier) {
		$recordExists = false;
		$paperId = $this->identifierToPaperId($identifier);
		if ($paperId) {
			$recordExists = $this->dao->recordExists($paperId, $this->conferenceId);
		}
		return $recordExists;
	}

	/**
	 * @see OAI#record
	 */
	function &record($identifier) {
		$paperId = $this->identifierToPaperId($identifier);
		if ($paperId) {
			$record =& $this->dao->getRecord($paperId, $this->conferenceId);
		}
		if (!isset($record)) {
			$record = false;
		}
		return $record;		
	}

	/**
	 * @see OAI#records
	 */
	function &records($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		if (isset($set)) {
			list($conferenceId, $schedConfId, $trackId) = $this->setSpecToTrackId($set);
		} else {
			$conferenceId = $this->conferenceId;
			$trackId = null;
			$schedConfId = null;
		}
		$records =& $this->dao->getRecords($conferenceId, $schedConfId, $trackId, $from, $until, $offset, $limit, $total);
		return $records;
	}

	/**
	 * @see OAI#identifiers
	 */
	function &identifiers($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		$trackId = null;
		if (isset($set)) {
			list($conferenceId, $schedConfId, $trackId) = $this->setSpecToTrackId($set);
		} else {
			$conferenceId = $this->conferenceId;
		}
		$records =& $this->dao->getIdentifiers($conferenceId, $schedConfId, $trackId, $from, $until, $offset, $limit, $total);
		return $records;
	}

	/**
	 * @see OAI#sets
	 */
	function &sets($offset, &$total) {
		$sets =& $this->dao->getConferenceSets($this->conferenceId, $offset, $total);
		return $sets;
	}

	/**
	 * @see OAI#resumptionToken
	 */
	function &resumptionToken($tokenId) {
		$this->dao->clearTokens();
		$token = $this->dao->getToken($tokenId);
		if (!isset($token)) {
			$token = false;
		}
		return $token;
	}

	/**
	 * @see OAI#saveResumptionToken
	 */
	function &saveResumptionToken($offset, $params) {
		$token = new OAIResumptionToken(null, $offset, $params, time() + $this->config->tokenLifetime);
		$this->dao->insertToken($token);
		return $token;
	}
}

?>
