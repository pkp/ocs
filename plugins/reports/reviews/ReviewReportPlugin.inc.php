<?php

/**
 * @file ReviewReportPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewReportPlugin
 * @ingroup plugins_reports_review
 * @see ReviewReportDAO
 *
 * @brief Review report plugin
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
			$reviewReportDAO = new ReviewReportDAO();
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
		return __('plugins.reports.reviews.displayName');
	}

	function getDescription() {
		return __('plugins.reports.reviews.description');
	}

	function display(&$args) {
		$request =& $this->getRequest();
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_MANAGER);

		header('content-type: text/comma-separated-values; charset=utf-8');
		header('content-disposition: attachment; filename=reviews-' . date('Ymd') . '.csv');

		$reviewReportDao = DAORegistry::getDAO('ReviewReportDAO');
		list($commentsIterator, $reviewsIterator) = $reviewReportDao->getReviewReport($schedConf->getId());

		$comments = array();
		while ($row =& $commentsIterator->next()) {
			if (isset($comments[$row['paper_id']][$row['author_id']])) {
				$comments[$row['paper_id']][$row['author_id']] .= "; " . $row['comments'];
			} else {
				$comments[$row['paper_id']][$row['author_id']] = $row['comments'];
			}
		}

		$yesnoMessages = array( 0 => __('common.no'), 1 => __('common.yes'));

		import('classes.schedConf.SchedConf');
		$reviewTypes = array(
			REVIEW_MODE_ABSTRACTS_ALONE => __('manager.schedConfSetup.submissions.abstractsAlone'),
			REVIEW_MODE_BOTH_SEQUENTIAL => __('manager.schedConfSetup.submissions.bothSequential'),
			REVIEW_MODE_PRESENTATIONS_ALONE => __('manager.schedConfSetup.submissions.presentationsAlone'),
			REVIEW_MODE_BOTH_SIMULTANEOUS => __('manager.schedConfSetup.submissions.bothTogether')
		);

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$recommendations = ReviewAssignment::getReviewerRecommendationOptions();

		$columns = array(
			'reviewRound' => __('submissions.reviewType'),
			'paper' => __('paper.papers'),
			'paperid' => __('paper.submissionId'),
			'reviewerid' => __('plugins.reports.reviews.reviewerId'),
			'reviewer' => __('plugins.reports.reviews.reviewer'),
			'firstname' => __('user.firstName'),
			'middlename' => __('user.middleName'),
			'lastname' => __('user.lastName'),
			'dateassigned' => __('plugins.reports.reviews.dateAssigned'),
			'datenotified' => __('plugins.reports.reviews.dateNotified'),
			'dateconfirmed' => __('plugins.reports.reviews.dateConfirmed'),
			'datecompleted' => __('plugins.reports.reviews.dateCompleted'),
			'datereminded' => __('plugins.reports.reviews.dateReminded'),
			'declined' => __('submissions.declined'),
			'cancelled' => __('common.cancelled'),
			'recommendation' => __('reviewer.paper.recommendation'),
			'comments' => __('comments.commentsOnPaper')
		);
		$yesNoArray = array('declined', 'cancelled');

		$fp = fopen('php://output', 'wt');
		fputcsv($fp, array_values($columns));

		while ($row =& $reviewsIterator->next()) {
			foreach ($columns as $index => $junk) {
				if (in_array($index, array('declined', 'cancelled'))) {
					$yesNoIndex = $row[$index];
					if (is_string($yesNoIndex)) {
						// Accomodate Postgres boolean casting
						$yesNoIndex = $yesNoIndex == "f" ? 0 : 1;
					}
					$columns[$index] = $yesnoMessages[$yesNoIndex];
				} elseif ($index == 'reviewRound') {
					$columns[$index] = $reviewTypes[$row[$index]];
				} elseif ($index == "recommendation") {
					$columns[$index] = (!isset($row[$index])) ? __('common.none') : __($recommendations[$row[$index]]);
				} elseif ($index == "comments") {
					if (isset($comments[$row['paperid']][$row['reviewerid']])) {
						$columns[$index] = html_entity_decode(strip_tags($comments[$row['paperid']][$row['reviewerid']]), ENT_QUOTES, 'UTF-8');
					} else {
						$columns[$index] = "";
					}
				} else {
					$columns[$index] = $row[$index];
				}
			}
			fputcsv($fp, $columns);
			unset($row);
		}
		fclose($fp);
	}
}

?>
