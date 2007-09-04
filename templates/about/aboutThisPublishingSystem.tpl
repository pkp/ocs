{**
 * aboutThisPublishingSystem.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference / About This Publishing System.
 *
 * TODO: Display the image describing the system.
 *
 * $Id$
 *}
{assign var="pageTitle" value="about.aboutThisPublishingSystem"}
{include file="common/header.tpl"}

<p>
{if $currentConference}
{translate key="about.aboutOCSConference" ocsVersion=$ocsVersion}
{else}
{translate key="about.aboutOCSSite" ocsVersion=$ocsVersion}
{/if}
</p>

{* TODO
<p align="center">
	<img src="{$baseUrl}/templates/images/edprocesslarge.png" width="620" height="701" style="border: 0;" alt="" />
</p>
*}

{include file="common/footer.tpl"}
