{**
 * editorialPolicies.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference / Editorial Policies.
 * 
 * TODO: - Crosses and checkmarks for the track properties are currently just
 * 		text. Replace with images.
 *		 - Editor Bio link doesn't exist yet.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.editorialPolicies"}
{include file="common/header.tpl"}

<ul class="plain">
	{if !empty($conferenceSettings.focusScopeDesc)}<li>&#187; <a href="{url op="editorialPolicies" anchor="focusAndScope"}">{translate key="about.focusAndScope"}</a></li>{/if}
	{if !empty($tracks)}<li>&#187; <a href="{url op="editorialPolicies" anchor="trackPolicies"}">{translate key="about.trackPolicies"}</a></li>{/if}
	{if !empty($conferenceSettings.reviewPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="peerReviewProcess"}">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if !empty($conferenceSettings.pubFreqPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="publicationFrequency"}">{translate key="about.publicationFrequency"}</a></li>{/if}
	{if !empty($conferenceSettings.openAccessPolicy) || !empty($conferenceSettings.enableDelayedOpenAccess) || !empty($conferenceSettings.enablePresenterSelfArchive)}<li>&#187; <a href="{url op="editorialPolicies" anchor="openAccessPolicy"}">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if $conferenceSettings.enableLockss && !empty($conferenceSettings.lockssLicense)}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiving"}">{translate key="about.archiving"}</a></li>{/if}
	{foreach key=key from=$conferenceSettings.customAboutItems item=customAboutItem}
		{if !empty($customAboutItem.title)}
			<li>&#187; <a href="{url op="editorialPolicies" anchor=custom`$key`}">{$customAboutItem.title|escape}</a></li>
		{/if}
	{/foreach}
</ul>

{if !empty($conferenceSettings.focusScopeDesc)}
<a name="focusAndScope"></a><h3>{translate key="about.focusAndScope"}</h3>
<p>{$conferenceSettings.focusScopeDesc|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($tracks)}
	<a name="trackPolicies"></a><h3>{translate key="about.trackPolicies"}</h3>
	{foreach from=$tracks item=track}
		<h4>{$track->getTrackTitle()}</h4>
		{if strlen($track->getPolicy()) > 0}
			<p>{$track->getPolicy()|nl2br}</p>
		{/if}
	
		{assign var="hasEditors" value=0}
		{foreach from=$trackDirectors item=trackTrackDirectors key=key}
			{if $key == $track->getTrackId()}
				{foreach from=$trackTrackDirectors item=trackDirector}
					{if 0 == $hasEditors++}
					{translate key="user.role.editors"}
					<ul class="plain">
					{/if}
					<li>{$trackDirector->getFirstName()|escape} {$trackDirector->getLastName()|escape}{if strlen($trackDirector->getAffiliation()) > 0}, {$trackDirector->getAffiliation()|escape}{/if}</li>
				{/foreach}
			{/if}
		{/foreach}
		{if $hasEditors}</ul>{/if}
	
		<table class="plain" width="60%">
			<tr>
				<td width="50%">{if !$track->getEditorRestricted()}{icon name="checked"}{else}{icon name="unchecked"}{/if} {translate key="manager.tracks.open"}</td>
				<td width="50%">{if $track->getMetaIndexed()}{icon name="checked"}{else}{icon name="unchecked"}{/if} {translate key="manager.tracks.indexed"}</td>
			</tr>
		</table>
	{/foreach}

	<div class="separator">&nbsp;</div>
{/if}

{if !empty($conferenceSettings.reviewPolicy)}<a name="peerReviewProcess"></a><h3>{translate key="about.peerReviewProcess"}</h3>
<p>{$conferenceSettings.reviewPolicy|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($conferenceSettings.pubFreqPolicy)}
<a name="publicationFrequency"></a><h3>{translate key="about.publicationFrequency"}</h3>
<p>{$conferenceSettings.pubFreqPolicy|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{if !empty($conferenceSettings.openAccessPolicy) || !empty($conferenceSettings.enableDelayedOpenAccess) || !empty($conferenceSettings.enablePresenterSelfArchive)}
<a name="openAccessPolicy"></a><h3>{translate key="about.openAccessPolicy"}</h3>
	{if empty($conferenceSettings.enableSubscriptions) && !empty($conferenceSettings.openAccessPolicy)} 
		<p>{$conferenceSettings.openAccessPolicy|nl2br}</p>
	{/if}
	{if !empty($conferenceSettings.enableSubscriptions) && !empty($conferenceSettings.enableDelayedOpenAccess)}
		<h4>{translate key="about.delayedOpenAccess"}</h4> 
		<p>{translate key="about.delayedOpenAccessDescription1"} {$conferenceSettings.delayedOpenAccessDuration} {translate key="about.delayedOpenAccessDescription2"}</p>
	{/if}
	{if !empty($conferenceSettings.enableSubscriptions) && !empty($conferenceSettings.enablePresenterSelfArchive)} 
		<h4>{translate key="about.presenterSelfArchive"}</h4> 
		<p>{$conferenceSettings.presenterSelfArchivePolicy|nl2br}</p>
	{/if}

<div class="separator">&nbsp;</div>
{/if}

{if $conferenceSettings.enableLockss && !empty($conferenceSettings.lockssLicense)}
<a name="archiving"></a><h3>{translate key="about.archiving"}</h3>
<p>{$conferenceSettings.lockssLicense|nl2br}</p>

<div class="separator">&nbsp;</div>
{/if}

{foreach key=key from=$conferenceSettings.customAboutItems item=customAboutItem name=customAboutItems}
	{if !empty($customAboutItem.title)}
		<a name="custom{$key}"></a><h3>{$customAboutItem.title|escape}</h3>
		<p>{$customAboutItem.content|nl2br}</p>
		{if !$smarty.foreach.customAboutItems.last}<div class="separator">&nbsp;</div>{/if}
	{/if}
{/foreach}

{include file="common/footer.tpl"}
