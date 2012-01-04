{**
 * setupHeader.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for conference setup pages.
 *
 * $Id$
 *}
{strip}
{assign var="pageCrumbTitle" value="manager.websiteManagement"}
{url|assign:"currentUrl" op="setup"}
{include file="common/header.tpl"}
{/strip}

<ul class="steplist">
	<li id="step1" {if $setupStep == 1} class="current"{/if}><a href="{url op="setup" path="1"}">1. {translate key="manager.setup.aboutConference.brief"}</a></li>
	<li id="step2" {if $setupStep == 2} class="current"{/if}><a href="{url op="setup" path="2"}">2. {translate key="manager.setup.additionalContent.brief"}</a></li>
	<li id="step3" {if $setupStep == 3} class="current"{/if}><a href="{url op="setup" path="3"}">3. {translate key="manager.setup.layout.brief"}</a></li>
	<li id="step4" {if $setupStep == 4} class="current"{/if}><a href="{url op="setup" path="4"}">4. {translate key="manager.setup.style.brief"}</a></li>
	<li id="step5" {if $setupStep == 5} class="current"{/if}><a href="{url op="setup" path="5"}">5. {translate key="manager.setup.loggingAndAuditing.brief"}</a></li>
	<li id="step6" {if $setupStep == 6} class="current"{/if}><a href="{url op="setup" path="6"}">6. {translate key="manager.setup.indexing.brief"}</a></li>
</ul>
