<?php

/**
 * @file SchedConfSetupHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfSetupHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for conference setup functions. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class SchedConfSetupHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function SchedConfSetupHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display conference setup form for the selected step.
	 * Displays setup index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function schedConfSetup($args) {
		$this->validate();
		$this->setupTemplate(true);

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 3) {

			$formClass = "SchedConfSetupStep{$step}Form";
			import("manager.form.schedConfSetup.$formClass");

			$setupForm = new $formClass();
			if ($setupForm->isLocaleResubmit()) {
				$setupForm->readInputData();
			} else {
				$setupForm->initData();
			}
			$setupForm->display();

		} else {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('helpTopicId','conference.currentConferences.setup');
			$templateMgr->display('manager/schedConfSetup/index.tpl');
		}
	}

	/**
	 * Save changes to conference settings.
	 * @param $args array first parameter is the step being saved
	 */
	function saveSchedConfSetup($args) {
		$this->validate();

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 3) {

			$this->setupTemplate(true);

			$formClass = "SchedConfSetupStep{$step}Form";
			import("manager.form.schedConfSetup.$formClass");

			$setupForm = new $formClass();
			$setupForm->readInputData();
			$formLocale = $setupForm->getFormLocale();

			// Check for any special cases before trying to save
			switch ($step) {
				case 1:
					if (Request::getUserVar('addSponsor')) {
						// Add a sponsor
						$editData = true;
						$sponsors = $setupForm->getData('sponsors');
						array_push($sponsors, array());
						$setupForm->setData('sponsors', $sponsors);

					} else if (($delSponsor = Request::getUserVar('delSponsor')) && count($delSponsor) == 1) {
						// Delete a sponsor
						$editData = true;
						list($delSponsor) = array_keys($delSponsor);
						$delSponsor = (int) $delSponsor;
						$sponsors = $setupForm->getData('sponsors');
						array_splice($sponsors, $delSponsor, 1);
						$setupForm->setData('sponsors', $sponsors);

					} else if (Request::getUserVar('addContributor')) {
						// Add a contributor
						$editData = true;
						$contributors = $setupForm->getData('contributors');
						array_push($contributors, array());
						$setupForm->setData('contributors', $contributors);

					} else if (($delContributor = Request::getUserVar('delContributor')) && count($delContributor) == 1) {
						// Delete a contributor
						$editData = true;
						list($delContributor) = array_keys($delContributor);
						$delContributor = (int) $delContributor;
						$contributors = $setupForm->getData('contributors');
						array_splice($contributors, $delContributor, 1);
						$setupForm->setData('contributors', $contributors);
					}
					break;
				case 2:
					if ($action = Request::getUserVar('paperTypeAction')) {
						$editData = true;
						$setupForm->readInputData();
						$paperTypeId = Request::getUserVar('paperTypeId');
						switch ($action) {
							case 'movePaperTypeUp':
							case 'movePaperTypeDown':
								if (isset($setupForm->_data['paperTypes'][$paperTypeId]['seq'])) $setupForm->_data['paperTypes'][$paperTypeId]['seq'] += ($action == 'movePaperTypeUp'?-1.5:1.5);
								uasort($setupForm->_data['paperTypes'], 'seqSortFunction');
								break;
							case 'deletePaperType':
								unset($setupForm->_data['paperTypes'][$paperTypeId]);
								break;
							case 'createPaperType':
								$setupForm->_data['paperTypes']['a' . count($setupForm->_data['paperTypes'])] = array(); // Hack: non-numeric for new
								break;
						}
					} elseif (Request::getUserVar('addChecklist')) {
						// Add a checklist item
						$editData = true;
						$checklist = $setupForm->getData('submissionChecklist');
						if (!isset($checklist[$formLocale]) || !is_array($checklist[$formLocale])) {
							$checklist[$formLocale] = array();
							$lastOrder = 0;
						} else {
							$lastOrder = $checklist[$formLocale][count($checklist[$formLocale])-1]['order'];
						}
						array_push($checklist[$formLocale], array('order' => $lastOrder+1));
						$setupForm->setData('submissionChecklist', $checklist);

					} else if (($delChecklist = Request::getUserVar('delChecklist')) && count($delChecklist) == 1) {
						// Delete a checklist item
						$editData = true;
						list($delChecklist) = array_keys($delChecklist);
						$delChecklist = (int) $delChecklist;
						$checklist = $setupForm->getData('submissionChecklist');
						if (!isset($checklist[$formLocale])) $checklist[$formLocale] = array();
						array_splice($checklist[$formLocale], $delChecklist, 1);
						$setupForm->setData('submissionChecklist', $checklist);
					}

					if (!isset($editData)) {
						// Reorder checklist items
						$checklist = $setupForm->getData('submissionChecklist');
						if (isset($checklist[$formLocale]) && is_array($checklist[$formLocale])) {
							usort($checklist[$formLocale], create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
						}
						$setupForm->setData('submissionChecklist', $checklist);
					}
					break;
			}

			if (!isset($editData) && $setupForm->validate()) {
				$setupForm->execute();
				Request::redirect(null, null, null, 'schedConfSetupSaved', $step);
			} else {
				$setupForm->display();
			}

		} else {
			Request::redirect();
		}
	}

	/**
	 * Display a "Scheduled conference settings saved" page
	 */
	function schedConfSetupSaved($args) {
		$this->validate();

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 3) {
			$this->setupTemplate(true);
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('setupStep', $step);
			$templateMgr->assign('helpTopicId', 'conference.currentConferences.setup');
			if ($step == 3) $templateMgr->assign('showSetupHints',true);
			$templateMgr->display('manager/schedConfSetup/settingsSaved.tpl');
		}
	}
}

function seqSortFunction($a, $b) {
	return 2*($a['seq'] - $b['seq']); // Must return integer
}

?>
