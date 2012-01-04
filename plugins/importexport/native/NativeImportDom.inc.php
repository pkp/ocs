<?php

/**
 * @file NativeImportDom.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportDom
 * @ingroup plugins_importexport_native
 *
 * @brief Native import/export plugin DOM functions for import
 */

//$Id$

import('xml.XMLCustomWriter');

class NativeImportDom {
	function importPapers(&$conference, &$schedConf, &$nodes, &$track, &$papers, &$errors, &$user, $isCommandLine) {
		$papers = array();
		$dependentItems = array();
		$hasErrors = false;
		foreach ($nodes as $node) {
			$result = NativeImportDom::handlePaperNode($conference, $schedConf, $node, $track, $paper, $publishedPaper, $paperErrors, $user, $isCommandLine, $dependentItems);
			if ($result) {
				$papers[] = $paper;
			} else {
				$errors = array_merge($errors, $paperErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) {
			NativeImportDom::cleanupFailure ($dependentItems);
			return false;
		}
		return true;
	}

	function importPaper(&$conference, &$schedConf, &$node, &$track, &$paper, &$errors, &$user, $isCommandLine) {
		$dependentItems = array();
		$result = NativeImportDom::handlePaperNode($conference, $schedConf, $node, $track, $paper, $publishedPaper, $errors, $user, $isCommandLine, $dependentItems);
		if (!$result) {
			NativeImportDom::cleanupFailure ($dependentItems);
		}
		return $result;
	}

	function isRelativePath($url) {
		// FIXME This is not very comprehensive, but will work for now.
		if (NativeImportDom::isAllowedMethod($url)) return false;
		if ($url[0] == '/') return false;
		return true;
	}

	function isAllowedMethod($url) {
		$allowedPrefixes = array(
			'http://',
			'ftp://',
			'https://',
			'ftps://'
		);
		foreach ($allowedPrefixes as $prefix) {
			if (substr($url, 0, strlen($prefix)) === $prefix) return true;
		}
		return false;
	}

	function handleTrackNode(&$conference, &$schedConf, &$trackNode, &$errors, &$user, $isCommandLine, &$dependentItems, $trackIndex = null) {
		$trackDao =& DAORegistry::getDAO('TrackDAO');

		$errors = array();

		$schedConfSupportedLocales = array_keys($schedConf->getSupportedLocaleNames()); // => sched conf locales must be set up before
		$schedConfPrimaryLocale = $schedConf->getPrimaryLocale();

		// The following page or two is responsible for locating an
		// existing track based on title and/or abbrev, or, if none
		// can be found, creating a new one.

		$titles = array();
		for ($index=0; ($node = $trackNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $schedConfPrimaryLocale;
			} elseif (!in_array($locale, $schedConfSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.trackTitleLocaleUnsupported', array('trackTitle' => $node->getValue(), 'locale' => $locale));
				return false; // or ignore this error?
			}
			$titles[$locale] = $node->getValue();
		}
		if (empty($titles)) {
			$errors[] = array('plugins.importexport.native.import.error.trackTitleMissing');
			return false;
		}	

		$abbrevs = array();
		for ($index=0; ($node = $trackNode->getChildByName('abbrev', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $schedConfPrimaryLocale;
			} elseif (!in_array($locale, $schedConfSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.trackAbbrevLocaleUnsupported', array('trackAbbrev' => $node->getValue(), 'locale' => $locale));
				return false; // or ignore this error?
			}
			$abbrevs[$locale] = $node->getValue();
		}

		$identifyTypes = array();
		for ($index=0; ($node = $trackNode->getChildByName('identify_type', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $schedConfPrimaryLocale;
			} elseif (!in_array($locale, $schedConfSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.trackIdentifyTypeLocaleUnsupported', array('trackIdentifyType' => $node->getValue(), 'locale' => $locale));
				return false; // or ignore this error?	
			}
			$identifyTypes[$locale] = $node->getValue();
		}
		
		$policies = array();
		for ($index=0; ($node = $trackNode->getChildByName('policy', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $schedConfPrimaryLocale;
			} elseif (!in_array($locale, $schedConfSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.trackPolicyLocaleUnsupported', array('trackPolicy' => $node->getValue(), 'locale' => $locale));
				return false; // or ignore this error?
			}
			$policies[$locale] = $node->getValue();
		}
		
		// $title and, optionally, $abbrev contain information that can
		// be used to locate an existing track. Otherwise, we'll
		// create a new one. If $title and $abbrev each match an
		// existing track, but not the same track, throw an error.
		$track = null;
		$foundTrackId = $foundTrackTitle = null;
		$index = 0;
		foreach($titles as $locale => $title) {
			$track = $trackDao->getTrackByTitle($title, $schedConf->getId());
			if ($track) {
				$trackId = $track->getId();
				if ($foundTrackId) { 
					if ($foundTrackId != $trackId) {
						// Mismatching tracks found. Throw an error.
						$errors[] = array('plugins.importexport.native.import.error.trackTitleMismatch', array('track1Title' => $title, 'track2Title' => $foundTrackTitle));
						return false;
					}
				} else if ($index > 0) { 
						// the current title matches, but the prev titles didn't => error
						$errors[] = array('plugins.importexport.native.import.error.trackTitleMatch', array('trackTitle' => $title));
						return false;
				}
				$foundTrackId = $trackId;
				$foundTrackTitle = $title;
			} else { 
				if ($foundTrackId) {
					// a prev title matched, but the current doesn't => error
					$errors[] = array('plugins.importexport.native.import.error.trackTitleMatch', array('trackTitle' => $foundTrackTitle));
					return false;				
				}
			}
			$index++;
		}

		// check abbrevs:
		$abbrevTrack = null;
		$foundTrackId = $foundTrackAbbrev = null;
		$index = 0;
		foreach($abbrevs as $locale => $abbrev) {
			$abbrevTrack = $trackDao->getTrackByAbbrev($abbrev, $schedConf->getId());
			if ($abbrevTrack) {
				$trackId = $abbrevTrack->getTrackId();
				if ($foundTrackId) {
					if ($foundTrackId != $trackId) {
						// Mismatching tracks found. Throw an error.
						$errors[] = array('plugins.importexport.native.import.error.trackAbbrevMismatch', array('track1Abbrev' => $abbrev, 'track2Abbrev' => $foundTrackAbbrev));
						return false;
					}
				} else if ($index > 0) {
					// the current abbrev matches, but the prev abbrevs didn't => error
					$errors[] = array('plugins.importexport.native.import.error.trackAbbrevMatch', array('trackAbbrev' => $trackAbbrev));
					return false;	
				}
				$foundTrackId = $trackId;
				$foundTrackAbbrev = $abbrev;
			} else {
				if ($foundTrackId) {
					// a prev abbrev matched, but the current doesn't => error
					$errors[] = array('plugins.importexport.native.import.error.trackAbbrevMatch', array('trackAbbrev' => $foundTrackAbbrev));
					return false;				
				}
			}
			$index++;
		}		

		if (!$track && !$abbrevTrack) {
			// The track was not matched. Create one.
			// Note that because tracks are global-ish,
			// we're not maintaining a list of created
			// tracks to delete in case the import fails.
			unset($track);
			$track = new Track();

			$track->setTitle($titles, null);
			$track->setAbbrev($abbrevs, null);
			$track->setIdentifyType($identifyTypes, null);
			$track->setPolicy($policies, null);
			$track->setSchedConfId($schedConf->getId());
			$track->setSequence(REALLY_BIG_NUMBER);
			$track->setMetaIndexed(1);
			$track->setEditorRestricted(1);
			$track->setTrackId($trackDao->insertTrack($track));
			$trackDao->resequenceTracks($schedConf>getSchedConfId());
		}

		if (!$track && $abbrevTrack) {
			unset($track);
			$track =& $abbrevTrack;
		}

		// $track *must* now contain a valid track, whether it was
		// found amongst existing tracks or created anew.

		$hasErrors = false;
		for ($index = 0; ($node = $trackNode->getChildByName('paper', $index)); $index++) {
			if (!NativeImportDom::handlePaperNode($conference, $schedConf, $node, $track, $paper, $publishedPaper, $paperErrors, $user, $isCommandLine, $dependentItems)) {
				$errors = array_merge($errors, $paperErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) return false;

		return true;
	}

	function handlePaperNode(&$conference, &$schedConf, &$paperNode, &$track, &$paper, &$publishedPaper, &$errors, &$user, $isCommandLine, &$dependentItems) {
		$errors = array();

		$conferenceSupportedLocales = array_keys($conference->getSupportedLocaleNames()); // => locales must be set up before
		$conferencePrimaryLocale = $conference->getPrimaryLocale();

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$paperDao =& DAORegistry::getDAO('PaperDAO');

		$paper = new Paper();
		$paper->setSchedConfId($schedConf->getId());
		$paper->setUserId($user->getId());
		$paper->setTrackId($track->getId());
		$paper->setStatus(STATUS_PUBLISHED);
		$paper->setSubmissionProgress(0);
		$paper->setCurrentStage(REVIEW_STAGE_ABSTRACT);
		$paper->setReviewMode(REVIEW_MODE_ABSTRACTS_ALONE);
		$paper->setDateSubmitted(Core::getCurrentDate());
		$paper->stampStatusModified();

		$titleExists = false;
		for ($index=0; ($node = $paperNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperTitleLocaleUnsupported', array('paperTitle' => $node->getValue(), 'locale' => $locale));
				return false;
			}
			$paper->setTitle($node->getValue(), $locale);
			$titleExists = true;
		}
		if (!$titleExists || $paper->getTitle($conferencePrimaryLocale) == "") {
			$errors[] = array('plugins.importexport.native.import.error.paperTitleMissing', array('trackTitle' => $track->getLocalizedTitle()));
			return false;
		}	

		for ($index=0; ($node = $paperNode->getChildByName('abstract', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperAbstractLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;
			}
			$paper->setAbstract($node->getValue(), $locale);
		}

		if (($indexingNode = $paperNode->getChildByName('indexing'))) {			
			for ($index=0; ($node = $indexingNode->getChildByName('discipline', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $conferencePrimaryLocale;
				} elseif (!in_array($locale, $conferenceSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.paperDisciplineLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
					return false;
				}
				$paper->setDiscipline($node->getValue(), $locale);
			}
			for ($index=0; ($node = $indexingNode->getChildByName('type', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $conferencePrimaryLocale;
				} elseif (!in_array($locale, $conferenceSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.paperTypeLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
					return false;
				}
				$paper->setType($node->getValue(), $locale);
			}
			for ($index=0; ($node = $indexingNode->getChildByName('subject', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $conferencePrimaryLocale;
				} elseif (!in_array($locale, $conferenceSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.paperSubjectLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
					return false;
				}
				$paper->setSubject($node->getValue(), $locale);
			}
			for ($index=0; ($node = $indexingNode->getChildByName('subject_class', $index)); $index++) {
				$locale = $node->getAttribute('locale');
				if ($locale == '') {
					$locale = $conferencePrimaryLocale;
				} elseif (!in_array($locale, $conferenceSupportedLocales)) {
					$errors[] = array('plugins.importexport.native.import.error.paperSubjectClassLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
					return false;
				}
				$paper->setSubjectClass($node->getValue(), $locale);
			}
			
			if (($coverageNode = $indexingNode->getChildByName('coverage'))) {
				for ($index=0; ($node = $coverageNode->getChildByName('geographical', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $conferencePrimaryLocale;
					} elseif (!in_array($locale, $conferenceSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.paperCoverageGeoLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
						return false;
					}
					$paper->setCoverageGeo($node->getValue(), $locale);
				}
				for ($index=0; ($node = $coverageNode->getChildByName('chronological', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $conferencePrimaryLocale;
					} elseif (!in_array($locale, $conferenceSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.paperCoverageChronLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
						return false;
					}
					$paper->setCoverageChron($node->getValue(), $locale);
				}
				for ($index=0; ($node = $coverageNode->getChildByName('sample', $index)); $index++) {
					$locale = $node->getAttribute('locale');
					if ($locale == '') {
						$locale = $conferencePrimaryLocale;
					} elseif (!in_array($locale, $conferenceSupportedLocales)) {
						$errors[] = array('plugins.importexport.native.import.error.paperCoverageSampleLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
						return false;
					}
					$paper->setCoverageSample($node->getValue(), $locale);
				}
			}
		}

		for ($index=0; ($node = $paperNode->getChildByName('sponsor', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSponsorLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;
			}
			$paper->setSponsor($node->getValue(), $locale);
		}
		
		if (($node = $paperNode->getChildByName('pages'))) $paper->setPages($node->getValue());
		if (($language = $paperNode->getAttribute('language'))) $paper->setLanguage($language); 

		/* --- Handle authors --- */
		$hasErrors = false;
		for ($index = 0; ($node = $paperNode->getChildByName('author', $index)); $index++) {
			if (!NativeImportDom::handleAuthorNode($conference, $schedConf, $node, $track, $paper, $authorErrors)) {
				$errors = array_merge($errors, $authorErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) return false;

		$paperDao->insertPaper($paper);
		$dependentItems[] = array('paper', $paper);

		// Log the import in the paper event log.
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent(
			$paper->getId(),
			PAPER_LOG_PAPER_IMPORT,
			PAPER_LOG_DEFAULT,
			0,
			'log.imported',
			array('userName' => $user->getFullName(), 'paperId' => $paper->getId())
		);

		// Insert published paper entry.
		$publishedPaper = new PublishedPaper();
		$publishedPaper->setPaperId($paper->getId());
		$publishedPaper->setSchedConfId($schedConf->getId());

		if (($node = $paperNode->getChildByName('date_published'))) {
			$publishedDate = strtotime($node->getValue());
			if ($publishedDate === -1) {
				$errors[] = array('plugins.importexport.native.import.error.invalidDate', array('value' => $node->getValue()));
				return false;
			} else {
				$publishedPaper->setDatePublished($publishedDate);
			}
		}
		$node = $paperNode->getChildByName('open_access');
		$publishedPaper->setSeq(REALLY_BIG_NUMBER);
		$publishedPaper->setViews(0);
		$publishedPaper->setPublicPaperId($paperNode->getAttribute('public_id'));

		$publishedPaper->setPubId($publishedPaperDao->insertPublishedPaper($publishedPaper));

		$publishedPaperDao->resequencePublishedPapers($track->getId(), $schedConf->getId());

		/* --- Galleys (html or otherwise handled simultaneously) --- */
		import('file.PaperFileManager');
		$paperFileManager = new PaperFileManager($paper->getId());

		/* --- Handle galleys --- */
		$hasErrors = false;
		$galleyCount = 0;
		for ($index=0; $index < count($paperNode->children); $index++) {
			$node = $paperNode->children[$index];

			if ($node->getName() == 'htmlgalley') $isHtml = true;
			elseif ($node->getName() == 'galley') $isHtml = false;
			else continue;
			
			if (!NativeImportDom::handleGalleyNode($conference, $schedConf, $node, $track, $paper, $galleyErrors, $isCommandLine, $isHtml, $galleyCount, $paperFileManager)) {
				$errors = array_merge($errors, $galleyErrors);
				$hasErrors = true;
			}
			$galleyCount++;
		}
		if ($hasErrors) return false;

		/* --- Handle supplemental files --- */
		$hasErrors = false;
		for ($index = 0; ($node = $paperNode->getChildByName('supplemental_file', $index)); $index++) {
			if (!NativeImportDom::handleSuppFileNode($conference, $schedConf, $node, $track, $paper, $suppFileErrors, $isCommandLine, $paperFileManager)) {
				$errors = array_merge($errors, $suppFileErrors);
				$hasErrors = true;
			}
		}
		if ($hasErrors) return false;

		// Index the inserted paper.
		import('search.PaperSearchIndex');
		PaperSearchIndex::indexPaperMetadata($paper);
		PaperSearchIndex::indexPaperFiles($paper);

		return true;
	}

	function handleAuthorNode(&$conference, &$schedConf, &$authorNode, &$track, &$paper, &$errors) {
		$errors = array();

		$conferenceSupportedLocales = array_keys($conference->getSupportedLocaleNames()); // => conference locales must be set up before
		$conferencePrimaryLocale = $conference->getPrimaryLocale();
		
		$author = new Author();
		if (($node = $authorNode->getChildByName('firstname'))) $author->setFirstName($node->getValue());
		if (($node = $authorNode->getChildByName('middlename'))) $author->setMiddleName($node->getValue());
		if (($node = $authorNode->getChildByName('lastname'))) $author->setLastName($node->getValue());
		if (($node = $authorNode->getChildByName('affiliation'))) $author->setAffiliation($node->getValue());
		if (($node = $authorNode->getChildByName('country'))) $author->setCountry($node->getValue());
		if (($node = $authorNode->getChildByName('email'))) $author->setEmail($node->getValue());
		if (($node = $authorNode->getChildByName('url'))) $author->setUrl($node->getValue());
		for ($index=0; ($node = $authorNode->getChildByName('biography', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperAuthorBiographyLocaleUnsupported', array('authorFullName' => $author->getFullName(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;
			} 
			$author->setBiography($node->getValue(), $locale);
		}
		
		$author->setPrimaryContact($authorNode->getAttribute('primary_contact')==='true'?1:0);
		$paper->addAuthor($author);		// instead of $author->setSequence($index+1);

		return true;

	}

	function handleGalleyNode(&$conference, &$schedConf, &$galleyNode, &$track, &$paper, &$errors, $isCommandLine, $isHtml, $galleyCount, &$paperFileManager) {
		$errors = array();

		$conferenceSupportedLocales = array_keys($conference->getSupportedLocaleNames()); // => locales must be set up before
		$conferencePrimaryLocale = $conference->getPrimaryLocale();

		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');

		if ($isHtml) $galley = new PaperHtmlGalley();
		else $galley = new PaperGalley();

		$galley->setPaperId($paper->getId());
		$galley->setSequence($galleyCount);

		// just conference supported locales?
		$locale = $galleyNode->getAttribute('locale');
		if ($locale == '') {
			$locale = $conferencePrimaryLocale;
		} elseif (!in_array($locale, $conferenceSupportedLocales)) {
			$errors[] = array('plugins.importexport.native.import.error.galleyLocaleUnsupported', array('paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
			return false;
		} 
		$galley->setLocale($locale); 
		
		/* --- Galley Label --- */
		if (!($node = $galleyNode->getChildByName('label'))) {
			$errors[] = array('plugins.importexport.native.import.error.galleyLabelMissing', array('paperTitle' => $paper->getLocalizedTitle(), 'trackTitle' => $track->getLocalizedTitle()));
			return false;
		}
		$galley->setLabel($node->getValue());

		/* --- Galley File --- */
		if (!($node = $galleyNode->getChildByName('file'))) {
			$errors[] = array('plugins.importexport.native.import.error.galleyFileMissing', array('paperTitle' => $paper->getLocalizedTitle(), 'trackTitle' => $track->getLocalizedTitle()));
			return false;
		}

		if (($href = $node->getChildByName('href'))) {
			$url = $href->getAttribute('src');
			if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
				if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
					// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
					$url = PWD . '/' . $url;
				}

				if (($fileId = $paperFileManager->copyPublicFile($url, $href->getAttribute('mime_type')))===false) {
					$errors[] = array('plugins.importexport.native.import.error.couldNotCopy', array('url' => $url));
					return false;
				}
			}
		}
		if (($embed = $node->getChildByName('embed'))) {
			if (($type = $embed->getAttribute('encoding')) !== 'base64') {
				$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
				return false;
			}
			$originalName = $embed->getAttribute('filename');
			if (($fileId = $paperFileManager->writePublicFile($originalName, base64_decode($embed->getValue()), $embed->getAttribute('mime_type')))===false) {
				$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
				return false;
			}
		}
		if (!isset($fileId)) {
			$errors[] = array('plugins.importexport.native.import.error.galleyFileMissing', array('paperTitle' => $paper->getLocalizedTitle(), 'trackTitle' => $track->getLocalizedTitle()));
			return false;
		}
		$galley->setFileId($fileId);
		$galleyDao->insertGalley($galley);

		if ($isHtml) {
			$result = NativeImportDom::handleHtmlGalleyNodes($galleyNode, $paperFileManager, $galley, $errors, $isCommandLine);
			if (!$result) return false;
		}

		return true;
		
	}

	/**
	 * Handle subnodes of a <galley> node specific to HTML galleys, such as stylesheet
	 * and image files. FIXME: The parameter lists, here and elsewhere, are getting
	 * ridiculous.
	 */
	function handleHtmlGalleyNodes(&$galleyNode, &$paperFileManager, &$galley, &$errors, &$isCommandLine) {
		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');

		foreach ($galleyNode->children as $node) {
			$isStylesheet = ($node->getName() == 'stylesheet');
			$isImage = ($node->getName() == 'image');
			if (!$isStylesheet && !$isImage) continue;

			if (($href = $node->getChildByName('href'))) {
				$url = $href->getAttribute('src');
				if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
					if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
						// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
						$url = PWD . '/' . $url;
					}

					if (($fileId = $paperFileManager->copyPublicFile($url, $href->getAttribute('mime_type')))===false) {
						$errors[] = array('plugins.importexport.native.import.error.couldNotCopy', array('url' => $url));
						return false;
					}
				}
			}
			if (($embed = $node->getChildByName('embed'))) {
				if (($type = $embed->getAttribute('encoding')) !== 'base64') {
					$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
					return false;
				}
				$originalName = $embed->getAttribute('filename');
				if (($fileId = $paperFileManager->writePublicFile($originalName, base64_decode($embed->getValue()), $embed->getAttribute('mime_type')))===false) {
					$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
					return false;
				}
			}

			if (!isset($fileId)) continue;

			if ($isStylesheet) {
				$galley->setStyleFileId($fileId);
				$paperGalleyDao->updateGalley($galley);
			} else {
				$paperGalleyDao->insertGalleyImage($galley->getId(), $fileId);
			}
		}
		return true;
	}

	function handleSuppFileNode(&$conference, &$schedConf, &$suppNode, &$track, &$paper, &$errors, $isCommandLine, &$paperFileManager) {
		$errors = array();

		$conferenceSupportedLocales = array_keys($conference->getSupportedLocaleNames()); // => locales must be set up before
		$conferencePrimaryLocale = $conference->getPrimaryLocale();

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			
		$suppFile = new SuppFile();
		$suppFile->setPaperId($paper->getId());

		for ($index=0; ($node = $suppNode->getChildByName('title', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSuppFileTitleLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setTitle($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('creator', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSuppFileCreatorLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setCreator($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('subject', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSuppFileSubjectLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setSubject($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('type_other', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSuppFileTypeOtherLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setTypeOther($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('description', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSuppFileDescriptionLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setDescription($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('publisher', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSuppFilePublisherLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setPublisher($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('sponsor', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSuppFileSponsorLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setSponsor($node->getValue(), $locale);
		}
		for ($index=0; ($node = $suppNode->getChildByName('source', $index)); $index++) {
			$locale = $node->getAttribute('locale');
			if ($locale == '') {
				$locale = $conferencePrimaryLocale;
			} elseif (!in_array($locale, $conferenceSupportedLocales)) {
				$errors[] = array('plugins.importexport.native.import.error.paperSuppFileSourceLocaleUnsupported', array('suppFileTitle' => $node->getValue(), 'paperTitle' => $paper->getLocalizedTitle(), 'locale' => $locale));
				return false;			
			}
			$suppFile->setSource($node->getValue(), $locale);
		}
		if (($node = $suppNode->getChildByName('date_created'))) {
			$createdDate = strtotime($node->getValue());
			if ($createdDate !== -1) $suppFile->setDateCreated($createdDate);
		}

		switch (($suppType = $suppNode->getAttribute('type'))) {
			case 'research_instrument': $suppFile->setType(__('author.submit.suppFile.researchInstrument')); break;
			case 'research_materials': $suppFile->setType(__('author.submit.suppFile.researchMaterials')); break;
			case 'research_results': $suppFile->setType(__('author.submit.suppFile.researchResults')); break;
			case 'transcripts': $suppFile->setType(__('author.submit.suppFile.transcripts')); break;
			case 'data_analysis': $suppFile->setType(__('author.submit.suppFile.dataAnalysis')); break;
			case 'data_set': $suppFile->setType(__('author.submit.suppFile.dataSet')); break;
			case 'source_text': $suppFile->setType(__('author.submit.suppFile.sourceText')); break;
			case 'other': $suppFile->setType(''); break;
			default:
				$errors[] = array('plugins.importexport.native.import.error.unknownSuppFileType', array('suppFileType' => $suppType));
				return false;
		}
		
		$suppFile->setLanguage($suppNode->getAttribute('language'));
		$suppFile->setPublicSuppFileId($suppNode->getAttribute('public_id'));

		if (!($fileNode = $suppNode->getChildByName('file'))) {
			$errors[] = array('plugins.importexport.native.import.error.suppFileMissing', array('paperTitle' => $paper->getLocalizedTitle(), 'trackTitle' => $track->getLocalizedTitle()));
			return false;
		}

		if (($href = $fileNode->getChildByName('href'))) {
			$url = $href->getAttribute('src');
			if ($isCommandLine || NativeImportDom::isAllowedMethod($url)) {
				if ($isCommandLine && NativeImportDom::isRelativePath($url)) {
					// The command-line tool does a chdir; we need to prepend the original pathname to relative paths so we're not looking in the wrong place.
					$url = PWD . '/' . $url;
				}

				if (($fileId = $paperFileManager->copySuppFile($url, $href->getAttribute('mime_type')))===false) {
					$errors[] = array('plugins.importexport.native.import.error.couldNotCopy', array('url' => $url));
					return false;
				}
			}
		}
		if (($embed = $fileNode->getChildByName('embed'))) {
			if (($type = $embed->getAttribute('encoding')) !== 'base64') {
				$errors[] = array('plugins.importexport.native.import.error.unknownEncoding', array('type' => $type));
				return false;
			}
			$originalName = $embed->getAttribute('filename');
			if (($fileId = $paperFileManager->writeSuppFile($originalName, base64_decode($embed->getValue()), $embed->getAttribute('mime_type')))===false) {
				$errors[] = array('plugins.importexport.native.import.error.couldNotWriteFile', array('originalName' => $originalName));
				return false;
			}
		}

		if (!$fileId) {
			$errors[] = array('plugins.importexport.native.import.error.suppFileMissing', array('paperTitle' => $paper->getLocalizedTitle(), 'trackTitle' => $track->getLocalizedTitle()));
			return false;
		}

		$suppFile->setFileId($fileId);
		$suppFileDao->insertSuppFile($suppFile);
			
		return true;
		
	}
	
	function cleanupFailure (&$dependentItems) {
		$paperDao =& DAORegistry::getDAO('PaperDAO');

		foreach ($dependentItems as $dependentItem) {
			$type = array_shift($dependentItem);
			$object = array_shift($dependentItem);

			switch ($type) {
				case 'paper':
					$paperDao->deletePaper($object);
					break;
				default:
					fatalError ('cleanupFailure: Unimplemented type');
			}
		}
	}
}

?>
