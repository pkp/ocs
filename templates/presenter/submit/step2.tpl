{**
 * step2.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of presenter paper submission.
 *
 * $Id$
 *}
{assign var="pageTitle" value="presenter.submit.step2"}
{include file="presenter/submit/submitHeader.tpl"}

<div class="separator"></div>

<form name="submit" method="post" action="{url op="saveSubmit" path=$submitStep}">
<input type="hidden" name="paperId" value="{$paperId|escape}" />
{include file="common/formErrors.tpl"}

{literal}
<script type="text/javascript">
<!--
// Move presenter up/down
function movePresenter(dir, presenterIndex) {
	var form = document.submit;
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
			{url|assign:"submitFormUrl" op="submit" path="2" paperId=$paperId escape=false}
			{* Maintain localized presenter info across requests *}
			{foreach from=$presenters key=presenterIndex item=presenter}
				{foreach from=$presenter.biography key="thisLocale" item="thisBiography"}
					{if $thisLocale != $formLocale}<input type="hidden" name="presenters[{$presenterIndex|escape}][biography][{$thisLocale|escape}]" value="{$thisBiography|escape}" />{/if}
				{/foreach}
			{/foreach}
			{form_language_chooser form="submit" url=$submitFormUrl}
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

{foreach name=presenters from=$presenters key=presenterIndex item=presenter}
<input type="hidden" name="presenters[{$presenterIndex|escape}][presenterId]" value="{$presenter.presenterId|escape}" />
<input type="hidden" name="presenters[{$presenterIndex|escape}][seq]" value="{$presenterIndex+1}" />
{if $smarty.foreach.presenters.total <= 1}
<input type="hidden" name="primaryContact" value="{$presenterIndex|escape}" />
{/if}

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-$presenterIndex-firstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[{$presenterIndex|escape}][firstName]" id="presenters-{$presenterIndex|escape}-firstName" value="{$presenter.firstName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-$presenterIndex-middleName" key="user.middleName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[{$presenterIndex|escape}][middleName]" id="presenters-{$presenterIndex|escape}-middleName" value="{$presenter.middleName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-$presenterIndex-lastName" required="true" key="user.lastName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[{$presenterIndex|escape}][lastName]" id="presenters-{$presenterIndex|escape}-lastName" value="{$presenter.lastName|escape}" size="20" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-$presenterIndex-affiliation" key="user.affiliation"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[{$presenterIndex|escape}][affiliation]" id="presenters-{$presenterIndex|escape}-affiliation" value="{$presenter.affiliation|escape}" size="30" maxlength="255"/></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-$presenterIndex-country" key="common.country"}</td>
	<td width="80%" class="value">
		<select name="presenters[{$presenterIndex|escape}][country]" id="presenters-{$presenterIndex|escape}-country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries selected=$presenter.country}
		</select>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-$presenterIndex-email" required="true" key="user.email"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[{$presenterIndex|escape}][email]" id="presenters-{$presenterIndex|escape}-email" value="{$presenter.email|escape}" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-$presenterIndex-url" key="user.url"}</td>
	<td width="80%" class="value">
		<input type="text" class="textField" name="presenters[{$presenterIndex|escape}][url]" id="presenters-{$presenterIndex|escape}-url" value="{$presenter.url|escape}" size="30" maxlength="90" />
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-$presenterIndex-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td width="80%" class="value"><textarea name="presenters[{$presenterIndex|escape}][biography][{$formLocale|escape}]" class="textArea" id="presenters-{$presenterIndex|escape}-biography" rows="5" cols="40">{$presenter.biography[$formLocale]|escape}</textarea></td>
</tr>
{if $smarty.foreach.presenters.total > 1}
<tr valign="top">
	<td colspan="2">
		<a href="javascript:movePresenter('u', '{$presenterIndex|escape}')" class="action">&uarr;</a> <a href="javascript:movePresenter('d', '{$presenterIndex|escape}')" class="action">&darr;</a><br/>
		{translate key="presenter.submit.reorderInstructions"}
	</td>
</tr>
<tr valign="top">
	<td width="80%" class="value" colspan="2"><input type="radio" name="primaryContact" value="{$presenterIndex|escape}"{if $primaryContact == $presenterIndex} checked="checked"{/if} /> <label for="primaryContact">{translate key="presenter.submit.selectPrincipalContact"}</label> <input type="submit" name="delPresenter[{$presenterIndex|escape}]" value="{translate key="presenter.submit.deletePresenter"}" class="button" /></td>
</tr>
<tr>
	<td colspan="2"><br/></td>
</tr>
{/if}
</table>
{foreachelse}
<input type="hidden" name="presenters[0][presenterId]" value="0" />
<input type="hidden" name="primaryContact" value="0" />
<input type="hidden" name="presenters[0][seq]" value="1" />
<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-0-firstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[0][firstName]" id="presenters-0-firstName" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-0-middleName" key="user.middleName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[0][middleName]" id="presenters-0-middleName" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-0-lastName" required="true" key="user.lastName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[0][lastName]" id="presenters-0-lastName" size="20" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-0-affiliation" key="user.affiliation"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[0][affiliation]" id="presenters-0-affiliation" size="30" maxlength="255" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-0-country" key="common.country"}</td>
	<td width="80%" class="value">
		<select name="presenters[0][country]" id="presenters-0-country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries}
		</select>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-0-email" required="true" key="user.email"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[0][email]" id="presenters-0-email" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-0-url" required="true" key="user.url"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="presenters[0][url]" id="presenters-0-url" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="presenters-0-biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td width="80%" class="value"><textarea name="presenters[0][biography]" class="textArea" id="presenters-0-biography[{$formLocale|escape}]" rows="5" cols="40"></textarea></td>
</tr>
</table>
{/foreach}

<p><input type="submit" class="button" name="addPresenter" value="{translate key="presenter.submit.addPresenter"}" /></p>

<div class="separator"></div>

{if $currentSchedConf->getSetting('allowIndividualSubmissions') && $currentSchedConf->getSetting('allowPanelSubmissions')}

<h3>{translate key="submission.paperType"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="paperType" key="submission.paperType"}</td>
		<td width="80%" class="value">
			<select name="paperType" class="selectMenu" id="paperType">
				<option {if $paperType==SUBMISSION_TYPE_SINGLE}selected="selected" {/if}value="{$smarty.const.SUBMISSION_TYPE_SINGLE}">{translate key="submission.paperType.single"}</option>
				<option {if $paperType==SUBMISSION_TYPE_PANEL}selected="selected" {/if}value="{$smarty.const.SUBMISSION_TYPE_PANEL}">{translate key="submission.paperType.panel"}</option>
			</select>
		</td>
	</tr>
</table>

<div class="separator"></div>

{/if}

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
	<td width="80%" class="value"><textarea name="abstract[{$formLocale|escape}]" id="abstract" class="textArea" rows="15" cols="60">{$abstract[$formLocale]|escape}</textarea></td>
</tr>
{/if}{* $collectAbstracts *}

</table>

<div class="separator"></div>

<h3>{translate key="submission.indexing"}</h3>

{if $currentSchedConf->getSetting('metaDiscipline') || $currentSchedConf->getSetting('metaSubjectClass') || $currentSchedConf->getSetting('metaSubject') || $currentSchedConf->getSetting('metaCoverage') || $currentSchedConf->getSetting('metaType')}<p>{translate key="presenter.submit.submissionIndexingDescription"}</p>{/if}

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
	<td><span class="instruct">{translate key="presenter.submit.languageInstructions"}</span></td>
</tr>
</table>

<div class="separator"></div>


<h3>{translate key="presenter.submit.submissionSupportingAgencies"}</h3>
<p>{translate key="presenter.submit.submissionSupportingAgenciesDescription"}</p>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="sponsor" key="presenter.submit.agencies"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="sponsor[{$formLocale|escape}]" id="sponsor" value="{$sponsor[$formLocale]|escape}" size="60" maxlength="255" /></td>
</tr>
</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{url page="presenter"}', '{translate|escape:"jsparam" key="presenter.submit.cancelSubmission"}')" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
