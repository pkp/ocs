{**
 * registration.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference Registration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.registration"}
{include file="common/header.tpl"}

<h3>{translate key="about.registrationContact"}</h3>
<p>
	{if !empty($registrationName)}
		<strong>{$registrationName|escape}</strong><br />
	{/if}
	{if !empty($registrationMailingAddress)}
		{$registrationMailingAddress|escape|nl2br}<br />
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

<h3>{translate key="about.availableRegistrationTypes"}</h3>
<p>
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{translate key="director.registrationTypes.name"}</td>
		<td width="15%">{translate key="director.registrationTypes.openDate"}</td>
		<td width="15%">{translate key="director.registrationTypes.closeDate"}</td>
		<td width="15%">{translate key="director.registrationTypes.expiryDate"}</td>
		<td width="15%">{translate key="director.registrationTypes.cost"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=registrationTypes item=registrationType}
	{if !$registrationType->getPublic()}
		<tr valign="top">
			<td>{$registrationType->getTypeName()|escape}<br />{$registrationType->getDescription()|escape|nl2br}</td>
			<td>{$registrationType->getOpeningDate()}</td>
			<td>{$registrationType->getClosingDate()}</td>
			<td>{$registrationType->getExpiryDate()}</td>
			<td>{$registrationType->getCost()|string_format:"%.2f"}&nbsp;({$registrationType->getCurrencyStringShort()|escape})</td>
		</tr>
		<tr><td colspan="5" class="{if $registrationTypes->eof()}end{/if}separator">&nbsp;</td></tr>
	{/if}
{/iterate}
</table>
</p>

{include file="common/footer.tpl"}
