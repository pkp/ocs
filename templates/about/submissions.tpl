{**
 * submissions.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference / Submissions.
 *
 * $Id$
 *}
{assign var="pageTitle" value="about.submissions"}
{include file="common/header.tpl"}

<ul class="plain">
	<li>&#187; <a href="{url page="about" op="submissions" anchor="onlineSubmissions"}">{translate key="about.onlineSubmissions"}</a></li>
	{if $currentConference->getLocalizedSetting('presenterGuidelines') != ''}<li>&#187; <a href="{url page="about" op="submissions" anchor="presenterGuidelines"}">{translate key="about.presenterGuidelines"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('copyrightNotice') != ''}<li>&#187; <a href="{url page="about" op="submissions" anchor="copyrightNotice"}">{translate key="about.copyrightNotice"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('privacyStatement') != ''}<li>&#187; <a href="{url page="about" op="submissions" anchor="privacyStatement"}">{translate key="about.privacyStatement"}</a></li>{/if}
</ul>

<a name="onlineSubmissions"></a><h3>{translate key="about.onlineSubmissions"}</h3>
<p>
	{translate key="about.onlineSubmissions.haveAccount" conferenceTitle=$siteTitle|escape}<br />
	<a href="{url page="login"}" class="action">{translate key="about.onlineSubmissions.login"}</a>
</p>
<p>
	{translate key="about.onlineSubmissions.needAccount"}<br />
	<a href="{url page="user" op="account"}" class="action">{translate key="about.onlineSubmissions.registration"}</a>
</p>
<p>{translate key="about.onlineSubmissions.registrationRequired"}</p>

<div class="separator">&nbsp;</div>

{if $currentConference->getLocalizedSetting('presenterGuidelines') != ''}
<a name="presenterGuidelines"></a><h3>{translate key="about.presenterGuidelines"}</h3>
<p>{$currentConference->getLocalizedSetting('presenterGuidelines')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

<a name="submissionPreparationChecklist"></a><h3>{translate key="about.submissionPreparationChecklist"}</h3>
<ol>
	{foreach from=$currentConference->getLocalizedSetting('submissionChecklist') item=checklistItem}
		<li>{$checklistItem.content|nl2br}</li>	
	{/foreach}
</ol>

<div class="separator">&nbsp;</div>

{if $currentConference->getLocalizedSetting('copyrightNotice') != ''}
<a name="copyrightNotice"></a><h3>{translate key="about.copyrightNotice"}</h3>
<p>{$currentConference->getLocalizedSetting('copyrightNotice')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if $currentConference->getLocalizedSetting('privacyStatement') != ''}<a name="privacyStatement"></a><h3>{translate key="about.privacyStatement"}</h3>
<p>{$currentConference->getLocalizedSetting('privacyStatement')|nl2br}</p>
{/if}

{include file="common/footer.tpl"}
