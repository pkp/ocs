<?php

/**
 * @file PaperCommentDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperCommentDAO
 * @ingroup paper
 * @see PaperComment
 *
 * @brief Operations for retrieving and modifying PaperComment objects.
 */


import('classes.paper.PaperComment');

class PaperCommentDAO extends DAO {
	/**
	 * Retrieve PaperComments by paper id
	 * @param $paperId int
	 * @param $commentType int
	 * @return PaperComment objects array
	 */
	function &getPaperComments($paperId, $commentType = null, $assocId = null) {
		$paperComments = array();

		if ($commentType == null) {
			$result =& $this->retrieve(
				'SELECT a.* FROM paper_comments a WHERE paper_id = ? ORDER BY date_posted',	$paperId
			);
		} else {
			if ($assocId == null) {
				$result =& $this->retrieve(
					'SELECT a.* FROM paper_comments a WHERE paper_id = ? AND comment_type = ? ORDER BY date_posted',	array($paperId, $commentType)
				);
			} else {
				$result =& $this->retrieve(
					'SELECT a.* FROM paper_comments a WHERE paper_id = ? AND comment_type = ? AND assoc_id = ? ORDER BY date_posted',
					array($paperId, $commentType, $assocId)
				);
			}				
		}

		while (!$result->EOF) {
			$paperComments[] =& $this->_returnPaperCommentFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $paperComments;
	}

	/**
	 * Retrieve PaperComments by user id
	 * @param $userId int
	 * @return PaperComment objects array
	 */
	function &getPaperCommentsByUserId($userId) {
		$paperComments = array();

		$result =& $this->retrieve(
			'SELECT a.* FROM paper_comments a WHERE author_id = ? ORDER BY date_posted',	$userId
		);

		while (!$result->EOF) {
			$paperComments[] =& $this->_returnPaperCommentFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $paperComments;
	}

	/**
	 * Retrieve most recent PaperComment
	 * @param $paperId int
	 * @param $commentType int
	 * @return PaperComment
	 */
	function getMostRecentPaperComment($paperId, $commentType = null, $assocId = null) {
		if ($commentType == null) {
			$result =& $this->retrieveLimit(
				'SELECT a.* FROM paper_comments a WHERE paper_id = ? ORDER BY date_posted DESC',
				$paperId,
				1
			);
		} else {
			if ($assocId == null) {
				$result =& $this->retrieveLimit(
					'SELECT a.* FROM paper_comments a WHERE paper_id = ? AND comment_type = ? ORDER BY date_posted DESC',
					array($paperId, $commentType),
					1
				);
			} else {
				$result =& $this->retrieveLimit(
					'SELECT a.* FROM paper_comments a WHERE paper_id = ? AND comment_type = ? AND assoc_id = ? ORDER BY date_posted DESC',
					array($paperId, $commentType, $assocId),
					1
				);
			}				
		}

		$returner = null;
		if (isset($result) && $result->RecordCount() != 0) {
			$returner =& $this->_returnPaperCommentFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve Paper Comment by comment id
	 * @param $commentId int
	 * @return PaperComment object
	 */
	function &getPaperCommentById($commentId) {
		$result =& $this->retrieve(
			'SELECT a.* FROM paper_comments a WHERE comment_id = ?', $commentId
		);

		$paperComment =& $this->_returnPaperCommentFromRow($result->GetRowAssoc(false));

		$result->Close();
		unset($result);

		return $paperComment;
	}	

	/**
	 * Creates and returns a paper comment object from a row
	 * @param $row array
	 * @return PaperComment object
	 */
	function &_returnPaperCommentFromRow($row) {
		$paperComment = new PaperComment();
		$paperComment->setId($row['comment_id']);
		$paperComment->setCommentType($row['comment_type']);
		$paperComment->setRoleId($row['role_id']);
		$paperComment->setPaperId($row['paper_id']);
		$paperComment->setAssocId($row['assoc_id']);
		$paperComment->setAuthorId($row['author_id']);
		$paperComment->setCommentTitle($row['comment_title']);
		$paperComment->setComments($row['comments']);
		$paperComment->setDatePosted($this->datetimeFromDB($row['date_posted']));
		$paperComment->setDateModified($this->datetimeFromDB($row['date_modified']));
		$paperComment->setViewable($row['viewable']);

		HookRegistry::call('PaperCommentDAO::_returnPaperCommentFromRow', array(&$paperComment, &$row));

		return $paperComment;
	}

	/**
	 * inserts a new paper comment into paper_comments table
	 * @param PaperComment object
	 * @return Paper Comment Id int
	 */
	function insertPaperComment(&$paperComment) {
		$this->update(
			sprintf('INSERT INTO paper_comments
				(comment_type, role_id, paper_id, assoc_id, author_id, date_posted, date_modified, comment_title, comments, viewable)
				VALUES
				(?, ?, ?, ?, ?, %s, %s, ?, ?, ?)',
				$this->datetimeToDB($paperComment->getDatePosted()), $this->datetimeToDB($paperComment->getDateModified())),
			array(
				$paperComment->getCommentType(),
				$paperComment->getRoleId(),
				$paperComment->getPaperId(),
				$paperComment->getAssocId(),
				$paperComment->getAuthorId(),
				String::substr($paperComment->getCommentTitle(), 0, 255),
				$paperComment->getComments(),
				$paperComment->getViewable() === null ? 0 : $paperComment->getViewable()
			)
		);

		$paperComment->setId($this->getInsertId());
		return $paperComment->getId();		
	}

	/**
	 * Get the ID of the last inserted paper comment.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('paper_comments', 'comment_id');
	}	

	/**
	 * removes a paper comment from paper_comments table
	 * @param PaperComment object
	 */
	function deletePaperComment($paperComment) {
		$this->deletePaperCommentById($paperComment->getId());
	}

	/**
	 * removes a paper note by id
	 * @param noteId int
	 */
	function deletePaperCommentById($commentId) {
		$this->update(
			'DELETE FROM paper_comments WHERE comment_id = ?', $commentId
		);
	}

	/**
	 * Delete all comments for a paper.
	 * @param $paperId int
	 */
	function deletePaperComments($paperId) {
		return $this->update(
			'DELETE FROM paper_comments WHERE paper_id = ?', $paperId
		);
	}

	/**
	 * updates a paper comment
	 * @param paperComment object
	 */
	function updatePaperComment($paperComment) {
		$this->update(
			sprintf('UPDATE paper_comments
				SET
					comment_type = ?,
					role_id = ?,
					paper_id = ?,
					assoc_id = ?,
					author_id = ?,
					date_posted = %s,
					date_modified = %s,
					comment_title = ?,
					comments = ?,
					viewable = ?
				WHERE comment_id = ?',
				$this->datetimeToDB($paperComment->getDatePosted()), $this->datetimeToDB($paperComment->getDateModified())),
			array(
				$paperComment->getCommentType(),
				$paperComment->getRoleId(),
				$paperComment->getPaperId(),
				$paperComment->getAssocId(),
				$paperComment->getAuthorId(),
				String::substr($paperComment->getCommentTitle(), 0, 255),
				$paperComment->getComments(),
				$paperComment->getViewable() === null ? 1 : $paperComment->getViewable(),
				$paperComment->getId()
			)
		);
	}
}

?>
