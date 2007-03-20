{**
 * presenterDetails.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published papers by presenter.
 *
 * $Id$
 *}

{assign var="pageTitle" value="search.presenterDetails"}
{include file="common/header.tpl"}

<h3>{$lastName}, {$firstName}{if $middleName} {$middleName}{/if}{if $affiliation} {$affiliation}{/if}</h3>
<ul>
{foreach from=$publishedPapers item=paper}
	{assign var=schedConfId value=$paper->getSchedConfId()}
	{assign var=schedConf value=$schedConfs[$schedConfId]}
	{assign var=schedConfUnavailable value=$schedConfsUnavailable.$schedConfId}
	{assign var=trackId value=$paper->getTrackId()}
	{assign var=track value=$tracks[$trackId]}
	{if $schedConf->getEnabled() && !$schedConfUnavailable}
	<li>

		<i><a href="{url schedConf=$schedConf->getPath()}">{$schedConf->getFullTitle()|escape}</a> - {$track->getTitle()|escape}</i><br />
		{$paper->getPaperTitle()|strip_unsafe_html}<br/>
		<a href="{url schedConf=$schedConf->getPath() page="paper" op="view" path=$paper->getBestPaperId()}" class="file">{translate key="paper.abstract"}</a>
		{foreach from=$paper->getGalleys() item=galley name=galleyList}
			&nbsp;<a href="{url schedConf=$schedConf->getPath() page="paper" op="view" path=$paper->getBestPaperId()|to_array:$galley->getGalleyId()}" class="file">{$galley->getLabel()|escape}</a>
		{/foreach}
	</li>
	{/if}
{/foreach}
</ul>

{include file="common/footer.tpl"}
