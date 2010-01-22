{**
 * site.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference site.
 *
 * $Id$
 *}
{assign var="pageTitle" value="about.aboutSite"}
{include file="common/header.tpl"}

{if !empty($about)}
	<p>{$about|nl2br}</p>
{/if}

<h3>{translate key="conference.conferences"}</h3>
<ul class="plain">
{iterate from=conferences item=conference}
	<li>&#187; <a href="{url conference=`$conference->getPath()` page="about" op="index"}">{$conference->getConferenceTitle()|escape}</a></li>
{/iterate}
</ul>

<a href="{url op="aboutThisPublishingSystem"}">{translate key="about.aboutThisPublishingSystem"}</a>

{include file="common/footer.tpl"}
