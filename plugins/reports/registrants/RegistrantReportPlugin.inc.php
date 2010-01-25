<?php

/**
 * @file RegistrantReportPlugin.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class RegistrantReportPlugin
 * @ingroup plugins_reports_registrant
 * @see RegistrantReportDAO
 *
 * @brief Registrant report plugin
 */

//$Id$

import('classes.plugins.ReportPlugin');

class RegistrantReportPlugin extends ReportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->import('RegistrantReportDAO');
			$registrantReportDAO = new RegistrantReportDAO();
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
		Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OCS_MANAGER));

		header('content-type: text/comma-separated-values');
		header('content-disposition: attachment; filename=report.csv');

		$registrantReportDao =& DAORegistry::getDAO('RegistrantReportDAO');
		list($registrants, $registrantOptions) = $registrantReportDao->getRegistrantReport(
			$conference->getId(),
			$schedConf->getId()
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
			'type' => Locale::translate('manager.registration.registrationType')
		);
		
		$registrationOptionDAO =& DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationOptions =& $registrationOptionDAO->getRegistrationOptionsBySchedConfId($schedConf->getId());
		
		// column name = 'option' + optionId => column value = name of the registration option
		while ($registrationOption =& $registrationOptions->next()) {
			$registrationOptionIds[] = $registrationOption->getOptionId();
			$columns = array_merge($columns, array('option' . $registrationOption->getOptionId() => $registrationOption->getRegistrationOptionName()));
			unset($registrationOption);
		} 
		
		$columns = array_merge($columns, array(
			'regdate' => Locale::translate('manager.registration.dateRegistered'),
			'paiddate' => Locale::translate('manager.registration.datePaid'),
			'specialreq' => Locale::translate('schedConf.registration.specialRequests')
			));


		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		while ($row =& $registrants->next()) {
			if ( isset($registrantOptions[$row['registration_id']]) ) { 
				$options = $this->mergeRegistrantOptions($registrationOptionIds, $registrantOptions[$row['registration_id']]);
			} else {
				$options = $this->mergeRegistrantOptions($registrationOptionIds);
			}
			
			foreach ($columns as $index => $junk) {
				if (isset($row[$index])) {
					$columns[$index] = $row[$index];
				} else if (isset($options[$index])) {
					$columns[$index] = $options[$index];
				} else $columns[$index] = '';
			}
			
			String::fputcsv($fp, $columns);
			unset($row);
		}
		fclose($fp);
	}

	
	/**
	 * Make a single array of "Yes"/"No" for each option id
	 * @param $registrationOptionIds array list of Option Ids for a given schedConfId
	 * @param $registrantOptions array list of Option Ids for a given Registrant
	 * @return array
	 */
	function mergeRegistrantOptions($registrationOptionIds, $registrantOptions = array()) {
		$returner = array();
		foreach ( $registrationOptionIds as $id ) { 
			$returner['option'. $id] = ( in_array($id, $registrantOptions) )?Locale::translate('common.yes'):Locale::translate('common.no');
		}
		return $returner;
	}
}

?>
