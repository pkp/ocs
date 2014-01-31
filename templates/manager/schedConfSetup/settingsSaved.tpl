{**
 * stepSaved.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show confirmation after saving settings.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.schedConfSetup.schedConfSetup"}
{include file="manager/schedConfSetup/setupHeader.tpl"}

{if $showSetupHints}
	{url|assign:"conferenceManagementUrl" page="manager"}
	<p>{translate key="manager.setup.finalSchedConfStepSavedNotes" conferenceManagementUrl=$conferenceManagementUrl}</p>
{else}
	<p>{translate key="manager.setup.conferenceSetupUpdated"}</p>
{/if}

{if $setupStep == 1}
<div id="step1"><span class="disabled">&lt;&lt; {translate key="navigation.previousStep"}</span> | <a href="{url op="schedConfSetup" path="2"}">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

{elseif $setupStep == 2}
<div id="step2"><a href="{url op="schedConfSetup" path="1"}">&lt;&lt; {translate key="navigation.previousStep"}</a> | <a href="{url op="schedConfSetup" path="3"}">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

{elseif $setupStep == 3}
<div id="step3"><a href="{url op="schedConfSetup" path="2"}">&lt;&lt; {translate key="navigation.previousStep"}</a> | <span class="disabled">{translate key="navigation.nextStep"} &gt;&gt;</span></div>
{/if}

{include file="common/footer.tpl"}
