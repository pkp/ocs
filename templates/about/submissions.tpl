{**
 * submissions.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
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
	{if !empty($conferenceSettings.presenterGuidelines)}<li>&#187; <a href="{url page="about" op="submissions" anchor="presenterGuidelines"}">{translate key="about.presenterGuidelines"}</a></li>{/if}
	{if !empty($conferenceSettings.copyrightNotice)}<li>&#187; <a href="{url page="about" op="submissions" anchor="copyrightNotice"}">{translate key="about.copyrightNotice"}</a></li>{/if}
	{if !empty($conferenceSettings.privacyStatement)}<li>&#187; <a href="{url page="about" op="submissions" anchor="privacyStatement"}">{translate key="about.privacyStatement"}</a></li>{/if}
</ul>

<a name="onlineSubmissions"></a><h3>{translate key="about.onlineSubmissions"}</h3>
<p>
	{translate key="about.onlineSubmissions.haveAccount" conferenceTitle=$siteTitle|escape}<br />
	<a href="{url page="login"}" class="action">{translate key="about.onlineSubmissions.login"}</a>
</p>
<p>
	{translate key="about.onlineSubmissions.needAccount"}<br />
	<a href="{url page="user" op="register"}" class="action">{translate key="about.onlineSubmissions.registration"}</a>
</p>
<p>{translate key="about.onlineSubmissions.registrationRequired"}</p>

<div class="separator">&nbsp;</div>

{if !empty($conferenceSettings.presenterGuidelines)}
<a name="presenterGuidelines"></a><h3>{translate key="about.presenterGuidelines"}</h3>
<p>{$conferenceSettings.presenterGuidelines|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

<a name="submissionPreparationChecklist"></a><h3>{translate key="about.submissionPreparationChecklist"}</h3>
<ol>
	{foreach from=$conferenceSettings.submissionChecklist item=checklistItem}
		<li>{$checklistItem.content|nl2br}</li>	
	{/foreach}
</ol>

<div class="separator">&nbsp;</div>

{if !empty($conferenceSettings.copyrightNotice)}
<a name="copyrightNotice"></a><h3>{translate key="about.copyrightNotice"}</h3>
<p>{$conferenceSettings.copyrightNotice|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($conferenceSettings.privacyStatement)}<a name="privacyStatement"></a><h3>{translate key="about.privacyStatement"}</h3>
<p>{$conferenceSettings.privacyStatement|nl2br}</p>
{/if}

{include file="common/footer.tpl"}
