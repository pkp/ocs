{**
 * conferences.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin conferences list
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="rt.readingTools"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="user.myConferences"}</h3>

<ul class="plain">
{foreach from=$conferences item=conference}
<li>&#187; <a href="{url conference=$conference->getPath() schedConf="index" page="rtadmin"}">{$conference->getConferenceTitle()|escape}</a></li>
{/foreach}
</ul>

{include file="common/footer.tpl"}
