<?php

/**
 * @file PaperReportPlugin.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class PaperReportPlugin
 * @ingroup plugins_reports_paper
 * @see PaperReportDAO
 *
 * @brief Paper report plugin
 */

//$Id$

import('classes.plugins.ReportPlugin');

class PaperReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('PaperReportDAO');
			$paperReportDAO =& new PaperReportDAO();
			DAORegistry::registerDAO('PaperReportDAO', $paperReportDAO);
		}
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'PaperReportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.reports.papers.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.reports.papers.description');
	}

	function display(&$args) {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$paperReportDao =& DAORegistry::getDAO('PaperReportDAO');
		list($papersIterator, $presentersIterator, $decisionsIteratorsArray) = $paperReportDao->getPaperReport(
			$conference->getConferenceId(),
			$schedConf->getSchedConfId()
		);
		$maxPresenters = $this->getMaxPresenterCount($presentersIterator);
		
		$decisions = array();
		foreach ($decisionsIteratorsArray as $decisionsIterator){
			while ($row =& $decisionsIterator->next()) {
				$decisions[$row['paper_id']] = $row['decision'];
			}
		}

		import('classes.paper.Paper');
		$decisionMessages = array(
			SUBMISSION_DIRECTOR_DECISION_INVITE => Locale::translate('director.paper.decision.invitePresentation'),
			SUBMISSION_DIRECTOR_DECISION_ACCEPT => Locale::translate('director.paper.decision.accept'),
			SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS => Locale::translate('director.paper.decision.pendingRevisions'),
			SUBMISSION_DIRECTOR_DECISION_DECLINE => Locale::translate('director.paper.decision.decline'),
			null => Locale::translate('plugins.reports.papers.nodecision')
		);

		$columns = array(
			'paper_id' => Locale::translate('paper.submissionId'),
			'title' => Locale::translate('paper.title'),
			'abstract' => Locale::translate('paper.abstract'),
		);
		
		for ($a = 1; $a <= $maxPresenters; $a++) {
			$columns = array_merge($columns, array(
				'fname' . $a => Locale::translate('user.firstName') . " (" . Locale::translate('user.role.presenter') . " $a)",
				'mname' . $a => Locale::translate('user.middleName') . " (" . Locale::translate('user.role.presenter') . " $a)",
				'lname' . $a => Locale::translate('user.lastName') . " (" . Locale::translate('user.role.presenter') . " $a)",
				'country' . $a => Locale::translate('common.country') . " (" . Locale::translate('user.role.presenter') . " $a)",
				'affiliation' . $a => Locale::translate('user.affiliation') . " (" . Locale::translate('user.role.presenter') . " $a)",
				'email' . $a => Locale::translate('user.email') . " (" . Locale::translate('user.role.presenter') . " $a)",
				'url' . $a => Locale::translate('user.url') . " (" . Locale::translate('user.role.presenter') . " $a)",
				'biography' . $a => Locale::translate('user.biography') . " (" . Locale::translate('user.role.presenter') . " $a)"
			));
		}
		
		$columns = array_merge($columns, array(
			'track_title' => Locale::translate('track.title'),
			'language' => Locale::translate('common.language'),
			'director_decision' => Locale::translate('submission.directorDecision'),
			'status' => Locale::translate('common.status')
		));

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		import('paper.Paper'); // Bring in getStatusMap function
		$statusMap =& Paper::getStatusMap();

		$presenterIndex = 0;
		while ($row =& $papersIterator->next()) {
			$presenters = $this->mergePresenters($presentersIterator[$presenterIndex]->toArray());
			foreach ($columns as $index => $junk) switch ($index) {
				case 'director_decision':
					if (isset($decisions[$row['paper_id']])) {
						$columns[$index] = $decisionMessages[$decisions[$row['paper_id']]];
					} else {
						$columns[$index] = $decisionMessages[null];
					}
					break;
				case 'status':
					$columns[$index] = Locale::translate($statusMap[$row[$index]]);
					break;
				case 'abstract':
					$columns[$index] = strip_tags($row[$index]);
					break;
				default:
					if (isset($row[$index])) {
						$columns[$index] = $row[$index];
					} else if (isset($presenters[$index])) {
						$columns[$index] = $presenters[$index];
					} else $columns[$index] = '';
					break;
			}
			String::fputcsv($fp, $columns);
			$presenterIndex++;
			unset($row);
		}
		
		fclose($fp);
	}
	
	/**
	 * Get the highest presenter count for any paper (to determine how many columns to set)
	 * @param $presentersIterator DBRowIterator
	 * @return int
	 */
	function getMaxPresenterCount($presentersIterator) {
		$maxPresenters = 0;
		foreach ($presentersIterator as $presenterIterator) {
			$maxPresenters = $presenterIterator->getCount() > $maxPresenters ? $presenterIterator->getCount() : $maxPresenters;
		}
		return $maxPresenters;
	}
	
	/**
	 * Flatten an array of presenter information into one array and append presenter sequence to each key
	 * @param $presenters array
	 * @return array
	 */
	function mergePresenters($presenters) {
		$returner = array();
		$seq = 0;
		foreach($presenters as $presenter) {
			$seq++;
			
			$returner['fname' . $seq] = isset($presenter['fname']) ? $presenter['fname'] : '';
			$returner['mname' . $seq] = isset($presenter['mname']) ? $presenter['mname'] : '';
			$returner['lname' . $seq] = isset($presenter['lname']) ? $presenter['lname'] : '';
			$returner['email' . $seq] = isset($presenter['email']) ? $presenter['email'] : '';
			$returner['affiliation'] = isset($presenter['affiliation']) ? $presenter['affiliation'] : '';
			$returner['country' . $seq] = isset($presenter['country']) ? $presenter['country'] : '';
			$returner['url' . $seq] = isset($presenter['url']) ? $presenter['url'] : '';
			$returner['biography' . $seq] = isset($presenter['biography']) ? $presenter['biography'] : '';
		}
		return $returner;
	}
}

?>
