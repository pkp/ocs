{**
 * layout.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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
		<td width="28%" colspan="2">&nbsp;</td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="16%" class="heading">{translate key="submission.underway"}</td>
		<td width="16%" class="heading">{translate key="submission.complete"}</td>
		<td width="22%" colspan="2" class="heading">{translate key="submission.acknowledge"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6">
			{translate key="common.file"}:&nbsp;&nbsp;&nbsp;&nbsp;
			{if $layoutFile}
				<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$layoutFile->getFileId()}" class="file">{$layoutFile->getFileName()|escape}</a>&nbsp;&nbsp;{$layoutFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>

	<tr>
		<td colspan="2">{translate key="submission.layout.galleyFormat"}</td>
		<td colspan="2" class="heading">{translate key="common.file"}</td>
		<td class="heading">{translate key="common.order"}</td>
		<td class="heading">{translate key="common.action"}</td>
		<td class="heading">{translate key="submission.views"}</td>
	</tr>
	{foreach name=galleys from=$submission->getGalleys() item=galley}
	<tr>
		<td width="2%">{$smarty.foreach.galleys.iteration}.</td>
		<td width="26%">{$galley->getLabel()|escape} &nbsp; <a href="{url op="proofGalley" path=$submission->getPaperId()|to_array:$galley->getGalleyId()}" class="action">{translate key="submission.layout.viewProof"}</td>
		<td colspan="2"><a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$galley->getFileId()}" class="file">{$galley->getFileName()|escape}</a>&nbsp;&nbsp;{$galley->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="orderGalley" d=u paperId=$submission->getPaperId() galleyId=$galley->getGalleyId()}" class="plain">&uarr;</a> <a href="{url op="orderGalley" d=d paperId=$submission->getPaperId() galleyId=$galley->getGalleyId()}" class="plain">&darr;</a></td>
		<td>
			<a href="{url op="editGalley" path=$submission->getPaperId()|to_array:$galley->getGalleyId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteGalley" path=$submission->getPaperId()|to_array:$galley->getGalleyId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteGalley"}')" class="action">{translate key="common.delete"}</a>
		</td>
		<td>{$galley->getViews()|escape}</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="28%" colspan="2">{translate key="submission.supplementaryFiles"}</td>
		<td width="34%" colspan="2" class="heading">{translate key="common.file"}</td>
		<td width="16%" class="heading">{translate key="common.order"}</td>
		<td width="16%" colspan="2" class="heading">{translate key="common.action"}</td>
	</tr>
	{foreach name=suppFiles from=$submission->getSuppFiles() item=suppFile}
	<tr>
		<td width="2%">{$smarty.foreach.suppFiles.iteration}.</td>
		<td width="26%">{$suppFile->getTitle()}</td>
		<td colspan="2"><a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="orderSuppFile" d=u paperId=$submission->getPaperId() suppFileId=$suppFile->getSuppFileId()}" class="plain">&uarr;</a> <a href="{url op="orderSuppFile" d=d paperId=$submission->getPaperId() suppFileId=$suppFile->getSuppFileId()}" class="plain">&darr;</a></td>
		<td colspan="2">
			<a href="{url op="editSuppFile" path=$submission->getPaperId()|to_array:$suppFile->getSuppFileId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSuppFile" path=$submission->getPaperId()|to_array:$suppFile->getSuppFileId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.layout.confirmDeleteSupplementaryFile"}')" class="action">{translate key="common.delete"}</a>
		</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="common.none"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="7" class="separator">&nbsp;</td>
	</tr>
</table>

<form method="post" action="{url op="uploadLayoutFile"}"  enctype="multipart/form-data">
	<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
	{translate key="submission.uploadFileTo"}
		<input type="radio" name="layoutFileType" id="layoutFileTypeGalley" value="galley" />
		<label for="layoutFileTypeGalley">{translate key="submission.galley"}</label>,
		<input type="radio" name="layoutFileType" id="layoutFileTypeSupp" value="supp" />
		<label for="layoutFileTypeSupp">{translate key="paper.suppFilesAbbrev"}</label>
	<input type="file" name="layoutFile" size="10" class="uploadField" />
	<input type="submit" value="{translate key="common.upload"}" class="button" />
</form>
