{**
 * editorialPolicies.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference / Editorial Policies.
 * 
 * $Id$
 *}
{assign var="pageTitle" value="about.editorialPolicies"}
{include file="common/header.tpl"}

<ul class="plain">
	{if $currentSchedConf->getLocalizedSetting('overview') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="overview"}">{translate key="schedConf.overview"}</a></li>{/if}
	{if $currentSchedConf->getLocalizedSetting('reviewPolicy') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="peerReviewProcess"}">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('archiveAccessPolicy') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiveAccessPolicy"}">{translate key="about.archiveAccessPolicy"}</a></li>{/if}
	{if $currentSchedConf && ($currentSchedConf->getLocalizedSetting('delayedOpenAccessPolicy') || $currentSchedConf->getSetting('enablePresenterSelfArchive'))}<li>&#187; <a href="{url op="editorialPolicies" anchor="openAccessPolicy"}">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if $conferenceSettings.enableLockss && $currentConference->getLocalizedSetting('lockssLicense') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiving"}">{translate key="about.archiving"}</a></li>{/if}
	{foreach key=key from=$currentConference->getLocalizedSetting('customAboutItems') item=customAboutItem}
		{if !empty($customAboutItem.title)}
			<li>&#187; <a href="{url op="editorialPolicies" anchor=custom`$key`}">{$customAboutItem.title|escape}</a></li>
		{/if}
	{/foreach}
</ul>

{if $currentSchedConf->getLocalizedSetting('overview') != ''}
<a name="overview"></a><h3>{translate key="schedConf.overview"}</h3>
<p>{$currentSchedConf->getLocalizedSetting('overview')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if $currentSchedConf && $currentSchedConf->getLocalizedSetting('reviewPolicy') != ''}<a name="peerReviewProcess"></a><h3>{translate key="about.peerReviewProcess"}</h3>
<p>{$currentSchedConf->getLocalizedSetting('reviewPolicy')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if $currentConference->getLocalizedSetting('archiveAccessPolicy') != ''}
<a name="archiveAccessPolicy"></a><h3>{translate key="about.archiveAccessPolicy"}</h3>
	<p>{$currentConference->getLocalizedSetting('archiveAccessPolicy')|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if $currentSchedConf && ($currentSchedConf->getSetting('enableDelayedOpenAccess') || $currentSchedConf->getSetting('enablePresenterSelfArchive'))}
<a name="openAccessPolicy"></a><h3>{translate key="about.openAccessPolicy"}</h3>
	{if $currentSchedConf->getLocalizedSetting('delayedOpenAccessPolicy')}
			<h4>{translate key="about.delayedOpenAccess"}</h4> 
			<p>{$currentSchedConf->getLocalizedSetting('delayedOpenAccessPolicy')|escape}</p>
	{/if}
	{if $currentSchedConf->getSetting('enablePresenterSelfArchive')} 
		<h4>{translate key="about.presenterSelfArchive"}</h4> 
		<p>{$currentSchedConf->getLocalizedSetting('presenterSelfArchivePolicy')|nl2br}</p>
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
