{**
 * papers.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of papers to potentially export
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.nlm.export.selectPaper"}
{assign var="pageCrumbTitle" value="plugins.importexport.nlm.export.selectPaper"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.papers.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'paperId[]') {
			elements[i].checked = !elements[i].checked;
		}
	}
}
// -->
{/literal}
</script>

<br/>

<div id="papers">
<form action="{plugin_url path="exportPapers"}" method="post" name="papers">
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td width="25%">{translate key="track.track"}</td>
		<td width="40%">{translate key="paper.title"}</td>
		<td width="25%">{translate key="paper.authors"}</td>
		<td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=papers item=paperData}
	{assign var=paper value=$paperData.paper}
	{assign var=track value=$paperData.track}
	<tr valign="top">
		<td><input type="checkbox" name="paperId[]" value="{$paper->getId()}"/></td>
		<td>{$paper->getTrackTitle()}</td>
		<td>{$paper->getLocalizedTitle()|strip_unsafe_html}</td>
		<td>{$paper->getAuthorString()|escape}</td>
		<td align="right"><a href="{plugin_url path="exportPaper"|to_array:$paper->getId()}" class="action">{translate key="common.export"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $papers->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $papers->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$papers}</td>
		<td colspan="3" align="right">{page_links anchor="papers" name="papers" iterator=$papers}</td>
	</tr>
{/if}
</table>
<p><input type="submit" value="{translate key="common.export"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /></p>
</form>
</div>
{include file="common/footer.tpl"}
