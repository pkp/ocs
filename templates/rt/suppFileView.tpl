{**
 * suppFileView.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Read-only view of supplementary file information.
 *
 * $Id$
 *}
{assign var="pageTitle" value="paper.suppFile"}
{include file="rt/header.tpl"}

<h3>{$paper->getPaperTitle()|strip_unsafe_html}</h3>

<br />

<h3>{translate key="presenter.submit.supplementaryFileData"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="common.title"}</td>
		<td width="80%" class="value">{$suppFile->getSuppFileTitle()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="presenter.submit.suppFile.createrOrOwner"}</td>
		<td class="value">{$suppFile->getSuppFileCreator()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.subject"}</td>
		<td class="value">{$suppFile->getSuppFileSubject()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.type"}</td>
		<td class="value">{$suppFile->getType()|escape|default:$suppFile->getSuppFileTypeOther()|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="presenter.submit.suppFile.briefDescription"}</td>
		<td class="value">{$suppFile->getSuppFileDescription()|escape|nl2br|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.publisher"}</td>
		<td class="value">{$suppFile->getSuppFilePublisher()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="presenter.submit.suppFile.contributorOrSponsor"}</td>
		<td class="value">{$suppFile->getSuppFileSponsor()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.date"}</td>
		<td class="value">{$suppFile->getDateCreated()|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.source"}</td>
		<td class="value">{$suppFile->getSuppFileSource()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.language"}</td>
		<td class="value">{$suppFile->getLanguage()|escape|default:"&mdash;"}</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="presenter.submit.supplementaryFileUpload"}</h3>

<table width="100%" class="data">
{if $suppFile}
	<tr valign="top">
		<td width="20%" class="label">{translate key="common.fileName"}</td>
		<td width="80%" class="value"><a href="{url page="paper" op="downloadSuppFile" path=$paperId|to_array:$suppFile->getBestSuppFileId($currentConference)}">{$suppFile->getFileName()|escape}</a></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.originalFileName"}</td>
		<td class="value">{$suppFile->getOriginalFileName()|escape}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.fileSize"}</td>
		<td class="value">{$suppFile->getNiceFileSize()}</td>
	</tr>
	<tr valign="top">
		<td class="infoLabel">{translate key="common.dateUploaded"}</td>
		<td class="value">{$suppFile->getDateUploaded()|date_format:$datetimeFormatShort}</td>
	</tr>
	</table>
{else}
	<tr valign="top">
		<td colspan="2" class="noResults">{translate key="presenter.submit.suppFile.noFile"}</td>
	</tr>
{/if}
</table>

{include file="rt/footer.tpl"}
