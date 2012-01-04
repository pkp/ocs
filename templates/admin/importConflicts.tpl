{**
 * importConflicts.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Migration conflicts list
 *
 * $Id$
 *}
{strip}
{include file="common/header.tpl"}
{/strip}

{if !empty($conflicts)}
<p>{translate key="admin.conferences.importOCS1.conflict.desc"}</p>

<ul id="conflicts">
{foreach from=$conflicts item=conflict}
	{assign var=firstUser value=$conflict[0]}
	{assign var=secondUser value=$conflict[1]}
	<li>{translate|escape key="admin.conferences.importOCS1.conflict" firstUsername=$firstUser->getUsername() firstName=$firstUser->getFullName() secondUsername=$secondUser->getUsername() secondName=$secondUser->getFullName()}</li>
{/foreach}
</ul>

{/if}

{if !empty($errors)}
<p>{translate key="admin.conferences.importOCS1.errors.desc"}</p>

<ul id="errors">
{foreach from=$errors item=error}
	<li>{$error|escape}</li>
{/foreach}
</ul>

{/if}
<p>&#187; <a href="{url op="editConference" path=$conferenceId}">{translate key="admin.conferences.importOCS1.editMigratedConference"}</a></p>

{include file="common/footer.tpl"}
