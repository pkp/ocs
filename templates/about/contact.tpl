{**
 * contact.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference / Conference Contact.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="about.conferenceContact"}
{include file="common/header.tpl"}
{/strip}
<div id="confContact">
{if !empty($conferenceSettings.mailingAddress)}
<div id="mailingAddress">
<h3>{translate key="common.mailingAddress"}</h3>
<p>{$conferenceSettings.mailingAddress|nl2br}</p>
</div>
{/if}

{if not (empty($conferenceSettings.contactTitle) && empty($conferenceSettings.contactAffiliation) && empty($conferenceSettings.contactAffiliation) && empty($conferenceSettings.contactMailingAddress) && empty($conferenceSettings.contactPhone) && empty($conferenceSettings.contactFax) && empty($conferenceSettings.contactEmail))}
<div id="principalContact">
<h3>{translate key="about.contact.principalContact"}</h3>
<p>
	{if !empty($conferenceSettings.contactName)}
		<strong>{$conferenceSettings.contactName|escape}</strong><br />
	{/if}
	{if !empty($conferenceSettings.contactTitle)}
		{$conferenceSettings.contactTitle|escape}<br />
	{/if}
	{if !empty($conferenceSettings.contactAffiliation.$currentLocale)}
		{$conferenceSettings.contactAffiliation.$currentLocale|escape}<br />
	{/if}
	{if !empty($conferenceSettings.contactMailingAddress)}
		{$conferenceSettings.contactMailingAddress|nl2br}<br />
	{/if}
	{if !empty($conferenceSettings.contactPhone)}
		{translate key="about.contact.phone"}: {$conferenceSettings.contactPhone|escape}<br />
	{/if}
	{if !empty($conferenceSettings.contactFax)}
		{translate key="about.contact.fax"}: {$conferenceSettings.contactFax|escape}<br />
	{/if}
	{if !empty($conferenceSettings.contactEmail)}
		{translate key="about.contact.email"}: {mailto address=$conferenceSettings.contactEmail|escape encode="hex"}<br />
	{/if}
</p>
</div>
{/if}

{if not (empty($conferenceSettings.supportName) && empty($conferenceSettings.supportPhone) && empty($conferenceSettings.supportEmail))}
<div id="supportContact">
<h3>{translate key="about.contact.supportContact"}</h3>
<p>
	{if !empty($conferenceSettings.supportName)}
		<strong>{$conferenceSettings.supportName|escape}</strong><br />
	{/if}
	{if !empty($conferenceSettings.supportPhone)}
		{translate key="about.contact.phone"}: {$conferenceSettings.supportPhone|escape}<br />
	{/if}
	{if !empty($conferenceSettings.supportEmail)}
		{translate key="about.contact.email"}: {mailto address=$conferenceSettings.supportEmail|escape encode="hex"}<br />
	{/if}
</p>
</div>
{/if}
</div>
{include file="common/footer.tpl"}
