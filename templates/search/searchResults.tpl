{**
 * searchResults.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display paper search results.
 *
 * $Id$
 *}
{strip}
{assign var=pageTitle value="search.searchResults"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function ensureKeyword() {
	if (document.search.query.value == '') {
		alert({/literal}'{translate|escape:"jsparam" key="search.noKeywordError"}'{literal});
		return false;
	}
	document.search.submit();
	return true;
}
// -->
{/literal}
</script>

<br/>

{if $basicQuery}
	<form method="post" name="search" action="{url op="results"}">
		<input type="text" size="40" maxlength="255" class="textField" name="query" value="{$basicQuery|escape}"/>&nbsp;&nbsp;
		<input type="hidden" name="searchField" value="{$searchField|escape}"/>
		<input type="submit" class="button defaultButton" onclick="ensureKeyword();" value="{translate key="common.search"}"/>
	</form>
	<br />
{else}
	<form name="revise" action="{url op="advanced"}" method="post">
		<input type="hidden" name="query" value="{$query|escape}"/>
		<input type="hidden" name="searchConference" value="{$searchConference|escape}"/>
		<input type="hidden" name="author" value="{$author|escape}"/>
		<input type="hidden" name="title" value="{$title|escape}"/>
		<input type="hidden" name="fullText" value="{$fullText|escape}"/>
		<input type="hidden" name="supplementaryFiles" value="{$supplementaryFiles|escape}"/>
		<input type="hidden" name="discipline" value="{$discipline|escape}"/>
		<input type="hidden" name="subject" value="{$subject|escape}"/>
		<input type="hidden" name="type" value="{$type|escape}"/>
		<input type="hidden" name="coverage" value="{$coverage|escape}"/>
		<input type="hidden" name="dateFromMonth" value="{$dateFromMonth|escape}"/>
		<input type="hidden" name="dateFromDay" value="{$dateFromDay|escape}"/>
		<input type="hidden" name="dateFromYear" value="{$dateFromYear|escape}"/>
		<input type="hidden" name="dateToMonth" value="{$dateToMonth|escape}"/>
		<input type="hidden" name="dateToDay" value="{$dateToDay|escape}"/>
		<input type="hidden" name="dateToYear" value="{$dateToYear|escape}"/>
	</form>
	<a href="javascript:document.revise.submit()" class="action">{translate key="search.reviseSearch"}</a><br />&nbsp;
{/if}

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
	<td width="{if !$currentConference}20%{else}40%{/if}">{translate key="schedConf.schedConf"}</td>
	<td width="60%" colspan="2">{translate key="paper.title"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>

{iterate from=results item=result}
{assign var=publishedPaper value=$result.publishedPaper}
{assign var=paper value=$result.paper}
{assign var=schedConf value=$result.schedConf}
{assign var=schedConfAvailable value=$result.schedConfAvailable}
{assign var=conference value=$result.conference}
{assign var=track value=$result.track}
<tr valign="top">
	{if !$currentConference}<td><a href="{url conference=$conference->getPath()}">{$conference->getConferenceTitle()|escape}</a></td>{/if}
	<td><a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath()}">{$schedConf->getSchedConfTitle()|escape}</a></td>
	<td width="30%">{$paper->getLocalizedTitle()|strip_unsafe_html}</td>
	<td width="30%" align="right">
		<a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page="paper" op="view" path=$publishedPaper->getBestPaperId($conference)}" class="file">{translate key="paper.abstract"}</a>{foreach from=$publishedPaper->getLocalizedGalleys() item=galley name=galleyList}&nbsp;<a href="{url conference=$conference->getPath() schedConf=$schedConf->getPath() page="paper" op="view" path=$publishedPaper->getBestPaperId($conference)|to_array:$galley->getId()}" class="file">{$galley->getGalleyLabel()|escape}</a>{/foreach}
	</td>
</tr>
<tr>
	<td colspan="{$numCols|escape}" style="padding-left: 30px;font-style: italic;">
		{foreach from=$paper->getAuthors() item=authorItem name=authorList}
			{$authorItem->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if !$results || $results->wasEmpty()}
<tr>
<td colspan="{$numCols|escape}" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td {if !$currentConference}colspan="2" {/if}align="left">{page_info iterator=$results}</td>
		{if $basicQuery}
			<td colspan="2" align="right">{page_links anchor="results" iterator=$results name="search" query=$basicQuery searchField=$searchField}</td>
		{else}
			<td colspan="2" align="right">{page_links anchor="results" iterator=$results name="search" query=$query searchConference=$searchConference author=$author title=$title fullText=$fullText supplementaryFiles=$supplementaryFiles discipline=$discipline subject=$subject type=$type coverage=$coverage dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear}</td>
		{/if}
	</tr>
{/if}
</table>

{translate key="search.syntaxInstructions"}
</div>
{include file="common/footer.tpl"}
