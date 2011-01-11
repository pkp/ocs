<?php

/**
 * @file RegistrantReportPlugin.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
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
		header('content-disposition: attachment; filename=registrants-' . date('Ymd') . '.csv');

		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationTypeDao =& DAORegistry::getDAO('RegistrationTypeDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$columns = array(
			Locale::translate('plugins.reports.registrants.registrationid'),
			Locale::translate('plugins.reports.registrants.userid'),
			Locale::translate('user.username'),
			Locale::translate('user.firstName'),
			Locale::translate('user.middleName'),
			Locale::translate('user.lastName'),
			Locale::translate('user.affiliation'),
			Locale::translate('user.url'),
			Locale::translate('user.email'),
			Locale::translate('user.phone'),
			Locale::translate('user.fax'),
			Locale::translate('common.mailingAddress'),
			Locale::translate('common.billingAddress'),
			Locale::translate('common.country'),
			Locale::translate('manager.registration.registrationType')
		);
		
		$registrationOptions =& $registrationOptionDao->getRegistrationOptionsBySchedConfId($schedConf->getId());
		
		// 'option' + optionId => name of the registration option
		$registrationOptionIds = array();
		while ($registrationOption =& $registrationOptions->next()) {
			$registrationOptionIds[] = $registrationOption->getOptionId();
			$columns = array_merge($columns, array('option' . $registrationOption->getOptionId() => $registrationOption->getRegistrationOptionName()));
			unset($registrationOption);
		} 
		
		$columns = array_merge($columns, array(
			Locale::translate('manager.registration.dateRegistered'),
			Locale::translate('manager.registration.datePaid'),
			Locale::translate('schedConf.registration.specialRequests'),
			Locale::translate('plugins.reports.registrants.total')
		));


		$fp = fopen('php://output', 'wt');
		String::fputcsv($fp, array_values($columns));

		$registrationOptionCosts = $registrationTypes = array();

		$registrations =& $registrationDao->getRegistrationsBySchedConfId($schedConf->getId());
		while ($registration =& $registrations->next()) {
			$registrationId = $registration->getId();
			$registrationTypeId = $registration->getTypeId();

			// Get registration option costs, caching as we go.
			if (!isset($registrationOptionCosts[$registrationTypeId])) {
				$registrationOptionCosts[$registrationTypeId] = $registrationTypeDao->getRegistrationOptionCosts($registrationTypeId);
			}

			// Get the registration type, caching as we go.
			if (!isset($registrationTypes[$registrationTypeId])) {
				$registrationTypes[$registrationTypeId] =& $registrationTypeDao->getRegistrationType($registrationTypeId);
			}
			$registrationType =& $registrationTypes[$registrationTypeId];

			// Get registrant user object
			$user =& $userDao->getUser($registration->getUserId());

			$columns = array(
				$registrationId,
				$user->getId(),
				$user->getUsername(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getLastName(),
				$user->getLocalizedAffiliation(),
				$user->getUrl(),
				$user->getEmail(),
				$user->getPhone(),
				$user->getFax(),
				$user->getMailingAddress(),
				$user->getBillingAddress(),
				$user->getCountry(),
				$registrationType->getRegistrationTypeName()
			);
			
			// Get selected registration options; calculate costs
			$totalCost = $registrationType->getCost();
			$selectedOptionIds = $registrationOptionDao->getRegistrationOptions($registrationId);
			foreach ($registrationOptionIds as $optionId) {
				if (in_array($optionId, $selectedOptionIds)) {
					$columns[] = Locale::translate('common.yes');
					if (isset($registrationOptionCosts[$registrationTypeId][$optionId])) {
						$totalCost += $registrationOptionCosts[$registrationTypeId][$optionId];
					}
				} else {
					$columns[] = Locale::translate('common.no');
				}
			}

			$columns[] = $registration->getDateRegistered();
			$columns[] = $registration->getDatePaid();
			$columns[] = $registration->getSpecialRequests();
			$columns[] = sprintf('%.2f', $totalCost);

			String::fputcsv($fp, $columns);
			unset($registration, $registrationType, $user);
		}
		fclose($fp);
	}
}

?>
