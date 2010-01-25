<?php

/**
 * @file PaperReportPlugin.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
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
			$paperReportDAO = new PaperReportDAO();
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
		Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OCS_MANAGER));

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$paperReportDao =& DAORegistry::getDAO('PaperReportDAO');
		list($papersIterator, $authorsIterator, $decisionsIteratorsArray) = $paperReportDao->getPaperReport(
			$conference->getId(),
			$schedConf->getId()
		);
		$maxAuthors = $this->getMaxAuthorCount($authorsIterator);
		
		$decisions = array();
		foreach ($decisionsIteratorsArray as $decisionsIterator) {
			while ($row =& $decisionsIterator->next()) {
				$decisions[$row['paper_id']] = $row['decision'];
			}
		}

		Locale::requireComponents(array(LOCALE_COMPONENT_OCS_DIRECTOR));
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
		
		for ($a = 1; $a <= $maxAuthors; $a++) {
			$columns = array_merge($columns, array(
				'fname' . $a => Locale::translate('user.firstName') . " (" . Locale::translate('user.role.author') . " $a)",
				'mname' . $a => Locale::translate('user.middleName') . " (" . Locale::translate('user.role.author') . " $a)",
				'lname' . $a => Locale::translate('user.lastName') . " (" . Locale::translate('user.role.author') . " $a)",
				'country' . $a => Locale::translate('common.country') . " (" . Locale::translate('user.role.author') . " $a)",
				'affiliation' . $a => Locale::translate('user.affiliation') . " (" . Locale::translate('user.role.author') . " $a)",
				'email' . $a => Locale::translate('user.email') . " (" . Locale::translate('user.role.author') . " $a)",
				'url' . $a => Locale::translate('user.url') . " (" . Locale::translate('user.role.author') . " $a)",
				'biography' . $a => Locale::translate('user.biography') . " (" . Locale::translate('user.role.author') . " $a)"
			));
		}
		
		$columns = array_merge($columns, array(
			'track_title' => Locale::translate('track.title'),
			'language' => Locale::translate('common.language'),
			'director_decision' => Locale::translate('submission.directorDecision'),
			'start_time' => Locale::translate('manager.scheduler.startTime'),
			'end_time' => Locale::translate('manager.scheduler.endTime'),
			'building' => Locale::translate('manager.scheduler.building'),
			'room' => Locale::translate('manager.scheduler.room'),
			'status' => Locale::translate('common.status')
		));

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		import('paper.Paper'); // Bring in getStatusMap function
		$statusMap =& Paper::getStatusMap();

		$controlledVocabDao =& DAORegistry::getDAO('ControlledVocabDAO');
		$sessionTypes = $controlledVocabDao->enumerateBySymbolic('paperType', ASSOC_TYPE_SCHED_CONF, $schedConf->getSchedConfId());

		// Load building and room data
		$buildingDao =& DAORegistry::getDAO('BuildingDAO');
		$roomDao =& DAORegistry::getDAO('RoomDAO');

		$buildings = $rooms = array();
		$buildingsIterator =& $buildingDao->getBuildingsBySchedConfId($schedConf->getId());
		while ($building =& $buildingsIterator->next()) {
			$buildingId = $building->getId();
			$buildings[$buildingId] =& $building;
			$roomsIterator =& $roomDao->getRoomsByBuildingId($buildingId);
			while ($room =& $roomsIterator->next()) {
				$roomId = $room->getId();
				$rooms[$roomId] =& $room;
				unset($room);
			}
			unset($building);
			unset($roomsIterator);
		}
		unset($buildingsIterator);

		$authorIndex = 0;
		while ($row =& $papersIterator->next()) {
			if (isset($authorsIterator[$row['paper_id']])) {
				$authorIterator =& $authorsIterator[$row['paper_id']];
				$authors = $this->mergeAuthors($authorIterator->toArray());
			} else {
				// No authors for a submission.
				$authors = array();
			}
			unset($authorIterator);
			foreach ($columns as $index => $junk) {
				if ($index == 'director_decision') {
					if (isset($decisions[$row['paper_id']])) {
						$columns[$index] = $decisionMessages[$decisions[$row['paper_id']]];
					} else {
						$columns[$index] = $decisionMessages[null];
					}
				} elseif ($index == 'status') {
					$columns[$index] = Locale::translate($statusMap[$row[$index]]);
				} elseif ($index == 'abstract') {
					$columns[$index] = html_entity_decode(strip_tags($row[$index]));
				} elseif ($index == 'start_time' || $index == 'end_time') {
					$columns[$index] = $row[$index];
				} elseif ($index == 'building') {
					$columns['building'] = '';
					$room =& $rooms[$row['room_id']];
					if ($room) {
						$building =& $buildings[$room->getBuildingId()];
						if ($building) $columns[$index] = $building->getBuildingName();
					}
					unset($room, $building);
				} elseif ($index == 'room') {
					$columns['room'] = '';
					$room =& $rooms[$row['room_id']];
					if ($room) $columns[$index] = $room->getRoomName();
					unset($room);
				} elseif (strstr($index, 'biography') !== false) {
					// "Convert" HTML to text for export
					$columns[$index] = isset($authors[$index])?html_entity_decode(strip_tags($authors[$index])):'';
				} else {
					if (isset($row[$index])) {
						$columns[$index] = $row[$index];
					} else if (isset($authors[$index])) {
						$columns[$index] = $authors[$index];
					} else $columns[$index] = '';
				}
			}
			String::fputcsv($fp, $columns);
			$authorIndex++;
			unset($row);
		}
		
		fclose($fp);
	}
	
	/**
	 * Get the highest author count for any paper (to determine how many columns to set)
	 * @param $authorsIterator DBRowIterator
	 * @return int
	 */
	function getMaxAuthorCount(&$authorsIterator) {
		$maxAuthors = 0;
		foreach ($authorsIterator as $authorIterator) {
			$maxAuthors = $authorIterator->getCount() > $maxAuthors ? $authorIterator->getCount() : $maxAuthors;
		}
		return $maxAuthors;
	}
	
	/**
	 * Flatten an array of author information into one array and append author sequence to each key
	 * @param $authors array
	 * @return array
	 */
	function mergeAuthors($authors) {
		$returner = array();
		$seq = 0;
		foreach($authors as $author) {
			$seq++;
			
			$returner['fname' . $seq] = isset($author['fname']) ? $author['fname'] : '';
			$returner['mname' . $seq] = isset($author['mname']) ? $author['mname'] : '';
			$returner['lname' . $seq] = isset($author['lname']) ? $author['lname'] : '';
			$returner['email' . $seq] = isset($author['email']) ? $author['email'] : '';
			$returner['affiliation'] = isset($author['affiliation']) ? $author['affiliation'] : '';
			$returner['country' . $seq] = isset($author['country']) ? $author['country'] : '';
			$returner['url' . $seq] = isset($author['url']) ? $author['url'] : '';
			$returner['biography' . $seq] = isset($author['biography']) ? $author['biography'] : '';
		}
		return $returner;
	}
}

?>
