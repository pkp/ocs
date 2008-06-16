<?php

/**
 * @file ReviewReportPlugin.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @package plugins.reports.review
 * @class ReviewReportPlugin
 *
 * Review report plugin
 *
 * $Id$
 */

import('classes.plugins.ReportPlugin');

class ReviewReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('ReviewReportDAO');
			$reviewReportDAO =& new ReviewReportDAO();
			DAORegistry::registerDAO('ReviewReportDAO', $reviewReportDAO);
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
		return 'ReviewReportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.reports.reviews.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.reports.reviews.description');
	}

	function display(&$args) {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$reviewReportDao =& DAORegistry::getDAO('ReviewReportDAO');
		list($commentsIterator, $reviewsIterator) = $reviewReportDao->getReviewReport(
			$conference->getConferenceId(),
			$schedConf->getSchedConfId()
		);

		$comments = array();
		while ($row =& $commentsIterator->next()) {
			if ( isset($comments[$row['paper_id']][$row['author_id']]) ){
				$comments[$row['paper_id']][$row['author_id']] .= "; " . $row['comments'];
			}else{
				$comments[$row['paper_id']][$row['author_id']] = $row['comments'];
			}
		}

		$yesnoMessages = array( 0 => Locale::translate('common.no'), 1 => Locale::translate('common.yes'));

		import('classes.schedConf.SchedConf');
		$reviewTypes = array(
			REVIEW_MODE_ABSTRACTS_ALONE => Locale::translate('manager.schedConfSetup.submissions.abstractsAlone'),
			REVIEW_MODE_BOTH_SEQUENTIAL => Locale::translate('manager.schedConfSetup.submissions.bothSequential'),
			REVIEW_MODE_PRESENTATIONS_ALONE => Locale::translate('manager.schedConfSetup.submissions.presentationsAlone'),
			REVIEW_MODE_BOTH_SIMULTANEOUS => Locale::translate('manager.schedConfSetup.submissions.bothTogether')
		);

		import('submission.reviewAssignment.ReviewAssignment');
		$recommendations = ReviewAssignment::getReviewerRecommendationOptions();

		$columns = array(
			'reviewstage' => Locale::translate('submissions.reviewType'),
			'paper' => Locale::translate('paper.papers'),
			'paperid' => Locale::translate('paper.submissionId'),
			'reviewerid' => Locale::translate('plugins.reports.reviews.reviewerId'),
			'reviewer' => Locale::translate('plugins.reports.reviews.reviewer'),
			'firstname' => Locale::translate('user.firstName'),
			'middlename' => Locale::translate('user.middleName'),
			'lastname' => Locale::translate('user.lastName'),
			'dateassigned' => Locale::translate('plugins.reports.reviews.dateAssigned'),
			'datenotified' => Locale::translate('plugins.reports.reviews.dateNotified'),
			'dateconfirmed' => Locale::translate('plugins.reports.reviews.dateConfirmed'),
			'datecompleted' => Locale::translate('plugins.reports.reviews.dateCompleted'),
			'datereminded' => Locale::translate('plugins.reports.reviews.dateReminded'),
			'declined' => Locale::translate('submissions.declined'),
			'cancelled' => Locale::translate('common.cancelled'),
			'recommendation' => Locale::translate('reviewer.paper.recommendation'),
			'comments' => Locale::translate('comments.commentsOnPaper')
		);
		$yesNoArray = array('declined', 'cancelled');

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		while ($row =& $reviewsIterator->next()) {
			foreach ($columns as $index => $junk) {
				if (in_array($index, $yesNoArray)){
					$columns[$index] = $yesnoMessages[$row[$index]];
				}else if ($index == "reviewstage"){
					$columns[$index] = $reviewTypes[$row[$index]];
				}else if ($index == "recommendation"){
					$columns[$index] = (!isset($row[$index])) ? Locale::translate('common.none') : Locale::translate($recommendations[$row[$index]]);
				}else if ($index == "comments"){
					if ( isset($comments[$row['paperid']][$row['reviewerid']]) ){
						$columns[$index] = $comments[$row['paperid']][$row['reviewerid']];
					}else{
						$columns[$index] = "";
					}
				}else{
					$columns[$index] = $row[$index];
				}
			}
			String::fputcsv($fp, $columns);
			unset($row);
		}
		fclose($fp);
	}
}

?>
