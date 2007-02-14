<?php

/**
 * Upgrade.inc.php
 *
 * Copyright (c) 2005-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package install
 *
 * Perform system upgrade.
 *
 * $Id$
 */

import('install.Installer');

class Upgrade extends Installer {

	/**
	 * Constructor.
	 * @param $params array upgrade parameters
	 */
	function Upgrade($params) {
		parent::Installer('upgrade.xml', $params);
	}
	

	/**
	 * Returns true iff this is an upgrade process.
	 */
	function isUpgrade() {
		return true;
	}

	//
	// Upgrade actions
	//
	
	/**
	 * Rebuild the search index.
	 * @return boolean
	 */
	function rebuildSearchIndex() {
		import('search.PaperSearchIndex');
		PaperSearchIndex::rebuildIndex();
		return true;
	}

	/**
	 * For upgrade to 2.1.1: Designate original versions as review versions
	 * in all cases where review versions aren't designated. (#2144)
	 */
	function designateReviewVersions() {
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$presenterSubmissionDao =& DAORegistry::getDAO('PresenterSubmissionDAO');
		import('submission.presenter.PresenterAction');

		$conferences =& $conferenceDao->getConferences();
		while ($conference =& $conferences->next()) {
			$papers =& $paperDao->getPapersByConferenceId($conference->getConferenceId());
			while ($paper =& $papers->next()) {
				if (!$paper->getReviewFileId() && $paper->getSubmissionProgress() == 0) {
					$presenterSubmission =& $presenterSubmissionDao->getPresenterSubmission($paper->getPaperId());
					PresenterAction::designateReviewVersion($presenterSubmission, true);
				}
				unset($paper);
			}
			unset($conference);
		}
		return true;
	}

	/**
	 * For upgrade to 2.1.1: Migrate the RT settings from the rt_settings
	 * table to conference settings and drop the rt_settings table.
	 */
	function migrateRtSettings() {
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$result =& $rtDao->retrieve('SELECT * FROM rt_settings');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$rt =& new ConferenceRT($row['conference_id']);
			$rt->setEnabled(true); // No toggle in prior OJS; assume true
			$rt->setVersion($row['version_id']);
			$rt->setAbstract(true); // No toggle in prior OJS; assume true
			$rt->setCaptureCite($row['capture_cite']);
			$rt->setBibFormat($row['bib_format']);
			$rt->setViewMetadata($row['view_metadata']);
			$rt->setSupplementaryFiles($row['supplementary_files']);
			$rt->setPrinterFriendly($row['printer_friendly']);
			$rt->setPresenterBio($row['presenter_bio']);
			$rt->setDefineTerms($row['define_terms']);
			$rt->setAddComment($row['add_comment']);
			$rt->setEmailPresenter($row['email_presenter']);
			$rt->setEmailOthers($row['email_others']);
			$rtDao->updateConferenceRT($rt);
			unset($rt);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Drop the table once all settings are migrated.
		$rtDao->update('DROP TABLE rt_settings');
		return true;
	}
}

?>
