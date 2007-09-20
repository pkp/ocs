{**
 * metadata.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission metadata table.
 *
 * $Id$
 *}
<a name="metadata"></a>
<table class="data">
	<tr valign="middle">
		<td><h3>{translate key="submission.metadata"}</h3></td>
		<td>&nbsp;<br/><a href="{url op="viewMetadata" path=$submission->getPaperId()}" class="action">{translate key="submission.editMetadata"}</a></td>
	</tr>
</table>

<h4>{translate key="paper.presenters"}</h4>
	
<table width="100%" class="data">
	{foreach name=presenters from=$presenters item=presenter}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">
			{assign var=emailString value="`$presenter->getFullName()` <`$presenter->getEmail()`>"}
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getPaperTitle|strip_tags paperId=$submission->getPaperId()}
			{$presenter->getFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	{if $presenter->getEmail()}<tr valign="top">
		<td class="label">{translate key="user.url"}</td>
		<td class="value"><a href="{$presenter->getUrl()|escape:"quotes"}">{$presenter->getUrl()|escape}</a></td>
	</tr>{/if}
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$presenter->getAffiliation()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.country"}</td>
		<td class="value">{$presenter->getCountryLocalized()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$presenter->getPresenterBiography()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{if $presenter->getPrimaryContact()}
	<tr valign="top">
		<td colspan="2" class="label">{translate key="presenter.submit.selectPrincipalContact"}</td>
	</tr>
	{/if}
	{if !$smarty.foreach.presenters.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{/foreach}
</table>

<h4>{translate key="submission.titleAndAbstract"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="paper.title"}</td>
		<td width="80%" class="value">{$submission->getPaperTitle()|strip_unsafe_html|default:"&mdash;"}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="paper.abstract"}</td>
		<td class="value">{$submission->getPaperAbstract()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
</table>

<h4>{translate key="submission.indexing"}</h4>
	
<table width="100%" class="data">
	{if $schedConfSettings.metaDiscipline}
	<tr valign="top">
		<td width="20%" class="label">{translate key="paper.discipline"}</td>
		<td width="80%" class="value">{$submission->getPaperDiscipline()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $schedConfSettings.metaSubjectClass}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="paper.subjectClassification"}</td>
		<td width="80%" class="value">{$submission->getPaperSubjectClass()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $schedConfSettings.metaSubject}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="paper.subject"}</td>
		<td width="80%" class="value">{$submission->getPaperSubject()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $schedConfSettings.metaCoverage}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="paper.coverageGeo"}</td>
		<td width="80%" class="value">{$submission->getPaperCoverageGeo()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="paper.coverageChron"}</td>
		<td class="value">{$submission->getPaperCoverageChron()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="paper.coverageSample"}</td>
		<td class="value">{$submission->getPaperCoverageSample()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $schedConfSettings.metaType}
	<tr valign="top">
		<td width="20%"  class="label">{translate key="paper.type"}</td>
		<td width="80%" class="value">{$submission->getPaperType()|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{translate key="paper.language"}</td>
		<td width="80%" class="value">{$submission->getLanguage()|escape|default:"&mdash;"}</td>
	</tr>
</table>

<h4>{translate key="submission.supportingAgencies"}</h4>
	
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="presenter.submit.agencies"}</td>
		<td width="80%" class="value">{$submission->getPaperSponsor()|escape|default:"&mdash;"}</td>
	</tr>
</table>
