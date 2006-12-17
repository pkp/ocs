{**
 * setupHeader.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for conference setup pages.
 *
 * $Id$
 *}

{assign var="pageCrumbTitle" value="eventDirector.setup.eventSetup"}
{url|assign:"currentUrl" op="setup"}
{include file="common/header.tpl"}


<ul class="steplist">
	<li{if $setupStep == 1} class="current"{/if}><a href="{url op="setup" path="1"}">1. {translate key="eventDirector.setup.details"}</a></li>
	<li{if $setupStep == 2} class="current"{/if}><a href="{url op="setup" path="2"}">2. {translate key="eventDirector.setup.submissions"}</a></li>
	<li{if $setupStep == 3} class="current"{/if}><a href="{url op="setup" path="3"}">3. {translate key="eventDirector.setup.review"}</a></li>
	<li{if $setupStep == 4} class="current"{/if}><a href="{url op="setup" path="4"}">4. {translate key="eventDirector.setup.participation"}</a></li>
	<li{if $setupStep == 5} class="current"{/if}><a href="{url op="setup" path="5"}">5. {translate key="eventDirector.setup.look"}</a></li>
</ul>

