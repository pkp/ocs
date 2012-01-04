<?php

/**
 * @file CommentDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommentDAO
 * @ingroup paper
 * @see Comment
 *
 * @brief Operations for retrieving and modifying Comment objects.
 */

//$Id$

import('comment.Comment');

define ('PAPER_COMMENT_RECURSE_ALL', -1);

class CommentDAO extends DAO {
	/**
	 * Retrieve Comments by paper id
	 * @param $paperId int
	 * @return Comment objects array
	 */
	function &getRootCommentsByPaperId($paperId, $childLevels = 0) {
		$comments = array();

		$result =& $this->retrieve('SELECT * FROM comments WHERE paper_id = ? AND parent_comment_id IS NULL ORDER BY date_posted', $paperId);

		while (!$result->EOF) {
			$comments[] =& $this->_returnCommentFromRow($result->GetRowAssoc(false), $childLevels);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $comments;
	}

	/**
	 * Retrieve Comments by parent comment id
	 * @param $parentId int
	 * @return Comment objects array
	 */
	function &getCommentsByParentId($parentId, $childLevels = 0) {
		$comments = array();

		$result =& $this->retrieve('SELECT * FROM comments WHERE parent_comment_id = ? ORDER BY date_posted', $parentId);

		while (!$result->EOF) {
			$comments[] =& $this->_returnCommentFromRow($result->GetRowAssoc(false), $childLevels);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $comments;
	}

	/**
	 * Retrieve comments by user id
	 * @param $userId int
	 * @return Comment objects array
	 */
	function &getCommentsByUserId($userId) {
		$comments = array();

		$result =& $this->retrieve('SELECT * FROM comments WHERE user_id = ?', $userId);

		while (!$result->EOF) {
			$comments[] =& $this->_returnCommentFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $comments;
	}

	/**
	 * Retrieve Comment by comment id
	 * @param $commentId int
	 * @return Comment object
	 */
	function &getComment($commentId, $paperId, $childLevels = 0) {
		$result =& $this->retrieve(
			'SELECT * FROM comments WHERE comment_id = ? and paper_id = ?', array($commentId, $paperId)
		);

		$comment = null;
		if ($result->RecordCount() != 0) {
			$comment =& $this->_returnCommentFromRow($result->GetRowAssoc(false), $childLevels);
		}

		$result->Close();
		unset($result);

		return $comment;
	}	

	/**
	 * Creates and returns a paper comment object from a row
	 * @param $row array
	 * @return Comment object
	 */
	function &_returnCommentFromRow($row, $childLevels = 0) {
		$userDao =& DAORegistry::getDAO('UserDAO');

		$comment = new Comment();
		$comment->setId($row['comment_id']);
		$comment->setPaperId($row['paper_id']);
		$comment->setUser($userDao->getUser($row['user_id']), true);
		$comment->setPosterIP($row['poster_ip']);
		$comment->setPosterName($row['poster_name']);
		$comment->setPosterEmail($row['poster_email']);
		$comment->setTitle($row['title']);
		$comment->setBody($row['body']);
		$comment->setDatePosted($this->datetimeFromDB($row['date_posted']));
		$comment->setDateModified($this->datetimeFromDB($row['date_modified']));
		$comment->setParentCommentId($row['parent_comment_id']);
		$comment->setChildCommentCount($row['num_children']);

		if (!HookRegistry::call('CommentDAO::_returnCommentFromRow', array(&$comment, &$row, &$childLevels))) {
			if ($childLevels>0) $comment->setChildren($this->getCommentsByParentId($row['comment_id'], $childLevels-1));
			else if ($childLevels==SUBMISSION_COMMENT_RECURSE_ALL) $comment->setChildren($this->getCommentsByParentId($row['comment_id'], SUBMISSION_COMMENT_RECURSE_ALL));
		}

		return $comment;
	}

	/**
	 * inserts a new paper comment into paper_comments table
	 * @param Comment object
	 * @return int ID of new comment
	 */
	function insertComment(&$comment) {
		$comment->setDatePosted(Core::getCurrentDate());
		$comment->setDateModified($comment->getDatePosted());
		$user = $comment->getUser();
		$this->update(
			sprintf('INSERT INTO comments
				(paper_id, num_children, parent_comment_id, user_id, poster_ip, date_posted, date_modified, title, body, poster_name, poster_email)
				VALUES
				(?, ?, ?, ?, ?, %s, %s, ?, ?, ?, ?)',
				$this->datetimeToDB($comment->getDatePosted()), $this->datetimeToDB($comment->getDateModified())),
			array(
				$comment->getPaperId(),
				$comment->getChildCommentCount(),
				$comment->getParentCommentId(),
				(isset($user)?$user->getId():null),
				$comment->getPosterIP(),
				String::substr($comment->getTitle(), 0, 255),
				$comment->getBody(),
				String::substr($comment->getPosterName(), 0, 90),
				String::substr($comment->getPosterEmail(), 0, 90)
			)
		);

		$comment->setId($this->getInsertCommentId());

		if ($comment->getParentCommentId()) $this->incrementChildCount($comment->getParentCommentId());

		return $comment->getId();
	}

	/**
	 * Get the ID of the last inserted paper comment.
	 * @return int
	 */
	function getInsertCommentId() {
		return $this->getInsertId('comments', 'comment_id');
	}	

	/**
	 * Increase the current count of child comments for the specified comment.
	 * @param commentId int
	 */
	function incrementChildCount($commentId) {
		$this->update('UPDATE comments SET num_children=num_children+1 WHERE comment_id = ?', $commentId);
	}

	/**
	 * Decrease the current count of child comments for the specified comment.
	 * @param commentId int
	 */
	function decrementChildCount($commentId) {
		$this->update('UPDATE comments SET num_children=num_children-1 WHERE comment_id = ?', $commentId);
	}

	/**
	 * removes a paper comment from paper_comments table
	 * @param Comment object
	 */
	function deleteComment(&$comment, $isRecursing = false) {
		$result = $this->update('DELETE FROM comments WHERE comment_id = ?', $comment->getId());
		if (!$isRecursing) $this->decrementChildCount($comment->getParentCommentId());
		foreach ($comment->getChildren() as $child) {
			$this->deleteComment($child, true);
		}
	}

	/**
	 * removes paper comments by paper ID
	 * @param Comment object
	 */
	function deleteCommentsByPaper($paperId) {
		return $this->update('DELETE FROM comments WHERE paper_id = ?', $paperId);
	}

	/**
	 * updates a comment
	 * @param Comment object
	 */
	function updateComment(&$comment) {
		$comment->setDateModified(Core::getCurrentDate());
		$user = $comment->getUser();
		$this->update(
			sprintf('UPDATE paper_comments
				SET
					paper_id = ?,
					num_children = ?,
					parent_comment_id = ?,
					user_id = ?,
					poster_ip = ?,
					date_posted = %s,
					date_modified = %s,
					title = ?,
					body = ?,
					poster_name = ?,
					poster_email = ?
				WHERE comment_id = ?',
				$this->datetimeToDB($comment->getDatePosted()), $this->datetimeToDB($comment->getDateModified())),
			array(
				$comment->getPaperId(),
				$comment->getChildCommentCount(),
				$comment->getParentCommentId(),
				(isset($user)?$user->getId():null),
				$comment->getPosterIP(),
				String::substr($comment->getTitle(), 0, 255),
				$comment->getBody(),
				String::substr($comment->getPosterName(), 0, 90),
				String::substr($comment->getPosterEmail(), 0, 90),
				$comment->getId()
			)
		);
	}
}

?>
