<?php

/**
 * @file PaperReportDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class PaperReportDAO
 * @ingroup plugins_reports_paper
 * @see PaperReportPlugin
 *
 * @brief Paper report DAO
 *
 */



import('classes.submission.common.Action');
import('lib.pkp.classes.db.DBRowIterator');

class PaperReportDAO extends DAO {
	/**
	 * Get the paper report data.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @return array
	 */
	function getPaperReport($conferenceId, $schedConfId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$paperTypeDao = DAORegistry::getDAO('PaperTypeDAO'); // Load constants

		$result =& $this->retrieve(
			'SELECT	p.status AS status,
				p.start_time AS start_time,
				p.end_time AS end_time,
				pp.room_id AS room_id,
				p.paper_id AS paper_id,
				p.comments_to_dr as comments,
				COALESCE(psl1.setting_value, pspl1.setting_value) AS title,
				COALESCE(psl2.setting_value, pspl2.setting_value) AS abstract,
				COALESCE(tl.setting_value, tpl.setting_value) AS track_title,
				COALESCE(cvesl.setting_value, cvesp.setting_value) AS paper_type,
				p.language AS language
			FROM	papers p
				LEFT JOIN published_papers pp ON (p.paper_id = pp.paper_id)
				LEFT JOIN paper_settings pspl1 ON (pspl1.paper_id=p.paper_id AND pspl1.setting_name = ? AND pspl1.locale = p.locale)
				LEFT JOIN paper_settings psl1 ON (psl1.paper_id=p.paper_id AND psl1.setting_name = ? AND psl1.locale = ?)
				LEFT JOIN paper_settings pspl2 ON (pspl2.paper_id=p.paper_id AND pspl2.setting_name = ? AND pspl2.locale = p.locale)
				LEFT JOIN paper_settings psl2 ON (psl2.paper_id=p.paper_id AND psl2.setting_name = ? AND psl2.locale = ?)
				LEFT JOIN paper_settings pti ON (pti.paper_id=p.paper_id AND pti.setting_name = ?)
				LEFT JOIN controlled_vocabs cv ON (cv.symbolic = ? AND cv.assoc_type = ? AND cv.assoc_id = ?)
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id AND pti.setting_value = cve.controlled_vocab_entry_id)
				LEFT JOIN controlled_vocab_entry_settings cvesp ON (cve.controlled_vocab_entry_id = cvesp.controlled_vocab_entry_id AND cvesp.setting_name = ? AND cvesp.locale = ?)
				LEFT JOIN controlled_vocab_entry_settings cvesl ON (cve.controlled_vocab_entry_id = cvesl.controlled_vocab_entry_id AND cvesl.setting_name = ? AND cvesl.locale = ?)
				LEFT JOIN track_settings tpl ON (tpl.track_id=p.track_id AND tpl.setting_name = ? AND tpl.locale = ?)
				LEFT JOIN track_settings tl ON (tl.track_id=p.track_id AND tl.setting_name = ? AND tl.locale = ?)
			WHERE	p.sched_conf_id = ? AND
				p.submission_progress = 0
			ORDER BY p.paper_id',
			array(
				'title', // Paper title (paper locale)
				'title', // Paper title (current locale)
				$locale,
				'abstract', // Paper abstract (paper locale)
				'abstract', // Paper abstract (current locale)
				$locale,
				'sessionType', // Paper type (controlled vocab)
                                PAPER_TYPE_SYMBOLIC,
                                ASSOC_TYPE_SCHED_CONF,
                                $schedConfId,
                                'description', // Paper type (primary locale)
                                $primaryLocale,
                                'description', // Paper type (current locale)
                                $locale,
				'title', // Track title (primary locale)
				$primaryLocale,
				'title', // Track title (current locale)
				$locale,
				$schedConfId
			)
		);
		$papersReturner = new DBRowIterator($result);
		unset($result);

		$result =& $this->retrieve(
			'SELECT	MAX(ed.date_decided) AS date_decided,
				ed.paper_id AS paper_id
			FROM	edit_decisions ed,
				papers p
			WHERE	p.sched_conf_id = ? AND
				p.submission_progress = 0 AND
				p.paper_id = ed.paper_id
			GROUP BY p.paper_id, ed.paper_id',
			array($schedConfId)
		);
		$decisionDatesIterator = new DBRowIterator($result);
		unset($result);

		$decisionsReturner = array();
		while ($row =& $decisionDatesIterator->next()) {
			$result =& $this->retrieve(
				'SELECT	d.decision AS decision,
					d.paper_id AS paper_id
				FROM	edit_decisions d,
					papers p
				WHERE	d.date_decided = ? AND
					d.paper_id = p.paper_id AND
					p.submission_progress = 0 AND
					p.paper_id = ?',
				array(
					$row['date_decided'],
					$row['paper_id']
				)
			);
			$decisionsReturner[] = new DBRowIterator($result);
			unset($result);
		}

		$paperDao = DAORegistry::getDAO('PaperDAO');
		$papers =& $paperDao->getPapersBySchedConfId($schedConfId);
		$authorsReturner = array();
		$index = 1;
		while ($paper =& $papers->next()) {
			$result =& $this->retrieve(
				'SELECT	pa.first_name AS fname,
					pa.middle_name AS mname,
					pa.last_name AS lname,
					pa.email AS email,
					pa.country AS country,
					pa.url AS url,
					COALESCE(pasl.setting_value, pas.setting_value) AS biography,
					COALESCE(paasl.setting_value, paas.setting_value) AS affiliation
				FROM	authors pa
					JOIN papers p ON (pa.submission_id = p.paper_id)
					LEFT JOIN author_settings pas ON (pa.author_id = pas.author_id AND pas.setting_name = ? AND pas.locale = ?)
					LEFT JOIN author_settings pasl ON (pa.author_id = pasl.author_id AND pasl.setting_name = ? AND pasl.locale = ?)
					LEFT JOIN author_settings paas ON (pa.author_id = paas.author_id AND paas.setting_name = ? AND paas.locale = ?)
					LEFT JOIN author_settings paasl ON (pa.author_id = paasl.author_id AND paasl.setting_name = ? AND paasl.locale = ?)
				WHERE	p.sched_conf_id = ? AND
					p.submission_progress = 0 AND
					p.paper_id = ?',
				array(
					'biography',
					$primaryLocale,
					'biography',
					$locale,
					'affiliation',
					$primaryLocale,
					'affiliation',
					$locale,
					$schedConfId,
					$paper->getId()
				)
			);
			$authorIterator = new DBRowIterator($result);
			unset($result);
			$authorsReturner[$paper->getId()] = $authorIterator;
			unset($authorIterator);
			$index++;
			unset($paper);
		}

		return array($papersReturner, $authorsReturner, $decisionsReturner);
	}
}

?>
