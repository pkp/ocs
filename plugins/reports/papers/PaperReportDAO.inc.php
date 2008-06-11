<?php

/**
 * @file PaperReportDAO.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @package plugins.reports.paper
 * @class PaperReportDAO
 *
 * Paper report DAO
 *
 * $Id$
 */

import('submission.common.Action');

class PaperReportDAO extends DAO {
	/**
	 * Get the paper report data.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @return array
	 */
	function getPaperReport($conferenceId, $schedConfId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$result =& $this->retrieve(
			'SELECT
				p.paper_id AS paper_id,
				COALESCE(psl1.setting_value, pspl1.setting_value) AS title,
				COALESCE(psl2.setting_value, pspl2.setting_value) AS abstract,
				u.first_name AS fname,
				u.middle_name AS mname,
				u.last_name AS lname,
				u.email AS email,
				u.affiliation AS affiliation,
				u.country AS country,
				u.phone AS phone,
				u.fax AS fax,
				u.url AS url,
				u.mailing_address AS address,
				COALESCE(usl.setting_value, uspl.setting_value) AS biography,
				COALESCE(tl.setting_value, tpl.setting_value) AS track_title,
				p.language AS language
			FROM
				papers p
					LEFT JOIN users u ON p.user_id=u.user_id
					LEFT JOIN user_settings uspl ON (u.user_id=uspl.user_id AND uspl.setting_name = ? AND uspl.locale = ?)
					LEFT JOIN user_settings usl ON (u.user_id=usl.user_id AND usl.setting_name = ? AND usl.locale = ?)
					LEFT JOIN paper_settings pspl1 ON (pspl1.paper_id=p.paper_id AND pspl1.setting_name = ? AND pspl1.locale = ?)
					LEFT JOIN paper_settings psl1 ON (psl1.paper_id=p.paper_id AND psl1.setting_name = ? AND psl1.locale = ?)
					LEFT JOIN paper_settings pspl2 ON (pspl2.paper_id=p.paper_id AND pspl2.setting_name = ? AND pspl2.locale = ?)
					LEFT JOIN paper_settings psl2 ON (psl2.paper_id=p.paper_id AND psl2.setting_name = ? AND psl2.locale = ?)
					LEFT JOIN track_settings tpl ON (tpl.track_id=p.track_id AND tpl.setting_name = ? AND tpl.locale = ?)
					LEFT JOIN track_settings tl ON (tl.track_id=p.track_id AND tl.setting_name = ? AND tl.locale = ?)
			WHERE
				p.sched_conf_id = ?
			ORDER BY
				title',
			array(
				'biography',
				$primaryLocale,
				'biography',
				$locale,
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abstract',
				$primaryLocale,
				'abstract',
				$locale,
				'title',
				$primaryLocale,
				'title',
				$locale,
				$schedConfId
			)
		);
		$papersReturner =& new DBRowIterator($result);

		$result =& $this->retrieve(
			'SELECT	MAX(ed.date_decided) AS date,
				ed.paper_id AS paper_id
			FROM	edit_decisions ed,
				papers p
			WHERE	p.sched_conf_id = ? AND
				p.paper_id = ed.paper_id
			GROUP BY paper_id',
			array($schedConfId)
		);
		$decisionDatesIterator =& new DBRowIterator($result);
		$decisions = array();
		$decisionsReturner = array();
		while ($row =& $decisionDatesIterator->next()) {
			$result =& $this->retrieve(
				'SELECT	decision AS decision,
					paper_id AS paper_id
				FROM	edit_decisions
				WHERE	date_decided = ? AND
					paper_id = ?',
				array(
					$row['date'],
					$row['paper_id']
				)
			);
			$decisionsReturner[] =& new DBRowIterator($result);
			unset($result);
		}

		return array($papersReturner, $decisionsReturner);
	}
}

?>
