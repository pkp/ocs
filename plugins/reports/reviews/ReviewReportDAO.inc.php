<?php

/**
 * @file ReviewReportDAO.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ReviewReportDAO
 * @ingroup plugins_reports_review
 * @see ReviewReportPlugin
 *
 * @brief Review report DAO
 */

//$Id$

define('DB_ONE', 1);

import('classes.paper.PaperComment');

class ReviewReportDAO extends DAO {
	/**
	 * Get the review report data.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @return array
	 */
	function getReviewReport($conferenceId, $schedConfId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$result =& $this->retrieve(
			'SELECT
				paper_id,
				comments,
				author_id
			FROM
				paper_comments
			WHERE
				comment_type=?',
			array(
				COMMENT_TYPE_PEER_REVIEW
			)
		);
		$commentsReturner =& new DBRowIterator($result);

		$result =& $this->retrieve(
			'SELECT
				r.stage AS reviewStage,
				COALESCE(psl.setting_value, pspl.setting_value) AS paper,
				p.paper_id AS paperId,
				u.user_id AS reviewerId,
				u.username AS reviewer,
				u.first_name AS firstName,
				u.middle_name AS middleName,
				u.last_name AS lastName,
				r.date_assigned AS dateAssigned,
				r.date_notified AS dateNotified,
				r.date_confirmed AS dateConfirmed,
				r.date_completed AS dateCompleted,
				r.date_reminded AS dateReminded,
				(r.declined=?) AS declined,
				(r.cancelled=?) AS cancelled,
				r.recommendation AS recommendation
			FROM
				review_assignments r
					LEFT JOIN papers p ON r.paper_id=p.paper_id
					LEFT JOIN paper_settings psl ON (p.paper_id=psl.paper_id AND psl.locale=? AND psl.setting_name=?)
					LEFT JOIN paper_settings pspl ON (p.paper_id=pspl.paper_id AND pspl.locale=? AND pspl.setting_name=?),
				users u
			WHERE
				u.user_id=r.reviewer_id AND p.sched_conf_id= ?
			ORDER BY
				paper',
			array(
				DB_ONE,
				DB_ONE,
				$locale,
				'title',
				$primaryLocale,
				'title',
				$schedConfId
			)
		);
		$reviewsReturner =& new DBRowIterator($result);

		return array($commentsReturner, $reviewsReturner);
	}
}

?>
