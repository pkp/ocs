{**
 * editorialPolicies.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference / Editorial Policies.
 * 
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="about.editorialPolicies"}
{include file="common/header.tpl"}
{/strip}

<ul class="plain">
	{if $currentConference->getLocalizedSetting('focusScopeDesc') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="focusAndScope"}">{translate key="about.focusAndScope"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('reviewPolicy') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="peerReviewProcess"}">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('pubFreqPolicy') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="publicationFrequency"}">{translate key="about.publicationFrequency"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('archiveAccessPolicy') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiveAccessPolicy"}">{translate key="about.archiveAccessPolicy"}</a></li>{/if}
	{if !empty($conferenceSettings.enableDelayedOpenAccess) || !empty($conferenceSettings.enableAuthorSelfArchive)}<li>&#187; <a href="{url op="editorialPolicies" anchor="openAccessPolicy"}">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if $conferenceSettings.enableLockss && $currentConference->getLocalizedSetting('lockssLicense') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiving"}">{translate key="about.archiving"}</a></li>{/if}
	{foreach key=key from=$currentConference->getLocalizedSetting('customAboutItems') item=customAboutItem}
		{if !empty($customAboutItem.title)}
			<li>&#187; <a href="{url op="editorialPolicies" anchor=custom`$key`}">{$customAboutItem.title|escape}</a></li>
		{/if}
	{/foreach}
</ul>

{if $currentConference->getLocalizedSetting('focusScopeDesc') != ''}
<a name="focusAndScope"></a><h3>{translate key="about.focusAndScope"}</h3>
<p>{$currentConference->getLocalizedSetting('focusScopeDesc')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if $currentConference->getLocalizedSetting('reviewPolicy') != ''}<a name="peerReviewProcess"></a><h3>{translate key="about.peerReviewProcess"}</h3>
<p>{$currentConference->getLocalizedSetting('reviewPolicy')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if $currentConference->getLocalizedSetting('pubFreqPolicy') != ''}
<a name="publicationFrequency"></a><h3>{translate key="about.publicationFrequency"}</h3>
<p>{$currentConference->getLocalizedSetting('pubFreqPolicy')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if $currentConference->getLocalizedSetting('archiveAccessPolicy') != ''}
<a name="archiveAccessPolicy"></a><h3>{translate key="about.archiveAccessPolicy"}</h3>
	<p>{$currentConference->getLocalizedSetting('archiveAccessPolicy')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($conferenceSettings.enableDelayedOpenAccess) || !empty($conferenceSettings.enableAuthorSelfArchive)}
<a name="openAccessPolicy"></a><h3>{translate key="about.openAccessPolicy"}</h3>
	{if $conferenceSettings.enableDelayedOpenAccess}
		<h4>{translate key="about.delayedOpenAccess"}</h4> 
		<p>{translate key="about.delayedOpenAccessDescription" delayedOpenAccessDuration=$conferenceSettings.delayedOpenAccessDuration|escape}</p>
	{/if}
	{if $conferenceSettings.enableAuthorSelfArchive} 
		<h4>{translate key="about.authorSelfArchive"}</h4> 
		<p>{$currentConference->getLocalizedSetting('authorSelfArchivePolicy')|nl2br}</p>
	{/if}

<div class="separator">&nbsp;</div>
{/if}

{if $conferenceSettings.enableLockss && $currentConference->getLocalizedSetting('lockssLicense') != ''}
<a name="archiving"></a><h3>{translate key="about.archiving"}</h3>
<p>{$currentConference->getLocalizedSetting('lockssLicense')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{foreach key=key from=$currentConference->getLocalizedSetting('customAboutItems') item=customAboutItem name=customAboutItems}
	{if !empty($customAboutItem.title)}
		<a name="custom{$key|escape}"></a><h3>{$customAboutItem.title|escape}</h3>
		<p>{$customAboutItem.content|nl2br}</p>
		{if !$smarty.foreach.customAboutItems.last}<div class="separator">&nbsp;</div>{/if}
	{/if}
{/foreach}

{include file="common/footer.tpl"}
