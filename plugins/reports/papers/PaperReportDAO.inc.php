<?php

/**
 * @file PaperReportDAO.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class PaperReportDAO
 * @ingroup plugins_reports_paper
 * @see PaperReportPlugin
 *
 * @brief Paper report DAO
 *
 */

// $Id$


import('submission.common.Action');
import('db.DBRowIterator');

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
			'SELECT	p.status AS status,
				p.start_time AS start_time,
				p.end_time AS end_time,
				pp.room_id AS room_id,
				p.paper_id AS paper_id,
				COALESCE(psl1.setting_value, pspl1.setting_value) AS title,
				COALESCE(psl2.setting_value, pspl2.setting_value) AS abstract,
				COALESCE(tl.setting_value, tpl.setting_value) AS track_title,
				p.language AS language
			FROM	papers p
				LEFT JOIN published_papers pp ON (p.paper_id = pp.paper_id)
				LEFT JOIN paper_settings pspl1 ON (pspl1.paper_id=p.paper_id AND pspl1.setting_name = ? AND pspl1.locale = ?)
				LEFT JOIN paper_settings psl1 ON (psl1.paper_id=p.paper_id AND psl1.setting_name = ? AND psl1.locale = ?)
				LEFT JOIN paper_settings pspl2 ON (pspl2.paper_id=p.paper_id AND pspl2.setting_name = ? AND pspl2.locale = ?)
				LEFT JOIN paper_settings psl2 ON (psl2.paper_id=p.paper_id AND psl2.setting_name = ? AND psl2.locale = ?)
				LEFT JOIN track_settings tpl ON (tpl.track_id=p.track_id AND tpl.setting_name = ? AND tpl.locale = ?)
				LEFT JOIN track_settings tl ON (tl.track_id=p.track_id AND tl.setting_name = ? AND tl.locale = ?)
			WHERE	p.sched_conf_id = ?
			ORDER BY title',
			array(
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
		$papersReturner = new DBRowIterator($result);
		unset($result);

		$result =& $this->retrieve(
			'SELECT	MAX(ed.date_decided) AS date,
				ed.paper_id AS paper_id
			FROM	edit_decisions ed,
				papers p
			WHERE	p.sched_conf_id = ? AND
				p.paper_id = ed.paper_id
			GROUP BY p.paper_id, ed.paper_id',
			array($schedConfId)
		);
		$decisionDatesIterator = new DBRowIterator($result);
		unset($result);

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
			$decisionsReturner[] = new DBRowIterator($result);
			unset($result);
		}

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$papers =& $paperDao->getPapersBySchedConfId($schedConfId);
		$authorsReturner = array();
		$index = 1;
		while ($paper =& $papers->next()) {
			$result =& $this->retrieve(
				'SELECT	pa.first_name AS fname,
					pa.middle_name AS mname,
					pa.last_name AS lname,
					pa.email AS email,
					pa.affiliation AS affiliation,
					pa.country AS country,
					pa.url AS url,
					COALESCE(pasl.setting_value, pas.setting_value) AS biography
				FROM	paper_authors pa
					LEFT JOIN papers p ON pa.paper_id=p.paper_id
					LEFT JOIN paper_author_settings pas ON (pa.author_id=pas.author_id AND pas.setting_name = ? AND pas.locale = ?)
					LEFT JOIN paper_author_settings pasl ON (pa.author_id=pasl.author_id AND pasl.setting_name = ? AND pasl.locale = ?)
				WHERE	p.sched_conf_id = ? AND
					p.paper_id = ?',
				array(
					'biography',
					$primaryLocale,
					'biography',
					$locale,
					$schedConfId,
					$paper->getPaperId()
				)
			);
			$authorIterator = new DBRowIterator($result);
			unset($result);
			$authorsReturner[$paper->getPaperId()] = $authorIterator;
			unset($authorIterator);
			$index++;
			unset($paper);
		}

		return array($papersReturner, $authorsReturner, $decisionsReturner);
	}
}

?>
