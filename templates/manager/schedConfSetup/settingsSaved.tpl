{**
 * stepSaved.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show confirmation after saving settings.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.schedConfSetup.schedConfSetup"}
{include file="manager/schedConfSetup/setupHeader.tpl"}

<p>{translate key="manager.setup.conferenceSetupUpdated"}</p>

{if $setupStep == 1}
<div><span class="disabled">&lt;&lt; {translate key="navigation.previousStep"}</span> | <a href="{url op="schedConfSetup" path="2"}">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

{elseif $setupStep == 2}
<div><a href="{url op="schedConfSetup" path="1"}">&lt;&lt; {translate key="navigation.previousStep"}</a> | <a href="{url op="schedConfSetup" path="3"}">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

{elseif $setupStep == 3}
<div><a href="{url op="schedConfSetup" path="2"}">&lt;&lt; {translate key="navigation.previousStep"}</a> | <span class="disabled">{translate key="navigation.nextStep"} &gt;&gt;</span></div>
{/if}

{include file="common/footer.tpl"}
