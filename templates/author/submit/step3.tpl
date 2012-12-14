{**
 * templates/author/submit/step3.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of author paper submission.
 *
 *}
{if $showPaperSteps}
	{assign var="pageTitle" value="author.submit.step3"}
{else}
	{assign var="pageTitle" value="author.submit.step3AbstOnly"}
{/if}
{include file="author/submit/submitHeader.tpl"}

<div class="separator"></div>

<form class="pkp_form" id="submit" method="post" action="{url op="saveSubmit" path=$submitStep}">
<input type="hidden" name="paperId" value="{$paperId|escape}" />
{include file="common/formErrors.tpl"}

{literal}
<script type="text/javascript">
<!--
// Move author up/down
function moveAuthor(dir, authorIndex) {
	var form = document.getElementById('submit');
	form.moveAuthor.value = 1;
	form.moveAuthorDir.value = dir;
	form.moveAuthorIndex.value = authorIndex;
	form.submit();
}
// -->
</script>
{/literal}

{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"submitFormUrl" op="submit" path="3" paperId=$paperId escape=false}
			{* Maintain localized author info across requests *}
			{foreach from=$authors key=authorIndex item=author}
				{foreach from=$author.biography key="thisLocale" item="thisBiography"}
					{if $thisLocale != $formLocale}<input type="hidden" name="authors[{$authorIndex|escape}][biography][{$thisLocale|escape}]" value="{$thisBiography|escape}" />{/if}
				{/foreach}
				{foreach from=$author.affiliation key="thisLocale" item="thisAffiliation"}
					{if $thisLocale != $formLocale}<input type="hidden" name="authors[{$authorIndex|escape}][affiliation][{$thisLocale|escape}]" value="{$thisAffiliation|escape}" />{/if}
				{/foreach}
			{/foreach}
			{form_language_chooser form="submit" url=$submitFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}

<div id="authors">
<h3>{translate key="paper.authors"}</h3>

<input type="hidden" name="deletedAuthors" value="{$deletedAuthors|escape}" />
<input type="hidden" name="moveAuthor" value="0" />
<input type="hidden" name="moveAuthorDir" value="" />
<input type="hidden" name="moveAuthorIndex" value="" />

{foreach name=authors from=$authors key=authorIndex item=author}
<input type="hidden" name="authors[{$authorIndex|escape}][authorId]" value="{$author.authorId|escape}" />
<input type="hidden" name="authors[{$authorIndex|escape}][seq]" value="{$authorIndex+1}" />
{if $smarty.foreach.authors.total <= 1}
<input type="hidden" name="primaryContact" value="{$authorIndex|escape}" />
{/if}

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-firstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex|escape}][firstName]" id="authors-{$authorIndex|escape}-firstName" value="{$author.firstName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-middleName" key="user.middleName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex|escape}][middleName]" id="authors-{$authorIndex|escape}-middleName" value="{$author.middleName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-lastName" required="true" key="user.lastName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex|escape}][lastName]" id="authors-{$authorIndex|escape}-lastName" value="{$author.lastName|escape}" size="20" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-email" required="true" key="user.email"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[{$authorIndex|escape}][email]" id="authors-{$authorIndex|escape}-email" value="{$author.email|escape}" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="authors-$authorIndex-url" key="user.url"}</td>
	<td class="value"><input type="text" name="authors[{$authorIndex|escape}][url]" id="authors-{$authorIndex|escape}-url" value="{$author.url|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-affiliation" key="user.affiliation"}</td>
	<td width="80%" class="value">
		<textarea name="authors[{$authorIndex|escape}][affiliation][{$formLocale|escape}]" class="textArea" id="authors-{$authorIndex|escape}-affiliation" rows="5" cols="40">{$author.affiliation[$formLocale]|escape}</textarea><br/>
		<span class="instruct">{translate key="user.affiliation.description"}</span>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-country" key="common.country"}</td>
	<td width="80%" class="value">
		<select name="authors[{$authorIndex|escape}][country]" id="authors-{$authorIndex|escape}-country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries selected=$author.country}
		</select>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-$authorIndex-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td width="80%" class="value"><textarea name="authors[{$authorIndex|escape}][biography][{$formLocale|escape}]" class="textArea richContent" id="authors-{$authorIndex|escape}-biography" rows="5" cols="40">{$author.biography[$formLocale]|escape}</textarea></td>
</tr>
{if $smarty.foreach.authors.total > 1}
<tr valign="top">
	<td colspan="2">
		<a href="javascript:moveAuthor('u', '{$authorIndex|escape}')" class="action">&uarr;</a> <a href="javascript:moveAuthor('d', '{$authorIndex|escape}')" class="action">&darr;</a><br/>
		{translate key="author.submit.reorderInstructions"}
	</td>
</tr>
<tr valign="top">
	<td width="80%" class="value" colspan="2"><input type="radio" name="primaryContact" value="{$authorIndex|escape}"{if $primaryContact == $authorIndex} checked="checked"{/if} /> <label for="primaryContact">{translate key="author.submit.selectPrincipalContact"}</label> <input type="submit" name="delAuthor[{$authorIndex|escape}]" value="{translate key="author.submit.deleteAuthor"}" class="button" /></td>
</tr>
<tr>
	<td colspan="2"><br/></td>
</tr>
{/if}
</table>
{foreachelse}
<input type="hidden" name="authors[0][authorId]" value="0" />
<input type="hidden" name="primaryContact" value="0" />
<input type="hidden" name="authors[0][seq]" value="1" />
<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-firstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][firstName]" id="authors-0-firstName" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-middleName" key="user.middleName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][middleName]" id="authors-0-middleName" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-lastName" required="true" key="user.lastName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][lastName]" id="authors-0-lastName" size="20" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-affiliation" key="user.affiliation"}</td>
	<td width="80%" class="value">
		<textarea name="authors[0][affiliation][{$formLocale|escape}]" class="textArea" id="authors-0-affiliation" rows="5" cols="40"></textarea><br/>
		<span class="instruct">{translate key="user.affiliation.description"}</span>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-country" key="common.country"}</td>
	<td width="80%" class="value">
		<select name="authors[0][country]" id="authors-0-country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries}
		</select>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-email" required="true" key="user.email"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][email]" id="authors-0-email" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-url" required="true" key="user.url"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="authors[0][url]" id="authors-0-url" size="30" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="authors-0-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td width="80%" class="value"><textarea name="authors[0][biography]" class="textArea richContent" id="authors-0-biography[{$formLocale|escape}]" rows="5" cols="40"></textarea></td>
</tr>
</table>
{/foreach}

<p><input type="submit" class="button" name="addAuthor" value="{translate key="author.submit.addAuthor"}" /></p>
</div>
<div class="separator"></div>

<div id="title">
{if $collectAbstracts}
	<h3>{translate key="submission.titleAndAbstract"}</h3>
{else}
	<h3>{translate key="paper.title"}</h3>
{/if}

<table width="100%" class="data">

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="title" required="true" key="paper.title"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="title[{$formLocale|escape}]" id="title" value="{$title[$formLocale]|escape}" size="60" maxlength="255" /></td>
</tr>

{if $collectAbstracts}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="abstract" key="paper.abstract" required="true"}</td>
	<td width="80%" class="value"><textarea name="abstract[{$formLocale|escape}]" id="abstract" class="textArea richContent" rows="15" cols="60">{$abstract[$formLocale]|escape}</textarea></td>
</tr>
{/if}{* $collectAbstracts *}

</table>
</div>
<div class="separator"></div>

<div id="indexing">
<h3>{translate key="submission.indexing"}</h3>

{if $currentSchedConf->getSetting('metaDiscipline') || $currentSchedConf->getSetting('metaSubjectClass') || $currentSchedConf->getSetting('metaSubject') || $currentSchedConf->getSetting('metaCoverage') || $currentSchedConf->getSetting('metaType')}<p>{translate key="author.submit.submissionIndexingDescription"}</p>{/if}

<table width="100%" class="data">
{if $currentSchedConf->getSetting('metaDiscipline')}
<tr valign="top">
	<td{if $currentSchedConf->getLocalizedSetting('metaDisciplineExamples') != ''} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="discipline" key="paper.discipline"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="discipline[{$formLocale|escape}]" id="discipline" value="{$discipline[$formLocale]|escape}" size="40" maxlength="255" /></td>
</tr>
{if $currentSchedConf->getLocalizedSetting('metaDisciplineExamples') != ''}
<tr valign="top">
	<td><span class="instruct">{$currentSchedConf->getLocalizedSetting('metaDisciplineExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
{/if}

{if $currentSchedConf->getSetting('metaSubjectClass')}
<tr valign="top">
	<td rowspan="2" width="20%" class="label">{fieldLabel name="subjectClass" key="paper.subjectClassification"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="subjectClass[{$formLocale|escape}]" id="subjectClass" value="{$subjectClass[$formLocale]|escape}" size="40" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label"><a href="{$currentSchedConf->getSetting('metaSubjectClassUrl')|escape}" target="_blank">{$currentSchedConf->getLocalizedSetting('metaSubjectClassTitle')|escape}</a></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
{/if}

{if $currentSchedConf->getSetting('metaSubject')}
<tr valign="top">
	<td{if $currentSchedConf->getLocalizedSetting('metaSubjectExamples') != ''} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="subject" key="paper.subject"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="subject[{$formLocale|escape}]" id="subject" value="{$subject[$formLocale]|escape}" size="40" maxlength="255" /></td>
</tr>
{if $currentSchedConf->getLocalizedSetting('metaSubjectExamples') != ''}
<tr valign="top">
	<td><span class="instruct">{$currentSchedConf->getLocalizedSetting('metaSubjectExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
{/if}

{if $currentSchedConf->getSetting('metaCoverage')}
<tr valign="top">
	<td{if $currentSchedConf->getLocalizedSetting('metaCoverageGeoExamples') != ''} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="coverageGeo" key="paper.coverageGeo"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="coverageGeo[{$formLocale|escape}]" id="coverageGeo" value="{$coverageGeo[$formLocale]|escape}" size="40" maxlength="255" /></td>
</tr>
{if $currentSchedConf->getLocalizedSetting('metaCoverageGeoExamples') != ''}
<tr valign="top">
	<td><span class="instruct">{$currentSchedConf->getLocalizedSetting('metaCoverageGeoExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td{if $currentSchedConf->getLocalizedSetting('metaCoverageChronExamples') != ''} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="coverageChron" key="paper.coverageChron"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="coverageChron[{$formLocale|escape}]" id="coverageChron" value="{$coverageChron[$formLocale]|escape}" size="40" maxlength="255" /></td>
</tr>
{if $currentSchedConf->getLocalizedSetting('metaCoverageChronExamples') != ''}
<tr valign="top">
	<td><span class="instruct">{$currentSchedConf->getLocalizedSetting('metaCoverageChronExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td{if $currentSchedConf->getLocalizedSetting('metaCoverageResearchSampleExamples') != ''} rowspan="2"{/if} width="20%" class="label">{fieldLabel name="coverageSample" key="paper.coverageSample"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="coverageSample[{$formLocale|escape}]" id="coverageSample" value="{$coverageSample[$formLocale]|escape}" size="40" maxlength="255" /></td>
</tr>
{if $currentSchedConf->getLocalizedSetting('metaCoverageResearchSampleExamples') != ''}
<tr valign="top">
	<td><span class="instruct">{$currentSchedConf->getLocalizedSetting('metaCoverageResearchSampleExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
{/if}

{if $currentSchedConf->getSetting('metaType')}
<tr valign="top">
	<td width="20%" {if $currentSchedConf->getLocalizedSetting('metaTypeExamples') != ''}rowspan="2" {/if}class="label">{fieldLabel name="type" key="paper.type"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="type[{$formLocale|escape}]" id="type" value="{$type[$formLocale]|escape}" size="40" maxlength="255" /></td>
</tr>

{if $currentSchedConf->getLocalizedSetting('metaTypeExamples') != ''}
<tr valign="top">
	<td><span class="instruct">{$currentSchedConf->getLocalizedSetting('metaTypeExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
{/if}

<tr valign="top">
	<td rowspan="2" width="20%" class="label">{fieldLabel name="language" key="paper.language"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="language" id="language" value="{$language|escape}" size="5" maxlength="10" /></td>
</tr>
<tr valign="top">
	<td><span class="instruct">{translate key="author.submit.languageInstructions"}</span></td>
</tr>
</table>
</div>
<div class="separator"></div>

<div id="supportingAgencies">
<h3>{translate key="author.submit.submissionSupportingAgencies"}</h3>
<p>{translate key="author.submit.submissionSupportingAgenciesDescription"}</p>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="sponsor" key="submission.agencies"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="sponsor[{$formLocale|escape}]" id="sponsor" value="{$sponsor[$formLocale]|escape}" size="60" maxlength="255" /></td>
</tr>
</table>
</div>
<div class="separator"></div>

{if $currentSchedConf->getSetting('metaCitations')}
<div id="metaCitations">
<h3>{translate key="submission.citations"}</h3>

<p>{translate key="author.submit.submissionCitations"}</p>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="citations" key="submission.citations"}</td>
	<td width="80%" class="value"><textarea name="citations" id="citations" class="textArea richContent" rows="15" cols="60">{$citations|escape}</textarea></td>
</tr>
</table>
</div>

<div class="separator"></div>
{/if}

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{url page="author"}', '{translate|escape:"jsparam" key="author.submit.cancelSubmission"}')" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{if $scrollToAuthor}
	{literal}
	<script type="text/javascript">
		var authors = document.getElementById('authors');
		authors.scrollIntoView(false);
	</script>
	{/literal}
{/if}

{include file="common/footer.tpl"}

