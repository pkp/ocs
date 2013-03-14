<?php

/**
 * @file RegistrantReportPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrantReportPlugin
 * @ingroup plugins_reports_registrant
 * @see RegistrantReportDAO
 *
 * @brief Registrant report plugin
 */


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
		return __('plugins.reports.registrants.displayName');
	}

	function getDescription() {
		return __('plugins.reports.registrants.description');
	}

	function display(&$args) {
		$request =& $this->getRequest();
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_APP_MANAGER);

		header('content-type: text/comma-separated-values; charset=utf-8');
		header('content-disposition: attachment; filename=registrants-' . date('Ymd') . '.csv');

		$registrationDao = DAORegistry::getDAO('RegistrationDAO');
		$registrationOptionDao = DAORegistry::getDAO('RegistrationOptionDAO');
		$registrationTypeDao = DAORegistry::getDAO('RegistrationTypeDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$columns = array(
			__('plugins.reports.registrants.registrationid'),
			__('plugins.reports.registrants.userid'),
			__('user.username'),
			__('user.firstName'),
			__('user.middleName'),
			__('user.lastName'),
			__('user.affiliation'),
			__('user.url'),
			__('user.email'),
			__('user.phone'),
			__('user.fax'),
			__('common.mailingAddress'),
			__('common.billingAddress'),
			__('common.country'),
			__('manager.registration.registrationType')
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
			__('manager.registration.dateRegistered'),
			__('manager.registration.datePaid'),
			__('schedConf.registration.specialRequests'),
			__('plugins.reports.registrants.total')
		));


		$fp = fopen('php://output', 'wt');
		fputcsv($fp, array_values($columns));

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
			$user =& $userDao->getById($registration->getUserId());

			$columns = array(
				$registrationId,
				$user->getId(),
				$user->getUsername(),
				$user->getFirstName(),
				$user->getMiddleName(),
				$user->getLastName(),
				html_entity_decode($user->getLocalizedAffiliation(), ENT_QUOTES, 'UTF-8'),
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
					$columns[] = __('common.yes');
					if (isset($registrationOptionCosts[$registrationTypeId][$optionId])) {
						$totalCost += $registrationOptionCosts[$registrationTypeId][$optionId];
					}
				} else {
					$columns[] = __('common.no');
				}
			}

			$columns[] = $registration->getDateRegistered();
			$columns[] = $registration->getDatePaid();
			$columns[] = $registration->getSpecialRequests();
			$columns[] = sprintf('%.2f', $totalCost);

			fputcsv($fp, $columns);
			unset($registration, $registrationType, $user);
		}
		fclose($fp);
	}
}

?>
