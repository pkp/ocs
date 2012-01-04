{**
 * titleIndex.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display published papers by title
 *
 * $Id$
 *}
{strip}
{assign var=pageTitle value="search.titleIndex"}
{include file="common/header.tpl"}
{/strip}

<br />

{if $currentConference}
	{assign var=numCols value=3}
{else}
	{assign var=numCols value=4}
{/if}

<div id="results">
<table width="100%" class="listing">
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	{if !$currentConference}<td width="20%">{translate key="conference.conference"}</td>{/if}
	<td width="20%">{translate key="schedConf.schedConf"}</td>
	<td width="{if !$currentConference}60%{else}80%{/if}" colspan="2">{translate key="paper.title"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>

{iterate from=results item=result}
{assign var=publishedPaper value=$result.publishedPaper}
{assign var=paper value=$result.paper}
{assign var=track value=$result.track}
{assign var=schedConf value=$result.schedConf}
{assign var=conference value=$result.conference}
<tr valign="top">
	{if !$currentConference}<td><a href="{url conference=$conference->getPath() schedConf="index"}">{$conference->getConferenceTitle()|escape}</a></td>{/if}
	<td><a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page="schedConf"}">{$schedConf->getSchedConfTitle()|escape}</a></td>
	<td width="35%">{$paper->getLocalizedTitle()|strip_unsafe_html}</td>
	<td width="25%" align="right">
			<a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page="paper" op="view" path=$publishedPaper->getBestPaperId($conference)}" class="file">{translate key="paper.abstract"}</a>
		{if $schedConfPaperPermissions[$schedConfId]}
		{foreach from=$publishedPaper->getLocalizedGalleys() item=galley name=galleyList}
			&nbsp;
			<a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page="paper" op="view" path=$publishedPaper->getBestPaperId($conference)|to_array:$galley->getId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
		{/foreach}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="{$numCols|escape}" style="padding-left: 30px;font-style: italic;">
		{foreach from=$paper->getAuthors() item=author name=authorList}
			{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $results->wasEmpty()}
<tr>
<td colspan="{$numCols|escape}" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td {if !$currentConference}colspan="2" {/if}align="left">{page_info iterator=$results}</td>
		<td colspan="2" align="right">{page_links anchor="results" iterator=$results name="search"}</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}
