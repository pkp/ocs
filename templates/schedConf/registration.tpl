{**
 * registration.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference program page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="schedConf.registration"}
{include file="common/header.tpl"}

<form action="{url op="register"}" method="post">

{assign var="registrationAdditionalInformation" value=$schedConf->getSetting('registrationAdditionalInformation')}
{if $registrationAdditionalInformation}
	<h3>{translate key="manager.registrationPolicies.registrationAdditionalInformation"}</h3>

	<p>{$registrationAdditionalInformation|nl2br}</p>

	<div class="separator"></div>
{/if}

<h3>{translate key="schedConf.registration.conferenceFees"}</h3>

{assign var="registrationMethodAvailable" value=0}

<table class="listing" width="100%">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td colspan="2" width="60%">{translate key="schedConf.registration.type"}</td>
		<td width="60%">{translate key="schedConf.registration.cost"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=registrationTypes item=registrationType}
		<tr valign="top">
			<td colspan="2" class="label">
				<strong>{$registrationType->getTypeName()|escape}</strong>
			</td>
			<td class="data">
				{$registrationType->getCost()|escape} ({$registrationType->getCurrencyCodeAlpha()|escape})
				{if $registrationType->getOpeningDate() < time() && $registrationType->getClosingDate() > time()}
					{assign var="registrationMethodAvailable" value=1}
					<input type="radio" name="registrationTypeId" {if $registrationTypeId == $registrationType->getTypeId()}checked="checked" {/if} value="{$registrationType->getTypeId()|escape}" />
				{else}
					<input type="radio" name="registrationTypeId" value="{$registrationType->getTypeId()|escape}" disabled="disabled" />&nbsp;{translate key="schedConf.registration.typeClosed"}
				{/if}
			</td>
		</tr>
		{if $registrationType->getDescription()}
			<tr valign="top">
				<td>&nbsp;</td>
				<td>{$registrationType->getDescription()|nl2br}</td>
			</tr>
		{/if}
	{/iterate}
	{if $registrationTypes->wasEmpty()}
		<tr>
			<td colspan="3" class="nodata">{translate key="schedConf.registration.noneAvailable"}</td>
		</tr>
	{/if}
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
</table>

<p>{translate key="schedConf.registration.feeCode"}&nbsp;&nbsp;<input type="text" value="{$feeCode|escape}" class="textField" /></p>

<div class="separator"></div>

<h3>{translate key="schedConf.registration.specialRequests"}</h3>

<p>{translate key="schedConf.registration.specialRequests.description"}</p>

<textarea name="specialRequests" id="specialRequests" cols="60" rows="10" class="textArea">{$specialRequests|escape}</textarea>

<input type="submit" value="{translate key="schedConf.registration.register"}" {if !$registrationMethodAvailable}disabled="disabled" class="button" {else}class="button defaultButton" {/if}/>

<div class="separator"></div>

</form>

{include file="common/footer.tpl"}
