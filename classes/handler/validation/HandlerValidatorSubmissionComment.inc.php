<?php
/**
 * @file classes/handler/HandlerValidatorSubmissionComment.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HandlerValidatorSubmissionComment
 * @ingroup handler_validation
 *
 * @brief Class to validate that a comment exists (by id) and that the current user has access
 */

import('handler.validation.HandlerValidator');

class HandlerValidatorSubmissionComment extends HandlerValidator {
	var $commentId;
	
	var $user;
	
	/**
	 * Constructor.
	 * @param $handler Handler the associated form
	 * @param $roles array of role id's 
	 * @param $all bool flag for whether all roles must exist or just 1
	 */	 
	function HandlerValidatorSubmissionComment(&$handler, $commentId, $user = null) {
		parent::HandlerValidator($handler);
		$this->commentId = $commentId;
		if ( $user ) {
			$this->user =& $user;
		} else {
			$this->user =& Request::getUser();
		}
	}

	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or validated by user-supplied function.
	 * @return boolean
	 */
	function isValid() {
		$isValid = true;

		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$user =& $this->user;

		$comment =& $paperCommentDao->getPaperCommentById($this->commentId);

		if ($comment == null) {
			$isValid = false;

		} else if ($comment->getAuthorId() != $user->getId()) {
			$isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, null, Request::getRequestedPage());
		}
		
		$handler =& $this->handler;
		$handler->comment =& $comment;		
		return true;
	}
}

?>
