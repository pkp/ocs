{**
 * step6.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 6 of conference setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.indexing.title"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="6"}">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="6" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<div id="searchEngineIndexing">
<h3>6.1 {translate key="manager.setup.indexing.searchEngineIndexing"}</h3>

<p>{translate key="manager.setup.indexing.searchEngineIndexing.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchDescription" key="common.description"}</td>
		<td width="80%" class="value"><input type="text" name="searchDescription[{$formLocale|escape}]" id="searchDescription" value="{$searchDescription[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchKeywords" key="common.keywords"}</td>
		<td width="80%" class="value"><input type="text" name="searchKeywords[{$formLocale|escape}]" id="searchKeywords" value="{$searchKeywords[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customHeaders" key="manager.setup.indexing.customTags"}</td>
		<td width="80%" class="value">
			<textarea name="customHeaders[{$formLocale|escape}]" id="customHeaders" rows="3" cols="40" class="textArea">{$customHeaders[$formLocale]|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.indexing.customTagsDescription"}</span>
		</td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="registerConferenceForIndexing">
<h3>6.2 {translate key="manager.setup.indexing.registerConferenceForIndexing"}</h3>

{url|assign:"oaiSiteUrl" conference=$currentConference->getPath()}
{url|assign:"oaiUrl" page="oai"}
<p>{translate key="manager.setup.indexing.registerConferenceForIndexing.description" siteUrl=$oaiSiteUrl oaiUrl=$oaiUrl}</p>
</div>
<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
