<?php

/**
 * @file GroupDAO.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GroupDAO
 * @ingroup group
 * @see Group
 *
 * @brief Operations for retrieving and modifying Group objects.
 */

//$Id$

import ('group.Group');

class GroupDAO extends DAO {
	/**
	 * Retrieve a group by ID.
	 * @param $groupId int
	 * @return Group
	 */
	function &getGroup($groupId) {
		$result = &$this->retrieve(
			'SELECT * FROM groups WHERE group_id = ?', $groupId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnGroupFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}

	/**
	 * Get all groups for a conference.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @param $rangeInfo object RangeInfo object (optional)
	 * @return array
	 */
	function &getGroups($conferenceId, $schedConfId, $rangeInfo = null) {
		if($conferenceId !== -1 && $schedConfId !== -1) {
			$result = &$this->retrieveRange(
				'SELECT * FROM groups WHERE conference_id = ? AND sched_conf_id = ? ORDER BY seq',
				array($conferenceId, $schedConfId), $rangeInfo
			);
		} elseif($conferenceId !== -1) {
			$result = &$this->retrieveRange(
				'SELECT * FROM groups WHERE conference_id = ? ORDER BY seq',
				array($conferenceId), $rangeInfo
			);
		} elseif($schedConfId !== -1) {
			$result = &$this->retrieveRange(
				'SELECT * FROM groups WHERE sched_conf_id = ? ORDER BY seq',
				array($schedConfId), $rangeInfo
			);
		} else {
			$result = &$this->retrieveRange(
				'SELECT * FROM groups ORDER BY seq',
				array($schedConfId), $rangeInfo
			);
		}

		$returner =& new DAOResultFactory($result, $this, '_returnGroupFromRow');
		return $returner;
	}

	/**
	 * Get the list of fields for which locale data is stored.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title');
	}

	/**
	 * Internal function to return a Group object from a row.
	 * @param $row array
	 * @return Group
	 */
	function &_returnGroupFromRow(&$row) {
		$group = &new Group();
		$group->setGroupId($row['group_id']);
		$group->setAboutDisplayed($row['about_displayed']);
		$group->setSequence($row['seq']);
		$group->setConferenceId($row['conference_id']);
		$group->setSchedConfId($row['sched_conf_id']);
		$this->getDataObjectSettings('group_settings', 'group_id', $row['group_id'], $group);

		HookRegistry::call('GroupDAO::_returnGroupFromRow', array(&$group, &$row));

		return $group;
	}

	/**
	 * Update the settings for this object
	 * @param $group object
	 */
	function updateLocaleFields(&$group) {
		$this->updateDataObjectSettings('group_settings', $group, array(
			'group_id' => $group->getGroupId()
		));
	}

	/**
	 * Insert a new board group.
	 * @param $group Group
	 */	
	function insertGroup(&$group) {
		$this->update(
			'INSERT INTO groups
				(seq, conference_id, sched_conf_id, about_displayed)
				VALUES
				(?, ?, ?, ?)',
			array(
				$group->getSequence() == null ? 0 : $group->getSequence(),
				$group->getConferenceId(),
				$group->getSchedConfId(),
				$group->getAboutDisplayed()
			)
		);

		$group->setGroupId($this->getInsertGroupId());
		$this->updateLocaleFields($group);
		return $group->getGroupId();
	}

	/**
	 * Update an existing board group.
	 * @param $group Group
	 */
	function updateGroup(&$group) {
		$returner = $this->update(
			'UPDATE groups
				SET
					seq = ?,
					conference_id = ?,
					sched_conf_id = ?,
					about_displayed = ?
				WHERE group_id = ?',
			array(
				$group->getSequence(),
				$group->getConferenceId(),
				$group->getSchedConfId(),
				$group->getAboutDisplayed(),
				$group->getGroupId()
			)
		);

		$this->updateLocaleFields($group);
		return $returner;
	}

	/**
	 * Delete a board group, including membership info
	 * @param $conference Group
	 */
	function deleteGroup(&$group) {
		return $this->deleteGroupById($group->getGroupId());
	}

	/**
	 * Delete a board group, including membership info
	 * @param $groupId int
	 */
	function deleteGroupById($groupId) {
		$groupMembershipDao = &DAORegistry::getDAO('GroupMembershipDAO');
		$groupMembershipDao->deleteMembershipByGroupId($groupId);
		$this->update('DELETE FROM group_settings WHERE group_id = ?', $groupId);
		return $this->update('DELETE FROM groups WHERE group_id = ?', $groupId);
	}

	/**
	 * Delete board groups by conference ID, including membership info
	 * @param $conferenceId int
	 */
	function deleteGroupsByConferenceId($conferenceId) {
		$groups =& $this->getGroups($conferenceId, -1);
		while ($group =& $groups->next()) {
			$this->deleteGroup($group);
		}
	}

	/**
	 * Delete board groups by scheduled conference ID, including membership info
	 * @param $schedConfId int
	 */
	function deleteGroupsBySchedConfId($schedConfId) {
		$groups =& $this->getGroups(-1, $schedConfId);
		while ($group =& $groups->next()) {
			$this->deleteGroup($group);
		}
	}

	/**
	 * Sequentially renumber board groups in their sequence order, optionally by conference.
	 * @param $conferenceId int
	 */
	function resequenceGroups($conferenceId = null) {
		$result = &$this->retrieve(
			'SELECT group_id FROM groups ' .
			($conferenceId !== null?'WHERE conference_id = ?':'') .
			'ORDER BY seq',
			$conferenceId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($groupId) = $result->fields;
			$this->update(
				'UPDATE groups SET seq = ? WHERE group_id = ?',
				array(
					$i,
					$groupId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted board group.
	 * @return int
	 */
	function getInsertGroupId() {
		return $this->getInsertId('groups', 'group_id');
	}

}

?>
