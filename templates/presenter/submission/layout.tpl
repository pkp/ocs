{**
 * layout.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the layout editing table.
 *
 * $Id$
 *}
{assign var=layoutFile value=$submission->getLayoutFile()}
<a name="layout"></a>
<h3>{translate key="submission.layout"}</h3>

<table width="100%" class="info">
	<tr>
		<td width="40%" colspan="2">{translate key="submission.layout.galleyFormat"}</td>
		<td width="40%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td>&nbsp;</td>
	</tr>
	{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr>
		<td width="5%">{$smarty.foreach.galleys.iteration}.</td>
		<td width="35%">{$galley->getGalleyLabel()|escape} &nbsp; <a href="{url op="proofGalley" path=$submission->getPaperId()|to_array:$galley->getGalleyId()}" class="action">{translate key="submission.layout.viewProof"}</td>
		<td colspan="3"><a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$galley->getFileId()}" class="file">{$galley->getFileName()|escape}</a>&nbsp;&nbsp;{$galley->getDateModified()|date_format:$dateFormatShort}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td colspan="3" class="heading">{translate key="common.file"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr>
		<td width="5%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="35%">{$suppFile->getSuppFileTitle()|escape}</td>
		<td colspan="3"><a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
</table>

