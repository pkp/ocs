<?php

/**
 * @defgroup oai_format_nlm
 */

/**
 * @file classes/oai/format/OAIMetadataFormat_NLM.inc.php
 *
 * Copyright (c) 2005-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_NLM
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- NLM 2.3
 */


class OAIMetadataFormat_NLM extends OAIMetadataFormat {

	/**
	 * @see OAIMetadataFormat#toXml
	 */
	function toXml(&$record, $format = null) {
		$conference =& $record->getData('conference');
		$schedConf =& $record->getData('schedConf');
		$paper =& $record->getData('paper');
		$track =& $record->getData('track');
		$galleys =& $record->getData('galleys');

		$paperId = $paper->getId();

		$primaryLocale = $conference->getPrimaryLocale();

		// If possible, use the paper presentation date for the paper date fields.
		// Otherwise, use the date published (i.e. the date it was marked "completed"
		// in the workflow).
		if ($datePublished = $paper->getStartTime()) {
			$datePublished = strtotime($datePublished);
		} else {
			$datePublished = strtotime($paper->getDatePublished());
		}

		$response = "<article\n" .
			"\txmlns=\"http://dtd.nlm.nih.gov/publishing/2.3\"\n" .
			"\txmlns:xlink=\"http://www.w3.org/1999/xlink\"\n" .
			"\txmlns:mml=\"http://www.w3.org/1998/Math/MathML\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://dtd.nlm.nih.gov/publishing/2.3\n" .
			"\thttp://dtd.nlm.nih.gov/publishing/2.3/xsd/journalpublishing.xsd\"\n" .
			(($s = $track->getLocalizedIdentifyType())!=''?"\tarticle-type=\"" . htmlspecialchars(Core::cleanVar($s)) . "\"":'') .
			"\txml:lang=\"" . strtoupper(substr($primaryLocale, 0, 2)) . "\">\n" .
			"\t<front>\n" .
			"\t\t<journal-meta>\n" .
			"\t\t\t<journal-id journal-id-type=\"other\">" . htmlspecialchars(Core::cleanVar(($s = Config::getVar('oai', 'nlm_journal_id'))!=''?$s:$conference->getPath() . '-' . $schedConf->getPath())) . "</journal-id>\n" .
			"\t\t\t<journal-title>" . htmlspecialchars(Core::cleanVar($schedConf->getLocalizedName())) . "</journal-title>\n";

		// Include translated scheduled conference titles
		foreach ($schedConf->getTitle(null) as $locale => $title) {
			if ($locale == $primaryLocale) continue;
			$response .= "\t\t\t<trans-title xml:lang=\"" . strtoupper(substr($locale, 0, 2)) . "\">" . htmlspecialchars(Core::cleanVar($title)) . "</trans-title>\n";
		}

		$response .=
			"\t\t</journal-meta>\n" .
			"\t\t<article-meta>\n" .
			"\t\t\t<article-id pub-id-type=\"other\">" . htmlspecialchars(Core::cleanVar($paper->getId())) . "</article-id>\n" .
			"\t\t\t<article-categories><subj-group subj-group-type=\"heading\"><subject>" . htmlspecialchars(Core::cleanVar($track->getLocalizedTitle())) . "</subject></subj-group></article-categories>\n" .
			"\t\t\t<title-group>\n" .
			"\t\t\t\t<article-title>" . htmlspecialchars(Core::cleanVar(strip_tags($paper->getLocalizedTitle()))) . "</article-title>\n";

		// Include translated journal titles
		foreach ($paper->getTitle(null) as $locale => $title) {
			if ($locale == $primaryLocale) continue;
			$response .= "\t\t\t\t<trans-title xml:lang=\"" . strtoupper(substr($locale, 0, 2)) . "\">" . htmlspecialchars(Core::cleanVar(strip_tags($title))) . "</trans-title>\n";
		}

		$response .=
			"\t\t\t</title-group>\n" .
			"\t\t\t<contrib-group>\n";

		// Include authors
		foreach ($paper->getAuthors() as $author) {
			$response .=
				"\t\t\t\t<contrib " . ($author->getPrimaryContact()?'corresp="yes" ':'') . "contrib-type=\"author\">\n" .
				"\t\t\t\t\t<name name-style=\"western\">\n" .
				"\t\t\t\t\t\t<surname>" . htmlspecialchars(Core::cleanVar($author->getLastName())) . "</surname>\n" .
				"\t\t\t\t\t\t<given-names>" . htmlspecialchars(Core::cleanVar($author->getFirstName()) . (($s = $author->getMiddleName()) != ''?" $s":'')) . "</given-names>\n" .
				"\t\t\t\t\t</name>\n" .
				(($s = $author->getLocalizedAffiliation()) != ''?"\t\t\t\t\t<aff>" . htmlspecialchars(Core::cleanVar($s)) . "</aff>\n":'') .
				"\t\t\t\t\t<email>" . htmlspecialchars(Core::cleanVar($author->getEmail())) . "</email>\n" .
				(($s = $author->getUrl()) != ''?"\t\t\t\t\t<uri>" . htmlspecialchars(Core::cleanVar($s)) . "</uri>\n":'') .
				"\t\t\t\t</contrib>\n";
		}

		// Include editorships (optimized)
		$response .= $this->getEditorialInfo($conference->getId());

		$response .=
			"\t\t\t</contrib-group>\n" .
			"\t\t\t<pub-date pub-type=\"epub\">\n" .
			"\t\t\t\t<day>" . strftime('%d', $datePublished) . "</day>\n" .
			"\t\t\t\t<month>" . strftime('%m', $datePublished) . "</month>\n" .
			"\t\t\t\t<year>" . strftime('%Y', $datePublished) . "</year>\n" .
			"\t\t\t</pub-date>\n";

		$response .=
			"\t\t\t<permissions>\n" .
			((($s = $conference->getLocalizedSetting('copyrightNotice')) != '')?"\t\t\t\t<copyright-statement>" . htmlspecialchars(Core::cleanVar($s)) . "</copyright-statement>\n":'') .
			"\t\t\t\t<copyright-year>" . strftime('%Y', $datePublished) . "</copyright-year>\n" .
			"\t\t\t</permissions>\n" .
			"\t\t\t<self-uri xlink:href=\"" . htmlspecialchars(Core::cleanVar(Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'view', $paper->getId()))) . "\" />\n";

		// Include galley links
		foreach ($paper->getGalleys() as $galley) {
			$response .= "\t\t\t<self-uri content-type=\"" . htmlspecialchars(Core::cleanVar($galley->getFileType())) . "\" xlink:href=\"" . htmlspecialchars(Core::cleanVar(Request::url($conference->getPath(), $schedConf->getPath(), 'paper', 'view', array($paper->getId(), $galley->getId())))) . "\" />\n";
		}

		// Include abstract(s)
		$abstract = htmlspecialchars(Core::cleanVar(strip_tags($paper->getLocalizedAbstract())));
		if (!empty($abstract)) {
			$abstract = "<p>$abstract</p>";
			$response .= "\t\t\t<abstract xml:lang=\"" . strtoupper(substr($primaryLocale, 0, 2)) . "\">$abstract</abstract>\n";
		}
		if (is_array($paper->getAbstract(null))) foreach ($paper->getAbstract(null) as $locale => $abstract) {
			if ($locale == $primaryLocale || empty($abstract)) continue;
			$abstract = htmlspecialchars(Core::cleanVar(strip_tags($abstract)));
			if (empty($abstract)) continue;
			$abstract = "<p>$abstract</p>";
			$response .= "\t\t\t<abstract-trans xml:lang=\"" . strtoupper(substr($locale, 0, 2)) . "\">$abstract</abstract-trans>\n";
		}

		$subjects = array();
		if (is_array($paper->getSubject(null))) foreach ($paper->getSubject(null) as $locale => $subject) {
			$s = array_map('trim', explode(';', Core::cleanVar($subject)));
			if (!empty($s)) $subjects[$locale] = $s;
		}
		if (!empty($subjects)) foreach ($subjects as $locale => $s) {
			$response .= "\t\t\t<kwd-group xml:lang=\"" . strtoupper(substr($locale, 0, 2)) . "\">\n";
			foreach ($s as $subject) $response .= "\t\t\t\t<kwd>" . htmlspecialchars($subject) . "</kwd>\n";
			$response .= "\t\t\t</kwd-group>\n";
		}

		$locationCity = $schedConf->getSetting('locationCity');
		$locationCountry = $schedConf->getSetting('locationCountry');
		if (empty($locationCity) && empty($locationCountry)) $confLoc = '';
		elseif (empty($locationCity) && !empty($locationCountry)) $confLoc = $locationCountry;
		elseif (empty($locationCountry)) $confLoc = $locationCity;
		else $confLoc = "$locationCity, $locationCountry";

		$response .=
			"\t\t\t<conference>\n" .
			"\t\t\t\t<conf-date>" . strftime('%Y-%m-%d', $schedConf->getSetting('startDate')) . "</conf-date>\n" .
			"\t\t\t\t<conf-name>" . htmlspecialchars(Core::cleanVar($schedConf->getLocalizedName())) . "</conf-name>\n" .
			"\t\t\t\t<conf-acronym>" . htmlspecialchars(Core::cleanVar($schedConf->getLocalizedAcronym())) . "</conf-acronym>\n" .
			(!empty($confLoc)?"\t\t\t\t<conf-loc>" . htmlspecialchars(Core::cleanVar($confLoc)) . "</conf-loc>\n":'') .
			"\t\t\t</conference>\n" .
			"\t\t</article-meta>\n" .
			"\t</front>\n";

		// Include body text (for search indexing only)
		import('classes.search.PaperSearchIndex');
		$text = '';
		// $galleys = $paper->getGalleys();

		// Give precedence to HTML galleys, as they're quickest to parse
		usort($galleys, create_function('$a, $b', 'return $a->isHtmlGalley()?-1:1;'));

		// Determine any access limitations. If there are, do not
		// provide the full-text.
		import('classes.schedConf.SchedConfAction');
		$mayViewProceedings = SchedConfAction::mayViewProceedings($schedConf);

		if ($mayViewProceedings) foreach ($galleys as $galley) {
			$parser =& SearchFileParser::fromFile($galley);
			if ($parser && $parser->open()) {
				while(($s = $parser->read()) !== false) $text .= $s;
				$parser->close();
			}

			if ($galley->isHtmlGalley()) $text = strip_tags($text);
			unset($galley);
			// Use the first parseable galley.
			if (!empty($text)) break;
		}
		if (!empty($text)) $response .= "\t<body><p>" . htmlspecialchars(Core::cleanVar(Core::cleanVar($text))) . "</p></body>\n";

		$response .= "</article>";

		return $response;
	}

	function getEditorialInfo($conferenceId) {
		static $editorialInfo = array();
		if (isset($editorialInfo[$conferenceId])) return $editorialInfo[$conferenceId];

		$response = '';
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleMap = array(ROLE_ID_DIRECTOR => 'editor', ROLE_ID_TRACK_DIRECTOR => 'secteditor', ROLE_ID_MANAGER => 'jmanager');
		foreach ($roleMap as $roleId => $roleName) {
			$users =& $roleDao->getUsersByRoleId($roleId, $conferenceId);
			$isFirst = true;
			while ($user =& $users->next()) {
				$response .= "\t\t\t\t<contrib contrib-type=\"$roleName\">\n" .
					"\t\t\t\t\t<name>\n" .
					"\t\t\t\t\t\t<surname>" . htmlspecialchars(Core::cleanVar($user->getLastName())) . "</surname>\n" .
					"\t\t\t\t\t\t<given-names>" . htmlspecialchars(Core::cleanVar($user->getFirstName() . ($user->getMiddleName() != ''?' ' . $user->getMiddleName():''))) . "</given-names>\n" .
					"\t\t\t\t\t</name>\n" .
					"\t\t\t\t</contrib>\n";
				unset($user);
			}
			unset($users);
		}
		$editorialInfo[$conferenceId] =& $response;
		return $response;
	}
}

?>
