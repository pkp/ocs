<?php

/**
 * @file PaperComment.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperComment
 * @ingroup paper
 * @see PaperCommentDAO
 *
 * @brief Class for PaperComment.
 */


/** Comment associative types. All types must be defined here. */
define('COMMENT_TYPE_PEER_REVIEW', 0x01);
define('COMMENT_TYPE_DIRECTOR_DECISION', 0x02);

class PaperComment extends DataObject {

	/**
	 * Constructor.
	 */
	function PaperComment() {
		parent::DataObject();
	}

	/**
	 * get comment type
	 * @return int
	 */
	function getCommentType() {
		return $this->getData('commentType');
	}

	/**
	 * set comment type
	 * @param $commentType int
	 */
	function setCommentType($commentType) {
		return $this->setData('commentType', $commentType);
	}

	/**
	 * get role id
	 * @return int
	 */
	function getRoleId() {
		return $this->getData('roleId');
	}

	/**
	 * set role id
	 * @param $roleId int
	 */
	function setRoleId($roleId) {
		return $this->setData('roleId', $roleId);
	}

	/**
	 * get role name
	 * @return string
	 */
	function getRoleName() {
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleName = $roleDao->getRoleName($this->getData('roleId'));

		return $roleName;
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
	 * get assoc id
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * set assoc id
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		return $this->setData('assocId', $assocId);
	}	

	/**
	 * get author id
	 * @return int
	 */
	function getAuthorId() {
		return $this->getData('authorId');
	}

	/**
	 * set author id
	 * @param $authorId int
	 */
	function setAuthorId($authorId) {
		return $this->setData('authorId', $authorId);
	}

	/**
	 * get author name
	 * @return string
	 */
	function getAuthorName() {
		static $authorFullName;

		if(!isset($authorFullName)) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$authorFullName = $userDao->getUserFullName($this->getAuthorId(), true);
		}

		return $authorFullName ? $authorFullName : '';
	}

	/**
	 * get author email
	 * @return string
	 */
	function getAuthorEmail() {
		static $authorEmail;

		if(!isset($authorEmail)) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$authorEmail = $userDao->getUserEmail($this->getAuthorId(), true);
		}

		return $authorEmail ? $authorEmail : '';
	}

	/**
	 * get comment title
	 * @return string
	 */
	function getCommentTitle() {
		return $this->getData('commentTitle');
	}

	/**
	 * set comment title
	 * @param $commentTitle string
	 */
	function setCommentTitle($commentTitle) {
		return $this->setData('commentTitle', $commentTitle);
	}

	/**
	 * get comments
	 * @return string
	 */
	function getComments() {
		return $this->getData('comments');
	}

	/**
	 * set comments
	 * @param $comments string
	 */
	function setComments($comments) {
		return $this->setData('comments', $comments);
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
	 * get viewable
	 * @return boolean
	 */
	function getViewable() {
		return $this->getData('viewable');
	}

	/**
	 * set viewable
	 * @param $viewable boolean
	 */
	function setViewable($viewable) {
		return $this->setData('viewable', $viewable);
	}
 }

?>
