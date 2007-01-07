{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference management index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.eventManagement"}
{include file="common/header.tpl"}

<h3>{translate key="director.managementPages"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="timeline"}">{translate key="director.timeline"}</a></li>
	{if $announcementsEnabled}
		<li>&#187; <a href="{url op="announcements"}">{translate key="director.announcements"}</a></li>
	{/if}
	<li>&#187; <a href="{url op="tracks"}">{translate key="track.tracks"}</a></li>
	<li>&#187; <a href="{url op="groups"}">{translate key="director.groups"}</a></li>
	{*<li>&#187; <a href="{url op="emails"}">{translate key="director.emails"}</a></li>*}
	<li>&#187; <a href="{url op="setup"}">{translate key="director.eventSetup"}</a></li>
	<li>&#187; <a href="{url op="statistics"}">{translate key="director.statistics"}</a></li>
	{if $registrationEnabled}
		<li>&#187; <a href="{url op="registration"}">{translate key="director.registration"}</a></li>
	{/if}
	{call_hook name="Templates::Director::Index::ManagementPages"}
</ul>

<h3>{translate key="director.users"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="people" path="all"}">{translate key="director.people.allUsers"}</a></li>
	<li>&#187; <a href="{url op="createUser"}">{translate key="director.people.createUser"}</a></li>
	<li>&#187; <a href="{url op="mergeUsers"}">{translate key="director.people.mergeUsers"}</a></li>
	{call_hook name="Templates::Director::Index::Users"}
</ul>


<h3>{translate key="director.roles"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="people" path="registrationManagers"}">{translate key="user.role.registrationManagers"}</a></li>
	{*<li>&#187; <a href="{url op="people" path="schedulingManagers"}">{translate key="user.role.schedulingManagers"}</a></li>*}
	<li>&#187; <a href="{url op="people" path="editors"}">{translate key="user.role.editors"}</a></li>
	<li>&#187; <a href="{url op="people" path="trackEditors"}">{translate key="user.role.trackEditors"}</a></li>
	<li>&#187; <a href="{url op="people" path="reviewers"}">{translate key="user.role.reviewers"}</a></li>
	{*<li>&#187; <a href="{url op="people" path="invitedAuthors"}">{translate key="user.role.invitedAuthors"}</a></li>*}
	<li>&#187; <a href="{url op="people" path="authors"}">{translate key="user.role.authors"}</a></li>
	{*<li>&#187; <a href="{url op="people" path="discussants"}">{translate key="user.role.discussants"}</a></li>
	<li>&#187; <a href="{url op="people" path="registrants"}">{translate key="user.role.registrants"}</a></li>*}
	<li>&#187; <a href="{url op="people" path="readers"}">{translate key="user.role.readers"}</a></li>
	{call_hook name="Templates::Director::Index::Roles"}
</ul>

{include file="common/footer.tpl"}
