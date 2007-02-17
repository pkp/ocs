{**
 * trackPolicies.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the tracks.
 * 
 * TODO: - Crosses and checkmarks for the track properties are currently just
 * 		text. Replace with images.
 *
 * $Id$
 *}

{assign var="pageTitle" value="schedConf.trackPolicies"}
{include file="common/header.tpl"}

{foreach from=$tracks item=track}
	<h4>{$track->getTrackTitle()}</h4>
	{if strlen($track->getPolicy()) > 0}
		<p>{$track->getPolicy()|nl2br}</p>
	{/if}

	{assign var="hasDirectors" value=0}
	{foreach from=$trackDirectors item=trackTrackDirectors key=key}
		{if $key == $track->getTrackId()}
			{foreach from=$trackTrackDirectors item=trackDirector}
				{if 0 == $hasDirectors++}
				{translate key="user.role.directors"}
				<ul class="plain">
				{/if}
				<li>{$trackDirector->getFirstName()|escape} {$trackDirector->getLastName()|escape}{if strlen($trackDirector->getAffiliation()) > 0}, {$trackDirector->getAffiliation()|escape}{/if}</li>
			{/foreach}
		{/if}
	{/foreach}
	{if $hasDirectors}</ul>{/if}

	<table class="plain" width="60%">
		<tr>
			<td width="33%">{if !$track->getDirectorRestricted()}{icon name="checked"}{else}{icon name="unchecked"}{/if} {translate key="manager.tracks.open"}</td>
			<td width="33%">{if $track->getMetaIndexed()}{icon name="checked"}{else}{icon name="unchecked"}{/if} {translate key="manager.tracks.indexed"}</td>
			<td width="34%">{if $track->getMetaReviewed()}{icon name="checked"}{else}{icon name="unchecked"}{/if} {translate key="manager.tracks.peerReviewed"}</td>
		</tr>
	</table>
{/foreach}

{include file="common/footer.tpl"}
