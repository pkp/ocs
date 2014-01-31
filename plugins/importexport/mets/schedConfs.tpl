{**
 * schedConfs.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of conference papers to potentially export
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.METSExport.export.selectschedConfs"}
{assign var="pageCrumbTitle" value="plugins.importexport.METSExport.export.selectschedConfs"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function selectAll() {
        document.schedConfs.selButton.value = "Unselect All";
        document.schedConfs.selButton.attributes["onclick"].value = "javascript:unSelectAll();";
	var elements = document.schedConfs.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'SchedConfId[]') {
			elements[i].checked = true;
		}
	}
}
function unSelectAll() {
        document.schedConfs.selButton.value = "Select All";
        document.schedConfs.selButton.attributes["onclick"].value  = "javascript:selectAll();";
	var elements = document.schedConfs.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'SchedConfId[]') {
			elements[i].checked = false;
		}
	}
}
function SubmitIfAnyIsChecked() {
	var elements = document.schedConfs.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'SchedConfId[]') {
			if(elements[i].checked){ 
                            document.schedConfs.submit();
                            return true;
                         }
		}
	}
        alert("No Scheduled Conference is selected");
        return false;
}
// -->
{/literal}
</script>

<form action="{plugin_url path="exportschedConf"}" method="post" name="schedConfs">

<h3>{translate key="plugins.importexport.METSExport.settings"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="60%" class="label" align="right">{translate key="plugins.importexport.METSExport.settings.FLocat"}</td>
		<td width="40%" class="value"><input type="radio" name="contentWrapper" id="contentWrapper" value="FLocat" checked /></td>
	</tr>
	<tr valign="top">
		<td class="label" align="right">{translate key="plugins.importexport.METSExport.settings.FContent"}</td>
		<td class="value"><input type="radio" name="contentWrapper" id="contentWrapper" value="FContent" /></td>
	</tr>
	<tr>
		<td colspan="2"><div class="separator">&nbsp;</div></td>
	</tr>
	<tr valign="top">
		<td class="label" align="right">{translate key="plugins.importexport.METSExport.settings.organization"}</td>
		<td class="value"><input type="text" name="organization" id="organization" value="{$organization|escape}" size="20" maxlength="50" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" align="right">{translate key="plugins.importexport.METSExport.settings.preservationLevel"}</td>
		<td class="value">
		<input type="text" name="preservationLevel" id="preservationLevel" value="1" size="2" maxlength="1" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" align="right">{translate key="plugins.importexport.METSExport.settings.exportSuppFiles"}</td>
		<td class="value"><input type="checkbox" name="exportSuppFiles" id="exportSuppFiles" value="on" /></td>
	</tr>
</table>

<br/>

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
	    <td width="5%">&nbsp;</td>
	    <td width="60%">{translate key="schedConf.schedConf"}</td>
	    <td width="30%">{translate key="common.date"}</td>
	    <td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>

	{iterate from=schedConfs item=schedConf}
	<tr valign="top">
		<td><input type="checkbox" name="SchedConfId[]" value="{$schedConf->getId()}"/></td>
		<td>{$schedConf->getSchedConfTitle()}</td>
		<td>{$schedConf->getStartDate()|date_format:$dateFormatShort} - {$schedConf->getEndDate()|date_format:$dateFormatShort}</td>
		<td align="right"><a href="{plugin_url path="exportschedConf"|to_array:$schedConf->getId()}" class="action">{translate key="common.export"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $schedConfs->eof()}end{/if}separator">&nbsp;</td>
	</tr>
	{/iterate}

	{if $schedConfs->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="conference.noCurrentConferences"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
	{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$schedConfs}</td>
		<td colspan="3" align="right">{page_links name="schedConfs" iterator=$schedConfs}</td>
	</tr>
	{/if}
</table>

<p><input type="button" value="{translate key="common.export"}" class="button defaultButton" onclick="SubmitIfAnyIsChecked();return false;"/>&nbsp;<input type="button" id="selButton" value="{translate key="common.selectAll"}" class="button" onclick="javascript:selectAll();" /></p>
</form>

{include file="common/footer.tpl"}
