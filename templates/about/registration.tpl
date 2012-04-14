{**
 * registration.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference Registration.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="about.registration"}
{include file="common/header.tpl"}
{/strip}
<div id="registrationContact">
<h3>{translate key="about.registrationContact"}</h3>
<p>
	{if !empty($registrationName)}
		<strong>{$registrationName|escape}</strong><br />
	{/if}
	{if !empty($registrationMailingAddress)}
		{$registrationMailingAddress|nl2br}<br />
	{/if}
	{if !empty($registrationPhone)}
		{translate key="user.phone"}: {$registrationPhone|escape}<br />
	{/if}
	{if !empty($registrationFax)}
		{translate key="user.fax"}: {$registrationFax|escape}<br />
	{/if}
	{if !empty($registrationEmail)}
		{translate key="user.email"}: {mailto address=$registrationEmail|escape encode="hex"}<br /><br />
	{/if}
	{if !empty($registrationAdditionalInformation)}
		{$registrationAdditionalInformation|nl2br}<br />
	{/if}
</p>
</div>
<div id="availableRegistrationTypes">
<h3>{translate key="about.availableRegistrationTypes"}</h3>
<p>
<table width="100%" class="listing">
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{translate key="manager.registrationTypes.name"}</td>
		<td width="12%">{translate key="manager.registrationTypes.access"}</td>
		<td width="12%">{translate key="manager.registrationTypes.openDate"}</td>
		<td width="12%">{translate key="manager.registrationTypes.closeDate"}</td>
		<td width="12%">{translate key="manager.registrationTypes.expiryDate"}</td>
		<td width="12%">{translate key="manager.registrationTypes.cost"}</td>
	</tr>
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=registrationTypes item=registrationType}
	{if $registrationType->getPublic()}
		<tr valign="top">
			<td>{$registrationType->getRegistrationTypeName()|escape}<br />{$registrationType->getRegistrationTypeDescription()|strip_unsafe_html|nl2br}</td>
			<td>{translate key=$registrationType->getAccessString()}</td>
			<td>{$registrationType->getOpeningDate()|date_format:$dateFormatLong}</td>
			<td>{$registrationType->getClosingDate()|date_format:$dateFormatLong}</td>
			<td>{$registrationType->getExpiryDate()|date_format:$dateFormatLong}</td>
			<td>{$registrationType->getCost()|string_format:"%.2f"}&nbsp;({$registrationType->getCurrencyStringShort()|escape})</td>
		</tr>
		<tr><td colspan="6" class="{if $registrationTypes->eof()}end{/if}separator">&nbsp;</td></tr>
	{/if}
{/iterate}
</table>
</p>
</div>
{include file="common/footer.tpl"}
