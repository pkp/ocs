{**
 * importConflicts.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Migration conflicts list
 *
 * $Id$
 *}

{include file="common/header.tpl"}

<p>{translate key="admin.conferences.importOCS1.conflict.desc"}</p>

<ul>
{foreach from=$conflicts item=conflict}
	{assign var=firstUser value=$conflict[0]}
	{assign var=secondUser value=$conflict[1]}
	<li>{translate|escape key="admin.conferences.importOCS1.conflict" firstUsername=$firstUser->getUsername() firstName=$firstUser->getFullName() secondUsername=$secondUser->getUsername() secondName=$secondUser->getFullName()}</li>
{/foreach}
</ul>

<p>&#187; <a href="{url op="editConference" path=$conferenceId}">{translate key="admin.conferences.importOCS1.editMigratedConference"}</a></p>

{include file="common/footer.tpl"}
