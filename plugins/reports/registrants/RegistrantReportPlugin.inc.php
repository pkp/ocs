<?php

/**
 * @file RegistrantReportPlugin.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @package plugins.reports.registrant
 * @class RegistrantReportPlugin
 *
 * Registrant report plugin
 *
 * $Id$
 */

import('classes.plugins.ReportPlugin');

class RegistrantReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('RegistrantReportDAO');
			$registrantReportDAO =& new RegistrantReportDAO();
			DAORegistry::registerDAO('RegistrantReportDAO', $registrantReportDAO);
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
		return 'RegistrantReportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.reports.registrants.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.reports.registrants.description');
	}

	function display(&$args) {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$registrantReportDao =& DAORegistry::getDAO('RegistrantReportDAO');
		$iterator =& $registrantReportDao->getRegistrantReport(
			$conference->getConferenceId(),
			$schedConf->getSchedConfId()
		);

		$columns = array(
			'userid' => Locale::translate('plugins.reports.registrants.userid'),
			'uname' => Locale::translate('user.username'),
			'fname' => Locale::translate('user.firstName'),
			'mname' => Locale::translate('user.middleName'),
			'lname' => Locale::translate('user.lastName'),
			'affiliation' => Locale::translate('user.affiliation'),
			'url' => Locale::translate('user.url'),
			'email' => Locale::translate('user.email'),
			'phone' => Locale::translate('user.phone'),
			'fax' => Locale::translate('user.fax'),
			'address' => Locale::translate('common.mailingAddress'),
			'country' => Locale::translate('common.country'),
			'type' => Locale::translate('manager.registration.registrationType'),
			'regdate' => Locale::translate('manager.registration.dateRegistered'),
			'paiddate' => Locale::translate('manager.registration.datePaid'),
			'specialreq' => Locale::translate('schedConf.registration.specialRequests')
		);

		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		while ($row =& $iterator->next()) {
			foreach ($columns as $index => $junk) {
				$columns[$index] = $row[$index];
			}
			String::fputcsv($fp, $columns);
			unset($row);
		}
		fclose($fp);
	}
}

?>
