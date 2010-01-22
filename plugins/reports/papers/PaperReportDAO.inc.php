<?php

/**
 * @file PaperReportDAO.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class PaperReportDAO
 * @ingroup plugins_reports_paper
 * @see PaperReportPlugin
 *
 * @brief Paper report DAO
 */

//$Id$

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
			'SELECT	p.status AS status,
				p.paper_id AS paper_id,
				COALESCE(psl1.setting_value, pspl1.setting_value) AS title,
				COALESCE(psl2.setting_value, pspl2.setting_value) AS abstract,
				COALESCE(tl.setting_value, tpl.setting_value) AS track_title,
				p.language AS language
			FROM
				papers p
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
			GROUP BY p.paper_id, ed.paper_id',
			array($schedConfId)
		);
		$decisionDatesIterator =& new DBRowIterator($result);
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

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$papers =& $paperDao->getPapersBySchedConfId($schedConfId);
		$presentersReturner = array();
		$index = 1;
		while ($paper =& $papers->next()) {
			$result =& $this->retrieve(
				'SELECT	pp.first_name AS fname,
					pp.middle_name AS mname,
					pp.last_name AS lname,
					pp.email AS email,
					pp.affiliation AS affiliation,
					pp.country AS country,
					pp.url AS url,
					COALESCE(ppsl.setting_value, pps.setting_value) AS biography
				FROM paper_presenters pp
					LEFT JOIN papers p ON pp.paper_id=p.paper_id
					LEFT JOIN paper_presenter_settings pps ON (pp.presenter_id=pps.presenter_id AND pps.setting_name = ? AND pps.locale = ?)
					LEFT JOIN paper_presenter_settings ppsl ON (pp.presenter_id=ppsl.presenter_id AND ppsl.setting_name = ? AND ppsl.locale = ?)
				WHERE	p.sched_conf_id = ? AND
					pp.paper_id = ?',
				array(
					'biography',
					$primaryLocale,
					'biography',
					$locale,
					$schedConfId,
					$paper->getPaperId()
				)
			);
			$presenterIterator =& new DBRowIterator($result);
			$presentersReturner[$paper->getPaperId()] = $presenterIterator;
			unset($presenterIterator);
			$index++;
			unset($paper);
		}

		return array($papersReturner, $presentersReturner, $decisionsReturner);
	}
}

?>
