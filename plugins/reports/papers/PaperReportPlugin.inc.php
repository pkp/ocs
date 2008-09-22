<?php

/**
 * @file PaperReportPlugin.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
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
		list($papersIterator, $decisionsIteratorsArray) = $paperReportDao->getPaperReport(
			$conference->getConferenceId(),
			$schedConf->getSchedConfId()
		);

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
			'fname' => Locale::translate('user.firstName'),
			'mname' => Locale::translate('user.middleName'),
			'lname' => Locale::translate('user.lastName'),
			'phone' => Locale::translate('user.phone'),
			'fax' => Locale::translate('user.fax'),
			'address' => Locale::translate('common.mailingAddress'),
			'country' => Locale::translate('common.country'),
			'affiliation' => Locale::translate('user.affiliation'),
			'email' => Locale::translate('user.email'),
			'url' => Locale::translate('user.url'),
			'biography' => Locale::translate('user.biography'),
			'track_title' => Locale::translate('track.title'),
			'language' => Locale::translate('common.language'),
			'director_decision' => Locale::translate('submission.directorDecision'),
			'status' => Locale::translate('common.status')
		);

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		import('paper.Paper'); // Bring in getStatusMap function
		$statusMap =& Paper::getStatusMap();

		while ($row =& $papersIterator->next()) {
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
				default:
					$columns[$index] = $row[$index];
					break;
			}
			String::fputcsv($fp, $columns);
			unset($row);
		}
		
		fclose($fp);
	}
}

?>
