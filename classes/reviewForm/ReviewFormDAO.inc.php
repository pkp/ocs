<?php

/**
 * @file classes/reviewForm/ReviewFormDAO.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormDAO
 * @ingroup reviewForm
 * @see ReviewerForm
 *
 * @brief Operations for retrieving and modifying ReviewForm objects.
 *
 */

import ('reviewForm.ReviewForm');

class ReviewFormDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ReviewFormDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a review form by ID.
	 * @param $reviewFormId int
	 * @param $conferenceId int optional
	 * @return ReviewForm
	 */
	function &getReviewForm($reviewFormId, $conferenceId = null) {
		$params = array((int) $reviewFormId);
		if ($conferenceId !== null) $params[] = (int) $conferenceId;

		$result =& $this->retrieve (
			'SELECT	rf.review_form_id,
				rf.conference_id,
				rf.seq,
				rf.is_active,
				COUNT(rac.review_id) AS complete_count,
				COUNT(rai.review_id) AS incomplete_count
			FROM	review_forms rf
				LEFT JOIN review_assignments rac ON (
					rac.review_form_id = rf.review_form_id AND
					rac.date_confirmed IS NOT NULL
				)
				LEFT JOIN review_assignments rai ON (
					rai.review_form_id = rf.review_form_id AND
					rai.date_notified IS NOT NULL AND
					rai.date_confirmed IS NULL
				)
			WHERE	rf.review_form_id = ?
				' . ($conferenceId!==null?' AND rf.conference_id = ?':'') . '
			GROUP BY rf.conference_id, rf.review_form_id, rf.seq, rf.is_active',
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnReviewFormFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a ReviewForm object from a row.
	 * @param $row array
	 * @return ReviewForm
	 */
	function &_returnReviewFormFromRow(&$row) {
		$reviewForm = new ReviewForm();
		$reviewForm->setReviewFormId($row['review_form_id']);
		$reviewForm->setConferenceId($row['conference_id']);
		$reviewForm->setSequence($row['seq']);
		$reviewForm->setActive($row['is_active']);
		$reviewForm->setCompleteCount($row['complete_count']);
		$reviewForm->setIncompleteCount($row['incomplete_count']);

		$this->getDataObjectSettings('review_form_settings', 'review_form_id', $row['review_form_id'], $reviewForm);

		HookRegistry::call('ReviewFormDAO::_returnReviewFormFromRow', array(&$reviewForm, &$row));

		return $reviewForm;
	}

	/**
	 * Check if a review form exists with the specified ID.
	 * @param $reviewFormId int
	 * @param $conferenceId int
	 * @return boolean
	 */
	function reviewFormExists($reviewFormId, $conferenceId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM review_forms WHERE review_form_id = ? AND conference_id = ?',
			array($reviewFormId, $conferenceId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'description');
	}

	/**
	 * Update the localized fields for this table
	 * @param $reviewForm object
	 */
	function updateLocaleFields(&$reviewForm) {
		$this->updateDataObjectSettings('review_form_settings', $reviewForm, array(
			'review_form_id' => $reviewForm->getReviewFormId()
		));
	}

	/**
	 * Insert a new review form.
	 * @param $reviewForm ReviewForm
	 */
	function insertReviewForm(&$reviewForm) {
		$this->update(
			'INSERT INTO review_forms
				(conference_id, seq, is_active)
				VALUES
				(?, ?, ?)',
			array(
				$reviewForm->getConferenceId(),
				$reviewForm->getSequence() == null ? 0 : $reviewForm->getSequence(),
				$reviewForm->getActive() ? 1 : 0
			)
		);

		$reviewForm->setReviewFormId($this->getInsertReviewFormId());
		$this->updateLocaleFields($reviewForm);

		return $reviewForm->getReviewFormId();
	}

	/**
	 * Update an existing review form.
	 * @param $reviewForm ReviewForm
	 */
	function updateReviewForm(&$reviewForm) {
		$returner = $this->update(
			'UPDATE review_forms
				SET
					conference_id = ?,
					seq = ?,
					is_active = ?
				WHERE review_form_id = ?',
			array(
				$reviewForm->getConferenceId(),
				$reviewForm->getSequence(),
				$reviewForm->getActive(),
				$reviewForm->getReviewFormId()
			)
		);

		$this->updateLocaleFields($reviewForm);

		return $returner;
	}

	/**
	 * Delete a review form.
	 * @param $reviewForm reviewForm
	 */
	function deleteReviewForm(&$reviewForm) {
		return $this->deleteReviewFormById($reviewForm->getReviewFormId(), $reviewForm->getConferenceId());
	}

	/**
	 * Delete a review form by ID.
	 * @param $reviewFormId int
	 * @param $conferenceId int optional
	 */
	function deleteReviewFormById($reviewFormId, $conferenceId = null) {
		if (isset($conferenceId)) {
			$reviewForm =& $this->getReviewForm($reviewFormId, $conferenceId);
			if (!$reviewForm) return null;
			unset($reviewForm);
		}

		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElementDao->deleteReviewFormElementsByReviewForm($reviewFormId);

		$this->update('DELETE FROM review_form_settings WHERE review_form_id = ?', array($reviewFormId));
		return $this->update('DELETE FROM review_forms WHERE review_form_id = ?', array($reviewFormId));
	}

	/**
	 * Delete all review forms by conference ID.
	 * @param $conferenceId int
	 */
	function deleteReviewFormsByConferenceId($conferenceId) {
		$reviewForms = $this->getConferenceReviewForms($conferenceId);

		while (!$reviewForms->eof()) {
			$reviewForm =& $reviewForms->next();
			$this->deleteReviewFormById($reviewForm->getReviewFormId());
		}
	}

	/**
	 * Get all review forms for a conference.
	 * @param $conferenceId int
	 * @return DAOResultFactory containing matching ReviewForms
	 */
	function &getConferenceReviewForms($conferenceId) {
		$result =& $this->retrieveRange(
			'SELECT	rf.review_form_id,
				rf.conference_id,
				rf.seq,
				rf.is_active,
				COUNT(rac.review_id) AS complete_count,
				COUNT(rai.review_id) AS incomplete_count
			FROM	review_forms rf
				LEFT JOIN review_assignments rac ON (
					rac.review_form_id = rf.review_form_id AND
					rac.date_confirmed IS NOT NULL
				)
				LEFT JOIN review_assignments rai ON (
					rai.review_form_id = rf.review_form_id AND
					rai.date_notified IS NOT NULL AND
					rai.date_confirmed IS NULL
				)
			WHERE	rf.conference_id = ?
			GROUP BY rf.conference_id, rf.review_form_id, rf.seq, rf.is_active
			ORDER BY rf.seq',
			$conferenceId
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewFormFromRow');
		return $returner;
	}

	/**
	 * Get active review forms for a conference.
	 * @param $conferenceId int
	 * @param $rangeInfo object RangeInfo object (optional)
	 * @return DAOResultFactory containing matching ReviewForms
	 */
	function &getConferenceActiveReviewForms($conferenceId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT	rf.review_form_id,
				rf.conference_id,
				rf.seq,
				rf.is_active,
				COUNT(rac.review_id) AS complete_count,
				COUNT(rai.review_id) AS incomplete_count
			FROM	review_forms rf
				LEFT JOIN review_assignments rac ON (
					rac.review_form_id = rf.review_form_id AND
					rac.date_confirmed IS NOT NULL
				)
				LEFT JOIN review_assignments rai ON (
					rai.review_form_id = rf.review_form_id AND
					rai.date_notified IS NOT NULL AND
					rai.date_confirmed IS NULL
				)
			WHERE	rf.conference_id = ? AND
				rf.is_active = 1
			GROUP BY rf.conference_id, rf.review_form_id, rf.seq, rf.is_active
			ORDER BY rf.seq',
			$conferenceId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewFormFromRow');
		return $returner;
	}

	/**
	 * Get used review forms for a conference.
	 * @param $conferenceId int
	 * @param $rangeInfo object RangeInfo object (optional)
	 * @return DAOResultFactory containing matching ReviewForms
	 */
	function &getConferenceUsedReviewForms($conferenceId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT	rf.review_form_id,
				rf.conference_id,
				rf.seq,
				rf.is_active,
				COUNT(rac.review_id) AS complete_count,
				COUNT(rai.review_id) AS incomplete_count
			FROM	review_forms rf
				LEFT JOIN review_assignments rac ON (
					rac.review_form_id = rf.review_form_id AND
					rac.date_confirmed IS NOT NULL
				)
				LEFT JOIN review_assignments rai ON (
					rai.review_form_id = rf.review_form_id AND
					rai.date_notified IS NOT NULL AND
					rai.date_confirmed IS NULL
				)
			WHERE	rf.conference_id = ? AND
				rf.is_active = 1
			GROUP BY rf.conference_id, rf.review_form_id, rf.seq, rf.is_active
			HAVING COUNT(rac.review_id) > 0 OR COUNT(rai.review_id) > 0
			ORDER BY rf.seq',
			$conferenceId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewFormFromRow');
		return $returner;
	}

	/**
	 * Get unused review forms for a conference.
	 * @param $conferenceId int
	 * @param $rangeInfo object RangeInfo object (optional)
	 * @return DAOResultFactory containing matching ReviewForms
	 */
	function &getConferenceUnusedReviewForms($conferenceId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT	rf.review_form_id,
				rf.conference_id,
				rf.seq,
				rf.is_active,
				COUNT(rac.review_id) AS complete_count,
				COUNT(rai.review_id) AS incomplete_count
			FROM	review_forms rf
				LEFT JOIN review_assignments rac ON (
					rac.review_form_id = rf.review_form_id AND
					rac.date_confirmed IS NOT NULL
				)
				LEFT JOIN review_assignments rai ON (
					rai.review_form_id = rf.review_form_id AND
					rai.date_notified IS NOT NULL AND
					rai.date_confirmed IS NULL
				)
			WHERE	rf.conference_id = ?
			GROUP BY rf.conference_id, rf.review_form_id, rf.seq, rf.is_active
			HAVING COUNT(rac.review_id) = 0 AND COUNT(rai.review_id) = 0
			ORDER BY rf.seq',
			$conferenceId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewFormFromRow');
		return $returner;
	}

	/**
	 * Retrieve the IDs and titles of all review forms for a conference in an associative array.
	 * @param $conferenceId int
	 * @param $used int
	 * @return array
	 */
	function &getConferenceReviewFormTitles($conferenceId, $used) {
		$reviewFormTitles = array();

		if ($used) {
			$reviewForms =& $this->getconferenceUsedReviewForms($conferenceId);
		} else {
			$reviewForms =& $this->getconferenceUnusedReviewForms($conferenceId);
		}
		while (($reviewForm =& $reviewForms->next())) {
			$reviewFormTitles[$reviewForm->getReviewFormId()] = $reviewForm->getReviewFormTitle();
			unset($reviewForm);
		}

		return $reviewFormTitles;
	}

	/**
	 * Check if a review form exists with the specified ID.
	 * @param $reviewFormId int
	 * @param $conferenceId int optional
	 * @return boolean
	 */
	function unusedReviewFormExists($reviewFormId, $conferenceId = null) {
		$params = array((int) $reviewFormId);
		if ($conferenceId !== null) $params[] = (int) $conferenceId;

		$result =& $this->retrieve (
			'SELECT	rf.review_form_id,
				COUNT(rac.review_id) AS complete_count,
				COUNT(rai.review_id) AS incomplete_count
			FROM	review_forms rf
				LEFT JOIN review_assignments rac ON (
					rac.review_form_id = rf.review_form_id AND
					rac.date_confirmed IS NOT NULL
				)
				LEFT JOIN review_assignments rai ON (
					rai.review_form_id = rf.review_form_id AND
					rai.date_notified IS NOT NULL AND
					rai.date_confirmed IS NULL
				)
			WHERE	rf.review_form_id = ?
				' . ($conferenceId!==null?' AND rf.conference_id = ?':'') . '
			GROUP BY rf.review_form_id
			HAVING COUNT(rac.review_id) = 0 AND COUNT(rai.review_id) = 0',
			$params
		);

		$returner = $result->RecordCount() != 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber review form in their sequence order.
	 * @param $conferenceId int
	 */
	function resequenceReviewForms($conferenceId) {
		$result =& $this->retrieve(
			'SELECT review_form_id FROM review_forms WHERE conference_id = ? ORDER BY seq',
			(int) $conferenceId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($reviewFormId) = $result->fields;
			$this->update(
				'UPDATE review_forms SET seq = ? WHERE review_form_id = ?',
				array(
					$i,
					$reviewFormId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted review form.
	 * @return int
	 */
	function getInsertReviewFormId() {
		return $this->getInsertId('review_forms', 'review_form_id');
	}
}

?>
