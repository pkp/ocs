{**
 * metadata.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for changing metadata of a paper.
 *
 * $Id$
 *}
{assign var="pageTitle" value="submission.editMetadata"}
{include file="common/header.tpl"}

<form name="metadata" method="post" action="{url op="saveMetadata"}">
<input type="hidden" name="paperId" value="{$paperId|escape}" />
{include file="common/formErrors.tpl"}

{if $canViewPresenters}
{literal}
<script type="text/javascript">
<!--
// Move presenter up/down
function movePresenter(dir, presenterIndex) {
	var form = document.metadata;
	form.movePresenter.value = 1;
	form.movePresenterDir.value = dir;
	form.movePresenterIndex.value = presenterIndex;
	form.submit();
}
// -->
</script>
{/literal}

{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"formUrl" path=$paperId}
			{* Maintain localized presenter bios across requests *}
			{foreach from=$presenters key=presenterIndex item=presenter}
				{foreach from=$presenter.biography key="thisLocale" item="thisBiography"}
					{if $thisLocale != $formLocale}<input type="hidden" name="presenters[{$presenterIndex|escape}][biography][{$thisLocale|escape}]" value="{$thisBiography|escape}" />{/if}
				{/foreach}
			{/foreach}
			{form_language_chooser form="metadata" url=$formUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
{/if}

<h3>{translate key="paper.presenters"}</h3>

<input type="hidden" name="deletedPresenters" value="{$deletedPresenters|escape}" />
<input type="hidden" name="movePresenter" value="0" />
<input type="hidden" name="movePresenterDir" value="" />
<input type="hidden" name="movePresenterIndex" value="" />

<table width="100%" class="data">
	{foreach name=presenters from=$presenters key=presenterIndex item=presenter}
	<tr valign="top">
		<td width="20%" class="label">
			<input type="hidden" name="presenters[{$presenterIndex|escape}][presenterId]" value="{$presenter.presenterId|escape}" />
			<input type="hidden" name="presenters[{$presenterIndex|escape}][seq]" value="{$presenterIndex+1}" />
			{if $smarty.foreach.presenters.total <= 1}
				<input type="hidden" name="primaryContact" value="{$presenterIndex|escape}" />
			{/if}
			{fieldLabel name="presenters-$presenterIndex-firstName" required="true" key="user.firstName"}
		</td>
		<td width="80%" class="value"><input type="text" name="presenters[{$presenterIndex|escape}][firstName]" id="presenters-{$presenterIndex|escape}-firstName" value="{$presenter.firstName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-$presenterIndex-middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="presenters[{$presenterIndex|escape}][middleName]" id="presenters-{$presenterIndex|escape}-middleName" value="{$presenter.middleName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-$presenterIndex-lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="presenters[{$presenterIndex|escape}][lastName]" id="presenters-{$presenterIndex|escape}-lastName" value="{$presenter.lastName|escape}" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-$presenterIndex-affiliation" key="user.affiliation"}</td>
		<td class="value"><input type="text" name="presenters[{$presenterIndex|escape}][affiliation]" id="presenters-{$presenterIndex|escape}-affiliation" value="{$presenter.affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-$presenterIndex-country" key="common.country"}</td>
		<td class="value">
			<select name="presenters[{$presenterIndex|escape}][country]" id="presenters-{$presenterIndex|escape}-country" class="selectMenu">
				<option value=""></option>
				{html_options options=$countries selected=$presenter.country|escape}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-$presenterIndex-email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="presenters[{$presenterIndex|escape}][email]" id="presenters-{$presenterIndex|escape}-email" value="{$presenter.email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-$presenterIndex-url" key="user.url"}</td>
		<td class="value"><input type="text" name="presenters[{$presenterIndex|escape}][url]" id="presenters-{$presenterIndex|escape}-url" value="{$presenter.url|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-$presenterIndex-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
		<td class="value"><textarea name="presenters[{$presenterIndex|escape}][biography][{$formLocale|escape}]" id="presenters-{$presenterIndex|escape}-biography" rows="5" cols="40" class="textArea">{$presenter.biography[$formLocale]|escape}</textarea></td>
	</tr>
	{if $smarty.foreach.presenters.total > 1}
	<tr valign="top">
		<td class="label">Reorder presenter's name</td>
		<td class="value"><a href="javascript:movePresenter('u', '{$presenterIndex|escape}')" class="action plain">&uarr;</a> <a href="javascript:movePresenter('d', '{$presenterIndex|escape}')" class="action plain">&darr;</a></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="label"><input type="radio" name="primaryContact" id="primaryContact-{$presenterIndex|escape}" value="{$presenterIndex|escape}"{if $primaryContact == $presenterIndex} checked="checked"{/if} /> <label for="primaryContact-{$presenterIndex|escape}">{translate key="presenter.submit.selectPrincipalContact"}</label></td>
		<td class="labelRightPlain">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><input type="submit" name="delPresenter[{$presenterIndex|escape}]" value="{translate key="presenter.submit.deletePresenter"}" class="button" /></td>
	</tr>
	{/if}
	{if !$smarty.foreach.presenters.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}

	{foreachelse}
	<input type="hidden" name="presenters[0][presenterId]" value="0" />
	<input type="hidden" name="primaryContact" value="0" />
	<input type="hidden" name="presenters[0][seq]" value="1" />
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="presenters-0-firstName" required="true" key="user.firstName"}</td>
		<td width="80%" class="value"><input type="text" name="presenters[0][firstName]" id="presenters-0-firstName" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-0-middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="presenters[0][middleName]" id="presenters-0-middleName" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-0-lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="presenters[0][lastName]" id="presenters-0-lastName" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-0-affiliation" key="user.affiliation"}</td>
		<td class="value"><input type="text" id="presenters-0-affiliation" name="presenters[0][affiliation]" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-0-email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="presenters[0][email]" id="presenters-0-email" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-0-url" key="user.url"}</td>
		<td class="value"><input type="text" name="presenters[0][url]" id="presenters-0-url" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="presenters-0-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
		<td class="value"><textarea name="presenters[0][biography][{$formLocale|escape}]" id="presenters-0-biography" rows="5" cols="40" class="textArea"></textarea></td>
	</tr>
	{/foreach}
</table>

<p><input type="submit" class="button" name="addPresenter" value="{translate key="presenter.submit.addPresenter"}" /></p>


<div class="separator"></div>
{/if}


<h3>{translate key="submission.titleAndAbstract"}</h3>

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{fieldLabel name="title" required="true" key="paper.title"}</td>
		<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" id="title" value="{$title[$formLocale]|escape}" size="60" maxlength="255" class="textField" /></td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="abstract" key="paper.abstract" required="true"}</td>
		<td class="value"><textarea name="abstract[{$formLocale|escape}]" id="abstract" rows="15" cols="60" class="textArea">{$abstract[$formLocale]|escape}</textarea></td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="submission.indexing"}</h3>

{if $schedConfSettings.metaDiscipline || $schedConfSettings.metaSubjectClass || $schedConfSettings.metaSubject || $schedConfSettings.metaCoverage || $schedConfSettings.metaType}<p>{translate key="presenter.submit.submissionIndexingDescription"}</p>{/if}

<table width="100%" class="data">
	{if $schedConfSettings.metaDiscipline}
	<tr valign="top">
		<td class="label">{fieldLabel name="discipline" key="paper.discipline"}</td>
		<td class="value">
			<input type="text" name="discipline[{$formLocale|escape}]" id="discipline" value="{$discipline[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentSchedConf->getLocalizedSetting('metaDisciplineExamples') != ''}
			<br />
			<span class="instruct">{$currentSchedConf->getLocalizedSetting('metaDisciplineExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $schedConfSettings.metaSubjectClass}
	<tr valign="top">
		<td colspan="2" class="label"><a href="{$schedConfSettings.metaSubjectClassUrl}" target="_blank">{$currentSchedConf->getLocalizedSetting('metaSubjectClassTitle')}</a></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="subjectClass" key="paper.subjectClassification"}</td>
		<td class="value">
			<input type="text" name="subjectClass[{$formLocale|escape}]" id="subjectClass" value="{$subjectClass[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="presenter.submit.subjectClassInstructions"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $schedConfSettings.metaSubject}
	<tr valign="top">
		<td class="label">{fieldLabel name="subject" key="paper.subject"}</td>
		<td class="value">
			<input type="text" name="subject[{$formLocale|escape}]" id="subject" value="{$subject[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentSchedConf->getLocalizedSetting('metaSubjectExamples') != ''}
			<br />
			<span class="instruct">{$currentSchedConf->getLocalizedSetting('metaSubjectExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $schedConfSettings.metaCoverage}
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageGeo" key="paper.coverageGeo"}</td>
		<td class="value">
			<input type="text" name="coverageGeo[{$formLocale|escape}]" id="coverageGeo" value="{$coverageGeo[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentSchedConf->getLocalizedSetting('metaCoverageGeoExamples') != ''}
			<br />
			<span class="instruct">{$currentSchedConf->getLocalizedSetting('metaCoverageGeoExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageChron" key="paper.coverageChron"}</td>
		<td class="value">
			<input type="text" name="coverageChron[{$formLocale|escape}]" id="coverageChron" value="{$coverageChron[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentSchedConf->getLocalizedSetting('metaCoverageChronExamples') != ''}
			<br />
			<span class="instruct">{$currentSchedConf->getLocalizedSetting('metaCoverageChronExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverageSample" key="paper.coverageSample"}</td>
		<td class="value">
			<input type="text" name="coverageSample[{$formLocale|escape}]" id="coverageSample" value="{$coverageSample[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
			{if $currentSchedConf->getLocalizedSetting('metaCoverageResearchSampleExamples') != ''}
			<br />
			<span class="instruct">{$currentSchedConf->getLocalizedSetting('metaCoverageResearchSampleExamples')|escape}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $schedConfSettings.metaType}
	<tr valign="top">
		<td class="label">{fieldLabel name="type" key="paper.type"}</td>
		<td class="value">
			<input type="text" name="type[{$formLocale|escape}]" id="type" value="{$type[$formLocale]|escape}" size="40" maxlength="255" class="textField" />
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="language" key="paper.language"}</td>
		<td width="80%" class="value">
			<input type="text" name="language" id="language" value="{$language|escape}" size="5" maxlength="10" class="textField" />
			<br />
			<span class="instruct">{translate key="presenter.submit.languageInstructions"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="submission.supportingAgencies"}</h3>

<p>{translate key="presenter.submit.submissionSupportingAgenciesDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsor" key="presenter.submit.agencies"}</td>
		<td width="80%" class="value">
			<input type="text" name="sponsor[{$formLocale|escape}]" id="sponsor" value="{$sponsor[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
		</td>
	</tr>
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="submission.saveMetadata"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
