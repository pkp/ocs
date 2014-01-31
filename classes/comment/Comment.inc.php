<?php

/**
 * @defgroup comment
 */
 
/**
 * @file Comment.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Comment
 * @ingroup comment
 * @see CommentDAO
 *
 * @brief Class for public Comment associated with paper.
 */

//$Id$

class Comment extends DataObject {

	/**
	 * Constructor.
	 */
	function Comment() {
		parent::DataObject();
		$this->setPosterIP(Request::getRemoteAddr());
	}

	/**
	 * get paper comment id
	 * @return int
	 */
	function getCommentId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * set paper comment id
	 * @param $commentId int
	 */
	function setCommentId($commentId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($commentId);
	}

	/**
	 * get number of child comments
	 * @return int
	 */
	function getChildCommentCount() {
		return $this->getData('childCommentCount');
	}

	/**
	 * set number of child comments
	 * @param $childCommentCount int
	 */
	function setChildCommentCount($childCommentCount) {
		return $this->setData('childCommentCount', $childCommentCount);
	}

	/**
	 * get parent comment id
	 * @return int
	 */
	function getParentCommentId() {
		return $this->getData('parentCommentId');
	}

	/**
	 * set parent comment id
	 * @param $parentCommentId int
	 */
	function setParentCommentId($parentCommentId) {
		return $this->setData('parentCommentId', $parentCommentId);
	}

	/**
	 * get paper id
	 * @return int
	 */
	function getPaperId() {
		return $this->getData('paperId');
	}

	/**
	 * set paper id
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		return $this->setData('paperId', $paperId);
	}

	/**
	 * get user id
	 * @return int
	 */
	function getUser() {
		return $this->getData('user');
	}

	/**
	 * set user id
	 * @param $user int
	 */
	function setUser($user) {
		return $this->setData('user', $user);
	}

	/**
	 * get poster name
	 */
	function getPosterName() {
		return $this->getData('posterName');
	}

	/**
	 * set poster name
	 * @param $posterName string
	 */
	function setPosterName($posterName) {
		return $this->setData('posterName', $posterName);
	}

	/**
	 * get poster email
	 */
	function getPosterEmail() {
		return $this->getData('posterEmail');
	}

	/**
	 * set poster email
	 * @param $posterEmail string
	 */
	function setPosterEmail($posterEmail) {
		return $this->setData('posterEmail', $posterEmail);
	}

	/**
	 * get posterIP
	 * @return string
	 */
	function getPosterIP() {
		return $this->getData('posterIP');
	}

	/**
	 * set posterIP
	 * @param $posterIP string
	 */
	function setPosterIP($posterIP) {
		return $this->setData('posterIP', $posterIP);
	}

	/**
	 * get title
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}

	/**
	 * set title
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title', $title);
	}

	/**
	 * get comment body
	 * @return string
	 */
	function getBody() {
		return $this->getData('body');
	}

	/**
	 * set comment body
	 * @param $body string
	 */
	function setBody($body) {
		return $this->setData('body', $body);
	}

 	/**
	 * get date posted
	 * @return date
	 */
	function getDatePosted() {
		return $this->getData('datePosted');
	}

	/**
	 * set date posted
	 * @param $datePosted date
	 */
	function setDatePosted($datePosted) {
		return $this->setData('datePosted', $datePosted);
	}

 	/**
	 * get date modified
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * set date modified
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		return $this->setData('dateModified', $dateModified);
	}

	/**
	 * get child comments (if fetched using recursive option)
	 * @return array
	 */
	function &getChildren() {
		$children =& $this->getData('children');
		return $children;
	}

	/**
	 * set child comments
	 * @param $children array
	 */
	function setChildren(&$children) {
		$this->setData('children', $children);
	}

 }

?>
