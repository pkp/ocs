<?php

/**
 * @file PublishedPaper.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedPaper
 * @ingroup paper
 * @see PublishedPaperDAO
 *
 * @brief Published paper class.
 */

//$Id$

import('paper.Paper');

class PublishedPaper extends Paper {

	/**
	 * Constructor.
	 */
	function PublishedPaper() {
		parent::Paper();
	}

	/**
	 * Get ID of published paper.
	 * @return int
	 */
	function getPubId() {
		return $this->getData('pubId');
	}

	/**
	 * Set ID of published paper.
	 * @param $pubId int
	 */
	function setPubId($pubId) {
		return $this->setData('pubId', $pubId);
	}

	/**
	 * Get ID of the scheduled conference this paper is in.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set ID of the scheduled conference this paper is in.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get the room ID of the published paper.
	 * @return int
	 */
	function getRoomId() {
		return $this->getData('roomId');
	}

	/**
	 * Set the room ID of the published paper.
	 * @param $roomId int
	 */
	function setRoomId($roomId) {
		return $this->setData('roomId', $roomId);
	}

	/**
	 * Get track ID of the scheduled conference this paper is in.
	 * @return int
	 */
	function getTrackId() {
		return $this->getData('trackId');
	}

	/**
	 * Set track ID of the scheduled conference this paper is in.
	 * @param $trackId int
	 */
	function setTrackId($trackId) {
		return $this->setData('trackId', $trackId);
	}

	/**
	 * Get date published.
	 * @return date
	 */

	function getDatePublished() {
		return $this->getData('datePublished');	
	}


	/**
	 * Set date published.
	 * @param $datePublished date
	 */

	function setDatePublished($datePublished) {
		return $this->SetData('datePublished', $datePublished);
	}

	/**
	 * Get sequence of paper in table of contents.
	 * @return float
	 */
	function getSeq() {
		return $this->getData('seq');
	}

	/**
	 * Set sequence of paper in table of contents.
	 * @param $sequence float
	 */
	function setSeq($seq) {
		return $this->setData('seq', $seq);
	}

	/**
	 * Get views of the published paper.
	 * @return int
	 */
	function getViews() {
		return $this->getData('views');
	}

	/**
	 * Set views of the published paper.
	 * @param $views int
	 */
	function setViews($views) {
		return $this->setData('views', $views);
	}

	/**
	 * Get the galleys for a paper.
	 * @return array PaperGalley
	 */
	function &getGalleys() {
		$galleys =& $this->getData('galleys');
		return $galleys;
	}

	/**
	 * Get the localized galleys for an paper.
	 * @return array PaperGalley
	 */
	function &getLocalizedGalleys() {
		$primaryLocale = AppLocale::getPrimaryLocale();

		$allGalleys =& $this->getData('galleys');
		$galleys = array();
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $tryLocale) {
			foreach (array_keys($allGalleys) as $key) {
				if ($allGalleys[$key]->getLocale() == $tryLocale) {
					$galleys[] =& $allGalleys[$key];
				}
			}
			if (!empty($galleys)) {
				HookRegistry::call('PaperGalleyDAO::getLocalizedGalleysByPaper', array(&$galleys, &$paperId));
				return $galleys;
			}
		}

		return $galleys;
	}

	/**
	 * Set the galleys for a paper.
	 * @param $galleys array PaperGalley
	 */
	function setGalleys(&$galleys) {
		return $this->setData('galleys', $galleys);
	}

	/**
	 * Get supplementary files for this paper.
	 * @return array SuppFiles
	 */
	function &getSuppFiles() {
		$returner =& $this->getData('suppFiles');
		return $returner;
	}

	/**
	 * Set supplementary file for this paper.
	 * @param $suppFiles array SuppFiles
	 */
	function setSuppFiles($suppFiles) {
		return $this->setData('suppFiles', $suppFiles);
	}

	/**
	 * Get public paper id
	 * @return string
	 */
	function getPublicPaperId() {
		// Ensure that blanks are treated as nulls
		$returner = $this->getData('publicPaperId');
		if ($returner === '') return null;
		return $returner;
	}

	/**
	 * Set public paper id
	 * @param $publicPaperId string
	 */
	function setPublicPaperId($publicPaperId) {
		return $this->setData('publicPaperId', $publicPaperId);
	}

	/**
	 * Return the "best" paper ID -- If a public paper ID is set,
	 * use it; otherwise use the internal paper Id. (Checks the conference
	 * settings to ensure that the public ID feature is enabled.)
	 * @param $conference Object the conference this paper is in
	 * @return string
	 */
	function getBestPaperId($conference = null) {
		// Retrieve the conference, if necessary.
		if (!isset($schedConf)) {
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf = $schedConfDao->getSchedConf($this->getSchedConfId());
		}

		if ($schedConf->getSetting('enablePublicPaperId')) {
			$publicPaperId = $this->getPublicPaperId();
			if (!empty($publicPaperId)) return $publicPaperId;
		}
		return $this->getId();
	}
}

?>
