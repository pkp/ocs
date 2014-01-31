<?php

/**
 * @file RTDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTDAO
 * @ingroup rt_ocs
 * @see RT
 *
 * @brief DAO operations for the OCS Reading Tools interface.
 */

//$Id$

import('rt.ocs.ConferenceRT');

class RTDAO extends DAO {
	//
	// RT
	//

	/**
	 * Retrieve an RT configuration.
	 * @param $conferenceId int
	 * @return RT
	 */
	function &getConferenceRTByConference(&$conference) {
		$rt = new ConferenceRT($conference->getId());
		$rt->setEnabled($conference->getSetting('rtEnabled')?true:false);
		$rt->setVersion((int) $conference->getSetting('rtVersionId'));
		$rt->setAbstract($conference->getSetting('rtAbstract')?true:false);
		$rt->setCaptureCite($conference->getSetting('rtCaptureCite')?true:false);
		$rt->setViewMetadata($conference->getSetting('rtViewMetadata')?true:false);
		$rt->setSupplementaryFiles($conference->getSetting('rtSupplementaryFiles')?true:false);
		$rt->setPrinterFriendly($conference->getSetting('rtPrinterFriendly')?true:false);
		$rt->setAuthorBio($conference->getSetting('rtAuthorBio')?true:false);
		$rt->setDefineTerms($conference->getSetting('rtDefineTerms')?true:false);
		$rt->setAddComment($conference->getSetting('rtAddComment')?true:false);
		$rt->setEmailAuthor($conference->getSetting('rtEmailAuthor')?true:false);
		$rt->setEmailOthers($conference->getSetting('rtEmailOthers')?true:false);
		$rt->setFindingReferences($conference->getSetting('rtFindingReferences')?true:false);
		return $rt;
	}

	function updateConferenceRT(&$rt) {
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$conference =& $conferenceDao->getConference($rt->getConferenceId());

		$conference->updateSetting('rtEnabled', $rt->getEnabled(), 'bool');
		$conference->updateSetting('rtVersionId', $rt->getVersion(), 'int');
		$conference->updateSetting('rtAbstract', $rt->getAbstract(), 'bool');
		$conference->updateSetting('rtCaptureCite', $rt->getCaptureCite(), 'bool');
		$conference->updateSetting('rtViewMetadata', $rt->getViewMetadata(), 'bool');
		$conference->updateSetting('rtSupplementaryFiles', $rt->getSupplementaryFiles(), 'bool');
		$conference->updateSetting('rtPrinterFriendly', $rt->getPrinterFriendly(), 'bool');
		$conference->updateSetting('rtAuthorBio', $rt->getAuthorBio(), 'bool');
		$conference->updateSetting('rtDefineTerms', $rt->getDefineTerms(), 'bool');
		$conference->updateSetting('rtAddComment', $rt->getAddComment(), 'bool');
		$conference->updateSetting('rtEmailAuthor', $rt->getEmailAuthor(), 'bool');
		$conference->updateSetting('rtEmailOthers', $rt->getEmailOthers(), 'bool');
		$conference->updateSetting('rtFindingReferences', $rt->getFindingReferences());
		return true;
	}

	/**
	 * Insert a new RT configuration.
	 * @param $rt object
	 */
	function insertConferenceRT(&$rt) {
		return $this->updateConferenceRT($rt);
	}

	//
	// RT Versions
	//

	/**
	 * Retrieve all RT versions for a conference.
	 * @param $conferenceId int
	 * @param $pagingInfo object DBResultRange (optional)
	 * @return array RTVersion
	 */
	function &getVersions($conferenceId, $pagingInfo = null) {
		$versions = array();

		$result =& $this->retrieveRange(
			'SELECT * FROM rt_versions WHERE conference_id = ? ORDER BY version_key',
			(int) $conferenceId,
			$pagingInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnVersionFromRow');
		return $returner;
	}

	/**
	 * Retrieve a version.
	 * @param $versionId int
	 * @param $conferenceId int
	 * @return RTVersion
	 */
	function &getVersion($versionId, $conferenceId) {
		$result =& $this->retrieve(
			'SELECT * FROM rt_versions WHERE version_id = ? AND conference_id = ?',
			array((int) $versionId, (int) $conferenceId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnVersionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Insert a new version.
	 * @param $conferenceId int
	 * @param $version RTVersion
	 */
	function insertVersion($conferenceId, &$version) {
		$this->update(
			'INSERT INTO rt_versions
			(conference_id, version_key, locale, title, description)
			VALUES
			(?, ?, ?, ?, ?)',
			array((int) $conferenceId, $version->key, $version->locale, $version->title, $version->description)
		);

		$version->versionId = $this->getInsertId('rt_versions', 'version_id');

		foreach ($version->contexts as $context) {
			$context->versionId = $version->versionId;
			$this->insertContext($context);
		}

		return $version->versionId;
	}

	/**
	 * Update an exisiting verison.
	 * @param $conferenceId int
	 * @param $version RTVersion
	 */
	function updateVersion($conferenceId, &$version) {
		// FIXME Update contexts and searches?
		return $this->update(
			'UPDATE rt_versions
			SET
				title = ?,
				description = ?,
				version_key = ?,
				locale = ?
			WHERE version_id = ? AND conference_id = ?',
			array(
				$version->getTitle(),
				$version->getDescription(),
				$version->getKey(),
				$version->getLocale(),
				(int) $version->getVersionId(),
				(int) $conferenceId
			)
		);
	}

	/**
	 * Delete all versions by conference ID.
	 * @param $conferenceId int
	 */
	function deleteVersionsByConferenceId($conferenceId) {
		$versions =& $this->getVersions($conferenceId);
		foreach ($versions->toArray() as $version) {
			$this->deleteVersion($version->getVersionId(), $conferenceId);
		}
	}

	/**
	 * Delete a version.
	 * @param $versionId int
	 * @param $conferenceId int
	 */
	function deleteVersion($versionId, $conferenceId) {
		$this->deleteContextsByVersionId($versionId);
		return $this->update(
			'DELETE FROM rt_versions WHERE version_id = ? AND conference_id = ?',
			array((int) $versionId, (int) $conferenceId)
		);
	}

	/**
	 * Delete RT versions (and dependent entities) by conference ID.
	 * @param $conferenceId int
	 */
	function deleteVersionsByConference($conferenceId) {
		$versions =& RTDAO::getVersions($conferenceId);
		while (!$versions->eof()) {
			$version =& $versions->next();
			$this->deleteVersion($version->getVersionId(), $conferenceId);
		}
	}

	/**
	 * Return RTVersion object from database row.
	 * @param $row array
	 * @return RTVersion
	 */
	function &_returnVersionFromRow(&$row) {
		$version = new RTVersion();
		$version->setVersionId($row['version_id']);
		$version->setKey($row['version_key']);
		$version->setLocale($row['locale']);
		$version->setTitle($row['title']);
		$version->setDescription($row['description']);

		if (!HookRegistry::call('RTDAO::_returnVersionFromRow', array(&$version, &$row))) {
			$contextsIterator =& $this->getContexts($row['version_id']);
			$version->setContexts($contextsIterator->toArray());
		}

		return $version;
	}

	/**
	 * Return RTSearch object from database row.
	 * @param $row array
	 * @return RTSearch
	 */
	function &_returnSearchFromRow(&$row) {
		$search = new RTSearch();
		$search->setSearchId($row['search_id']);
		$search->setContextId($row['context_id']);
		$search->setTitle($row['title']);
		$search->setDescription($row['description']);
		$search->setUrl($row['url']);
		$search->setSearchUrl($row['search_url']);
		$search->setSearchPost($row['search_post']);
		$search->setOrder($row['seq']);

		HookRegistry::call('RTDAO::_returnSearchFromRow', array(&$search, &$row));

		return $search;
	}



	//
	// RT Contexts
	//

	/**
	 * Retrieve an RT context.
	 * @param $contextId int
	 * @return RT
	 */
	function &getContext($contextId) {
		$result =& $this->retrieve(
			'SELECT * FROM rt_contexts WHERE context_id = ?',
			array((int) $contextId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnContextFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all RT contexts for a version (in order).
	 * @param $versionId int
	 * @param $pagingInfo object DBResultRange (optional)
	 * @return array RTContext
	 */
	function &getContexts($versionId, $pagingInfo = null) {
		$contexts = array();

		$result =& $this->retrieveRange(
			'SELECT * FROM rt_contexts WHERE version_id = ? ORDER BY seq',
			array((int) $versionId),
			$pagingInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnContextFromRow');
		return $returner;
	}

	/**
	 * Insert a context.
	 * @param $versionId int
	 * @param $context RTContext
	 */
	function insertContext(&$context) {
		$this->update(
			'INSERT INTO rt_contexts
			(version_id, title, abbrev, description, cited_by, author_terms, geo_terms, define_terms, seq)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				(int) $context->versionId,
				$context->title,
				$context->abbrev,
				$context->description,
				$context->citedBy?1:0,
				$context->authorTerms?1:0,
				$context->geoTerms?1:0,
				$context->defineTerms?1:0,
				(int) $context->order
			)
		);

		$context->contextId = $this->getInsertId('rt_contexts', 'context_id');

		foreach ($context->searches as $search) {
			$search->contextId = $context->contextId;
			$this->insertSearch($search);
		}

		return $context->contextId;
	}

	/**
	 * Update an existing context.
	 * @param $context RTContext
	 */
	function updateContext(&$context) {
		// FIXME Update searches?
		return $this->update(
			'UPDATE rt_contexts
			SET title = ?, abbrev = ?, description = ?, cited_by = ?, author_terms = ?, geo_terms = ?, define_terms = ?, seq = ?
			WHERE context_id = ? AND version_id = ?',
			array($context->title, $context->abbrev, $context->description, $context->citedBy?1:0, $context->authorTerms?1:0, $context->geoTerms?1:0, $context->defineTerms?1:0, (int) $context->order, (int) $context->contextId, (int) $context->versionId)
		);
	}

	/**
	 * Delete all contexts by version ID.
	 * @param $versionId int
	 */
	function deleteContextsByVersionId($versionId) {
		$contexts =& $this->getContexts($versionId);
		foreach ($contexts->toArray() as $context) {
			$this->deleteContext(
				$context->getContextId(),
				$context->getVersionId()
			);
		}
	}

	/**
	 * Delete a context.
	 * @param $contextId int
	 * @param $versionId int
	 */
	function deleteContext($contextId, $versionId) {
		$result = $this->update(
			'DELETE FROM rt_contexts WHERE context_id = ? AND version_id = ?',
			array((int) $contextId, (int) $versionId)
		);
		if ($result) $this->deleteSearchesByContextId($contextId);
		return $result;
	}

	/**
	 * Sequentially renumber contexts in their sequence order.
	 */
	function resequenceContexts($versionId) {
		$result =& $this->retrieve(
			'SELECT context_id FROM rt_contexts WHERE version_id = ? ORDER BY seq',
			array((int) $versionId)
		);

		for ($i=1; !$result->EOF; $i++) {
			list($contextId) = $result->fields;
			$this->update(
				'UPDATE rt_contexts SET seq = ? WHERE context_id = ?',
				array(
					$i,
					$contextId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Return RTContext object from database row.
	 * @param $row array
	 * @return RTContext
	 */
	function &_returnContextFromRow(&$row) {
		$context = new RTContext();
		$context->setContextId($row['context_id']);
		$context->setVersionId($row['version_id']);
		$context->setTitle($row['title']);
		$context->setAbbrev($row['abbrev']);
		$context->setDescription($row['description']);
		$context->setCitedBy($row['cited_by']);
		$context->setAuthorTerms($row['author_terms']);
		$context->setGeoTerms($row['geo_terms']);
		$context->setDefineTerms($row['define_terms']);
		$context->setOrder($row['seq']);

		if (!HookRegistry::call('RTDAO::_returnContextFromRow', array(&$context, &$row))) {
			$searchesIterator =& $this->getSearches($row['context_id']);
			$context->setSearches($searchesIterator->toArray());
		}

		return $context;
	}



	//
	// RT Searches
	//

	/**
	 * Retrieve an RT search.
	 * @param $searchId int
	 * @return RTSearch
	 */
	function &getSearch($searchId) {
		$result =& $this->retrieve(
			'SELECT * FROM rt_searches WHERE search_id = ?',
			array((int) $searchId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSearchFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all RT searches for a context (in order).
	 * @param $contextId int
	 * @param $pagingInfo object DBResultRange (optional)
	 * @return array RTSearch
	 */
	function &getSearches($contextId, $pagingInfo = null) {
		$searches = array();

		$result =& $this->retrieveRange(
			'SELECT * FROM rt_searches WHERE context_id = ? ORDER BY seq',
			array((int) $contextId),
			$pagingInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSearchFromRow');
		return $returner;
	}

	/**
	 * Insert new search.
	 * @param $search RTSearch
	 */
	function insertSearch(&$search) {
		$this->update(
			'INSERT INTO rt_searches
			(context_id, title, description, url, search_url, search_post, seq)
			VALUES
			(?, ?, ?, ?, ?, ?, ?)',
			array(
				(int) $search->getContextId(),
				$search->getTitle(),
				$search->getDescription(),
				$search->getUrl(),
				$search->getSearchUrl(),
				$search->getSearchPost(),
				(int) $search->getOrder()
			)
		);

		$search->searchId = $this->getInsertId('rt_searches', 'search_id');
		return $search->searchId;
	}

	/**
	 * Update an existing search.
	 * @param $search RTSearch
	 */
	function updateSearch(&$search) {
		return $this->update(
			'UPDATE rt_searches
			SET title = ?, description = ?, url = ?, search_url = ?, search_post = ?, seq = ?
			WHERE search_id = ? AND context_id = ?',
			array(
				$search->getTitle(),
				$search->getDescription(),
				$search->getUrl(),
				$search->getSearchUrl(),
				$search->getSearchPost(),
				(int) $search->getOrder(),
				(int) $search->getSearchId(),
				(int) $search->getContextId()
			)
		);
	}

	/**
	 * Delete all searches by context ID.
	 * @param $contextId int
	 */
	function deleteSearchesByContextId($contextId) {
		return $this->update(
			'DELETE FROM rt_searches WHERE context_id = ?',
			array((int) $contextId)
		);
	}

	/**
	 * Delete a search.
	 * @param $searchId int
	 * @param $contextId int
	 */
	function deleteSearch($searchId, $contextId) {
		return $this->update(
			'DELETE FROM rt_searches WHERE search_id = ? AND context_id = ?',
			array((int) $searchId, (int) $contextId)
		);
	}

	/**
	 * Sequentially renumber searches in their sequence order.
	 */
	function resequenceSearches($contextId) {
		$result =& $this->retrieve(
			'SELECT search_id FROM rt_searches WHERE context_id = ? ORDER BY seq',
			array((int) $contextId)
		);

		for ($i=1; !$result->EOF; $i++) {
			list($searchId) = $result->fields;
			$this->update(
				'UPDATE rt_searches SET seq = ? WHERE search_id = ?',
				array(
					$i,
					$searchId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}
}

?>
