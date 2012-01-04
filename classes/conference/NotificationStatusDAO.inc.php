<?php

/**
 * @file NotificationStatusDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationStatusDAO
 * @ingroup conference
 *
 * @brief Operations for retrieving and modifying users' sched conf notification status.
 */

//$Id$

class NotificationStatusDAO extends DAO {
	function &getSchedConfNotifications($userId) {
		$returner = array();

		$result =& $this->retrieve(
			'SELECT j.sched_conf_id AS sched_conf_id, n.sched_conf_id AS notification FROM sched_confs j LEFT JOIN notification_status n ON j.sched_conf_id = n.sched_conf_id AND n.user_id = ? ORDER BY j.seq',
			$userId
		);

		while (!$result->EOF) {
			$row =& $result->GetRowAssoc(false);
			$returner[$row['sched_conf_id']] = $row['notification'];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Changes whether or not a user will receive email notifications about a given sched conf.
	 * @param $schedConfId int
	 * @param $userId int
	 * @param $notificationStatus bool
	 */
	function setSchedConfNotifications($schedConfId, $userId, $notificationStatus) {
		return $this->update(
			($notificationStatus?'INSERT INTO notification_status (user_id, sched_conf_id) VALUES (?, ?)':
			'DELETE FROM notification_status WHERE user_id = ? AND sched_conf_id = ?'),
			array($userId, $schedConfId)
		);
	}

	/**
	 * Delete notification status entries by schedConf ID
	 * @param $schedConfId int
	 */
	function deleteNotificationStatusBySchedConf($schedConfId) {
		return $this->update(
			'DELETE FROM notification_status WHERE sched_conf_id = ?', $schedConfId
		);
	}

	/**
	 * Delete notification status entries by user ID
	 * @param $userId int
	 */
	function deleteNotificationStatusByUserId($userId) {
		return $this->update(
			'DELETE FROM notification_status WHERE user_id = ?', $userId
		);
	}

	/**
	 * Retrieve a list of users who wish to receive updates about the specified sched conf.
	 * @param $schedConfId int
	 * @return DAOResultFactory matching Users
	 */
	function &getNotifiableUsersBySchedConfId($schedConfId) {
		$userDao =& DAORegistry::getDAO('UserDAO');

		$result =& $this->retrieve(
			'SELECT u.* FROM users u, notification_status n WHERE u.user_id = n.user_id AND n.sched_conf_id = ?',
			$schedConfId
		);

		$returner = new DAOResultFactory($result, $userDao, '_returnUserFromRow');
		return $returner;
	}

	/**
	 * Retrieve the number of users who wish to receive updates about the specified schedConf.
	 * @param $schedConfId int
	 * @return int
	 */
	function getNotifiableUsersCount($schedConfId) {
		$userDao =& DAORegistry::getDAO('UserDAO');

		$result =& $this->retrieve(
			'SELECT count(*) FROM notification_status n WHERE n.sched_conf_id = ?',
			$schedConfId
		);

		$returner = $result->fields[0];

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
