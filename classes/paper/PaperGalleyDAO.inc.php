<?php

/**
 * @file PaperGalleyDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperGalleyDAO
 * @ingroup paper
 * @see Papergalley
 *
 * @brief Operations for retrieving and modifying PaperGalley/PaperHTMLGalley objects.
 */

//$Id$

import('paper.PaperGalley');
import('paper.PaperHTMLGalley');

class PaperGalleyDAO extends DAO {
	/** Helper file DAOs. */
	var $paperFileDao;

	/**
	 * Constructor.
	 */
	function PaperGalleyDAO() {
		parent::DAO();
		$this->paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
	}

	/**
	 * Retrieve a galley by ID.
	 * @param $galleyId int
	 * @param $paperId int optional
	 * @return PaperGalley
	 */
	function &getGalley($galleyId, $paperId = null) {
		if (isset($paperId)) {
			$result =& $this->retrieve(
				'SELECT g.*,
				a.file_name, a.original_file_name, a.file_type, a.file_size, a.date_uploaded, a.date_modified
				FROM paper_galleys g
				LEFT JOIN paper_files a ON (g.file_id = a.file_id)
				WHERE g.galley_id = ? AND g.paper_id = ?',
				array($galleyId, $paperId)
			);

		} else {
			$result =& $this->retrieve(
				'SELECT g.*,
				a.file_name, a.original_file_name, a.file_type, a.file_size, a.date_uploaded, a.date_modified
				FROM paper_galleys g
				LEFT JOIN paper_files a ON (g.file_id = a.file_id)
				WHERE g.galley_id = ?',
				$galleyId
			);
		}

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all galleys for a paper.
	 * @param $paperId int
	 * @return array PaperGalleys
	 */
	function &getGalleysByPaper($paperId) {
		$galleys = array();

		$result =& $this->retrieve(
			'SELECT g.*,
			a.file_name, a.original_file_name, a.file_type, a.file_size, a.date_uploaded, a.date_modified
			FROM paper_galleys g
			LEFT JOIN paper_files a ON (g.file_id = a.file_id)
			WHERE g.paper_id = ? ORDER BY g.seq',
			$paperId
		);

		while (!$result->EOF) {
			$galleys[] =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		HookRegistry::call('PaperGalleyDAO::getGalleysByPaper', array(&$galleys, &$paperId));

		return $galleys;
	}

	/**
	 * Internal function to return an PaperGalley object from a row.
	 * @param $row array
	 * @return PaperGalley
	 */
	function &_returnGalleyFromRow(&$row) {
		if ($row['html_galley']) {
			$galley = new PaperHTMLGalley();

			// HTML-specific settings
			$galley->setStyleFileId($row['style_file_id']);
			if ($row['style_file_id']) {
				$galley->setStyleFile($this->paperFileDao->getPaperFile($row['style_file_id']));
			}

			// Retrieve images
			$images =& $this->getGalleyImages($row['galley_id']);
			$galley->setImageFiles($images); 

		} else {
			$galley = new PaperGalley();
		}
		$galley->setId($row['galley_id']);
		$galley->setPaperId($row['paper_id']);
		$galley->setLocale($row['locale']);
		$galley->setFileId($row['file_id']);
		$galley->setLabel($row['label']);
		$galley->setSequence($row['seq']);
		$galley->setViews($row['views']);

		// PaperFile set methods
		$galley->setFileName($row['file_name']);
		$galley->setOriginalFileName($row['original_file_name']);
		$galley->setFileType($row['file_type']);
		$galley->setFileSize($row['file_size']);
		$galley->setDateModified($this->datetimeFromDB($row['date_modified']));
		$galley->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));

		HookRegistry::call('PaperGalleyDAO::_returnGalleyFromRow', array(&$galley, &$row));

		return $galley;
	}

	/**
	 * Insert a new PaperGalley.
	 * @param $galley PaperGalley
	 */	
	function insertGalley(&$galley) {
		$this->update(
			'INSERT INTO paper_galleys
				(paper_id, file_id, label, locale, html_galley, style_file_id, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$galley->getPaperId(),
				$galley->getFileId(),
				$galley->getLabel(),
				$galley->getLocale(),
				(int)$galley->isHTMLGalley(),
				$galley->isHTMLGalley() ? $galley->getStyleFileId() : null,
				$galley->getSequence() == null ? $this->getNextGalleySequence($galley->getPaperId()) : $galley->getSequence()
			)
		);
		$galley->setId($this->getInsertGalleyId());
		return $galley->getId();
	}

	/**
	 * Update an existing PaperGalley.
	 * @param $galley PaperGalley
	 */
	function updateGalley(&$galley) {
		return $this->update(
			'UPDATE paper_galleys
				SET
					file_id = ?,
					label = ?,
					locale = ?,
					html_galley = ?,
					style_file_id = ?,
					seq = ?
				WHERE galley_id = ?',
			array(
				$galley->getFileId(),
				$galley->getLabel(),
				$galley->getLocale(),
				(int)$galley->isHTMLGalley(),
				$galley->isHTMLGalley() ? $galley->getStyleFileId() : null,
				$galley->getSequence(),
				$galley->getId()
			)
		);
	}

	/**
	 * Delete an PaperGalley.
	 * @param $galley PaperGalley
	 */
	function deleteGalley(&$galley) {
		return $this->deleteGalleyById($galley->getId());
	}

	/**
	 * Delete a galley by ID.
	 * @param $galleyId int
	 * @param $paperId int optional
	 */
	function deleteGalleyById($galleyId, $paperId = null) {
		$this->deleteImagesByGalley($galleyId);
		if (isset($paperId)) {
			return $this->update(
				'DELETE FROM paper_galleys WHERE galley_id = ? AND paper_id = ?',
				array($galleyId, $paperId)
			);

		} else {
			return $this->update(
				'DELETE FROM paper_galleys WHERE galley_id = ?', $galleyId
			);
		}
	}

	/**
	 * Delete galleys (and dependent galley image entries) by paper.
	 * NOTE that this will not delete paper_file entities or the respective files.
	 * @param $paperId int
	 */
	function deleteGalleysByPaper($paperId) {
		$galleys =& $this->getGalleysByPaper($paperId);
		foreach ($galleys as $galley) {
			$this->deleteGalleyById($galley->getId(), $paperId);
		}
	}

	/**
	 * Check if a galley exists with the associated file ID.
	 * @param $paperId int
	 * @param $fileId int
	 * @return boolean
	 */
	function galleyExistsByFileId($paperId, $fileId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM paper_galleys
			WHERE paper_id = ? AND file_id = ?',
			array($paperId, $fileId)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Increment the views count for a galley.
	 * @param $galleyId int
	 */
	function incrementViews($galleyId) {
		return $this->update(
			'UPDATE paper_galleys SET views = views + 1 WHERE galley_id = ?',
			$galleyId
		);
	}

	/**
	 * Sequentially renumber galleys for a paper in their sequence order.
	 * @param $paperId int
	 */
	function resequenceGalleys($paperId) {
		$result =& $this->retrieve(
			'SELECT galley_id FROM paper_galleys WHERE paper_id = ? ORDER BY seq',
			$paperId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($galleyId) = $result->fields;
			$this->update(
				'UPDATE paper_galleys SET seq = ? WHERE galley_id = ?',
				array($i, $galleyId)
			);
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the the next sequence number for a paper's galleys (i.e., current max + 1).
	 * @param $paperId int
	 * @return int
	 */
	function getNextGalleySequence($paperId) {
		$result =& $this->retrieve(
			'SELECT MAX(seq) + 1 FROM paper_galleys WHERE paper_id = ?',
			$paperId
		);
		$returner = floor($result->fields[0]);

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted gallery.
	 * @return int
	 */
	function getInsertGalleyId() {
		return $this->getInsertId('paper_galleys', 'galley_id');
	}


	//
	// Extra routines specific to HTML galleys.
	//

	/**
	 * Retrieve array of the images for an HTML galley.
	 * @param $galleyId int
	 * @return array PaperFile
	 */
	function &getGalleyImages($galleyId) {
		$images = array();

		$result =& $this->retrieve(
			'SELECT a.* FROM paper_html_galley_images i, paper_files a
			WHERE i.file_id = a.file_id AND i.galley_id = ?',
			$galleyId
		);

		while (!$result->EOF) {
			$images[] =& $this->paperFileDao->_returnPaperFileFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $images;
	}

	/**
	 * Attach an image to an HTML galley.
	 * @param $galleyId int
	 * @param $fileId int
	 */
	function insertGalleyImage($galleyId, $fileId) {
		return $this->update(
			'INSERT INTO paper_html_galley_images
			(galley_id, file_id)
			VALUES
			(?, ?)',
			array($galleyId, $fileId)
		);
	}

	/**
	 * Delete an image from an HTML galley.
	 * @param $galleyId int
	 * @param $fileId int
	 */
	function deleteGalleyImage($galleyId, $fileId) {
		return $this->update(
			'DELETE FROM paper_html_galley_images
			WHERE galley_id = ? AND file_id = ?',
			array($galleyId, $fileId)
		);
	}

	/**
	 * Delete HTML galley images by galley.
	 * @param $galleyId int
	 */
	function deleteImagesByGalley($galleyId) {
		return $this->update(
			'DELETE FROM paper_html_galley_images WHERE galley_id = ?',
			$galleyId
		);
	}
}

?>
