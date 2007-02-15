{**
 * titleIndex.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display published papers by title
 *
 * $Id$
 *}

{assign var=pageTitle value="search.titleIndex"}

{include file="common/header.tpl"}

<br />

{if $currentConference}
	{assign var=numCols value=3}
{else}
	{assign var=numCols value=4}
{/if}

<table width="100%" class="listing">
<tr><td colspan="{$numCols}" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	{if !$currentConference}<td width="20%">{translate key="conference.conference"}</td>{/if}
	<td width="20%">{translate key="schedConf.schedConf"}</td>
	<td width="{if !$currentConference}60%{else}80%{/if}" colspan="2">{translate key="paper.title"}</td>
</tr>
<tr><td colspan="{$numCols}" class="headseparator">&nbsp;</td></tr>

{iterate from=results item=result}
{assign var=publishedPaper value=$result.publishedPaper}
{assign var=paper value=$result.paper}
{assign var=track value=$result.track}
{assign var=schedConf value=$result.schedConf}
{assign var=schedConfAvailable value=$result.schedConfAvailable}
{assign var=conference value=$result.conference}
<tr valign="top">
	{if !$currentConference}<td><a href="{url conference=$conference->getPath()}">{$conference->getTitle()|escape}</a></td>{/if}
	<td><a href="{url conference=$conference->getPath() page="schedConf" op="view"}">{$schedConf->getTitle()|escape}</a></td>
	<td width="35%">{$paper->getPaperTitle()|strip_unsafe_html}</td>
	<td width="25%" align="right">
			<a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page="paper" op="view" path=$publishedPaper->getBestPaperId($conference)}" class="file">{translate key="paper.abstract"}</a>
		{if ($schedConfAvailable)}
		{foreach from=$publishedPaper->getGalleys() item=galley name=galleyList}
			&nbsp;
			<a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page="paper" op="view" path=$publishedPaper->getBestPaperId($conference)|to_array:$galley->getGalleyId()}" class="file">{$galley->getLabel()|escape}</a>
		{/foreach}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="{$numCols}" style="padding-left: 30px;font-style: italic;">
		{foreach from=$paper->getPresenters() item=presenter name=presenterList}
			{$presenter->getFullName()|escape}{if !$smarty.foreach.presenterList.last},{/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="{$numCols}" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $results->wasEmpty()}
<tr>
<td colspan="{$numCols}" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="{$numCols}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td {if !$currentConference}colspan="2" {/if}align="left">{page_info iterator=$results}</td>
		<td colspan="2" align="right">{page_links iterator=$results name="search"}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
