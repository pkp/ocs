<?php

/**
 * @file ReviewReportDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ReviewReportDAO
 * @ingroup plugins_reports_review
 * @see ReviewReportPlugin
 *
 * @brief Review report DAO
 */



import('classes.paper.PaperComment');
import('lib.pkp.classes.db.DBRowIterator');

class ReviewReportDAO extends DAO {
	/**
	 * Get the review report data.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @return array
	 */
	function getReviewReport($schedConfId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$result =& $this->retrieve(
			'SELECT	paper_id,
				comments,
				author_id
			FROM	paper_comments
			WHERE	comment_type = ?',
			array(
				COMMENT_TYPE_PEER_REVIEW
			)
		);
		import('lib.pkp.classes.db.DBRowIterator');
		$commentsReturner = new DBRowIterator($result);

		$result =& $this->retrieve(
			'SELECT	r.round AS reviewRound,
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
				(r.declined=1) AS declined,
				(r.cancelled=1) AS cancelled,
				r.recommendation AS recommendation
			FROM	review_assignments r
				LEFT JOIN papers p ON (r.submission_id = p.paper_id)
				LEFT JOIN paper_settings psl ON (p.paper_id=psl.paper_id AND psl.locale=? AND psl.setting_name=?)
				LEFT JOIN paper_settings pspl ON (p.paper_id=pspl.paper_id AND pspl.locale=p.locale AND pspl.setting_name=?),
				users u
			WHERE	u.user_id=r.reviewer_id AND p.sched_conf_id= ?
			ORDER BY paper',
			array(
				$locale, // Paper title (current locale)
				'title',
				'title', // Paper title (paper locale)
				$schedConfId
			)
		);
		$reviewsReturner = new DBRowIterator($result);

		return array($commentsReturner, $reviewsReturner);
	}
}

?>
