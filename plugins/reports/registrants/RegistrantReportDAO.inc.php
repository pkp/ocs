<?php

/**
 * @file RegistrantReportDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class RegistrantReportDAO
 * @ingroup plugins_reports_registrant
 * @see RegistrantReportPlugin
 *
 * @brief Registrant report DAO
 *
 */

// $Id$


import('db.DBRowIterator');

class RegistrantReportDAO extends DAO {
	/**
	 * Get the registrant report data.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @return array
	 */
	function getRegistrantReport($conferenceId, $schedConfId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$result =& $this->retrieve(
			'SELECT
				r.registration_id AS registration_id,
				r.user_id AS userid,
				u.username AS uname,
				u.first_name AS fname,
				u.middle_name AS mname,
				u.last_name AS lname,
				u.affiliation AS affiliation,
				u.url AS url,
				u.email AS email,
				u.phone AS phone,
				u.fax AS fax,
				u.mailing_address AS address,
				u.country AS country,
				COALESCE(rtsl.setting_value, rtspl.setting_value) AS type,
				r.date_registered AS regdate,
				r.date_paid AS paiddate,
				r.special_requests AS specialreq
			FROM
				registrations r
					LEFT JOIN users u ON r.user_id=u.user_id
					LEFT JOIN registration_type_settings rtsl ON (r.type_id=rtsl.type_id AND rtsl.locale=? AND rtsl.setting_name=?)
					LEFT JOIN registration_type_settings rtspl ON (r.type_id=rtspl.type_id AND rtsl.locale=? AND rtspl.setting_name=?)
			WHERE
				r.sched_conf_id = ?
			ORDER BY
				lname',
			array(
				$locale,
				'name',
				$primaryLocale,
				'name',
				(int) $schedConfId
			)
		);

		// prepare an iterator of all the registration information
		$registrationReturner = new DBRowIterator($result);

		$result =& $this->retrieve(
			'SELECT 
				r.registration_id as registration_id,
				roa.option_id as option_id
			FROM
				registrations r 
					LEFT JOIN registration_option_assoc roa ON (r.registration_id = roa.registration_id)
			WHERE 
				r.sched_conf_id = ?',
			(int) $schedConfId
		);
		
		// Prepare an array of registration Options by registration Id
		$registrationOptionDAO =& DAORegistry::getDAO('RegistrationOptionDAO');
		$iterator = new DBRowIterator($result);
		$registrationOptionReturner = array();
		while ($row =& $iterator->next()) {
			$registrationId = $row['registration_id'];
			$registrationOptionReturner[$registrationId] =& $registrationOptionDAO->getRegistrationOptions($registrationId);
		}

		return array($registrationReturner, $registrationOptionReturner);
	}
}

?>
