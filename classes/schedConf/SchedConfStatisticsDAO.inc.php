<?php

/**
 * @file SchedConfStatisticsDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfStatisticsDAO
 * @ingroup schedConf
 *
 * @brief Operations for retrieving scheduled conference statistics.
 */

//$Id$

define('REPORT_TYPE_CONFERENCE',	0x00001);
define('REPORT_TYPE_SCHED_CONF',	0x00002);
define('REPORT_TYPE_DIRECTOR',		0x00003);
define('REPORT_TYPE_REVIEWER',		0x00004);
define('REPORT_TYPE_TRACK',		0x00005);

class SchedConfStatisticsDAO extends DAO {
	/**
	 * Get statistics about papers in the system.
	 * Returns a map of name => value pairs.
	 * @param $schedConfId int The scheduled conference to fetch statistics for
	 * @param $trackId int The track to query stats for (optional)
	 * @param $dateStart date The submit date to search from; optional
	 * @param $dateEnd date The submit date to search to; optional
	 * @return array
	 */
	function getPaperStatistics($schedConfId, $trackIds = null, $dateStart = null, $dateEnd = null) {
		// Bring in status constants
		import('paper.Paper');

		$params = array($schedConfId);
		if (!empty($trackIds)) {
			$trackSql = ' AND (a.track_id = ?';
			$params[] = array_shift($trackIds);
			foreach ($trackIds as $trackId) {
				$trackSql .= ' OR a.track_id = ?';
				$params[] = $trackId;
			}
			$trackSql .= ')';
		} else $trackSql = '';

		$sql =	'SELECT	a.paper_id,
				a.date_submitted,
				pa.date_published,
				pa.pub_id,
				d.decision,
				a.status
			FROM	papers a
				LEFT JOIN published_papers pa ON (a.paper_id = pa.paper_id)
				LEFT JOIN edit_decisions d ON (d.paper_id = a.paper_id)
			WHERE	a.sched_conf_id = ?' .
			($dateStart !== null ? ' AND a.date_submitted >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND a.date_submitted <= ' . $this->datetimeToDB($dateEnd) : '') .
			$trackSql .
			' ORDER BY a.paper_id, d.date_decided DESC';

		$result =& $this->retrieve($sql, $params);

		$returner = array(
			'numSubmissions' => 0,
			'numReviewedSubmissions' => 0,
			'numPublishedSubmissions' => 0,
			'submissionsAccept' => 0,
			'submissionsDecline' => 0,
			'submissionsRevise' => 0,
			'submissionsAcceptPercent' => 0,
			'submissionsDeclinePercent' => 0,
			'submissionsRevisePercent' => 0,
			'daysToPublication' => 0
		);

		// Track which papers we're including
		$paperIds = array();

		$totalTimeToPublication = 0;
		$timeToPublicationCount = 0;

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			// For each paper, pick the most recent director
			// decision only and ignore the rest. Depends on sort
			// order. FIXME -- there must be a better way of doing
			// this that's database independent.
			if (!in_array($row['paper_id'], $paperIds)) {
				$paperIds[] = $row['paper_id'];
				$returner['numSubmissions']++;

				if (!empty($row['pub_id']) && $row['status'] == STATUS_PUBLISHED) {
					$returner['numPublishedSubmissions']++;
				}

				if (!empty($row['date_submitted']) && !empty($row['date_published']) && $row['status'] == STATUS_PUBLISHED) {
					$timeSubmitted = strtotime($this->datetimeFromDB($row['date_submitted']));
					$timePublished = strtotime($this->datetimeFromDB($row['date_published']));
					if ($timePublished > $timeSubmitted) {
						$totalTimeToPublication += ($timePublished - $timeSubmitted);
						$timeToPublicationCount++;
					}
				}

				import('submission.common.Action');
				switch ($row['decision']) {
					case SUBMISSION_DIRECTOR_DECISION_ACCEPT:
						$returner['submissionsAccept']++;
						$returner['numReviewedSubmissions']++;
						break;
					case SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS:
						$returner['submissionsRevise']++;
						break;
					case SUBMISSION_DIRECTOR_DECISION_DECLINE:
						$returner['submissionsDecline']++;
						$returner['numReviewedSubmissions']++;
						break;
				}
			}

			$result->moveNext();
		}

		$result->Close();
		unset($result);

		// Calculate percentages where necessary
		if ($returner['numReviewedSubmissions'] != 0) {
			$returner['submissionsAcceptPercent'] = round($returner['submissionsAccept'] * 100 / $returner['numReviewedSubmissions']);
			$returner['submissionsDeclinePercent'] = round($returner['submissionsDecline'] * 100 / $returner['numReviewedSubmissions']);
			$returner['submissionsRevisePercent'] = round($returner['submissionsRevise'] * 100 / $returner['numReviewedSubmissions']);
		}

		if ($timeToPublicationCount != 0) {
			// Keep one sig fig
			$returner['daysToPublication'] = round($totalTimeToPublication / $timeToPublicationCount / 60 / 60 / 24);
		}

		return $returner;
	}

	/**
	 * Get statistics about users in the system.
	 * Returns a map of name => value pairs.
	 * @param $schedConfId int The scheduled conference to fetch statistics for
	 * @param $dateStart date optional
	 * @param $dateEnd date optional
	 * @return array
	 */
	function getUserStatistics($schedConfId, $dateStart = null, $dateEnd = null) {
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		// Get count of total users for this scheduled conference
		$result =& $this->retrieve(
			'SELECT COUNT(DISTINCT r.user_id) FROM roles r, users u WHERE r.user_id = u.user_id AND r.sched_conf_id = ?' .
			($dateStart !== null ? ' AND u.date_registered >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND u.date_registered <= ' . $this->datetimeToDB($dateEnd) : ''),
			$schedConfId
		);

		$returner = array(
			'totalUsersCount' => $result->fields[0]
		);

		$result->Close();
		unset($result);

		// Get user counts for each role.
		$result =& $this->retrieve(
			'SELECT r.role_id, COUNT(r.user_id) AS role_count FROM roles r LEFT JOIN users u ON (r.user_id = u.user_id) WHERE r.sched_conf_id = ?' .
			($dateStart !== null ? ' AND u.date_registered >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND u.date_registered <= ' . $this->datetimeToDB($dateEnd) : '') .
			'GROUP BY r.role_id',
			$schedConfId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$roleDao->getRolePath($row['role_id'])] = $row['role_count'];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get statistics about registrations.
	 * @param $schedConfId int The scheduled conference to fetch statistics for
	 * @param $dateStart date optional
	 * @param $dateEnd date optional
	 * @return array
	 */
	function getRegistrationStatistics($schedConfId, $dateStart = null, $dateEnd = null) {
		$result =& $this->retrieve(
			'SELECT	st.type_id,
				rts.setting_value AS type_name,
				COUNT(s.registration_id) AS type_count
			FROM	registration_types st
				LEFT JOIN sched_confs sc ON (st.sched_conf_id = sc.sched_conf_id)
				LEFT JOIN conferences c ON (sc.conference_id = c.conference_id)
				LEFT JOIN registration_type_settings rts ON (rts.type_id = st.type_id AND rts.setting_name = ? AND rts.locale = c.primary_locale),
				registrations s
			WHERE	st.sched_conf_id = ? AND
				s.type_id = st.type_id' .
				($dateStart !== null ? ' AND st.opening_date >= ' . $this->datetimeToDB($dateStart) : '') .
				($dateEnd !== null ? ' AND st.closing_date <= ' . $this->datetimeToDB($dateEnd) : '') .
			' GROUP BY st.type_id, rts.setting_value',
			array('name', $schedConfId)
		);

		$returner = array();

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$returner[$row['type_id']] = array(
				'name' => $row['type_name'],
				'count' => $row['type_count']
			);
			$result->moveNext();
		}
		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get statistics about issues in the system.
	 * Returns a map of name => value pairs.
	 * @param $schedConfId int The scheduled conference to fetch statistics for
	 * @param $dateStart date The publish date to search from; optional
	 * @param $dateEnd date The publish date to search to; optional
	 * @return array
	 */
	function getIssueStatistics($schedConfId, $dateStart = null, $dateEnd = null) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) AS count, published FROM issues WHERE sched_conf_id = ?' .
			($dateStart !== null ? ' AND date_published >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND date_published <= ' . $this->datetimeToDB($dateEnd) : '') .
			' GROUP BY published',
			$schedConfId
		);

		$returner = array(
			'numPublishedIssues' => 0,
			'numUnpublishedIssues' => 0
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			if ($row['published']) {
				$returner['numPublishedIssues'] = $row['count'];
			} else {
				$returner['numUnpublishedIssues'] = $row['count'];
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		$returner['numIssues'] = $returner['numPublishedIssues'] + $returner['numUnpublishedIssues'];

		return $returner;
	}

	/**
	 * Get statistics about reviewers in the system.
	 * Returns a map of name => value pairs.
	 * @param $schedConfId int The scheduled conference to fetch statistics for
	 * @param $dateStart date The publish date to search from; optional
	 * @param $dateEnd date The publish date to search to; optional
	 * @return array
	 */
	function getReviewerStatistics($schedConfId, $trackIds, $dateStart = null, $dateEnd = null) {
		$params = array($schedConfId);
		if (!empty($trackIds)) {
			$trackSql = ' AND (a.track_id = ?';
			$params[] = array_shift($trackIds);
			foreach ($trackIds as $trackId) {
				$trackSql .= ' OR a.track_id = ?';
				$params[] = $trackId;
			}
			$trackSql .= ')';
		} else $trackSql = '';

		$sql =	'SELECT	a.paper_id,
				af.date_uploaded AS date_rv_uploaded,
				r.review_id,
				u.date_registered,
				r.reviewer_id,
				r.quality AS quality,
				r.date_assigned,
				r.date_completed
			FROM	papers a,
				paper_files af,
				review_assignments r
				LEFT JOIN users u ON (u.user_id = r.reviewer_id)
			WHERE	a.sched_conf_id = ? AND
				r.paper_id = a.paper_id AND
				af.paper_id = a.paper_id AND
				af.file_id = a.review_file_id AND
				af.revision = 1' .
			($dateStart !== null ? ' AND a.date_submitted >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND a.date_submitted <= ' . $this->datetimeToDB($dateEnd) : '') .
			$trackSql;
		$result =& $this->retrieve($sql, $params);

		$returner = array(
			'reviewsCount' => 0,
			'reviewerScore' => 0,
			'daysPerReview' => 0,
			'reviewerAddedCount' => 0,
			'reviewerCount' => 0,
			'reviewedSubmissionsCount' => 0
		);

		$scoredReviewsCount = 0;
		$totalScore = 0;
		$completedReviewsCount = 0;
		$totalElapsedTime = 0;
		$reviewerList = array();
		$paperIds = array();

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner['reviewsCount']++;
			if (!empty($row['quality'])) {
				$scoredReviewsCount++;
				$totalScore += $row['quality'];
			}

			$paperIds[] = $row['paper_id'];

			if (!empty($row['reviewer_id']) && !in_array($row['reviewer_id'], $reviewerList)) {
				$returner['reviewerCount']++;
				$dateRegistered = strtotime($this->datetimeFromDB($row['date_registered']));
				if (($dateRegistered >= $dateStart || $dateStart === null) && ($dateRegistered <= $dateEnd || $dateEnd == null)) {
					$returner['reviewerAddedCount']++;
				}
				array_push($reviewerList, $row['reviewer_id']);
			}

			if (!empty($row['date_assigned']) && !empty($row['date_completed'])) {
				$timeReviewVersionUploaded = strtotime($this->datetimeFromDB($row['date_rv_uploaded']));
				$timeCompleted = strtotime($this->datetimeFromDB($row['date_completed']));
				if ($timeCompleted > $timeReviewVersionUploaded) {
					$completedReviewsCount++;
					$totalElapsedTime += ($timeCompleted - $timeReviewVersionUploaded);
				}
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		if ($scoredReviewsCount > 0) {
			// To one decimal place
			$returner['reviewerScore'] = round($totalScore * 10 / $scoredReviewsCount) / 10;
		}
		if ($completedReviewsCount > 0) {
			$seconds = $totalElapsedTime / $completedReviewsCount;
			$returner['daysPerReview'] = $seconds / 60 / 60 / 24;
		}

		$paperIds = array_unique($paperIds);
		$returner['reviewedSubmissionsCount'] = count($paperIds);

		return $returner;
	}
}

?>
