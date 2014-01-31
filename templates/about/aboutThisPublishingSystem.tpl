{**
 * aboutThisPublishingSystem.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference / About This Publishing System.
 *
 * TODO: Display the image describing the system.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="about.aboutThisPublishingSystem"}
{include file="common/header.tpl"}
{/strip}
<div id="aboutThisPublishingSystem">
<p>
{if $currentConference}
	{translate key="about.aboutOCSConference" ocsVersion=$ocsVersion}
{else}
	{translate key="about.aboutOCSSite" ocsVersion=$ocsVersion}
{/if}
</p>

<p align="center">
	<img src="{$baseUrl}/{$edProcessFile}" style="border: 0;" alt="{translate key="about.aboutThisPublishingSystem.altText"}" />
</p>
</div>
{include file="common/footer.tpl"}
