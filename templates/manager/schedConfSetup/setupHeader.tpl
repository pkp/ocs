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
{assign var="pageCrumbTitle" value="manager.schedConfSetup.schedConfSetup"}
{url|assign:"currentUrl" op="schedConfSetup"}
{include file="common/header.tpl"}
{/strip}


<ul class="steplist">
	<li id="step1" {if $setupStep == 1} class="current"{/if}><a href="{url op="schedConfSetup" path="1"}">1. {translate key="manager.schedConfSetup.details"}</a></li>
	<li id="step2" {if $setupStep == 2} class="current"{/if}><a href="{url op="schedConfSetup" path="2"}">2. {translate key="manager.schedConfSetup.submissions"}</a></li>
	<li id="step3" {if $setupStep == 3} class="current"{/if}><a href="{url op="schedConfSetup" path="3"}">3. {translate key="manager.schedConfSetup.review"}</a></li>
</ul>

